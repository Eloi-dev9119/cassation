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

        // Export PDF de la liste filtrée (avant tout rendu HTML).
        if (entree_get('export', '') === 'pdf' && $action === 'liste') {
            $modeleExport = new RegistreModel($definition);
            $colonnesPdf = array_map(static fn (array $c): array => [
                'champ' => $c['nom'], 'libelle' => $c['libelle'],
            ], registre_colonnes($definition, 'liste'));
            PdfExporter::telechargerTableau(
                'registre-' . $this->codeRegistre($definition) . '-' . date('Ymd') . '.pdf',
                $definition['libelle'],
                $colonnesPdf,
                $modeleExport->exporter(
                    trim((string) entree_get('q', '')),
                    (string) entree_get('annee', '') !== '' ? (int) entree_get('annee') : null,
                    entree_get('detention', '') === '1'
                )
            );
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
        // Filtre « dossiers en détention » (registres pénaux) : permet à la
        // tuile du tableau de bord d'ouvrir la liste correspondant au chiffre.
        $detention = entree_get('detention', '') === '1';

        $modele = new RegistreModel($definition);
        if (entree_get('export', '') === 'csv') {
            $colonnes = array_map(static fn (array $c): array => [
                'champ' => $c['nom'], 'libelle' => $c['libelle'], 'type' => $c['type'] ?? '',
            ], registre_colonnes($definition, 'liste'));
            CsvExporter::telecharger(
                'registre-' . $this->codeRegistre($definition) . '-' . date('Ymd') . '.csv',
                $colonnes,
                $modele->exporter($recherche, $annee, $detention)
            );
        }
        $resultat = $modele->liste($page, $recherche, $annee, $detention);

        // Vue complète : les colonnes longues s'affichent sans troncature
        // (tout le contenu du registre visible dans le tableau).
        $vueComplete = entree_get('vue', '') === 'complet';

        // Indicateur « Mémoires » : nombre de mémoires reliés à chaque
        // dossier de la page (registres pénaux et civils uniquement).
        $origineMemoire = match ($definition['table']) {
            'pgcc_reg_pourvoi_pen' => 'penal',
            'pgcc_reg_pourvoi_civ' => 'civil',
            default => null,
        };
        $memoiresParDossier = [];
        if ($origineMemoire !== null) {
            $idsPage = array_map(
                static fn (array $ligne): int => (int) $ligne[$definition['cle']],
                $resultat['lignes']
            );
            $memoiresParDossier = (new MemoireModel())->compterParDossier($origineMemoire, $idsPage);
        }

        View::afficher('registres/liste.php', [
            'titrePage'   => $definition['libelle'] . ' — ' . APP_NOM,
            // Code de ROUTE (clé sans accent de la définition), pas le
            // libellé affichable : les liens des vues en dépendent.
            'code'        => $this->codeRegistre($definition),
            'definition'  => $definition,
            'resultat'    => $resultat,
            'recherche'   => $recherche,
            'annee'       => $annee,
            'detention'   => $detention,
            'annees'      => $modele->annees(),
            'vueComplete' => $vueComplete,
            'afficherMemoires'   => $origineMemoire !== null,
            'memoiresParDossier' => $memoiresParDossier,
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

            // Édition concurrente : le formulaire transporte la version
            // (date_modification) vue par l'éditeur ; si le dossier a été
            // modifié entre-temps par un autre agent, on refuse l'écriture.
            if ($dossier !== null) {
                $versionRecue = (string) entree_post('version', '');
                $versionActuelle = (string) ($dossier['date_modification'] ?? '');
                if ($versionRecue !== $versionActuelle) {
                    $erreurs[] = 'Ce dossier a été modifié entre-temps'
                        . (!empty($dossier['modifie_par']) ? ' par ' . $dossier['modifie_par'] : '')
                        . ' — la fiche affichée était périmée. Rechargez la fiche puis ressaisissez vos modifications.';
                }
            }

            if ($erreurs === []) {
                // Unicité déclarative : la clé métier du registre (par
                // exemple num_dossier_PGCS) porte une contrainte UNIQUE en
                // base. On la vérifie en amont pour afficher un message
                // lisible au lieu d'une erreur SQL (HTTP 500).
                $conflit = $modele->doublonUnicite($donnees, $dossier !== null ? $id : null);
                if ($conflit !== null) {
                    $libelles = array_map(
                        fn (string $colonne): string => registre_libelle_colonne($definition, $colonne),
                        $conflit
                    );
                    $erreurs[] = 'Un dossier avec le même '
                        . implode(' et ', $libelles)
                        . ' existe déjà dans ce registre.';
                }
            }

            if ($erreurs === []) {
                try {
                    if ($dossier === null) {
                        $idNouveau = $modele->inserer($donnees);

                        // Liaison différée : rattache automatiquement les
                        // mémoires restés sans dossier source portant ce numéro.
                        $origineMemoire = match ($definition['table']) {
                            'pgcc_reg_pourvoi_pen' => 'penal',
                            'pgcc_reg_pourvoi_civ' => 'civil',
                            default => null,
                        };
                        $nbMemoiresLies = 0;
                        if ($origineMemoire !== null && isset($donnees['num_dossier_PGCS'])) {
                            $nbMemoiresLies = (new MemoireModel())->lierDossiersManquants(
                                $origineMemoire,
                                (string) $donnees['num_dossier_PGCS'],
                                $idNouveau
                            );
                        }
                        flash('succes', 'Le dossier a été enregistré dans le registre.'
                            . ($nbMemoiresLies > 0
                                ? ' ' . $nbMemoiresLies . ' mémoire(s) du même numéro ont été relié(s) automatiquement.'
                                : ''));
                    } else {
                        $modele->modifier($id, $donnees);
                        flash('succes', 'Le dossier a été mis à jour.');
                    }
                } catch (PDOException $e) {
                    // Filet de sécurité : une violation d'unicité non prévue
                    // (course entre deux saisies simultanées) ne doit pas
                    // produire une erreur 500.
                    if ($e->getCode() === '23000') {
                        error_log('[PGCC2] Registre ' . $definition['table'] . ' : ' . $e->getMessage());
                        $erreurs[] = 'Un dossier portant déjà ce numéro existe dans ce registre.';
                    } else {
                        throw $e;
                    }
                }
            }

            if ($erreurs === []) {
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
            'version'   => $dossier !== null ? (string) ($dossier['date_modification'] ?? '') : '',
        ]);
    }

    private function fiche(array $definition, int $id): void
    {
        $modele  = new RegistreModel($definition);
        $code    = $this->codeRegistre($definition);
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

        // Pièces jointes du dossier (documents scannés archivés).
        // Si la table n'existe pas encore (migration non exécutée), la
        // fiche reste consultable : le bloc affiche une note discrète.
        $pieces = null;
        try {
            $pieces = (new PieceModel())->pourEntite('dossier_' . $code, (int) $dossier[$definition['cle']]);
        } catch (Throwable) {
            // pgcc_pieces_jointes absente — dégradation silencieuse.
        }

        View::afficher('registres/fiche.php', [
            'titrePage'  => 'Dossier ' . ($dossier['num_dossier_PGCS'] ?? $id) . ' — ' . APP_NOM,
            'definition' => $definition,
            'code'       => $code,
            'dossier'    => $dossier,
            'memoires'   => $memoires,
            'dossierCivilLie' => $dossierCivilLie,
            'siegesCivil' => $siegesCivil,
            'pieces'     => $pieces,
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

        $code = $this->codeRegistre($definition);
        // Modèle prérempli : civil → ConclusionCivile, déssaisissement →
        // ConclusionDessaisissement (requête aux fins de renvoi d'une
        // juridiction à une autre), pénal → ConclusionPenale.
        $modeleDisponible = in_array($code, ['civils', 'penaux', 'dessaisissements'], true);
        $conclusion = (string) ($dossier[$definition['conclusion']] ?? '');

        // Export PDF de la conclusion (document A4, sans l'interface).
        if (entree_get('export', '') === 'pdf') {
            if (trim($conclusion) === '' && $modeleDisponible) {
                $conclusion = $this->modeleConclusion($code, $dossier);
            }
            PdfExporter::telechargerTexte(
                'conclusion-' . preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($dossier['num_dossier_PGCS'] ?? (string) $id)) . '.pdf',
                'Conclusion — dossier ' . ($dossier['num_dossier_PGCS'] ?? $id),
                $conclusion
            );
        }

        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
                rediriger('/registres/' . $code . '/fiche?id=' . $id);
            }
            if ($modeleDisponible && entree_post('action') === 'modele') {
                $conclusion = $this->conclusionModeleAvecAnnexe($definition, $dossier, $code);
                flash('info', 'Le modèle prérempli a été inséré. Complétez les mentions entre crochets avant d’enregistrer.');
            } else {
                $modele->enregistrerConclusion($id, (string) entree_post('conclusion', ''));
                flash('succes', 'La conclusion a été enregistrée.');
                rediriger('/registres/' . $code . '/fiche?id=' . $id);
            }
        } elseif ($modeleDisponible && entree_get('modele', '') === '1') {
            // Réinsertion du modèle via un lien GET : la page reste rendue en
            // GET (pas de POST dans l'historique → le bouton « retour » du
            // navigateur ne déclenche plus d'erreur de cache).
            $conclusion = $this->conclusionModeleAvecAnnexe($definition, $dossier, $code);
            flash('info', 'Le modèle prérempli a été réinséré dans l’éditeur. Complétez les mentions entre crochets avant d’enregistrer.');
        }

        if ($modeleDisponible && trim($conclusion) === '') {
            $conclusion = $this->conclusionModeleAvecAnnexe($definition, $dossier, $code);
        }

        View::afficher('registres/conclusion.php', [
            'titrePage' => 'Conclusion — ' . APP_NOM,
            'definition'=> $definition,
            'code'      => $code,
            'dossier'   => $dossier,
            'conclusion'=> $conclusion,
            'modeleDisponible' => $modeleDisponible,
        ]);
    }

    /**
     * Choisit le modèle de conclusion selon le registre : civil, déssaisis-
     * sement (requête aux fins de renvoi) ou pénal (par défaut).
     */
    private function modeleConclusion(string $code, array $dossier): string
    {
        return match ($code) {
            'civils'           => ConclusionCivile::modele($dossier),
            'dessaisissements' => ConclusionDessaisissement::modele($dossier),
            default            => ConclusionPenale::modele($dossier),
        };
    }

    /**
     * Modèle de conclusion prérempli (pénal/civil/déssaisissement selon le
     * registre), complété d'une annexe reprenant l'intégralité des données du
     * dossier telles qu'affichées sur sa fiche — le document imprimé (A4)
     * contient ainsi toutes les informations utiles au rédacteur.
     */
    private function conclusionModeleAvecAnnexe(array $definition, array $dossier, string $code): string
    {
        $modele = $this->modeleConclusion($code, $dossier);

        $lignes = [
            '',
            str_repeat('=', 64),
            'ANNEXE — DONNÉES COMPLÈTES DU DOSSIER',
            str_repeat('=', 64),
        ];
        foreach ($definition['colonnes'] as $colonne) {
            if (($colonne['afficherFiche'] ?? true) === false) { continue; }
            if ($colonne['nom'] === ($definition['conclusion'] ?? '')) { continue; }

            $valeur = $dossier[$colonne['nom']] ?? '';
            $texte = match ($colonne['type'] ?? '') {
                'booleen' => bin_vers_bool($valeur) ? 'Oui' : 'Non',
                'date'    => date_fr((string) $valeur),
                default   => trim(strip_tags((string) $valeur)),
            };
            $lignes[] = $colonne['libelle'] . ' : ' . ($texte === '' ? '—' : $texte);
        }

        return $modele . "\n" . implode("\n", $lignes);
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
