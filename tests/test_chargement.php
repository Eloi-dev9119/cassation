<?php
/**
 * Test de fumée sans base de données : charge la configuration, les
 * helpers et toutes les classes, puis vérifie l'existence des fonctions
 * et classes attendues après les livraisons T82–T85.
 *
 * Usage : php tests/test_chargement.php
 */

declare(strict_types=1);

$racine = dirname(__DIR__);
$_SERVER['REQUEST_METHOD'] = 'GET';

require $racine . '/config.php';
require $racine . '/app/core/helpers.php';

// Simule une session pour Auth::utilisateur() / jeton().
session_start();
$_SESSION['utilisateur'] = ['id' => 1, 'nom' => 'Test', 'prenom' => 'Dev', 'login' => 'test@pgcc.com', 'poste' => 'PG', 'sigleAG' => 'TST'];

foreach (['app/core/Database.php', 'app/core/Auth.php', 'app/core/View.php', 'app/core/Graphiques.php'] as $f) {
    require $racine . '/' . $f;
}
// Les modèles/contrôleurs sont chargés comme dans index.php.
foreach (glob($racine . '/app/models/*.php') as $f)     require $f;
foreach (glob($racine . '/app/controllers/*.php') as $f) require $f;

$echecs = 0;
function verifier(bool $condition, string $libelle): void
{
    global $echecs;
    echo ($condition ? '  OK  ' : 'ECHEC ') . $libelle . PHP_EOL;
    if (!$condition) { $echecs++; }
}

echo '--- Fonctions utilitaires ---' . PHP_EOL;
verifier(function_exists('e'), 'e() existe');
verifier(function_exists('url'), 'url() existe');
verifier(function_exists('page_404'), 'page_404() existe (T84)');
verifier(function_exists('est_administration'), 'est_administration() existe (T83)');

echo '--- Administration (T83) ---' . PHP_EOL;
verifier(est_administration(), 'poste PG = administration');
verifier(!est_administration(['poste' => 'AG']), 'poste AG ≠ administration');
verifier(est_administration(['poste' => 'pg ']), 'poste "pg " (casse/espace) accepté');

echo '--- Session (T83) ---' . PHP_EOL;
verifier(Auth::utilisateur() !== null, 'Auth::utilisateur() lit la session');
verifier(strlen(Auth::jeton()) === 64, 'Auth::jeton() génère 64 hex');

echo '--- Classes métier ---' . PHP_EOL;
foreach (['Auth','View','Database','Graphiques','RegistreModel','MemoireModel','SuiviModel',
          'SuiviDossierModel','RepartitionModel','DistributionModel','ClientModel','StatsModel',
          'AuthController','DashboardController','RegistreController','MemoireController','SuiviController',
          'SuiviDossierController','RepartitionController','DistributionController','MembreController','ProfilController'] as $classe) {
    verifier(class_exists($classe), "classe $classe");
}

echo '--- Constantes de configuration ---' . PHP_EOL;
verifier(defined('POSTES_ADMINISTRATION'), 'POSTES_ADMINISTRATION définie (T83)');
verifier(in_array('PG', POSTES_ADMINISTRATION, true), 'PG habilité par défaut');

echo PHP_EOL . ($echecs === 0 ? 'TOUS LES TESTS PASSENT' : "$echecs ÉCHEC(S)") . PHP_EOL;
exit($echecs === 0 ? 0 : 1);
