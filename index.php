<?php
/**
 * Point d’entrée HTTP de l’application.
 *
 * Le routeur lit le paramètre « r » et délègue chaque requête à son
 * contrôleur. Les règles métier et les requêtes SQL restent dans leurs
 * couches respectives.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require APP_CHEMIN . '/app/core/helpers.php';
require APP_CHEMIN . '/app/models/Registres.php';

/**
 * Charge une classe depuis le dossier correspondant à sa couche.
 * Les fonctions partagées et les définitions de registres sont chargées
 * explicitement, car elles ne peuvent pas être résolues par nom de classe.
 */
spl_autoload_register(static function (string $classe): void {
    if (str_contains($classe, '\\')) {
        return;
    }

    foreach (['app/core', 'app/models', 'app/controllers'] as $dossier) {
        $fichier = APP_CHEMIN . '/' . $dossier . '/' . $classe . '.php';
        if (is_file($fichier)) {
            require_once $fichier;
            return;
        }
    }
});

// Sonde réservée à une instance de test explicitement configurée. Le script
// E2E compare cette réponse à sa base jetable avant toute écriture.
if (entree_get('e2e_probe') !== null) {
    if (getenv('PGCC_E2E_MODE') !== 'true' || preg_match('/^pgcc_e2e_[a-zA-Z0-9_]+$/', DB_NOM) !== 1) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: text/plain; charset=UTF-8');
    echo DB_NOM;
    exit;
}

Auth::demarrer();

/** @var string[] $route Segments de la route demandée. */
$route = array_values(array_filter(explode('/', (string) entree_get('r', '')), static fn (string $segment): bool => $segment !== ''));
$page = $route[0] ?? '';

try {
    match ($page) {
        ''                    => (new AuthController())->accueil(),
        'connexion'           => (new AuthController())->connexion(),
        'deconnexion'         => (new AuthController())->deconnexion(),
        'inscription'          => (new AuthController())->inscription(),
        'tableau-de-bord'     => (new DashboardController())->index(),
        'registres'            => (new RegistreController())->dispatch($route),
        'memoires'             => (new MemoireController())->dispatch($route),
        'suivis'               => (new SuiviController())->dispatch($route),
        'suivis-dossiers'      => (new SuiviDossierController())->index(),
        'repartition'           => (new RepartitionController())->index(),
        'distributions'         => (new DistributionController())->index(),
        'membres'               => (new MembreController())->dispatch($route),
        'profil'                => (new ProfilController())->index(),
        'mot-de-passe-oublie'  => (new AuthController())->motDePasseOublie(),
        default                 => page_404('Page introuvable'),
    };
} catch (Throwable $e) {
    http_response_code(500);
    $message = DEBUG
        ? ($e->getMessage() . ' — ' . basename($e->getFile()) . ':' . $e->getLine())
        : 'Erreur interne';

    error_log('[PGCC2] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    View::afficher('erreurs/500.php', ['titrePage' => 'Erreur', 'exception' => $message]);
}
