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
rendre('registres/liste.php', [
    'titrePage' => 't', 'definition' => $def, 'code' => 'penaux',
    'resultat' => ['lignes' => [$ligneDossier], 'total' => 1, 'pages' => 1, 'page' => 1],
    'recherche' => '', 'annee' => null, 'annees' => [2025],
    'afficherMemoires' => true, 'memoiresParDossier' => [42 => 3],
], 'registres/liste (indicateur Mémoires : dossier 42 = 3)');

// Vérification ciblée du badge « Mémoires » : colonne présente, compteur
// affiché quand le dossier a des mémoires, tiret sinon.
ob_start();
View::afficher('registres/liste.php', [
    'titrePage' => 't', 'definition' => $def, 'code' => 'penaux',
    'resultat' => [
        'lignes' => [$ligneDossier, array_merge($ligneDossier, ['id_reg_pourvoi' => 99, 'num_dossier_PGCS' => '2025-0999'])],
        'total' => 2, 'pages' => 1, 'page' => 1,
    ],
    'recherche' => '', 'annee' => null, 'annees' => [2025],
    'afficherMemoires' => true, 'memoiresParDossier' => [42 => 2],
]);
$htmlIndicateur = (string) ob_get_clean();
$badgeOk = str_contains($htmlIndicateur, '>Mémoires</th>')
    && str_contains($htmlIndicateur, 'badge badge-vert')
    && str_contains($htmlIndicateur, '2 mémoire(s) rattaché(s)')
    && str_contains($htmlIndicateur, '>—</span>');
echo ($badgeOk ? '  OK  ' : 'ECHEC ') . 'indicateur Mémoires : en-tête + badge compteur + « — »' . PHP_EOL;
if (!$badgeOk) { $echecs++; }

// Registre du Siège : parité v1 — la liste reprend l'intégralité des
// colonnes détaillées du modèle v1 (siegePenaux.tpl), en plus de l'année et
// de l'indicateur d'archivage propres à v2.
$defSiege = registre_definition('siege');
$ligneSiege = [
    'id_civil' => 5, 'num_dossier' => '388/18', 'annee' => 2018,
    'nom_demandeur' => 'RAKOTO', 'avocat_demandeur' => 'Me A',
    'nom_defendeur' => 'État', 'avocat_defendeur' => 'Me B',
    'numero_arret_cours_appel' => '12/2017', 'rapporteur' => 'M. RANDRIAN',
    'date_rapporteur' => '2018-03-01', 'date_notification_pourvoi' => '2018-03-05',
    'date_depot_memoire_ampliatif' => '2018-04-01', 'date_notification_memoire_ampliatif' => '2018-04-10',
    'date_depot_memoire_defense' => '2018-04-20', 'date_depot_rapport' => '2018-05-01',
    'date_transmission' => '2018-05-15', 'date_depot_conclusion' => '2018-06-01',
    'numero_arret_cs' => '45/2018', 'decision' => 'rejet', 'archive' => '0',
    'date_creation' => '2018-01-01 08:00:00', 'cree_par' => 'system',
    'date_modification' => null, 'modifie_par' => null,
];
ob_start();
View::afficher('registres/liste.php', [
    'titrePage' => 't', 'definition' => $defSiege, 'code' => 'siege',
    'resultat' => ['lignes' => [$ligneSiege], 'total' => 1, 'pages' => 1, 'page' => 1],
    'recherche' => '', 'annee' => null, 'annees' => [2018],
]);
$htmlSiege = (string) ob_get_clean();
$libellesV1Siege = ['Nom et domicile du demandeur', 'Avocat du demandeur',
    'Nom et domicile du défendeur', 'Avocat du défendeur', 'Nom conseiller rapporteur',
    'Date de dépôt au rapporteur', 'Date de notification du mémoire de pourvoi',
    'Date de dépôt du mémoire ampliatif', 'Date de notification du mémoire ampliatif',
    'Date de dépôt du mémoire en défense', 'Date de dépôt du rapport',
    'Date de transmission au ministère public',
    'Date de dépôt de la conclusion au ministère public', 'Motif de décision'];
