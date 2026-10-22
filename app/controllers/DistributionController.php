<?php
/**
 * Contrôleur des distributions de dossiers aux Avocats Généraux :
 * consultation des listes (pénales et civiles, comme les deux pages de
 * la v1) et génération d'une nouvelle liste depuis les dossiers non
 * distribués du type choisi.
 */

declare(strict_types=1);

final class DistributionController
{
    public function index(): void
    {
        Auth::exiger();

        $type   = in_array(entree_get('type', ''), ['civ'], true) ? 'civ' : 'pen';
        $action = (string) entree_get('a', '');

        if ($action === 'nouvelle') {
            $this->nouvelleListe($type);
            return;
        }

        $page      = max(1, (int) entree_get('p', 1));
        $recherche = trim((string) entree_get('q', ''));
        $modeleDistribution = new DistributionModel();
        if (entree_get('export', '') === 'csv') {
            CsvExporter::telecharger('distributions-' . $type . '-' . date('Ymd') . '.csv', [
                ['champ' => 'annee', 'libelle' => 'Année'],
                ['champ' => 'nom_liste', 'libelle' => 'Liste'],
                ['champ' => 'num_dossier_PGCS', 'libelle' => 'N° dossier PGCS'],
                ['champ' => 'parties', 'libelle' => 'Parties'],
                ['champ' => 'sigle', 'libelle' => 'Sigle AG'],
                ['champ' => 'date_remise_secr', 'libelle' => 'Remise au secrétariat', 'type' => 'date'],
                ['champ' => 'date_remise_AG', 'libelle' => 'Remise à l’AG', 'type' => 'date'],
            ], $modeleDistribution->exporter($recherche, $type));
        }
        $resultat  = $modeleDistribution->liste($page, $recherche, $type);

        View::afficher('distributions/liste.php', [
            'titrePage' => ($type === 'civ' ? 'Distributions Civiles' : 'Distributions Pénaux') . ' — ' . APP_NOM,
            'type'      => $type,
            'resultat'  => $resultat,
            'recherche' => $recherche,
        ]);
    }

    /**
     * Formulaire et traitement de création d'une liste de distribution.
     */
    private function nouvelleListe(string $type): void
    {
        $modele = new DistributionModel();

        $erreurs = [];
        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
                rediriger('/distributions?type=' . $type);
            }

            $typePost = $type;
            $nomListe = trim((string) entree_post('nom_liste', ''));
            $annee    = (int) entree_post('annee', 0);
            $sigle    = trim((string) entree_post('sigle', ''));
            $ids      = array_filter((array) entree_post('dossiers', []), fn ($v) => ctype_digit((string) $v));
            $remiseBrute = entree_post('date_remise_secr', '');
            $remise = null;
            if (!is_scalar($remiseBrute)) {
                $erreurs[] = 'La date de remise au secrétariat est invalide.';
            } elseif (trim((string) $remiseBrute) !== '') {
                $date = trim((string) $remiseBrute);
                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $partiesDate) !== 1
                    || !checkdate((int) $partiesDate[2], (int) $partiesDate[3], (int) $partiesDate[1])) {
                    $erreurs[] = 'La date de remise au secrétariat doit être une date valide.';
                } else {
                    $remise = $date;
                }
            }

            if ($nomListe === '')            $erreurs[] = 'Le nom de la liste est obligatoire.';
            if ($annee < 1990 || $annee > 2100) $erreurs[] = 'L\'année doit être comprise entre 1990 et 2100.';
            if ($sigle === '')               $erreurs[] = 'Le sigle de l\'Avocat Général est obligatoire.';
            if ($ids === [])                 $erreurs[] = 'Sélectionnez au moins un dossier.';

            if ($erreurs === []) {
                $ajoutes = $modele->creerListe($typePost, $nomListe, $annee, $sigle, array_map('intval', $ids), $remise);
                $selectionDemandee = count(array_unique(array_map('intval', $ids)));
                if ($ajoutes === 0) {
                    flash('erreur', 'Aucun dossier n’a été attribué : la sélection a déjà été distribuée ou n’est plus disponible. Actualisez la liste.');
                } elseif ($ajoutes < $selectionDemandee) {
                    $libelleNombre = $ajoutes . ' dossier' . ($ajoutes === 1 ? '' : 's');
                    $accord = $ajoutes === 1 ? 'a été attribué' : 'ont été attribués';
                    flash('info', $libelleNombre . ' ' . $accord . ' à ' . $sigle . '. Les autres ne sont plus disponibles; actualisez la liste.');
                } else {
                    $libelleNombre = $ajoutes . ' dossier' . ($ajoutes === 1 ? '' : 's');
                    $accord = $ajoutes === 1 ? 'a été attribué' : 'ont été attribués';
                    flash('succes', 'La liste « ' . $nomListe . ' » a été créée. ' . $libelleNombre . ' ' . $accord . ' à ' . $sigle . '.');
                }
                rediriger('/distributions?type=' . $typePost);
            }
        }

        View::afficher('distributions/nouvelle.php', [
            'titrePage' => 'Nouvelle liste de distribution — ' . APP_NOM,
            'type'      => $type,
            'dossiers'  => $modele->dossiersNonDistribues($type, trim((string) entree_get('q', ''))),
            'recherche' => trim((string) entree_get('q', '')),
            'erreurs'   => $erreurs,
        ]);
    }
}
