<?php
/**
 * Contrôleur générique des registres (arrivées, pénaux, civils,
 * dessaisissements).
 *
 * Toutes les actions sont pilotées par la définition déclarative du
 * registre : liste, recherche, création, modification, fiche, archivage
 * et édition de conclusion.
 */

declare(strict_types=1);

final class RegistreController
{
    /** Répartition des segments de route : ['registres', '<code>', '<action>'] */
    public function dispatch(array $route): void
    {
        Auth::exiger();

        $code = $route[1] ?? '';
        $action = $route[2] ?? 'liste';
        $definition = registre_definition($code);

        if ($definition === null) {
            http_response_code(404);
            View::afficher('erreurs/404.php', ['titrePage' => 'Registre inconnu']);
            return;
        }

        match ($action) {
            'liste'      => $this->liste($definition),
            'creer'      => $this->formulaire($definition, null),
            'modifier'   => $this->formulaire($definition, (int) entree_get('id', 0)),
            'fiche'      => $this->fiche($definition, (int) entree_get('id', 0)),
            'archiver'   => $this->archiver($definition, (int) entree_get('id', 0)),
            'conclusion' => $this->conclusion($definition, (int) entree_get('id', 0)),
            default      => page_404('Action inconnue'),
        };
    }

    // ------------------------------------------------------------------ //

    private function liste(array $definition): void
    {
        $page      = max(1, (int) entree_get('p', 1));
        $recherche = trim((string) entree_get('q', ''));
        $anneeBrute = (string) entree_get('annee', '');
        $annee     = $anneeBrute !== '' ? (int) $anneeBrute : null;

        $modele = new RegistreModel($definition);
        if (entree_get('export', '') === 'csv') {
            $colonnes = array_map(static fn (array $c): array => [
                'champ' => $c['nom'], 'libelle' => $c['libelle'], 'type' => $c['type'] ?? '',
            ], registre_colonnes($definition, 'liste'));
            CsvExporter::telecharger(
                'registre-' . $this->codeRegistre($definition) . '-' . date('Ymd') . '.csv',
                $colonnes,
                $modele->exporter($recherche, $annee)
            );
        }
        $resultat = $modele->liste($page, $recherche, $annee);

        View::afficher('registres/liste.php', [
            'titrePage'   => $definition['libelle'] . ' — ' . APP_NOM,
            // Code de ROUTE (clé sans accent de la définition), pas le
            // libellé affichable : les liens des vues en dépendent.
            'code'        => $this->codeRegistre($definition),
            'definition'  => $definition,
            'resultat'    => $resultat,
            'recherche'   => $recherche,
            'annee'       => $annee,
            'annees'      => $modele->annees(),
        ]);
    }

    /**
     * Création (id = null) ou modification (id renseigné) d'un dossier.
     */
    private function formulaire(array $definition, ?int $id): void
    {
        $modele = new RegistreModel($definition);
        $dossier = $id !== null ? $modele->trouver($id) : null;

        if ($id !== null && $dossier === null) {
            http_response_code(404);
            View::afficher('erreurs/404.php', ['titrePage' => 'Dossier introuvable']);
            return;
        }

        $erreurs = [];
        $valeursFormulaire = [];
        $dossiersCivils = [];
        if ($definition['table'] === 'siege_cassation_civ') {
            $dossiersCivils = (new LiaisonRegistreModel())->dossiersCivils();
            $idCivilPreselectionne = filter_var(entree_get('dossier_civil'), FILTER_VALIDATE_INT);
            if ($dossier === null && $idCivilPreselectionne !== false && $idCivilPreselectionne !== null && $idCivilPreselectionne > 0) {
                $valeursFormulaire['id_reg_pourvoi_civ'] = (string) $idCivilPreselectionne;
            }
        }
        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
                rediriger('/registres/' . $this->codeRegistre($definition));
            }
            foreach (registre_colonnes($definition, 'formulaire') as $colonne) {
                $nom = $colonne['nom'];
                $valeurRecue = entree_post($nom);
                if ($valeurRecue !== null && is_scalar($valeurRecue)) {
                    $valeursFormulaire[$nom] = (string) $valeurRecue;
                }
            }
            $donnees = $this->lireFormulaire($definition, $erreurs);

