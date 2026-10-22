<?php
/**
 * Connexion à la base de données via PDO (instance unique).
 *
 * Toutes les requêtes de l'application passent par cette classe :
 * cela garantit un seul point de configuration et l'usage exclusif
 * de requêtes préparées.
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    /**
     * Retourne la connexion PDO partagée (la crée au premier appel).
     */
    public static function connexion(): PDO
    {
        if (self::$instance === null) {
            // utf8mb4 : aligné sur la base convertie (utf8mb4_unicode_ci) et
            // compatible avec l'ancien encodage latin1 (conversion MySQL à la volée).
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOTE, DB_NOM);
            self::$instance = new PDO($dsn, DB_USER, DB_MDP, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }

    /** Connexion dédiée aux exports volumineux, dont les résultats ne sont pas mis en mémoire. */
    public static function connexionPourExport(): PDO
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOTE, DB_NOM);
        return new PDO($dsn, DB_USER, DB_MDP, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        ]);
    }
}
