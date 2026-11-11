<?php
/**
 * Modèle des « Suivis Dossiers » : suivi de l'activité de chaque
 * Avocat Général sur les dossiers qui lui ont été distribués.
 *
 * Parité avec la page suiviDossier.php de la v1 : pour un AG donné,
 * nombre de dossiers pénaux et civils distribués, liste des dossiers
 * pénaux avec l'état de leur conclusion, et dossiers civils en attente
 * de frappe au secrétariat (date de transmission non renseignée).
 */

declare(strict_types=1);

final class SuiviDossierModel
{
    /**
     * Liste des Avocats Généraux actifs (nom, prénom, sigle).
     */
    public function avocatsGeneraux(): array
    {
        return Database::connexion()
            ->query('SELECT nom, prenom, sigle_AG FROM pgcc_client
                     WHERE poste = "AG" AND status = "Actifs" ORDER BY nom, prenom')
            ->fetchAll();
    }

    /**
     * Synthèse pour un AG : comptages par type et listes utiles.
     */
    public function synthese(string $sigle): array
    {
        $bdd = Database::connexion();

        $compte = fn (string $type): int => (function () use ($bdd, $sigle, $type) {
            $req = $bdd->prepare(
                'SELECT COUNT(*) FROM pgcc_distribution_pg WHERE sigle = :sigle AND type_dossier = :type'
            );
            $req->execute(['sigle' => $sigle, 'type' => $type]);
            return (int) $req->fetchColumn();
        })();

        // Dossiers pénaux distribués, avec l'état de leur conclusion et la
        // clé du registre pour un lien direct vers la fiche du dossier.
        $req = $bdd->prepare(
            'SELECT d.num_dossier_PGCS, p.id_reg_pourvoi, p.conclusion,
                    p.date_audience, p.num_arret
             FROM pgcc_distribution_pg d
             LEFT JOIN pgcc_reg_pourvoi_pen p ON p.num_dossier_PGCS = d.num_dossier_PGCS
             WHERE d.sigle = :sigle AND d.type_dossier = "pen"
             ORDER BY d.num_dossier_PGCS ASC'
        );
        $req->execute(['sigle' => $sigle]);
        $penaux = $req->fetchAll();

        // Dossiers civils distribués : liste complète avec l'état de la
        // transmission pour frappe au secrétariat.
        $req = $bdd->prepare(
            'SELECT d.num_dossier_PGCS, c.id_reg_pourvoi,
                    c.date_de_transmission_pour_frappe_au_secretariat
             FROM pgcc_distribution_pg d
             LEFT JOIN pgcc_reg_pourvoi_civ c ON c.num_dossier_PGCS = d.num_dossier_PGCS
             WHERE d.sigle = :sigle AND d.type_dossier = "civ"
             ORDER BY d.num_dossier_PGCS ASC'
        );
        $req->execute(['sigle' => $sigle]);
        $civils = $req->fetchAll();

        $civilsEnAttente = array_values(array_filter($civils, function ($ligne) {
            return $ligne['date_de_transmission_pour_frappe_au_secretariat'] === null
                || $ligne['date_de_transmission_pour_frappe_au_secretariat'] === '0000-00-00';
        }));

        return [
            'nbPenaux'         => $compte('pen'),
            'nbCivils'         => $compte('civ'),
            'penaux'           => $penaux,
            'civils'           => $civils,
            'civilsEnAttente'  => $civilsEnAttente,
            'nbConclusionsRedigees' => count(array_filter($penaux, fn ($l) => !empty($l['conclusion']))),
        ];
    }

    /**
     * Dossiers encore « pour conclusion » par Avocat Général : pénaux et
     * civils distribués dont la date de transmission pour frappe au
     * secrétariat n'est pas renseignée. Alimente le graphique de la
     * synthèse (parité avec grapheSuiviDossier.php de la v1).
     *
     * @return array{labels: string[], penaux: int[], civils: int[]}
     */
    public function dossiersPourConclusionParAg(): array
    {
        $req = Database::connexion()->query(
            "SELECT d.sigle,
                    SUM(CASE WHEN d.type_dossier = 'pen'
                              AND (p.date_de_transmission_pour_frappe_au_secretariat IS NULL
                                   OR p.date_de_transmission_pour_frappe_au_secretariat = '0000-00-00')
                             THEN 1 ELSE 0 END) AS penaux,
                    SUM(CASE WHEN d.type_dossier = 'civ'
                              AND (c.date_de_transmission_pour_frappe_au_secretariat IS NULL
                                   OR c.date_de_transmission_pour_frappe_au_secretariat = '0000-00-00')
                             THEN 1 ELSE 0 END) AS civils
             FROM pgcc_distribution_pg d
             LEFT JOIN pgcc_reg_pourvoi_pen p
                    ON p.num_dossier_PGCS = d.num_dossier_PGCS AND d.type_dossier = 'pen'
             LEFT JOIN pgcc_reg_pourvoi_civ c
                    ON c.num_dossier_PGCS = d.num_dossier_PGCS AND d.type_dossier = 'civ'
             GROUP BY d.sigle
             ORDER BY d.sigle"
        );

        $labels = [];
        $penaux = [];
        $civils = [];
        foreach ($req->fetchAll() as $ligne) {
            $labels[] = (string) $ligne['sigle'];
            $penaux[] = (int) $ligne['penaux'];
            $civils[] = (int) $ligne['civils'];
        }

        return ['labels' => $labels, 'penaux' => $penaux, 'civils' => $civils];
    }
}
