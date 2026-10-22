<?php
/**
 * Modèle du Registre des Mémoires (nouveau module PGCC v2).
 *
 * Un mémoire est une pièce de procédure déposée dans le cadre d'un
 * pourvoi : « ampliatif » (pour le demandeur) ou « en défense »
 * (pour le défendeur). Le registre centralise le suivi des dépôts,
 * des notifications et de la transmission au secrétariat.
 *
 * Table dédiée « pgcc_reg_memoires » créée dans la base pgcc (voir
 * database/migrations/pgcc_reg_memoires.sql) — commune aux deux applications.
 */

declare(strict_types=1);

final class MemoireModel
{
    /** États possibles d'un mémoire. */
    public const ETATS = [
        'depose'    => 'Déposé',
        'transmis'  => 'Transmis au rapporteur',
        'rappore'   => 'Rapport établi',
        'classe'    => 'Classé',
    ];

    /** Types de mémoire. */
    public const TYPES = [
        'ampliatif' => 'Mémoire ampliatif',
        'defense'   => 'Mémoire en défense',
    ];

    /** Origines (juridiction du dossier lié). */
    public const ORIGINES = [
        'penal' => 'Pénal',
        'civil' => 'Civil',
    ];

    /**
     * Liste paginée avec filtres recherche / type / état.
     * @return array{lignes: array, total: int, pages: int, page: int}
     */
    /** Résout un numéro vers un dossier source seulement s'il est unique. */
    public function dossierSourceId(string $origine, string $numDossier): ?int
    {
        $sources = [
            'penal' => ['pgcc_reg_pourvoi_pen', 'id_reg_pourvoi'],
            'civil' => ['pgcc_reg_pourvoi_civ', 'id_reg_pourvoi'],
        ];
        if (!isset($sources[$origine]) || $numDossier === '') return null;
        [$table, $cle] = $sources[$origine];
        $req = Database::connexion()->prepare('SELECT ' . nom_sql($cle) . ' FROM ' . nom_sql($table)
            . ' WHERE num_dossier_PGCS = :dossier LIMIT 2');
        $req->execute(['dossier' => $numDossier]);
        $ids = $req->fetchAll(PDO::FETCH_COLUMN);
        return count($ids) === 1 ? (int) $ids[0] : null;
    }

    /** Retourne les dossiers correspondant à un numéro pour résoudre les doublons historiques. */
    public function dossiersCorrespondants(string $origine, string $numDossier): array
    {
        $sources = [
            'penal' => 'pgcc_reg_pourvoi_pen',
            'civil' => 'pgcc_reg_pourvoi_civ',
        ];
        if (!isset($sources[$origine]) || $numDossier === '') return [];
        $table = $sources[$origine];
        $req = Database::connexion()->prepare(
            "SELECT id_reg_pourvoi, num_ordre, annee, num_dossier_PGCS, cassationnaire
             FROM " . nom_sql($table) . " WHERE num_dossier_PGCS = :dossier ORDER BY annee DESC, num_ordre DESC"
        );
        $req->execute(['dossier' => $numDossier]);
        return $req->fetchAll();
    }

    public function trouverDossierSource(string $origine, int $id): ?array
    {
        $sources = [
            'penal' => 'pgcc_reg_pourvoi_pen',
            'civil' => 'pgcc_reg_pourvoi_civ',
        ];
        if (!isset($sources[$origine]) || $id < 1) return null;
        $table = $sources[$origine];
        $req = Database::connexion()->prepare(
            "SELECT id_reg_pourvoi, num_dossier_PGCS FROM " . nom_sql($table) . " WHERE id_reg_pourvoi = :id"
        );
        $req->execute(['id' => $id]);
        $dossier = $req->fetch();
        return $dossier === false ? null : $dossier;
    }

