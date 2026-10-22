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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::verifierJeton($_POST['jeton'] ?? null)) {
                $erreur = 'Session expirée, veuillez réessayer.';
            } else {
                $email    = trim((string) ($_POST['email'] ?? ''));
                $motDePasse = (string) ($_POST['motDePasse'] ?? '');

                if ($email === '' || $motDePasse === '') {
                    $erreur = 'Veuillez renseigner votre e-mail et votre mot de passe.';
                } elseif (Auth::connecter($email, $motDePasse)) {
                    flash('succes', 'Connexion réussie. Bienvenue ' . Auth::nomComplet() . '.');
                    rediriger('/tableau-de-bord');
                } else {
                    $erreur = 'Identifiants incorrects, compte non validé ou membre retiré.';
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
        $email = trim((string) ($_POST['email'] ?? ($_GET['email'] ?? '')));

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::verifierJeton($_POST['jeton'] ?? null)) {
            $erreurs[] = 'Session expirée, veuillez réessayer.';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['etape'] ?? '') === 'reponse') {
            $etape = 'reponse';
            $reponse = trim((string) ($_POST['reponse'] ?? ''));
            $nouveau = (string) ($_POST['motDePasse'] ?? '');
            $confirm = (string) ($_POST['confirmation'] ?? '');

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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            rediriger(Auth::utilisateur() !== null ? '/tableau-de-bord' : '/connexion');
        }

        if (!Auth::verifierJeton($_POST['jeton'] ?? null)) {
            flash('erreur', 'Votre session a expiré, veuillez réessayer.');
            rediriger(Auth::utilisateur() !== null ? '/tableau-de-bord' : '/connexion');
        }

        Auth::deconnecter();
        Auth::demarrer();
        session_regenerate_id(true);
        flash('info', 'Vous avez été déconnecté de votre session.');
        rediriger('/connexion');
    }

    /**
     * Écran et traitement de la demande d'inscription.
     * Le compte est enregistré dans pgcc_new_client et devra être validé
     * par un administrateur (module Membres) — comme dans la v1.
     */
    public function inscription(): void
    {
        $postes = ['PG' => 'Procureur Général', 'AG' => 'Avocat Général', 'SG' => 'Secrétaire Général',
                   'secretaire_AG' => 'Secrétaire AG', 'Secretaire particulier PGCC' => 'Secrétaire particulier PGCC'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::verifierJeton($_POST['jeton'] ?? null)) {
                $erreurs = ['Session expirée, veuillez réessayer.'];
            } else {
                $donnees = [
                    'nom'         => trim((string) ($_POST['nom'] ?? '')),
                    'prenom'      => trim((string) ($_POST['prenom'] ?? '')),
                    'sexe'        => ($_POST['sexe'] ?? '') === 'feminin' ? 'feminin' : 'masculin',
                    'email'       => trim((string) ($_POST['email'] ?? '')),
                    'motDePasse'  => (string) ($_POST['motDePasse'] ?? ''),
                    'confirmation'=> (string) ($_POST['confirmation'] ?? ''),
                    'poste'       => (string) ($_POST['poste'] ?? ''),
                    'sigleAG'     => trim((string) ($_POST['sigleAG'] ?? '')),
                    'matricule'   => (string) ($_POST['matricule'] ?? ''),
                    'bureau'      => (string) ($_POST['bureau'] ?? '0'),
                ];

                $erreurs = $this->validerInscription($donnees);
                if ($erreurs === []) {
                    Auth::inscrire($donnees);
                    oublier_ancien();
                    flash('succes', 'Votre demande d\'inscription a été enregistrée. Elle sera examinée par l\'administration.');
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

        $req = Database::connexion()->prepare(
            'SELECT
                (SELECT COUNT(*) FROM pgcc_client WHERE login = :login_actif)
                +
                (SELECT COUNT(*) FROM pgcc_new_client WHERE login = :login_en_attente AND Admin_confirm = 0)'
        );
        $req->execute([
            'login_actif' => $d['email'],
            'login_en_attente' => $d['email'],
        ]);
        if ((int) $req->fetchColumn() > 0) {
            $erreurs[] = 'Cette adresse e-mail possède déjà un compte ou une demande en attente.';
        }

        return $erreurs;
    }
}
