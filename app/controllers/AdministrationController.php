<?php
/**
 * Contrôleur de l'espace d'administration.
 *
 * Réservé aux postes habilités (POSTES_ADMINISTRATION) : tableau de bord,
 * gestion des comptes (création, modification, validation, statut, mot de
 * passe) et journal d'activité issu des colonnes d'audit.
 */

declare(strict_types=1);

final class AdministrationController
{
    public function dispatch(array $route): void
    {
        $utilisateur = Auth::exiger();
        if (!est_administration($utilisateur)) {
            flash('erreur', 'L\'espace d\'administration est réservé aux postes habilités du Parquet.');
            rediriger('/tableau-de-bord');
        }

        $section = $route[1] ?? '';
        $sousSection = $route[2] ?? '';

        switch ($section) {
            case '':
            case 'tableau':
                $this->tableau();
                break;

            case 'comptes':
                if ($sousSection === 'creer') {
                    $this->creer();
                } elseif ($sousSection === 'modifier') {
                    $this->modifier();
                } elseif ($sousSection === 'action') {
                    $this->action();
                } else {
                    $this->comptes();
                }
                break;

            case 'audit':
                $this->audit();
                break;

            default:
                page_404('Page d\'administration introuvable');
        }
    }

    private function tableau(): void
    {
        $modele = new AdministrationModel();
        $stats = new StatsModel();

        View::afficher('administration/tableau.php', [
            'titrePage' => 'Administration — ' . APP_NOM,
            'compteurs' => $modele->compteurs(),
            'dossiers'  => $stats->compteurs(),
            'journal'   => $modele->journal(8),
        ]);
    }

    private function comptes(): void
    {
        $filtre = (string) entree_get('filtre', 'tous');
        if (!in_array($filtre, ['tous', 'actifs', 'retraites', 'attente'], true)) {
            $filtre = 'tous';
        }
        $recherche = trim((string) entree_get('q', ''));

        if ($filtre === 'attente') {
            $comptes = (new ClientModel())->liste('attente');
            if ($recherche !== '') {
                $comptes = array_values(array_filter(
                    $comptes,
                    static fn (array $compte): bool => mb_stripos(
                        $compte['nom'] . ' ' . $compte['prenom'] . ' ' . $compte['login'],
                        $recherche
                    ) !== false
                ));
            }
        } else {
            $comptes = (new AdministrationModel())->comptes($recherche, $filtre);
        }

        View::afficher('administration/comptes.php', [
            'titrePage' => 'Comptes utilisateurs — ' . APP_NOM,
            'filtre'    => $filtre,
            'recherche' => $recherche,
            'comptes'   => $comptes,
        ]);
    }

    private function creer(): void
    {
        $modele = new AdministrationModel();

        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Votre session a expiré. Recommencez la création du compte.');
                rediriger('/administration/comptes');
            }

            $donnees = $this->donneesFormulaire();
            $erreurs = $this->valider($donnees, true);
            if ($erreurs === []) {
                $modele->creer($donnees);
                flash('succes', 'Le compte de ' . $donnees['prenom'] . ' ' . $donnees['nom'] . ' a été créé.');
                rediriger('/administration/comptes');
            }