    public function liste(int $page = 1, string $recherche = '', ?string $type = null, ?string $etat = null): array
    {
        $bdd     = Database::connexion();
        $where   = [];
        $params  = [];

        $recherche = trim($recherche);
        if ($recherche !== '') {
            $where[] = '(num_dossier_PGCS LIKE :q1 OR parties LIKE :q2 OR avocat LIKE :q3)';
            $params += ['q1' => "%$recherche%", 'q2' => "%$recherche%", 'q3' => "%$recherche%"];
        }
        if ($type !== null && isset(self::TYPES[$type])) {
            $where[]            = 'type_memoire = :type';
            $params['type']     = $type;
        }
        if ($etat !== null && isset(self::ETATS[$etat])) {
            $where[]            = 'etat = :etat';
            $params['etat']     = $etat;
        }

        $condition = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $req = $bdd->prepare('SELECT COUNT(*) FROM pgcc_reg_memoires' . $condition);
        $req->execute($params);
        $total = (int) $req->fetchColumn();

        $pages  = max(1, (int) ceil($total / LIGNES_PAR_PAGE));
        $page   = min(max(1, $page), $pages);
        $offset = ($page - 1) * LIGNES_PAR_PAGE;

        $req = $bdd->prepare(
            'SELECT * FROM pgcc_reg_memoires' . $condition
            . ' ORDER BY annee DESC, num_ordre DESC LIMIT ' . LIGNES_PAR_PAGE . " OFFSET $offset"
        );
        $req->execute($params);

        return ['lignes' => $req->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** Toutes les lignes correspondant aux filtres pour produire un export complet. */
    public function exporter(string $recherche = '', ?string $type = null, ?string $etat = null): iterable
    {
        $where = [];
        $params = [];
        $recherche = trim($recherche);
        if ($recherche !== '') {
            $where[] = '(num_dossier_PGCS LIKE :q1 OR parties LIKE :q2 OR avocat LIKE :q3)';
            $params += ['q1' => "%$recherche%", 'q2' => "%$recherche%", 'q3' => "%$recherche%"];
        }
        if ($type !== null && isset(self::TYPES[$type])) {
            $where[] = 'type_memoire = :type';
            $params['type'] = $type;
        }
        if ($etat !== null && isset(self::ETATS[$etat])) {
            $where[] = 'etat = :etat';
            $params['etat'] = $etat;
        }
        $condition = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $req = Database::connexionPourExport()->prepare(
            'SELECT * FROM pgcc_reg_memoires' . $condition . ' ORDER BY annee DESC, num_ordre DESC'
        );
        $req->execute($params);
        return $req;
    }

    public function trouver(int $id): ?array
    {
        $req = Database::connexion()->prepare('SELECT * FROM pgcc_reg_memoires WHERE id_memoire = :id');
        $req->execute(['id' => $id]);
        $ligne = $req->fetch();
        return $ligne === false ? null : $ligne;
    }

    /**
     * Mémoires rattachés à un dossier (pour l'onglet mémoire d'une fiche).
     */
    /** Mémoires rattachés à l'identifiant précis du dossier source. */
    public function pourDossier(string $origine, int $idDossier): array
    {
        $colonne = match ($origine) {
            'penal' => 'id_reg_pourvoi_pen',
            'civil' => 'id_reg_pourvoi_civ',
            default => null,
        };
        if ($colonne === null || $idDossier < 1) return [];
        $req = Database::connexion()->prepare("SELECT * FROM pgcc_reg_memoires WHERE "
            . nom_sql($colonne) . " = :id ORDER BY date_depot DESC");
        $req->execute(['id' => $idDossier]);
        return $req->fetchAll();
    }

    public function inserer(array $d): int
    {
        $d['cree_par'] = utilisateur_login();
        $sql = 'INSERT INTO pgcc_reg_memoires
                    (num_ordre, annee, date_insertion, type_memoire, origine, num_dossier_PGCS,
                     id_reg_pourvoi_pen, id_reg_pourvoi_civ, parties, avocat, date_notification,
                     date_depot, date_transmission_secretaire, rapporteur, observation, etat, cree_par)
                VALUES
                    (:num_ordre, :annee, :date_insertion, :type_memoire, :origine, :num_dossier_PGCS,
                     :id_reg_pourvoi_pen, :id_reg_pourvoi_civ, :parties, :avocat, :date_notification,
                     :date_depot, :date_transmission_secretaire, :rapporteur, :observation, :etat, :cree_par)';
        $req = Database::connexion()->prepare($sql);
        $req->execute($d);
        return (int) Database::connexion()->lastInsertId();
    }

    public function modifier(int $id, array $d): bool
    {
        $d['modifie_par'] = utilisateur_login();
        $affectations = implode(', ', array_map(fn ($c) => "`$c` = :$c", array_keys($d)));
        $req = Database::connexion()->prepare("UPDATE pgcc_reg_memoires SET $affectations WHERE id_memoire = :id");
        return $req->execute($d + ['id' => $id]);
    }

    public function supprimer(int $id): bool
    {
        $req = Database::connexion()->prepare('DELETE FROM pgcc_reg_memoires WHERE id_memoire = :id');
        return $req->execute(['id' => $id]);
    }

    /**
     * Prochain numéro d'ordre disponible pour une année.
     */
    public function prochainNumero(int $annee): int
    {
        $req = Database::connexion()->prepare(
            'SELECT COALESCE(MAX(num_ordre), 0) + 1 FROM pgcc_reg_memoires WHERE annee = :annee'
        );
        $req->execute(['annee' => $annee]);
        return (int) $req->fetchColumn();
    }
}
