<?php
/**
 * Contrôleur des « Suivis Dossiers » : suivi par Avocat Général des
 * dossiers distribués (parité avec suiviDossier.php de la v1).
 */

declare(strict_types=1);

final class SuiviDossierController
{
    public function index(): void
    {
        Auth::exiger();

        $modele = new SuiviDossierModel();
        $ags = $modele->avocatsGeneraux();

        // AG sélectionné : paramètre ou premier de la liste.
        $sigleChoisi = trim((string) ($_GET['ag'] ?? ''));
        if ($sigleChoisi === '' && $ags !== []) {
            $sigleChoisi = (string) $ags[0]['sigle_AG'];
        }

        $synthese = $sigleChoisi !== '' ? $modele->synthese($sigleChoisi) : null;

        if (($_GET['export'] ?? '') === 'csv' && $synthese !== null) {
            $lignes = [];
            foreach ($synthese['penaux'] as $dossier) {
                $lignes[] = [
                    'type' => 'Pénal', 'dossier' => $dossier['num_dossier_PGCS'],
                    'conclusion' => !empty($dossier['conclusion']) ? 'Rédigée' : 'Non rédigée',
                    'audience' => $dossier['date_audience'] ?? '', 'arret' => $dossier['num_arret'] ?? '',
                ];
            }
            foreach ($synthese['civilsEnAttente'] as $dossier) {
                $lignes[] = [
                    'type' => 'Civil en attente de frappe', 'dossier' => $dossier['num_dossier_PGCS'],
                    'conclusion' => '', 'audience' => '', 'arret' => '',
                ];
            }
            CsvExporter::telecharger('suivi-ag-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $sigleChoisi) . '.csv', [
                ['champ' => 'type', 'libelle' => 'Type / statut'],
                ['champ' => 'dossier', 'libelle' => 'N° dossier PGCS'],
                ['champ' => 'conclusion', 'libelle' => 'Conclusion'],
                ['champ' => 'audience', 'libelle' => 'Audience', 'type' => 'date'],
                ['champ' => 'arret', 'libelle' => 'N° arrêt'],
            ], $lignes);
        }

        View::afficher('suivis-dossiers/synthese.php', [
            'titrePage'  => 'Suivis Dossiers — ' . APP_NOM,
            'ags'        => $ags,
            'sigleChoisi'=> $sigleChoisi,
            'synthese'   => $synthese,
        ]);
    }
}
