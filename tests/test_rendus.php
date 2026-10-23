<?php
/**
 * Test de rendu des vues modifiées (T82–T84) avec des données factices,
 * sans base de données : chaque vue est rendue via View::afficher() dans
 * le gabarit complet. Toute variable manquante, fonction inexistante ou
 * erreur fatale est interceptée.
 *
 * Usage : php tests/test_rendus.php
 */

declare(strict_types=1);

$racine = dirname(__DIR__);
$_SERVER['REQUEST_METHOD'] = 'GET';

require $racine . '/config.php';
require $racine . '/app/core/helpers.php';
session_start();
$_SESSION['utilisateur'] = ['id' => 1, 'nom' => 'RAKOTO', 'prenom' => 'Jean', 'login' => 'j.rakoto@pgcc.com', 'poste' => 'PG', 'sigleAG' => 'JRK'];

foreach (['app/core/Database.php', 'app/core/Auth.php', 'app/core/View.php', 'app/core/Graphiques.php'] as $f) {
    require $racine . '/' . $f;
}
foreach (glob($racine . '/app/models/*.php') as $f)     require $f;
foreach (glob($racine . '/app/controllers/*.php') as $f) require $f;

$echecs = 0;
function rendre(string $vue, array $donnees, string $libelle): void
{
    global $echecs;
    ob_start();
    try {
        View::afficher($vue, $donnees);
        $html = (string) ob_get_clean();
        $ok = strlen($html) > 500 && !str_contains($html, 'Fatal error') && !str_contains($html, 'Warning:');
        echo ($ok ? '  OK  ' : 'ECHEC ') . $libelle . ' (' . strlen($html) . ' octets)' . PHP_EOL;
        if (!$ok) {
            $echecs++;
            echo mb_substr($html, 0, 600) . PHP_EOL;
        }
    } catch (Throwable $ex) {
        ob_end_clean();
        echo 'ECHEC ' . $libelle . ' — ' . $ex->getMessage() . ' @ ' . basename($ex->getFile()) . ':' . $ex->getLine() . PHP_EOL;
        $echecs++;
    }
}

$ligneDossier = [
    'id_reg_pourvoi' => 42, 'num_ordre' => 7, 'annee' => 2025, 'num_dossier_PGCS' => '2025-0123',
    'cassationnaire' => 'RAKOTO v/ État', 'detention' => '1', 'archive' => '0', 'observation' => '—',
    'date_creation' => '2025-01-10 08:30:00', 'cree_par' => 'j.rakoto@pgcc.com',
    'date_modification' => null, 'modifie_par' => null,
];

echo '--- Vues registres ---' . PHP_EOL;
$def = registre_definition('penaux');
$colonnes = registre_colonnes($def, 'liste');
rendre('registres/liste.php', [
    'titrePage' => 't', 'definition' => $def, 'code' => 'penaux',
    'resultat' => ['lignes' => [$ligneDossier], 'total' => 1, 'pages' => 1, 'page' => 1],
    'recherche' => '', 'annee' => null, 'annees' => [2025],
], 'registres/liste (avec confirmation archivage corrigée)');
rendre('registres/fiche.php', [
    'titrePage' => 't', 'definition' => $def, 'code' => 'penaux',
    'dossier' => $ligneDossier, 'memoires' => [],
], 'registres/fiche (lien préremplissage mémoire)');

echo '--- Formulaires de registres ---' . PHP_EOL;
foreach (['arrivees', 'penaux', 'civils', 'dessaisissements'] as $codeRegistre) {
    $definitionFormulaire = registre_definition($codeRegistre);
    rendre('registres/formulaire.php', [
        'titrePage' => 'Formulaire de test',
        'definition' => $definitionFormulaire,
        'code' => $codeRegistre,
        'dossier' => null,
        'erreurs' => [],
        'valeursFormulaire' => [],
    ], "registres/formulaire $codeRegistre");
}

echo '--- Vues suivis (T82/T84) ---' . PHP_EOL;
$ligneSuivi = ['id_suivi_arrivee' => 9, 'num_ordre' => 3, 'annee' => 2025, 'num_dossier_PGCS' => '2025-0789',
    'detention' => '0', 'archiver' => '0', 'sigle_AG' => 'JRK', 'localisation' => '—', 'observation' => '—',
    'observation_suivi' => '—', 'date_arrivee' => '2025-02-01', 'date_detention' => null,
    'date_transmission_au_PGCS' => '2025-02-05', 'date_reception' => '2025-02-08', 'date_transmission' => null];
