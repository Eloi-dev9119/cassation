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
        if ($action === 'modifier') {
            $this->modifier($type);
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

        // Vue complète : tout le contenu des colonnes longues affiché.
        $vueComplete = entree_get('vue', '') === 'complet';

        View::afficher('distributions/liste.php', [
            'titrePage'   => ($type === 'civ' ? 'Distributions Civiles' : 'Distributions Pénaux') . ' — ' . APP_NOM,
            'type'        => $type,
            'resultat'    => $resultat,
            'recherche'   => $recherche,
            'vueComplete' => $vueComplete,
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

    /**
     * Formulaire et traitement de modification d'une distribution :
     * année, liste, parties, sigle et dates de remise (secrétariat / AG),
     * comme les deux blocs de modification de la v1.
     */
    private function modifier(string $type): void
    {
        $modele = new DistributionModel();
        $id = (int) entree_get('id', 0);
        $distribution = $modele->lire($id);

        if ($distribution === null) {
            http_response_code(404);
            View::afficher('erreurs/404.php', ['titrePage' => 'Distribution introuvable']);
            return;
        }

        $erreurs = [];
        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
                rediriger('/distributions?type=' . $type);
            }
            [$donnees, $erreurs] = $this->lireFormulaireModification();
            if ($erreurs === []) {
                $modele->modifier($id, $donnees);
                flash('succes', 'La distribution du dossier ' . $distribution['num_dossier_PGCS'] . ' a été mise à jour.');
                rediriger('/distributions?type=' . $type);
            }
        }

        View::afficher('distributions/formulaire.php', [
            'titrePage'    => 'Modifier la distribution — ' . APP_NOM,
            'type'         => $type,
            'distribution' => $distribution,
            'erreurs'      => $erreurs,
        ]);
    }

    /**
     * Lit et valide les champs du formulaire de modification.
     * @return array{0: array, 1: string[]} données + erreurs
     */
    private function lireFormulaireModification(): array
    {
        $erreurs  = [];
        $nomListe = trim((string) entree_post('nom_liste', ''));
        $annee    = (int) entree_post('annee', 0);
        $parties  = trim((string) entree_post('parties', ''));
        $sigle    = trim((string) entree_post('sigle', ''));

        $donnees = [
            'nom_liste' => $nomListe,
            'annee'     => $annee,
            'parties'   => mb_substr($parties, 0, 255),
            'sigle'     => $sigle,
        ];

        foreach (['date_remise_secr' => 'remise au secrétariat', 'date_remise_AG' => 'remise à l’AG'] as $champ => $libelle) {
            $valeur = trim((string) entree_post($champ, ''));
            if ($valeur === '') {
                // Convention historique : absence de date = 0000-00-00.
                $donnees[$champ] = '0000-00-00';
            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valeur, $p) !== 1
                || !checkdate((int) $p[2], (int) $p[3], (int) $p[1])) {
                $erreurs[] = 'La date de ' . $libelle . ' doit être une date valide.';
                $donnees[$champ] = '0000-00-00';
            } else {
                $donnees[$champ] = $valeur;
            }
        }

        if ($nomListe === '')               $erreurs[] = 'Le nom de la liste est obligatoire.';
        if ($annee < 1990 || $annee > 2100) $erreurs[] = 'L\'année doit être comprise entre 1990 et 2100.';
        if ($sigle === '')                  $erreurs[] = 'Le sigle de l\'Avocat Général est obligatoire.';

        return [$donnees, $erreurs];
    }
}
