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

        // Dossiers pénaux distribués, avec l'état de leur conclusion.
        $req = $bdd->prepare(
            'SELECT d.num_dossier_PGCS, p.conclusion,
                    p.date_audience, p.num_arret
             FROM pgcc_distribution_pg d
             LEFT JOIN pgcc_reg_pourvoi_pen p ON p.num_dossier_PGCS = d.num_dossier_PGCS
             WHERE d.sigle = :sigle AND d.type_dossier = "pen"
             ORDER BY d.num_dossier_PGCS ASC'
        );
        $req->execute(['sigle' => $sigle]);
        $penaux = $req->fetchAll();

        // Dossiers civils distribués encore en attente de frappe au secrétariat.
        $req = $bdd->prepare(
            'SELECT d.num_dossier_PGCS, c.date_de_transmission_pour_frappe_au_secretariat
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
            'civilsEnAttente'  => $civilsEnAttente,
            'nbConclusionsRedigees' => count(array_filter($penaux, fn ($l) => !empty($l['conclusion']))),
        ];
    }
}