            if ($erreurs === []) {
                if ($dossier === null) {
                    $modele->inserer($donnees);
                    flash('succes', 'Le dossier a été enregistré dans le registre.');
                } else {
                    $modele->modifier($id, $donnees);
                    flash('succes', 'Le dossier a été mis à jour.');
                }
                rediriger('/registres/' . array_key_first(array_filter(
                    registres_definitions(),
                    fn ($d) => $d['table'] === $definition['table']
                )));
            }
        }

        View::afficher('registres/formulaire.php', [
            'titrePage' => ($dossier === null ? 'Nouveau dossier — ' : 'Modifier le dossier — ')
                         . $definition['libelle'] . ' — ' . APP_NOM,
            'definition'=> $definition,
            'code'      => $this->codeRegistre($definition),
            'dossier'   => $dossier,
            'erreurs'   => $erreurs,
            'valeursFormulaire' => $valeursFormulaire,
            'dossiersCivils' => $dossiersCivils,
        ]);
    }

    private function fiche(array $definition, int $id): void
    {
        $modele  = new RegistreModel($definition);
        $dossier = $modele->trouver($id);

        if ($dossier === null) {
            http_response_code(404);
            View::afficher('erreurs/404.php', ['titrePage' => 'Dossier introuvable']);
            return;
        }

        $memoires = [];
        $origineMemoire = match ($definition['table']) {
            'pgcc_reg_pourvoi_pen' => 'penal',
            'pgcc_reg_pourvoi_civ' => 'civil',
            default => null,
        };
        if ($origineMemoire !== null) {
            $memoires = (new MemoireModel())->pourDossier($origineMemoire, (int) $dossier[$definition['cle']]);
        }

        $liaisons = new LiaisonRegistreModel();
        $dossierCivilLie = null;
        $siegesCivil = [];
        if ($definition['table'] === 'siege_cassation_civ' && !empty($dossier['id_reg_pourvoi_civ'])) {
            $dossierCivilLie = $liaisons->trouverDossierCivil((int) $dossier['id_reg_pourvoi_civ']);
        } elseif ($definition['table'] === 'pgcc_reg_pourvoi_civ') {
            $siegesCivil = $liaisons->siegesPourDossierCivil((int) $dossier[$definition['cle']]);
        }

        View::afficher('registres/fiche.php', [
            'titrePage'  => 'Dossier ' . ($dossier['num_dossier_PGCS'] ?? $id) . ' — ' . APP_NOM,
            'definition' => $definition,
            'code'       => $this->codeRegistre($definition),
            'dossier'    => $dossier,
            'memoires'   => $memoires,
            'dossierCivilLie' => $dossierCivilLie,
            'siegesCivil' => $siegesCivil,
        ]);
    }

    private function archiver(array $definition, int $id): void
    {
        if (!requete_post()) {
            flash('erreur', 'Cette action doit être envoyée depuis le formulaire prévu.');
            rediriger('/registres/' . $this->codeRegistre($definition));
        }
        if (!Auth::verifierJeton(entree_post('jeton'))) {
            flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
            rediriger('/registres/' . $this->codeRegistre($definition));
        }
        $modele = new RegistreModel($definition);
        $dossier = $modele->trouver($id);
        if ($dossier !== null) {
            $colonne = registre_colonne_archive($definition);
            $dejaArchive = bin_vers_bool($dossier[$colonne] ?? null);
            $modele->modifier($id, [$colonne => $dejaArchive ? '0' : '1']);
            flash('info', $dejaArchive ? 'Dossier désarchivé.' : 'Dossier archivé.');
        }
        rediriger('/registres/' . $this->codeRegistre($definition));
    }

    /**
     * Édition de la conclusion (registres pénaux, civils, dessaisissements).
     */
    private function conclusion(array $definition, int $id): void
    {
        $modele  = new RegistreModel($definition);
        $dossier = $modele->trouver($id);

        if ($dossier === null || $definition['conclusion'] === null) {
            http_response_code(404);
            View::afficher('erreurs/404.php', ['titrePage' => 'Dossier introuvable']);
            return;
        }

        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
                rediriger('/registres/' . $this->codeRegistre($definition) . '/fiche?id=' . $id);
            }
            $modele->enregistrerConclusion($id, (string) entree_post('conclusion', ''));
            flash('succes', 'La conclusion a été enregistrée.');
            rediriger('/registres/' . $this->codeRegistre($definition) . '/fiche?id=' . $id);
        }

        View::afficher('registres/conclusion.php', [
            'titrePage' => 'Conclusion — ' . APP_NOM,
            'definition'=> $definition,
            'code'      => $this->codeRegistre($definition),
            'dossier'   => $dossier,
        ]);
    }

    /**
     * Code du registre à partir de sa table (pour construire les URLs).
     */
    private function codeRegistre(array $definition): string
    {
        foreach (registres_definitions() as $code => $d) {
            if ($d['table'] === $definition['table']) {
                return $code;
            }
        }
        return '';
    }

    /**
     * Lit et valide les champs du formulaire selon la définition du registre.
     * Les erreurs de validation sont ajoutées à $erreurs (passé par référence).
     */
    private function lireFormulaire(array $definition, array &$erreurs): array
    {
        $donnees = [];
        foreach (registre_colonnes($definition, 'formulaire') as $colonne) {
            $nom  = $colonne['nom'];
            $valeur = entree_post($nom, '');
            if (!is_scalar($valeur)) {
                $erreurs[] = 'La valeur du champ « ' . $colonne['libelle'] . ' » est invalide.';
                $valeur = '';
            }

            switch ($colonne['type']) {
                case 'dossier_civil':
                    $idCivil = filter_var((string) $valeur, FILTER_VALIDATE_INT);
                    $dossierCivil = $idCivil !== false && $idCivil > 0
                        ? (new LiaisonRegistreModel())->trouverDossierCivil((int) $idCivil)
                        : null;
                    if ($dossierCivil === null) {
                        $erreurs[] = 'Sélectionnez un dossier civil existant pour le registre du Siège.';
                        $donnees[$nom] = null;
                    } else {
                        $donnees[$nom] = (int) $dossierCivil['id_reg_pourvoi'];
                    }
                    break;

                case 'nombre':
                    $nombreSaisi = (string) $valeur;
                    $nombreValide = ctype_digit($nombreSaisi) && (int) $nombreSaisi > 0;
                    if ($nom === 'annee' && $nombreValide) {
                        $nombreValide = (int) $nombreSaisi >= 1900 && (int) $nombreSaisi <= (int) date('Y') + 1;
                    }

                    if (!$nombreValide) {
                        if (!empty($colonne['requis'])) {
                            $erreurs[] = $nom === 'annee'
                                ? 'Choisissez une année comprise entre 1900 et ' . ((int) date('Y') + 1) . '.'
                                : 'Le champ « ' . $colonne['libelle'] . ' » doit être un nombre positif.';
                        }
                        $donnees[$nom] = 0;
                    } else {
                        $donnees[$nom] = (int) $nombreSaisi;
                    }
                    break;

                case 'date':
                    // Les anciennes tables imposent une date ; une valeur vide
                    // reste donc stockée au format historique zéro.
                    $dateSaisie = trim((string) $valeur);
                    $dateValide = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateSaisie, $partiesDate) === 1
                        && checkdate((int) $partiesDate[2], (int) $partiesDate[3], (int) $partiesDate[1]);

                    if ($dateSaisie === '') {
                        $donnees[$nom] = '0000-00-00';
                    } elseif (!$dateValide) {
                        $erreurs[] = 'Le champ « ' . $colonne['libelle'] . ' » doit contenir une date valide.';
                        $donnees[$nom] = '0000-00-00';
                    } else {
                        $donnees[$nom] = $dateSaisie;
                    }
                    break;

                case 'booleen':
                    // Stockage historique : binary(1) '0'/'1'.
                    $donnees[$nom] = !empty($valeur) ? '1' : '0';
                    break;

                default: // texte et textelong
                    $valeur = trim((string) $valeur);
                    if ($valeur === '' && !empty($colonne['requis'])) {
                        $erreurs[] = 'Le champ « ' . $colonne['libelle'] . ' » est obligatoire.';
                    }
                    $donnees[$nom] = $valeur;
            }
        }
        return $donnees;
    }
}
