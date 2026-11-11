<?php
/**
 * Configuration générale de l'application PGCC v2.
 *
 * PGCC v2 est la refonte lisible du progiciel du Parquet Général près la
 * Cour Suprême. Elle partage la même base de données que la version
 * historique (application « pgcc ») : depuis le 2026-10-20, la base
 * « pgcc_utf8mb4 » (InnoDB, collation utf8mb4_unicode_ci). Les deux
 * applications sont donc synchronisées sur les mêmes données.
 */

declare(strict_types=1);

// --- Configuration e-mail locale (facultative) ------------------------------
// Copier config/mail.local.php.example vers config/mail.local.php et y
// renseigner les identifiants SMTP. Les variables d'environnement restent
// prioritaires. Ce fichier est protégé par .htaccess et ne doit pas être publié.
$mailLocal = __DIR__ . '/mail.local.php';
if (is_file($mailLocal)) {
    $mailLocalConfig = require $mailLocal;
    if (is_array($mailLocalConfig)) {
        foreach ($mailLocalConfig as $cle => $valeur) {
            if (is_string($cle) && str_starts_with($cle, 'PGCC_MAIL_') && getenv($cle) === false) {
                putenv($cle . '=' . (string) $valeur);
                $_ENV[$cle] = (string) $valeur;
            }
        }
    }
}

// --- Base de données --------------------------------------------------------
// Bascule utf8mb4 effectuée le 2026-10-20 (README, tâche 3).
// Retour arrière : remettre DB_NOM = 'pgcc' (ET dbname=pgcc_utf8mb4 → pgcc
// dans v1/cnx.php). L'ancienne base « pgcc » reste sur le serveur en
// secours — ne plus y écrire.
// Les valeurs locales restent adaptées à XAMPP. En hébergement, définir
// PGCC_DB_HOST, PGCC_DB_NAME, PGCC_DB_USER et PGCC_DB_PASSWORD dans le serveur.
define('DB_HOTE', getenv('PGCC_DB_HOST') !== false ? getenv('PGCC_DB_HOST') : 'localhost');
define('DB_NOM', getenv('PGCC_DB_NAME') !== false ? getenv('PGCC_DB_NAME') : 'pgcc_utf8mb4');
define('DB_USER', getenv('PGCC_DB_USER') !== false ? getenv('PGCC_DB_USER') : 'root');
define('DB_MDP', getenv('PGCC_DB_PASSWORD') !== false ? getenv('PGCC_DB_PASSWORD') : '');

define('MAIL_ACTIF', filter_var(getenv('PGCC_MAIL_ACTIF') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('MAIL_HOTE', getenv('PGCC_MAIL_HOST') ?: '');
define('MAIL_PORT', (int) (getenv('PGCC_MAIL_PORT') ?: 0));
define('MAIL_SECURITE', strtolower(getenv('PGCC_MAIL_SECURITE') ?: 'tls'));
define('MAIL_UTILISATEUR', getenv('PGCC_MAIL_USER') ?: '');
define('MAIL_MOT_DE_PASSE', getenv('PGCC_MAIL_PASSWORD') ?: '');
define('MAIL_EXPEDITEUR', getenv('PGCC_MAIL_FROM') ?: '');
define('MAIL_NOM_EXPEDITEUR', getenv('PGCC_MAIL_FROM_NAME') ?: 'PGCC - Parquet Général');
define('MAIL_VERIFIER_CERT', filter_var(getenv('PGCC_MAIL_VERIFY_CERT') ?: 'true', FILTER_VALIDATE_BOOLEAN));
define('MAIL_TIMEOUT', max(5, (int) (getenv('PGCC_MAIL_TIMEOUT') ?: 15)));

// --- Application ------------------------------------------------------------
const APP_NOM      = 'PGCC v2';
const APP_ORGANE   = 'Parquet Général — Justice Madagascar';
define('APP_URL', getenv('PGCC_APP_URL') !== false ? rtrim(getenv('PGCC_APP_URL'), '/') : 'http://localhost/pgcc2');
define('APP_CHEMIN', dirname(__DIR__)); // racine du projet

// Délai de session (secondes) avant déconnexion automatique.
const SESSION_DUREE = 3600;

// Sécurité de l'écran de connexion : blocage après N échecs successifs pour
// une même adresse, pendant M minutes (limitation des tentatives).
const CONNEXION_TENTATIVES_MAX = 5;
const CONNEXION_BLOCAGE_MINUTES = 15;

// Nombre de lignes par page dans les listes.
const LIGNES_PAR_PAGE = 25;

// Durée (secondes) pendant laquelle un utilisateur reste considéré
// « en ligne » après sa dernière requête HTTP (pastille de présence).
const PRESENCE_DUREE = 900;

// Postes habilités aux actions d'administration des membres (validation
// des inscriptions, retraite/réactivation, réinitialisation de mot de
// passe). Ajuster si les codes de postes de la base diffèrent.
const POSTES_ADMINISTRATION = ['PG', 'SG'];

// Postes proposés à l'inscription et à la création d'un compte par
// l'administration. Les clés sont les codes stockés dans pgcc_client.poste.
const POSTES_UTILISATEUR = [
    'PG'                        => 'Procureur Général',
    'AG'                        => 'Avocat Général',
    'SG'                        => 'Secrétaire Général',
    'secretaire_AG'             => 'Secrétaire AG',
    'Secretaire particulier PGCC' => 'Secrétaire particulier PGCC',
];

// --- Erreurs ----------------------------------------------------------------
// Les erreurs restent journalisées. Seul leur affichage HTTP dépend du mode debug.
define('DEBUG', filter_var(getenv('PGCC_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');

date_default_timezone_set('Indian/Antananarivo');
