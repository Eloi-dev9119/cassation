<?php
/**
 * Modèle des statistiques du tableau de bord.
 *
 * Les volumes sont calculés directement en base (compteurs SQL) puis
 * mis en forme pour un graphique SVG généré côté serveur.
 */

declare(strict_types=1);

final class StatsModel
{
    /**
     * Compteurs généraux affichés sur le tableau de bord.
     */
    public function compteurs(): array
    {
        $bdd = Database::connexion();
        return [
            'arrivees'        => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_reg_arrivee_pen')->fetchColumn(),
            'penaux'          => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_reg_pourvoi_pen')->fetchColumn(),
            'civils'          => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_reg_pourvoi_civ')->fetchColumn(),
            'siege'           => (int) @$bdd->query('SELECT COUNT(*) FROM siege_cassation_civ')->fetchColumn(),
            'dessaisissements'=> (int) $bdd->query('SELECT COUNT(*) FROM pgcc_reg_des')->fetchColumn(),
            'pil'             => (int) @$bdd->query('SELECT COUNT(*) FROM pgcc_reg_pil')->fetchColumn(),
            'memoires'        => (int) @$bdd->query('SELECT COUNT(*) FROM pgcc_reg_memoires')->fetchColumn(),
            'detentions'      => (int) $bdd->query("SELECT COUNT(*) FROM pgcc_reg_pourvoi_pen WHERE detention = 0x31")->fetchColumn(),
            'membresActifs'   => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_client WHERE valide = 1 AND status = "Actifs"')->fetchColumn(),
            'enAttente'       => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_client WHERE valide = 0')->fetchColumn(),
        ];
    }

    /**
     * Volume de dossiers par année et par registre (8 dernières années).
     *
     * @return array{ annees: int[], series: array<string, int[]> }
     */
    public function dossiersParAnnee(): array
    {
        $bdd = Database::connexion();

        $annees = [];
        $req = $bdd->query(
            'SELECT annee FROM (
                SELECT annee FROM pgcc_reg_pourvoi_pen
                UNION SELECT annee FROM pgcc_reg_pourvoi_civ
                UNION SELECT annee FROM pgcc_reg_arrivee_pen
             ) t ORDER BY annee DESC LIMIT 8'
        );
        foreach ($req->fetchAll(PDO::FETCH_COLUMN) as $a) {
            $annees[] = (int) $a;
        }
        $annees = array_values(array_unique(array_filter($annees, fn ($a) => $a > 0)));
        sort($annees);

        $series = [];
        foreach (['pgcc_reg_pourvoi_pen' => 'Pénaux', 'pgcc_reg_pourvoi_civ' => 'Civils', 'pgcc_reg_arrivee_pen' => 'Arrivées'] as $table => $libelle) {
            $req = $bdd->prepare('SELECT annee, COUNT(*) n FROM ' . nom_sql($table) . ' GROUP BY annee');
            $req->execute();
            $parAnnee = [];
            foreach ($req->fetchAll() as $l) {
                $parAnnee[(int) $l['annee']] = (int) $l['n'];
            }
            $series[$libelle] = array_map(fn ($a) => $parAnnee[$a] ?? 0, $annees);
        }

