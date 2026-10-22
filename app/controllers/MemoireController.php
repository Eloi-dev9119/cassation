<?php
/**
 * Contrôleur du Registre des Mémoires (nouveau module).
 *
 * Actions : liste filtrable, création, modification, suppression,
 * changement d'état.
 */

declare(strict_types=1);

final class MemoireController
{
    public function dispatch(array $route): void
    {
        Auth::exiger();

        $action = $route[1] ?? 'liste';

        match ($action) {
            'liste'      => $this->liste(),
            'creer'      => $this->formulaire(null),
            'modifier'   => $this->formulaire((int) entree_get('id', 0)),
            'supprimer'  => $this->supprimer((int) entree_get('id', 0)),
            'etat'       => $this->changerEtat((int) entree_get('id', 0)),
            default      => page_404('Action inconnue'),
        };
    }

    // ------------------------------------------------------------------ //

    private function liste(): void
    {
        $page      = max(1, (int) entree_get('p', 1));
        $recherche = trim((string) entree_get('q', ''));
        $typeBrut  = (string) entree_get('type', '');
        $type      = $typeBrut !== '' ? $typeBrut : null;
        $etatBrut  = (string) entree_get('etat', '');
        $etat      = $etatBrut !== '' ? $etatBrut : null;

        $modele = new MemoireModel();
        if (entree_get('export', '') === 'csv') {
            CsvExporter::telecharger('memoires-' . date('Ymd') . '.csv', [
                ['champ' => 'num_ordre', 'libelle' => 'N° ordre'],
                ['champ' => 'annee', 'libelle' => 'Année'],
                ['champ' => 'type_memoire', 'libelle' => 'Type', 'valeurs' => self::libellesMemoire()],
                ['champ' => 'origine', 'libelle' => 'Origine', 'valeurs' => MemoireModel::ORIGINES],
                ['champ' => 'num_dossier_PGCS', 'libelle' => 'N° dossier PGCS'],
                ['champ' => 'parties', 'libelle' => 'Parties'],
                ['champ' => 'avocat', 'libelle' => 'Avocat'],
                ['champ' => 'date_depot', 'libelle' => 'Date de dépôt', 'type' => 'date'],
                ['champ' => 'rapporteur', 'libelle' => 'Rapporteur'],
                ['champ' => 'etat', 'libelle' => 'État', 'valeurs' => MemoireModel::ETATS],
            ], $modele->exporter($recherche, $type, $etat));
        }
        $resultat = $modele->liste($page, $recherche, $type, $etat);

        View::afficher('memoires/liste.php', [
            'titrePage' => 'Registre des Mémoires — ' . APP_NOM,
            'resultat'  => $resultat,
            'recherche' => $recherche,
            'type'      => $type,
            'etat'      => $etat,
        ]);
    }

    private static function libellesMemoire(): array
    {
        return ['ampliatif' => 'Mémoire ampliatif', 'defense' => 'Mémoire en défense'];
    }

    private function formulaire(?int $id): void
    {
        $modele  = new MemoireModel();
        $memoire = $id !== null ? $modele->trouver($id) : null;

        if ($id !== null && $memoire === null) {
            page_404('Mémoire introuvable');
        }

        // N° de dossier pré-rempli quand on arrive depuis la fiche d'un
        // dossier de registre (?dossier=...).
        $prefillDossier = (string) ($memoire['num_dossier_PGCS'] ?? trim((string) entree_get('dossier', '')));
        $prefillOrigine = (string) ($memoire['origine'] ?? entree_get('origine', 'penal'));
        if (!isset(MemoireModel::ORIGINES[$prefillOrigine])) {
            $prefillOrigine = 'penal';
        }
        $colonneSource = $prefillOrigine === 'civil' ? 'id_reg_pourvoi_civ' : 'id_reg_pourvoi_pen';
        $idDossierSourceSelectionne = (int) ($memoire[$colonneSource] ?? entree_get('source_id', 0));
        $dossiersCorrespondants = $prefillDossier !== ''
            ? $modele->dossiersCorrespondants($prefillOrigine, $prefillDossier)
            : [];
        $valeursFormulaire = [];
        $erreurs = [];
        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
                rediriger('/memoires');
            }
            foreach (entrees_post() as $champ => $valeur) {
                if ($champ !== 'jeton' && is_scalar($valeur)) {
                    $valeursFormulaire[$champ] = (string) $valeur;
                }
            }
            $prefillDossier = trim((string) entree_post('num_dossier_PGCS', ''));
            $prefillOrigine = (string) entree_post('origine', 'penal');
            $idDossierSourceSelectionne = (int) entree_post('id_dossier_source', 0);
            $dossiersCorrespondants = isset(MemoireModel::ORIGINES[$prefillOrigine]) && $prefillDossier !== ''
                ? $modele->dossiersCorrespondants($prefillOrigine, $prefillDossier)
                : [];
            $donnees = $this->lireFormulaire($erreurs, (bool) $memoire, $memoire);

