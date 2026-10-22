<?php
/**
 * Gestion de l'authentification et de la session utilisateur.
 *
 * Compatibilité PGCC v1 : les comptes proviennent de la même table
 * « pgcc_client » et les mots de passe sont vérifiés en MD5, comme dans
 * l'application historique. Les deux applications partagent ainsi les
 * mêmes comptes (synchronisation totale des données).
 */

declare(strict_types=1);

final class Auth
{
    /**
     * Démarre la session en toute sécurité (une seule fois).
     */
    public static function demarrer(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('PGCC2SESS');
        // Cookie de session durci : inaccessible en JavaScript, envoyé
        // uniquement dans le même site, et sécurisé si le site est en HTTPS.
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => base_chemin() . '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();

        // Déconnexion automatique après inactivité prolongée.
        $maintenant = time();
        if (isset($_SESSION['derniere_activite']) && ($maintenant - $_SESSION['derniere_activite']) > SESSION_DUREE) {
            self::deconnecter();
            session_start();
        }
        $_SESSION['derniere_activite'] = $maintenant;
    }

    /**
     * Tente une connexion avec un couple e-mail / mot de passe.
     * Retourne true si l'utilisateur existe, est validé et actif.
     */
    public static function connecter(string $email, string $motDePasse): bool
    {
        // Un membre mis à la retraite ne peut plus se connecter (les autres
        // statuts — y compris vides pour les comptes historiques — restent
        // autorisés, comme dans la v1).
        $sql = 'SELECT id_client, nom, prenom, login, poste, sigle_AG, mdp, valide, status
                FROM pgcc_client
                WHERE login = :login
                  AND valide = 1
                  AND (status IS NULL OR status <> :retraite)
                LIMIT 1';
        $req = Database::connexion()->prepare($sql);
        $req->execute(['login' => $email, 'retraite' => 'Retraite']);
        $utilisateur = $req->fetch();

        if ($utilisateur === false || !Password::verifierEtMigrer(
            Database::connexion(),
            (int) $utilisateur['id_client'],
            (string) $utilisateur['mdp'],
            $motDePasse
        )) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['utilisateur'] = [
            'id'      => (int) $utilisateur['id_client'],
            'nom'     => $utilisateur['nom'],
            'prenom'  => $utilisateur['prenom'],
            'login'   => $utilisateur['login'],
            'poste'   => $utilisateur['poste'],
            'sigleAG' => $utilisateur['sigle_AG'],
        ];
        return true;
    }

    /**
     * Crée une demande d'inscription (compte en attente de validation).
     */
    public static function inscrire(array $donnees): bool
    {
        $sql = 'INSERT INTO pgcc_new_client
                    (nom, prenom, sexe, login, mdp, poste, sigle_AG, IM, num_bureau, Admin_confirm)
                VALUES
                    (:nom, :prenom, :sexe, :login, :mdp, :poste, :sigle, :im, :bureau, 0)';
        $req = Database::connexion()->prepare($sql);
        return $req->execute([
            'nom'    => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'sexe'   => $donnees['sexe'],
            'login'  => $donnees['email'],
            'mdp'    => Password::creer($donnees['motDePasse']),
            'poste'  => $donnees['poste'],
            'sigle'  => $donnees['sigleAG'],
            'im'     => (int) $donnees['matricule'],
            'bureau' => (int) $donnees['bureau'],
        ]);
    }

    /**
     * Utilisateur connecté (ou null).
     */
    public static function utilisateur(): ?array
    {
        return $_SESSION['utilisateur'] ?? null;
    }

    /**
     * Vérifie qu'un utilisateur est connecté ; sinon redirige vers la page
     * de connexion.
     */
    public static function exiger(): array
    {
        $u = self::utilisateur();
        if ($u === null) {
            header('Location: ' . url('/connexion'));
            exit;
        }
        return $u;
    }

    /**
     * Nom complet de l'utilisateur connecté.
     */
    public static function nomComplet(): string
    {
        $u = self::utilisateur();
        return $u ? trim($u['nom'] . ' ' . $u['prenom']) : '';
    }

    /**
     * Déconnecte l'utilisateur courant.
     */
    public static function deconnecter(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies')) {
                $cookie = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires'  => time() - 42000,
                    'path'     => $cookie['path'],
                    'domain'   => $cookie['domain'],
                    'secure'   => $cookie['secure'],
                    'httponly' => $cookie['httponly'],
                    'samesite' => $cookie['samesite'] ?? 'Lax',
                ]);
            }
            session_destroy();
        }
    }

    /**
     * Jeton CSRF de la session (généré à la demande).
     */
    public static function jeton(): string
    {
        if (empty($_SESSION['jeton'])) {
            $_SESSION['jeton'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['jeton'];
    }

    /**
     * Vérifie le jeton CSRF fourni par un formulaire.
     */
    public static function verifierJeton(?string $jeton): bool
    {
        return is_string($jeton) && hash_equals($_SESSION['jeton'] ?? '', $jeton);
    }
}