$manquantsSiege = array_filter($libellesV1Siege, static fn (string $l): bool => !str_contains($htmlSiege, $l));
$siegeOk = strlen($htmlSiege) > 500 && $manquantsSiege === [];
echo ($siegeOk ? '  OK  ' : 'ECHEC ') . 'registres/liste Siège (parité v1 : ' . count($libellesV1Siege) . ' colonnes détaillées)' . PHP_EOL;
if (!$siegeOk) { $echecs++; }

rendre('registres/fiche.php', [
    'titrePage' => 't', 'definition' => $def, 'code' => 'penaux',
    'dossier' => $ligneDossier, 'memoires' => [],
], 'registres/fiche (lien préremplissage mémoire)');

echo '--- Formulaires de registres ---' . PHP_EOL;
foreach (['arrivees', 'penaux', 'civils', 'dessaisissements', 'siege', 'pil'] as $codeRegistre) {
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

echo '--- Espace administration ---' . PHP_EOL;
$compteAdmin = [
    'id_client' => 7, 'nom' => 'RAKOTO', 'prenom' => 'Jean', 'sexe' => 'masculin',
    'login' => 'j.rakoto@pgcc.com', 'poste' => 'PG', 'sigle_AG' => 'JRK', 'IM' => 123,
    'num_bureau' => 4, 'valide' => 1, 'status' => 'Actifs', 'source' => 'client',
    'date_creation' => '2025-01-10 08:30:00', 'date_modification' => null,
    'cree_par' => 'system', 'modifie_par' => null,
];
$journalAdmin = [[
    'module' => 'Pourvois pénaux', 'reference' => '2025-0123', 'cree_par' => 'j.rakoto@pgcc.com',
    'modifie_par' => null, 'date_creation' => '2025-01-10 08:30:00',
    'date_modification' => null, 'derniere_date' => '2025-01-10 08:30:00',
]];
rendre('administration/tableau.php', [
    'titrePage' => 't',
    'compteurs' => ['total' => 12, 'actifs' => 10, 'retraites' => 1,
        'attenteNouvelles' => 0, 'attenteHistoriques' => 1, 'enAttente' => 1],
    'dossiers' => ['penaux' => 20, 'civils' => 5, 'memoires' => 8, 'detentions' => 4],
    'journal' => $journalAdmin,
], 'administration/tableau (compteurs et journal)');
rendre('administration/comptes.php', [
    'titrePage' => 't', 'filtre' => 'actifs', 'recherche' => '', 'comptes' => [$compteAdmin],
], 'administration/comptes (comptes actifs)');
rendre('administration/comptes.php', [
    'titrePage' => 't', 'filtre' => 'attente', 'recherche' => '',
    'comptes' => [['id_client' => 9, 'nom' => 'RABE', 'prenom' => 'Ada', 'login' => 'a.rabe@pgcc.com',
        'poste' => 'AG', 'sigle_AG' => 'ARB', 'IM' => 456, 'num_bureau' => 2,
        'valide' => 0, 'status' => 'En attente', 'source' => 'new']],
], 'administration/comptes (demandes en attente)');
rendre('administration/formulaire.php', [
    'titrePage' => 't', 'mode' => 'creer', 'compte' => null,
    'valeurs' => [], 'erreurs' => [], 'postes' => POSTES_UTILISATEUR,
], 'administration/formulaire création');
rendre('administration/formulaire.php', [
    'titrePage' => 't', 'mode' => 'modifier', 'compte' => $compteAdmin,
    'valeurs' => $compteAdmin, 'erreurs' => [], 'postes' => POSTES_UTILISATEUR,
], 'administration/formulaire modification (traçabilité)');
rendre('administration/audit.php', [
    'titrePage' => 't', 'journal' => $journalAdmin,
], 'administration/audit (journal complet)');

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