            if ($erreurs === []) {
                if ($memoire === null) {
                    $modele->inserer($donnees);
                    flash('succes', 'Le mémoire a été enregistré.');
                } else {
                    // La date d'insertion historique est préservée en
                    // modification (elle marque l'entrée du mémoire au registre).
                    $donnees['date_insertion'] = $memoire['date_insertion'] ?? $donnees['date_insertion'];
                    $modele->modifier($id, $donnees);
                    flash('succes', 'Le mémoire a été mis à jour.');
                }
                rediriger('/memoires');
            }
        }

        View::afficher('memoires/formulaire.php', [
            'titrePage' => ($memoire === null ? 'Nouveau mémoire — ' : 'Modifier le mémoire — ') . APP_NOM,
            'memoire'   => $memoire,
            'erreurs'   => $erreurs,
            'prefillDossier' => $prefillDossier,
            'prefillOrigine' => $prefillOrigine,
            'idDossierSourceSelectionne' => $idDossierSourceSelectionne,
            'dossiersCorrespondants' => $dossiersCorrespondants,
            'valeursFormulaire' => $valeursFormulaire,
        ]);
    }

    private function supprimer(int $id): void
    {
        if (!requete_post()) {
            flash('erreur', 'Cette action doit être envoyée depuis le formulaire prévu.');
            rediriger('/memoires');
        }
        if (!Auth::verifierJeton(entree_post('jeton'))) {
            flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
            rediriger('/memoires');
        }
        (new MemoireModel())->supprimer($id);
        flash('info', 'Le mémoire a été supprimé du registre.');
        rediriger('/memoires');
    }

    /**
     * Change l'état d'un mémoire (déposé → transmis → rapport établi → classé).
     */
    private function changerEtat(int $id): void
    {
        if (!requete_post()) {
            flash('erreur', 'Cette action doit être envoyée depuis le formulaire prévu.');
            rediriger('/memoires');
        }
        if (!Auth::verifierJeton(entree_post('jeton'))) {
            flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
            rediriger('/memoires');
        }
        $etat = (string) entree_post('etat', '');
        if (isset(MemoireModel::ETATS[$etat])) {
            (new MemoireModel())->modifier($id, ['etat' => $etat]);
            flash('succes', 'État du mémoire mis à jour : ' . MemoireModel::ETATS[$etat] . '.');
        } else {
            flash('erreur', 'L’état sélectionné n’est pas reconnu. Le mémoire n’a pas été modifié.');
        }
        rediriger('/memoires');
    }

    /**
     * Lit et valide le formulaire mémoire.
     */
    private function lireFormulaire(array &$erreurs, bool $modification, ?array $memoireExistant = null): array
    {
        $d = [
            'num_ordre'    => 0,
            'annee'        => 0,
            'date_insertion' => null,
            'type_memoire' => entree_post('type_memoire', '') === 'defense' ? 'defense' : 'ampliatif',
            'origine'      => (string) entree_post('origine', 'penal'),
            'num_dossier_PGCS' => trim((string) entree_post('num_dossier_PGCS', '')),
            'parties'      => trim((string) entree_post('parties', '')),
            'avocat'       => trim((string) entree_post('avocat', '')),
            'date_notification' => $this->lireDate('date_notification', $erreurs),
            'date_depot'        => $this->lireDate('date_depot', $erreurs),
            'date_transmission_secretaire' => $this->lireDate('date_transmission_secretaire', $erreurs),
            'rapporteur'   => trim((string) entree_post('rapporteur', '')),
            'observation'  => trim((string) entree_post('observation', '')),
            'etat'         => (string) entree_post('etat', 'depose'),
        ];

        if (!isset(MemoireModel::ETATS[$d['etat']])) {
            $d['etat'] = 'depose';
        }
        if (!isset(MemoireModel::ORIGINES[$d['origine']])) {
            $erreurs[] = 'Choisissez une origine de dossier valide.';
            $d['origine'] = 'penal';
        }
        $idDossierSource = null;
        $erreurChoixSource = false;
        $modeleMemoire = new MemoireModel();
        if ($d['num_dossier_PGCS'] === '') {
            $erreurs[] = 'Le numéro de dossier PGCS est obligatoire.';
        } else {
            $selectionBrute = trim((string) entree_post('id_dossier_source', ''));
            $memeSource = ($memoireExistant['origine'] ?? null) === $d['origine']
                && ($memoireExistant['num_dossier_PGCS'] ?? null) === $d['num_dossier_PGCS'];
            if ($selectionBrute !== '') {
                $idChoisi = filter_var($selectionBrute, FILTER_VALIDATE_INT);
                $dossierChoisi = $idChoisi !== false && $idChoisi > 0
                    ? $modeleMemoire->trouverDossierSource($d['origine'], (int) $idChoisi)
                    : null;
                if ($dossierChoisi === null || trim((string) $dossierChoisi['num_dossier_PGCS']) !== $d['num_dossier_PGCS']) {
                    $erreurChoixSource = true;
                    $erreurs[] = 'Le dossier choisi ne correspond pas à l\'origine et au numéro saisis.';
                } else {
                    $idDossierSource = (int) $dossierChoisi['id_reg_pourvoi'];
                }
            } else {
                $colonneSource = $d['origine'] === 'penal' ? 'id_reg_pourvoi_pen' : 'id_reg_pourvoi_civ';
                $idDossierSource = $memeSource ? (int) ($memoireExistant[$colonneSource] ?? 0) : 0;
                if ($idDossierSource < 1) {
                    $idDossierSource = $modeleMemoire->dossierSourceId($d['origine'], $d['num_dossier_PGCS']);
                }
            }
            if (($idDossierSource === null || $idDossierSource < 1) && !$erreurChoixSource) {
                $candidats = $modeleMemoire->dossiersCorrespondants($d['origine'], $d['num_dossier_PGCS']);
                $erreurs[] = count($candidats) > 1
                    ? 'Plusieurs dossiers portent ce numéro. Sélectionnez le dossier précis dans la liste.'
                    : 'Aucun dossier unique ne correspond à cette origine et à ce numéro. Vérifiez le registre et le numéro PGCS.';
                $idDossierSource = null;
            }
        }
        $d['id_reg_pourvoi_pen'] = $d['origine'] === 'penal' ? $idDossierSource : null;
        $d['id_reg_pourvoi_civ'] = $d['origine'] === 'civil' ? $idDossierSource : null;

        $annee = (int) entree_post('annee', 0);
        if ($annee < 1990 || $annee > 2100) {
            $erreurs[] = 'L\'année doit être comprise entre 1990 et 2100.';
        }
        $d['annee'] = $annee;

        // Numéro d'ordre : auto-calculé en création si laissé vide.
        $numero = trim((string) entree_post('num_ordre', ''));
        if ($numero === '' || !ctype_digit($numero)) {
            if ($modification) {
                $erreurs[] = 'Le numéro d\'ordre doit être numérique.';
            } else {
                $numero = (string) (new MemoireModel())->prochainNumero($annee);
            }
        }
        $d['num_ordre'] = (int) $numero;

        // La date d'insertion est toujours « aujourd'hui » en création.
        if ($d['date_insertion'] === null) {
            $d['date_insertion'] = date('Y-m-d');
        }

        return $d;
    }

    /**
     * Valide une date ISO optionnelle avant de la transmettre à la base.
     */
    private function lireDate(string $champ, array &$erreurs): ?string
    {
        $valeurBrute = entree_post($champ, '');
        $libelles = [
            'date_notification' => 'date de notification',
            'date_depot' => 'date de dépôt',
            'date_transmission_secretaire' => 'date de transmission au secrétariat',
        ];
        $libelle = $libelles[$champ] ?? 'date';

        if (!is_scalar($valeurBrute)) {
            $erreurs[] = 'Le champ « ' . $libelle . ' » contient une valeur invalide.';
            return null;
        }

        $valeur = trim((string) $valeurBrute);
        if ($valeur === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valeur, $parties) !== 1
            || !checkdate((int) $parties[2], (int) $parties[3], (int) $parties[1])) {
            $erreurs[] = 'Le champ « ' . $libelle . ' » doit contenir une date valide.';
            return null;
        }

        return $valeur;
    }
}