foreach (['321', '107'] as $type) {
    rendre('suivis/liste.php', [
        'titrePage' => 't', 'type' => $type,
        'resultat' => ['lignes' => [$ligneSuivi], 'total' => 1, 'pages' => 1, 'page' => 1],
        'recherche' => '',
    ], "suivis/liste type $type");
}

echo '--- Vues mémoires (T82) ---' . PHP_EOL;
rendre('memoires/formulaire.php', [
    'titrePage' => 't', 'memoire' => null, 'erreurs' => [], 'prefillDossier' => '2025-0123',
], 'memoires/formulaire création (préremplissage)');
rendre('memoires/formulaire.php', [
    'titrePage' => 't', 'erreurs' => [],
    'memoire' => ['id_memoire' => 5, 'num_ordre' => 2, 'annee' => 2025, 'type_memoire' => 'ampliatif',
        'origine' => 'penal', 'num_dossier_PGCS' => '2025-0123', 'parties' => 'X v/ Y', 'avocat' => 'Me Z',
        'date_notification' => null, 'date_depot' => '2025-03-01', 'date_transmission_secretaire' => null,
        'rapporteur' => 'AG JRK', 'observation' => '', 'etat' => 'depose',
        'date_creation' => '2025-03-02 09:00:00', 'cree_par' => 'j.rakoto@pgcc.com',
        'date_modification' => null, 'modifie_par' => null],
    'prefillDossier' => '',
], 'memoires/formulaire modification (bandeau traçabilité)');

echo '--- Vues membres (T82/T83) ---' . PHP_EOL;
$membre = ['id_client' => 3, 'nom' => 'RABE', 'prenom' => 'Ada', 'login' => 'a.rabe@pgcc.com', 'poste' => 'AG',
    'sigle_AG' => 'ARB', 'IM' => 123, 'num_bureau' => 4, 'valide' => '0', 'status' => null];
foreach (['attente', 'actifs', 'retraites'] as $filtre) {
    rendre('membres/liste.php', [
        'titrePage' => 't', 'filtre' => $filtre, 'membres' => [$membre],
        'utilisateur' => $_SESSION['utilisateur'],
    ], "membres/liste onglet $filtre (admin PG)");
}
rendre('membres/liste.php', [
    'titrePage' => 't', 'filtre' => 'attente', 'membres' => [$membre],
    'utilisateur' => ['id' => 2, 'nom' => 'X', 'prenom' => 'Y', 'login' => 'y@pgcc.com', 'poste' => 'AG', 'sigleAG' => ''],
], 'membres/liste onglet attente (non-admin : pas de bouton)');

echo '--- Autres vues ---' . PHP_EOL;
rendre('dashboard/accueil.php', [
    'titrePage' => 't',
    'compteurs' => ['arrivees' => 10, 'penaux' => 20, 'civils' => 5, 'dessaisissements' => 2,
        'memoires' => 8, 'detentions' => 4, 'membresActifs' => 12, 'enAttente' => 1],
    'parAnnee' => ['annees' => [2024, 2025], 'series' => ['Pénaux' => [3, 5], 'Civils' => [1, 2], 'Arrivées' => [2, 4]]],
], 'dashboard (compteur détentions affiché)');
rendre('distributions/nouvelle.php', [
    'titrePage' => 't', 'dossiers' => [$ligneDossier], 'recherche' => '', 'erreurs' => [], 'type' => 'pen',
], 'distributions/nouvelle (titre non dupliqué)');
rendre('auth/connexion.php', ['titrePage' => 't', 'erreur' => null], 'auth/connexion (jeton CSRF)');
rendre('auth/inscription.php', ['titrePage' => 't', 'postes' => ['PG' => 'PG'], 'erreurs' => []], 'auth/inscription (jeton CSRF)');
rendre('auth/mot-de-passe-oublie.php', ['titrePage' => 't', 'etape' => 'reponse', 'erreurs' => [], 'question' => 'Q ?', 'email' => 'a@b.c'], 'auth/mot-de-passe-oublie (jeton CSRF)');
rendre('profil/fiche.php', ['titrePage' => 't', 'membre' => $membre + ['mdp' => 'x']], 'profil/fiche');
rendre('aide/index.php', ['titrePage' => 't'], 'aide/index (page d\'aide intégrée)');

echo PHP_EOL . ($echecs === 0 ? 'TOUS LES RENDUS PASSENT' : "$echecs RENDU(S) EN ÉCHEC") . PHP_EOL;
exit($echecs === 0 ? 0 : 1);
