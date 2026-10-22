<?php
/**
 * Comptage des empreintes de mots de passe par algorithme, en lecture seule.
 *
 * La couche de compatibilité MD5 de app/core/Password.php ne pourra être
 * supprimée que lorsque plus aucun compte ne porte d'empreinte MD5 — et
 * après le retrait de PGCC v1, qui écrit encore du MD5 dans la table
 * partagée sans jamais la migrer. Cet outil sert à suivre la migration.
 *
 * Usage : php tools/maintenance/compter_mdp_legacy.php
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

$p = new PDO('mysql:host=' . DB_HOTE . ';dbname=' . DB_NOM . ';charset=utf8mb4', DB_USER, DB_MDP, [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function compterEmpreintes(array $empreintes): array
{
    $stats = ['bcrypt' => 0, 'md5' => 0, 'autre' => 0, 'total' => count($empreintes)];
    foreach ($empreintes as $empreinte) {
        $empreinte = (string) $empreinte;
        if ($empreinte === '') {
            $stats['autre']++;
        } elseif (str_starts_with($empreinte, '$')) {
            $stats['bcrypt']++; // bcrypt ($2y$...) ou argon2 ($argon2...)
        } elseif (preg_match('/^[a-f0-9]{32}$/i', $empreinte) === 1) {
            $stats['md5']++;
        } else {
            $stats['autre']++;
        }
    }
    return $stats;
}

$md5Total = 0;
foreach (['pgcc_client', 'pgcc_new_client'] as $table) {
    $empreintes = $p->query("SELECT mdp FROM $table")->fetchAll(PDO::FETCH_COLUMN);
    $stats = compterEmpreintes($empreintes);
    $md5Total += $stats['md5'];
    printf("%-16s : %2d compte(s) — %2d bcrypt/argon2, %2d MD5, %2d autre\n",
        $table, $stats['total'], $stats['bcrypt'], $stats['md5'], $stats['autre']);
}

echo PHP_EOL;
if ($md5Total === 0) {
    echo "Aucune empreinte MD5 restante : la branche de compatibilité de\n";
    echo "Password::verifier() peut être retirée dès que PGCC v1 est hors service.\n";
} else {
    echo "$md5Total empreinte(s) MD5 restante(s) : conserver la compatibilité.\n";
    echo "Elles migrent vers bcrypt à chaque connexion réussie via PGCC v2.\n";
}