        return ['annees' => $annees, 'series' => $series];
    }

    /**
     * Conclusions à rédiger / à transmettre pour frappe, par registre.
     *
     * Seuls les registres dotés d'une colonne « conclusion » sont pris en
     * compte (pénaux, civils, dessaisissements). Les dossiers archivés sont
     * exclus. Une conclusion est « à rédiger » tant que la colonne
     * conclusion est vide ; elle est « à transmettre » une fois rédigée
     * mais pas encore transmise pour frappe au secrétariat.
     *
     * @return array{
     *     registres: array<int, array<string, mixed>>,
     *     totalARediger: int,
     *     totalATransmettre: int
     * }
     */
    public function conclusionsArediger(): array
    {
        $bdd = Database::connexion();
        $registres = [];
        $totalARediger = 0;
        $totalATransmettre = 0;

        $definitions = [
            'penaux'           => 'Pénaux',
            'civils'           => 'Civils',
            'dessaisissements' => 'Dessaisissements',
        ];
        $tables = [
            'penaux'           => 'pgcc_reg_pourvoi_pen',
            'civils'           => 'pgcc_reg_pourvoi_civ',
            'dessaisissements' => 'pgcc_reg_des',
        ];

        foreach ($tables as $code => $table) {
            try {
                $tableSql = nom_sql($table);
                // Requête unique et sans TRIM() : TRIM() sur une colonne
                // LONGTEXT force un scan complet (≈450 ms par registre) et
                // ne change pas le résultat (aucune conclusion composée
                // uniquement d'espaces dans la base).
                // MariaDB compare une date « zéro » à NULL dans un WHERE mais
                // pas dans un CASE : les deux cas sont testés explicitement.
                $req = $bdd->query(
                    "SELECT
                        SUM(CASE WHEN conclusion IS NULL OR conclusion = '' THEN 1 ELSE 0 END) AS a_rediger,
                        SUM(CASE WHEN conclusion IS NOT NULL AND conclusion <> ''
                                  AND (date_de_transmission_pour_frappe_au_secretariat IS NULL
                                       OR date_de_transmission_pour_frappe_au_secretariat = '0000-00-00')
                                 THEN 1 ELSE 0 END) AS a_transmettre
                     FROM $tableSql
                     WHERE COALESCE(archiver, '0') <> '1'"
                );
                $ligne = $req->fetch() ?: [];
                $aRediger = (int) ($ligne['a_rediger'] ?? 0);
                $aTransmettre = (int) ($ligne['a_transmettre'] ?? 0);
            } catch (PDOException) {
                // Colonne ou table absente : le registre est simplement ignoré.
                continue;
            }

            $registres[] = [
                'code'           => $code,
                'libelle'        => $definitions[$code],
                'aRediger'       => $aRediger,
                'aTransmettre'   => $aTransmettre,
                'url'            => '/registres/' . $code . '?vue=complet',
            ];
            $totalARediger += $aRediger;
            $totalATransmettre += $aTransmettre;
        }

        return [
            'registres'         => $registres,
            'totalARediger'     => $totalARediger,
            'totalATransmettre' => $totalATransmettre,
        ];
    }

    /**
     * Derniers dossiers créés ou modifiés, tous registres confondus.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dossiersRecents(int $limite = 6): array
    {
        $bdd = Database::connexion();
        $limite = max(1, min(20, $limite));

        $sources = [
            'arrivees' => [
                'table'  => 'pgcc_reg_arrivee_pen',
                'cle'    => 'id_reg_arrivee',
                'resume' => 'num_dossier_et_pers_concernee',
            ],
            'penaux' => [
                'table'  => 'pgcc_reg_pourvoi_pen',
                'cle'    => 'id_reg_pourvoi',
                'resume' => 'cassationnaire',
            ],
            'civils' => [
                'table'  => 'pgcc_reg_pourvoi_civ',
                'cle'    => 'id_reg_pourvoi',
                'resume' => 'cassationnaire',
            ],
            'dessaisissements' => [
                'table'  => 'pgcc_reg_des',
                'cle'    => 'id_reg_des',
                'resume' => 'cassationnaire',
            ],
            'pil' => [
                'table'  => 'pgcc_reg_pil',
                'cle'    => 'id_pil',
                'resume' => 'requete',
            ],
        ];

        $recents = [];
        foreach ($sources as $code => $source) {
            $definition = registre_definition($code);
            if ($definition === null) {
                continue;
            }
            $archive = nom_sql(registre_colonne_archive($definition));
            $tableSql = nom_sql($source['table']);
            $cleSql = nom_sql($source['cle']);
            $resumeSql = nom_sql($source['resume']);
            $referenceSql = $code === 'pil' ? nom_sql('num_pil') : nom_sql('num_dossier_PGCS');

            try {
                $req = $bdd->query(
                    "SELECT $cleSql AS id, $referenceSql AS reference, $resumeSql AS resume,
                            COALESCE(date_modification, date_creation) AS activite
                     FROM $tableSql
                     WHERE COALESCE($archive, '0') <> '1'
                     ORDER BY COALESCE(date_modification, date_creation) DESC, $cleSql DESC
                     LIMIT " . (int) $limite
                );
            } catch (PDOException) {
                continue;
            }

            foreach ($req->fetchAll() as $ligne) {
                $resume = trim((string) ($ligne['resume'] ?? ''));
                $reference = trim((string) ($ligne['reference'] ?? ''));
                $recents[] = [
                    'code'      => $code,
                    'registre'  => (string) $definition['libelleCourt'],
                    'reference' => $reference !== '' ? $reference : 'Dossier #' . (int) $ligne['id'],
                    'resume'    => $resume,
                    'activite'  => (string) ($ligne['activite'] ?? ''),
                    'url'       => '/registres/' . $code . '/fiche?id=' . (int) $ligne['id'],
                ];
            }
        }

        usort(
            $recents,
            static fn (array $a, array $b): int => strcmp($b['activite'], $a['activite'])
        );

        return array_slice($recents, 0, $limite);
    }
}
