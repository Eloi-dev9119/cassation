<?php
/**
 * Modèle des distributions de dossiers aux Avocats Généraux.
 *
 * Une « liste de distribution » regroupe des dossiers de pourvoi remis
 * à un Avocat Général (sigle). Comme dans la v1, la liste est alimentée
 * depuis les dossiers pénaux du registre qui ne sont pas encore
 * distribués (absents de pgcc_distribution_pg).
 */

declare(strict_types=1);

final class DistributionModel
{
    /**
     * Liste paginée des distributions, filtrable par type de dossier
     * ('pen' pénaux / 'civ' civils / null tous).
     * @return array{lignes: array, total: int, pages: int, page: int}
     */
    public function liste(int $page = 1, string $recherche = '', ?string $type = null): array
    {
        $bdd    = Database::connexion();
        $params = [];

        $conditions = [];
        $recherche = trim($recherche);
        if ($recherche !== '') {
            $conditions[] = '(num_dossier_PGCS LIKE :q_dossier OR nom_liste LIKE :q_liste OR parties LIKE :q_parties OR sigle LIKE :q_sigle)';
            $motif = "%$recherche%";
            $params['q_dossier'] = $motif;
            $params['q_liste'] = $motif;
            $params['q_parties'] = $motif;
            $params['q_sigle'] = $motif;
        }
        if ($type !== null && in_array($type, ['pen', 'civ'], true)) {
            $conditions[]     = 'type_dossier = :type';
            $params['type']   = $type;
        }
        $condition = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $req = $bdd->prepare('SELECT COUNT(*) FROM pgcc_distribution_pg' . $condition);
        $req->execute($params);
        $total = (int) $req->fetchColumn();

        $pages  = max(1, (int) ceil($total / LIGNES_PAR_PAGE));
        $page   = min(max(1, $page), $pages);
        $offset = ($page - 1) * LIGNES_PAR_PAGE;

        $req = $bdd->prepare(
            'SELECT * FROM pgcc_distribution_pg' . $condition
            . " ORDER BY annee DESC, id_distribution DESC LIMIT " . LIGNES_PAR_PAGE . " OFFSET $offset"
        );
        $req->execute($params);

        return ['lignes' => $req->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** Toutes les lignes correspondant aux filtres pour l'export CSV. */
    public function exporter(string $recherche = '', ?string $type = null): iterable
    {
        $params = [];
        $conditions = [];
        $recherche = trim($recherche);
        if ($recherche !== '') {
            $conditions[] = '(num_dossier_PGCS LIKE :q_dossier OR nom_liste LIKE :q_liste OR parties LIKE :q_parties OR sigle LIKE :q_sigle)';
            $motif = "%$recherche%";
            $params += ['q_dossier' => $motif, 'q_liste' => $motif, 'q_parties' => $motif, 'q_sigle' => $motif];
        }
        if ($type !== null && in_array($type, ['pen', 'civ'], true)) {
            $conditions[] = 'type_dossier = :type';
            $params['type'] = $type;
        }
        $condition = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $req = Database::connexionPourExport()->prepare(
            'SELECT * FROM pgcc_distribution_pg' . $condition . ' ORDER BY annee DESC, id_distribution DESC'
        );
        $req->execute($params);
        return $req;
    }

    /**
     * Dossiers non encore distribués (candidats à une nouvelle liste),
     * du type demandé : pourvois pénaux ou civils, filtrables par recherche.
     */
    public function dossiersNonDistribues(string $type = 'pen', string $recherche = ''): array
    {
        $table = $type === 'civ' ? 'pgcc_reg_pourvoi_civ' : 'pgcc_reg_pourvoi_pen';
        $typeNormalise = $type === 'civ' ? 'civ' : 'pen';
        $bdd    = Database::connexion();
        $params = ['type' => $typeNormalise];

        $condition = ' WHERE NOT EXISTS (
                           SELECT 1 FROM pgcc_distribution_pg d
                           WHERE d.num_dossier_PGCS = p.num_dossier_PGCS
                             AND d.type_dossier = :type
                       )';
        $recherche = trim($recherche);
        if ($recherche !== '') {
            $condition .= ' AND (p.num_dossier_PGCS LIKE :q_numero OR p.cassationnaire LIKE :q_parties)';
            $params['q_numero'] = "%$recherche%";
            $params['q_parties'] = "%$recherche%";
        }

        $req = $bdd->prepare(
            "SELECT p.id_reg_pourvoi, p.num_ordre, p.annee, p.num_dossier_PGCS, p.cassationnaire
             FROM `$table` p" . $condition
            . ' ORDER BY p.annee DESC, p.num_ordre DESC LIMIT 200'
        );
        $req->execute($params);
        return $req->fetchAll();
    }

    /**
     * Crée une liste de distribution : un enregistrement par dossier
     * sélectionné, avec le type de dossier correspondant ('pen' ou 'civ').
     * Retourne le nombre de dossiers ajoutés.
     *
     * @param int[] $idsDossiers identifiants du registre source
     */
    public function creerListe(string $type, string $nomListe, int $annee, string $sigle, array $idsDossiers, ?string $dateRemiseSecretariat): int
    {
        $table = $type === 'civ' ? 'pgcc_reg_pourvoi_civ' : 'pgcc_reg_pourvoi_pen';
        $typeNormalise = $type === 'civ' ? 'civ' : 'pen';
        $idsDossiers = array_values(array_unique(array_filter(array_map('intval', $idsDossiers), static fn (int $id): bool => $id > 0)));
        if ($idsDossiers === []) {
            return 0;
        }

        $bdd = Database::connexion();
        $req = $bdd->prepare(
            "SELECT num_dossier_PGCS, cassationnaire FROM `$table` WHERE id_reg_pourvoi = :id"
        );
        $dejaDistribue = $bdd->prepare(
            'SELECT 1 FROM pgcc_distribution_pg
             WHERE num_dossier_PGCS = :numero AND type_dossier = :type LIMIT 1'
        );
        $insert = $bdd->prepare(
            'INSERT INTO pgcc_distribution_pg
                 (annee, nom_liste, num_dossier_PGCS, type_dossier, parties, sigle, date_remise_secr, date_remise_AG, cree_par)
             VALUES
                 (:annee, :nom_liste, :num_dossier, :type_dossier, :parties, :sigle, :remise_secr, "0000-00-00", :cree_par)'
        );

        $ajoutes = 0;
        $bdd->beginTransaction();
        try {
            foreach ($idsDossiers as $id) {
                $req->execute(['id' => $id]);
                $dossier = $req->fetch();
                if ($dossier === false || trim((string) $dossier['num_dossier_PGCS']) === '') {
                    continue;
                }

                // Une page restée ouverte ne doit pas attribuer une seconde fois un dossier.
                $dejaDistribue->execute([
                    'numero' => $dossier['num_dossier_PGCS'],
                    'type'   => $typeNormalise,
                ]);
                if ($dejaDistribue->fetchColumn() !== false) {
                    continue;
                }

                $insert->execute([
                    'annee'       => $annee,
                    'nom_liste'   => $nomListe,
                    'num_dossier' => $dossier['num_dossier_PGCS'],
                    'type_dossier'=> $typeNormalise,
                    'parties'     => mb_substr((string) $dossier['cassationnaire'], 0, 255),
                    'sigle'       => $sigle,
                    'remise_secr' => $dateRemiseSecretariat ?? '0000-00-00',
                    'cree_par'    => utilisateur_login(),
                ]);
                $ajoutes++;
            }
            $bdd->commit();
        } catch (Throwable $e) {
            if ($bdd->inTransaction()) {
                $bdd->rollBack();
            }
            throw $e;
        }
        return $ajoutes;
    }
}
