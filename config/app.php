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

// --- Application ------------------------------------------------------------
const APP_NOM      = 'PGCC v2';
const APP_ORGANE   = 'Parquet Général — Justice Madagascar';
define('APP_URL', getenv('PGCC_APP_URL') !== false ? rtrim(getenv('PGCC_APP_URL'), '/') : 'http://localhost/pgcc2');
define('APP_CHEMIN', dirname(__DIR__)); // racine du projet

// Délai de session (secondes) avant déconnexion automatique.
const SESSION_DUREE = 3600;

// Nombre de lignes par page dans les listes.
const LIGNES_PAR_PAGE = 25;

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
