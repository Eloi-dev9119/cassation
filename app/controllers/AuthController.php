<?php
/**
 * Contrôleur d'authentification : connexion, déconnexion, inscription.
 */

declare(strict_types=1);

final class AuthController
{
    /**
     * Page d'accueil de l'application : redirige vers le tableau de bord
     * si l'utilisateur est connecté, sinon vers l'écran de connexion.
     */
    public function accueil(): void
    {
        rediriger(Auth::utilisateur() ? '/tableau-de-bord' : '/connexion');
    }

    /**
     * Écran et traitement de connexion.
     */
    public function connexion(): void
    {
        if (Auth::utilisateur() !== null) {
            rediriger('/tableau-de-bord');
        }

        $erreur = null;
        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                $erreur = 'Session expirée, veuillez réessayer.';
            } else {
                $email    = trim((string) entree_post('email', ''));
                $motDePasse = (string) entree_post('motDePasse', '');

                if ($email === '' || $motDePasse === '') {
                    $erreur = 'Veuillez renseigner votre e-mail et votre mot de passe.';
                } else {
                    // Limitation des tentatives : blocage temporaire après
                    // CONNEXION_TENTATIVES_MAX échecs pour cette adresse.
                    $secondes = self::blocageRestant($email);
                    if ($secondes > 0) {
                        $erreur = 'Trop de tentatives infructueuses pour cette adresse : réessayez dans '
                            . max(1, (int) ceil($secondes / 60) ) . ' minute(s).';
                    } elseif (Auth::connecter($email, $motDePasse)) {
                        self::effacerEchecs($email);
                        flash(
                            'succes',
                            'Bienvenue ' . Auth::nomComplet() . '. Votre session est maintenant ouverte.',
                            'Connexion réussie'
                        );
                        rediriger('/tableau-de-bord');
                    } else {
                        self::enregistrerEchec($email);
                        $erreur = 'Identifiants incorrects, compte non validé ou membre retiré.';
                    }
                }
            }
        }

        View::afficherBrut('auth/connexion.php', [
            'titrePage' => 'Connexion — ' . APP_NOM,
            'erreur'    => $erreur,
        ]);
    }

    /**
     * Mot de passe oublié : comme dans la v1 (pwdAG), l'utilisateur
     * répond à sa question secrète pour poser un nouveau mot de passe.
     * Le nouveau mot de passe est valable dans les deux applications.
     */
    public function motDePasseOublie(): void
    {
        $etape = 'email';
        $erreurs = [];
        $question = null;
        $email = trim((string) entree_post('email', entree_get('email', '')));

        if (requete_post() && !Auth::verifierJeton(entree_post('jeton'))) {
            $erreurs[] = 'Session expirée, veuillez réessayer.';
        } elseif (requete_post() && entree_post('etape', '') === 'reponse') {
            $etape = 'reponse';
            $reponse = trim((string) entree_post('reponse', ''));
            $nouveau = (string) entree_post('motDePasse', '');
            $confirm = (string) entree_post('confirmation', '');

            $req = Database::connexion()->prepare(
                'SELECT id_client, question, reponse FROM pgcc_client WHERE login = :login LIMIT 1'
            );
            $req->execute(['login' => $email]);
            $compte = $req->fetch();

            if ($compte === false || empty($compte['question'])) {
                $erreurs[] = 'Aucune question secrète n\'est définie pour ce compte. Contactez l\'administration.';
                $etape = 'email';
            } elseif (strcasecmp($reponse, (string) $compte['reponse']) !== 0) {
                $erreurs[] = 'La réponse à la question secrète est incorrecte.';
                $question = $compte['question'];
            } elseif (strlen($nouveau) < 4) {
                $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 4 caractères.';
                $question = $compte['question'];
            } elseif ($nouveau !== $confirm) {
                $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
                $question = $compte['question'];
            } else {
                (new ClientModel())->changerMotDePasse((int) $compte['id_client'], $nouveau);
                flash('succes', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');
                rediriger('/connexion');
            }
        } elseif ($email !== '') {
            // Demande initiale : vérifier l'existence du compte et de sa question.
            $req = Database::connexion()->prepare(
                'SELECT question FROM pgcc_client WHERE login = :login LIMIT 1'
            );
            $req->execute(['login' => $email]);
            $question = $req->fetchColumn();

            if ($question === false || $question === null || $question === '') {
                $erreurs[] = 'Compte inconnu ou sans question secrète définie. Contactez l\'administration.';
                $etape = 'email';
            } else {
                $etape = 'reponse';
            }
        }

        View::afficherBrut('auth/mot-de-passe-oublie.php', [
            'titrePage' => 'Mot de passe oublié — ' . APP_NOM,
            'etape'     => $etape,
            'erreurs'   => $erreurs,
            'question'  => $question,
            'email'     => $email,
        ]);
    }

    /**
     * Déconnexion de l'utilisateur.
     */
    public function deconnexion(): void
    {
        if (!requete_post()) {
            rediriger(Auth::utilisateur() !== null ? '/tableau-de-bord' : '/connexion');
        }

        if (!Auth::verifierJeton(entree_post('jeton'))) {
            flash('erreur', 'Votre session a expiré, veuillez réessayer.');
            rediriger(Auth::utilisateur() !== null ? '/tableau-de-bord' : '/connexion');
        }

        Auth::deconnecter();
        Auth::demarrer();
        session_regenerate_id(true);
        flash(
            'info',
            'Votre session a été fermée en toute sécurité. À bientôt.',
            'Déconnexion'
        );
        rediriger('/connexion');
    }

    /**
     * Écran et traitement de la demande d'inscription.
     * Le compte est enregistré dans pgcc_new_client et devra être validé
     * par un administrateur (module Membres) — comme dans la v1.
     */
    public function inscription(): void
    {
        $postes = POSTES_UTILISATEUR;

        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                $erreurs = ['Session expirée, veuillez réessayer.'];
            } else {
                $donnees = [
                    'nom'         => trim((string) entree_post('nom', '')),
                    'prenom'      => trim((string) entree_post('prenom', '')),
                    'sexe'        => entree_post('sexe', '') === 'feminin' ? 'feminin' : 'masculin',
                    'email'       => trim((string) entree_post('email', '')),
                    'motDePasse'  => (string) entree_post('motDePasse', ''),
                    'confirmation'=> (string) entree_post('confirmation', ''),
                    'poste'       => (string) entree_post('poste', ''),
                    'sigleAG'     => trim((string) entree_post('sigleAG', '')),
                    'matricule'   => (string) entree_post('matricule', ''),
                    'bureau'      => (string) entree_post('bureau', '0'),
                ];

                $erreurs = $this->validerInscription($donnees);
                if ($erreurs === []) {
                    try {
                        Auth::inscrire($donnees);
                    } catch (Throwable $e) {
                        // Filet de sécurité : une contrainte UNIQUE non prévue
                        // (course entre deux demandes simultanées) ne doit pas
                        // produire une erreur 500.
                        error_log('[PGCC2] Inscription : ' . $e->getMessage());
                        $erreurs[] = 'L\'enregistrement a échoué : cette adresse e-mail ou ce matricule '
                            . 'est déjà utilisé. Vérifiez vos informations puis réessayez.';
                    }
                }
                if ($erreurs === []) {
                    oublier_ancien();
                    flash(
                        'succes',
                        'Votre demande d\'inscription a été enregistrée. Elle sera examinée par l\'administration.',
                        'Demande envoyée'
                    );
                    rediriger('/connexion');
                }
                memoriser_ancien(array_diff_key($donnees, ['motDePasse' => '', 'confirmation' => '']));
            }
            View::afficherBrut('auth/inscription.php', [
                'titrePage' => 'Inscription — ' . APP_NOM,
                'postes'    => $postes,
                'erreurs'   => $erreurs,
            ]);
            return;
        }

        oublier_ancien();
        View::afficherBrut('auth/inscription.php', [
            'titrePage' => 'Inscription — ' . APP_NOM,
            'postes'    => $postes,
            'erreurs'   => [],
        ]);
    }

    /**
     * Valide les données d'inscription.
     * @return string[] messages d'erreurs (vide si tout est correct)
     */
    private function validerInscription(array $d): array
    {
        $erreurs = [];
        if ($d['nom'] === '' || $d['prenom'] === '')          $erreurs[] = 'Le nom et le prénom sont obligatoires.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL))  $erreurs[] = 'Adresse e-mail invalide.';
        if (strlen($d['motDePasse']) < 4)                     $erreurs[] = 'Le mot de passe doit contenir au moins 4 caractères.';
        if ($d['motDePasse'] !== $d['confirmation'])          $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        if (!ctype_digit($d['matricule']))                    $erreurs[] = 'Le matricule doit être numérique.';

        $db = Database::connexion();

        // Adresse e-mail déjà utilisée : compte actif ou demande existante.
        // On interroge TOUTE la table pgcc_new_client (et non les seules
        // demandes Admin_confirm = 0), car la contrainte UNIQUE porte sur
        // l'ensemble des lignes — sinon l'INSERT échouerait en erreur SQL.
        $req = $db->prepare(
            'SELECT
                (SELECT COUNT(*) FROM pgcc_client WHERE login = :login_actif)
                +
                (SELECT COUNT(*) FROM pgcc_new_client WHERE login = :login_attente)'
        );
        $req->execute([
            'login_actif'   => $d['email'],
            'login_attente' => $d['email'],
        ]);
        if ((int) $req->fetchColumn() > 0) {
            $erreurs[] = 'Cette adresse e-mail possède déjà un compte ou une demande enregistrée.';
        }

        // Matricule (IM) déjà utilisé : la contrainte UNIQUE des deux tables
        // provoquerait une erreur SQL ; on la prévient par une validation.
        if (ctype_digit($d['matricule'])) {
            $req = $db->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM pgcc_client WHERE IM = :im_actif)
                    +
                    (SELECT COUNT(*) FROM pgcc_new_client WHERE IM = :im_attente)'
            );
            $req->execute([
                'im_actif'   => (int) $d['matricule'],
                'im_attente' => (int) $d['matricule'],
            ]);
            if ((int) $req->fetchColumn() > 0) {
                $erreurs[] = 'Ce matricule (IM) est déjà utilisé par un compte ou une demande.';
            }
        }

        return $erreurs;
    }

    // ------------------------------------------------------------------ //
    //  Limitation des tentatives de connexion
    // ------------------------------------------------------------------ //

    /**
     * Secondes de blocage restantes pour une adresse (0 = non bloquée).
     * Le compteur est en session : adapté à l'intranet ; il se réinitialise
     * à la déconnexion volontaire, mais le blocage par adresse échoue
     * systématiquement avant d'atteindre le seuil via une seule session.
     */
    private static function blocageRestant(string $email): int
    {
        $entree = $_SESSION['echecs_connexion'][mb_strtolower($email)] ?? null;
        if (!is_array($entree) || (int) $entree['nb'] < CONNEXION_TENTATIVES_MAX) {
            return 0;
        }
        $reste = CONNEXION_BLOCAGE_MINUTES * 60 - (time() - (int) $entree['derniere']);
        return max(0, $reste);
    }

    /** Enregistre un échec de connexion pour l'adresse donnée. */
    private static function enregistrerEchec(string $email): void
    {
        $cle = mb_strtolower($email);
        $entree = $_SESSION['echecs_connexion'][$cle] ?? ['nb' => 0, 'derniere' => 0];
        // Au-delà du seuil déjà atteint, on ne rallonge pas le blocage :
        // seule une nouvelle tentative effective (post-blocage) compte.
        if ((int) $entree['nb'] >= CONNEXION_TENTATIVES_MAX
            && (time() - (int) $entree['derniere']) < CONNEXION_BLOCAGE_MINUTES * 60) {
            return;
        }
        $entree['nb'] = ((int) $entree['nb'] >= CONNEXION_TENTATIVES_MAX) ? 1 : (int) $entree['nb'] + 1;
        $entree['derniere'] = time();
        $_SESSION['echecs_connexion'][$cle] = $entree;
    }

    /** Efface les échecs mémorisés (connexion réussie). */
    private static function effacerEchecs(string $email): void
    {
        unset($_SESSION['echecs_connexion'][mb_strtolower($email)]);
    }
}
