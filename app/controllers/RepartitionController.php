<?php
/**
 * Contrôleur de la « Répartition des dossiers » : comptages mensuels
 * par Avocat Général et graphique (parité avec repartitionDossier.php).
 */

declare(strict_types=1);

final class RepartitionController
{
    public function index(): void
    {
        Auth::exiger();

        $modele = new RepartitionModel();

        $moisChoisi  = in_array(entree_get('mois', ''), RepartitionModel::MOIS, true)
            ? (string) entree_get('mois')
            : RepartitionModel::MOIS[(int) date('n') - 1];
        $anneeChoisie = (int) entree_get('annee', $modele->derniereAnnee());

        $donnees = $modele->parAvocatGeneral($moisChoisi, $anneeChoisie);

        if (entree_get('export', '') === 'csv') {
            $lignes = [];
            $totalPen = 0;
            $totalCiv = 0;
            foreach ($donnees['ags'] as $i => $sigle) {
                $pen = (int) $donnees['penaux'][$i];
                $civ = (int) $donnees['civils'][$i];
                $lignes[] = ['sigle' => $sigle, 'penaux' => $pen, 'civils' => $civ, 'total' => $pen + $civ];
                $totalPen += $pen;
                $totalCiv += $civ;
            }
            $lignes[] = ['sigle' => 'Total', 'penaux' => $totalPen, 'civils' => $totalCiv, 'total' => $totalPen + $totalCiv];
            CsvExporter::telecharger('repartition-' . strtolower($moisChoisi) . '-' . $anneeChoisie . '.csv', [
                ['champ' => 'sigle', 'libelle' => 'Sigle AG'],
                ['champ' => 'penaux', 'libelle' => 'Dossiers pénaux'],
                ['champ' => 'civils', 'libelle' => 'Dossiers civils'],
                ['champ' => 'total', 'libelle' => 'Total'],
            ], $lignes);
        }

        View::afficher('suivis-dossiers/repartition.php', [
            'titrePage'  => 'Répartition des dossiers — ' . APP_NOM,
            'mois'       => RepartitionModel::MOIS,
            'moisChoisi' => $moisChoisi,
            'annee'      => $anneeChoisie,
            'donnees'    => $donnees,
        ]);
    }
}