            View::afficher('administration/formulaire.php', [
                'titrePage' => 'Nouveau compte — ' . APP_NOM,
                'mode'      => 'creer',
                'compte'    => null,
                'valeurs'   => $donnees,
                'erreurs'   => $erreurs,
                'postes'    => POSTES_UTILISATEUR,
            ]);
            return;
        }

        View::afficher('administration/formulaire.php', [
            'titrePage' => 'Nouveau compte — ' . APP_NOM,
            'mode'      => 'creer',
            'compte'    => null,
            'valeurs'   => [],
            'erreurs'   => [],
            'postes'    => POSTES_UTILISATEUR,
        ]);
    }

    private function modifier(): void
    {
        $modele = new AdministrationModel();
        $id = (int) entree_get('id', 0);
        $compte = $id > 0 ? $modele->trouver($id) : null;

        if ($compte === null) {
            flash('erreur', 'Le compte demandé est introuvable.');
            rediriger('/administration/comptes');
        }

        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
                rediriger('/administration/comptes/modifier?id=' . $id);
            }

            $donnees = $this->donneesFormulaire();
            $erreurs = $this->valider($donnees, false, $id);
            if ($erreurs === []) {
                $modele->modifier($id, $donnees);
                flash('succes', 'Le compte a été mis à jour.');
                rediriger('/administration/comptes');
            }

            View::afficher('administration/formulaire.php', [
                'titrePage' => 'Modifier un compte — ' . APP_NOM,
                'mode'      => 'modifier',
                'compte'    => $compte,
                'valeurs'   => $donnees,
                'erreurs'   => $erreurs,
                'postes'    => POSTES_UTILISATEUR,
            ]);
            return;
        }

        View::afficher('administration/formulaire.php', [
            'titrePage' => 'Modifier un compte — ' . APP_NOM,
            'mode'      => 'modifier',
            'compte'    => $compte,
            'valeurs'   => [
                'nom'           => $compte['nom'],
                'prenom'        => $compte['prenom'],
                'sexe'          => $compte['sexe'],
                'login'         => $compte['login'],
                'poste'         => $compte['poste'],
                'sigle_AG'      => $compte['sigle_AG'],
                'IM'            => $compte['IM'],
                'num_bureau'    => $compte['num_bureau'],
                'status'        => $compte['status'] ?: 'Actifs',
                'motDePasse'    => '',
                'confirmation'  => '',
            ],
            'erreurs'   => [],
            'postes'    => POSTES_UTILISATEUR,
        ]);
    }

    private function action(): void
    {
        if (!requete_post()) {
            rediriger('/administration/comptes');
        }
        if (!Auth::verifierJeton(entree_post('jeton'))) {
            flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
            rediriger('/administration/comptes');
        }

        $id = (int) entree_post('id', 0);
        $action = (string) entree_post('action', '');
        $source = (string) entree_post('source', 'client');
        if ($id <= 0) {
            flash('erreur', 'Le compte demandé est introuvable.');
            rediriger('/administration/comptes');
        }

        $modele = new AdministrationModel();
        $membres = new ClientModel();

        switch ($action) {
            case 'valider':
                $ok = $source === 'new'
                    ? $membres->valider($id, 'new')
                    : $modele->valider($id);
                flash($ok ? 'succes' : 'erreur', $ok
                    ? 'Le compte a été validé. Le membre peut désormais se connecter.'
                    : 'Impossible de valider ce compte.');
                break;

            case 'refuser':
                $ok = $membres->refuser($id, $source);
                flash($ok ? 'info' : 'erreur', $ok
                    ? 'La demande d\'inscription a été refusée et supprimée.'
                    : 'Impossible de refuser cette demande.');
                break;

            case 'retraite':
                $ok = $modele->changerStatut($id, 'Retraite');
                flash($ok ? 'info' : 'erreur', $ok
                    ? 'Le membre a été mis à la retraite.'
                    : 'Impossible de modifier le statut de ce compte.');
                break;

            case 'reactiver':
                $ok = $modele->changerStatut($id, 'Actifs');
                flash($ok ? 'succes' : 'erreur', $ok
                    ? 'Le membre a été réactivé.'
                    : 'Impossible de modifier le statut de ce compte.');
                break;

            case 'motdepasse':
                $nouveau = 'pgcc' . date('Y');
                $ok = $modele->reinitialiserMotDePasse($id, $nouveau);
                flash($ok ? 'succes' : 'erreur', $ok
                    ? 'Le mot de passe temporaire est « ' . $nouveau . ' ». Transmettez-le au membre par un canal sécurisé.'
                    : 'Impossible de réinitialiser le mot de passe de ce compte.');
                break;

            default:
                flash('erreur', 'Action inconnue.');
        }

        rediriger('/administration/comptes');
    }

    private function audit(): void
    {
        View::afficher('administration/audit.php', [
            'titrePage' => 'Journal d\'activité — ' . APP_NOM,
            'journal'   => (new AdministrationModel())->journal(200),
        ]);
    }

    private function donneesFormulaire(): array
    {
        $bureau = trim((string) entree_post('bureau', ''));

        return [
            'nom'          => trim((string) entree_post('nom', '')),
            'prenom'       => trim((string) entree_post('prenom', '')),
            'sexe'         => entree_post('sexe', '') === 'feminin' ? 'feminin' : 'masculin',
            'login'        => trim((string) entree_post('email', '')),
            'poste'        => (string) entree_post('poste', ''),
            'sigle_AG'     => trim((string) entree_post('sigleAG', '')),
            'IM'           => trim((string) entree_post('matricule', '')),
            'num_bureau'   => $bureau === '' ? '0' : $bureau,
            'status'       => (string) entree_post('status', 'Actifs'),
            'motDePasse'   => (string) entree_post('motDePasse', ''),
            'confirmation' => (string) entree_post('confirmation', ''),
        ];
    }

    /**
     * @return string[] messages d'erreur (vide si les données sont valides)
     */
    private function valider(array $donnees, bool $creation, ?int $id = null): array
    {
        $erreurs = [];

        if ($donnees['nom'] === '' || $donnees['prenom'] === '') {
            $erreurs[] = 'Le nom et le prénom sont obligatoires.';
        }
        if (!filter_var($donnees['login'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'Adresse e-mail invalide.';
        }
        if ($donnees['poste'] === '' || mb_strlen($donnees['poste']) > 100) {
            $erreurs[] = 'Le poste est obligatoire (100 caractères maximum).';
        }
        if (!ctype_digit($donnees['IM'])) {
            $erreurs[] = 'Le matricule doit être numérique.';
        }
        if (!ctype_digit($donnees['num_bureau'])) {
            $erreurs[] = 'Le numéro de bureau doit être numérique.';
        }
        if (!in_array($donnees['status'], ['Actifs', 'Retraite'], true)) {
            $erreurs[] = 'Le statut sélectionné n\'est pas valide.';
        }
        if ($donnees['login'] !== ''
            && (new AdministrationModel())->loginExiste($donnees['login'], $id)) {
            $erreurs[] = 'Cette adresse e-mail possède déjà un compte ou une demande en attente.';
        }

        if ($creation) {
            if (strlen($donnees['motDePasse']) < 4) {
                $erreurs[] = 'Le mot de passe doit contenir au moins 4 caractères.';
            }
            if ($donnees['motDePasse'] !== $donnees['confirmation']) {
                $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
            }
        }

        return $erreurs;
    }
}
