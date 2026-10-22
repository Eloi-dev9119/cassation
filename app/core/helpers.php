<?php
/**
 * Fonctions utilitaires partagées par toute l'application.
 */

declare(strict_types=1);

/**
 * Échappe une valeur pour l'affichage HTML (protection XSS).
 */
function e(mixed $valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

/**
 * Préfixe du répertoire contenant index.php dans l'URL, déduit de la
 * requête (pas de chemin en dur) : '/pgcc2' sous XAMPP, '' quand
 * l'application est servie à la racine (serveur PHP intégré).
 */
function base_chemin(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return rtrim($dir, '/');
}

/**
 * Construit une URL interne de l'application, toujours servie par le front
 * controller (fonctionne sur Apache avec OU sans mod_rewrite, ainsi que sur
 * le serveur PHP intégré).
 *
 * Ex. sous XAMPP : url('/registres/penaux') → '/pgcc2/index.php?r=/registres/penaux'
 */
function url(string $chemin): string
{
    $pos = strpos($chemin, '?');
    $route = $pos === false ? $chemin : substr($chemin, 0, $pos);
    $requete = $pos === false ? '' : substr($chemin, $pos + 1);

    $url = base_chemin() . '/index.php?r=' . $route;
    return $requete === '' ? $url : $url . '&' . $requete;
}

/**
 * URL d'une ressource statique (CSS, image) servie directement par le
 * serveur, sans passer par le front controller.
 * Ex. sous XAMPP : asset('style.css') → '/pgcc2/public/assets/style.css'
 */
function asset(string $ressource): string
{
    return base_chemin() . '/public/assets/' . ltrim($ressource, '/');
}

/**
 * Formatte une date SQL (AAAA-MM-JJ) en JJ/MM/AAAA.
 * Retourne '—' si la date est vide ou invalide.
 */
function date_fr(?string $date): string
{
    if (empty($date) || str_starts_with($date, '0000')) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts === false ? '—' : date('d/m/Y', $ts);
}

/**
 * Convertit une valeur binaire(1) de la base (0x30/0x31) en booléen.
 */
function bin_vers_bool(mixed $valeur): bool
{
    return in_array($valeur, [1, '1', "\x01", true], true);
}

/**
 * Affiche un badge coloré selon un état booléen.
 */
function badge_oui_non(bool $valeur, string $libelleVrai = 'Oui', string $libelleFaux = 'Non'): string
{
    $classe = $valeur ? 'badge badge-vert' : 'badge badge-gris';
    return '<span class="' . $classe . '">' . e($valeur ? $libelleVrai : $libelleFaux) . '</span>';
}

/**
 * Redirige vers une URL interne et interrompt le script.
 */
function rediriger(string $chemin): never
{
    header('Location: ' . url($chemin));
    exit;
}

/**
 * Affiche la page 404 et interrompt le script.
 */
function page_404(string $titre = 'Page introuvable'): never
{
    http_response_code(404);
    View::afficher('erreurs/404.php', ['titrePage' => $titre]);
    exit;
}

/**
 * L'utilisateur connecté appartient-il à l'administration du Parquet ?
 * Les postes habilités sont définis dans config.php (POSTES_ADMINISTRATION).
 */
function est_administration(?array $utilisateur = null): bool
{
    $u = $utilisateur ?? Auth::utilisateur();
    if ($u === null) {
        return false;
    }
    $poste = mb_strtoupper(trim((string) ($u['poste'] ?? '')));
    foreach (POSTES_ADMINISTRATION as $code) {
        if ($poste === mb_strtoupper(trim($code))) {
            return true;
        }
    }
    return false;
}

/**
 * Login de l'utilisateur connecté (ou « system » hors session).
 * Utilisé pour renseigner les colonnes d'audit cree_par / modifie_par.
 */
function utilisateur_login(): string
{
    $u = class_exists('Auth') ? Auth::utilisateur() : null;
    return $u['login'] ?? 'system';
}

/**
 * Valeur soumise ancienne (pour remplir de nouveau un formulaire en erreur).
 */
function ancienne(string $cle, mixed $defaut = ''): mixed
{
    return $_SESSION['formulaire_ancien'][$cle] ?? $defaut;
}

/**
 * Enregistre les valeurs d'un formulaire pour réaffichage ultérieur.
 */
function memoriser_ancien(array $valeurs): void
{
    $_SESSION['formulaire_ancien'] = $valeurs;
}

/**
 * Oublie les valeurs d'un formulaire mémorisées.
 */
function oublier_ancien(): void
{
    unset($_SESSION['formulaire_ancien'], $_SESSION['erreurs']);
}

/**
 * Enregistre un message flash affiché une seule fois à la prochaine page.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Récupère (et efface) le message flash en attente.
 */
function recuperer_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Qualifie un identifiant SQL (table, colonne) après validation stricte.
 * PDO ne sait pas paramétrer un identifiant : les noms interpolés dans une
 * requête proviennent des définitions déclaratives et doivent se limiter à
 * l'alphanumérique et au souligné pour exclure toute injection.
 */
function nom_sql(string $nom): string
{
    if (preg_match('/^[A-Za-z0-9_]+$/', $nom) !== 1) {
        throw new InvalidArgumentException('Identifiant SQL invalide : ' . $nom);
    }
    return '`' . $nom . '`';
}

/**
 * Valide une clause ORDER BY issue des définitions déclaratives :
 * liste de « colonne [ASC|DESC] » séparées par des virgules, rien d'autre.
 */
function ordre_sql(string $ordre): string
{
    if (preg_match('/^[A-Za-z0-9_]+( (ASC|DESC))?(, [A-Za-z0-9_]+( (ASC|DESC))?)*$/', $ordre) !== 1) {
        throw new InvalidArgumentException('Clause ORDER BY invalide : ' . $ordre);
    }
    return $ordre;
}

/**
 * Point d'accès unique aux entrées de la requête HTTP, via filter_input()
 * avec le filtre FILTER_DEFAULT (valeur brute) : aucune superglobale n'est
 * lue ailleurs. Les validations propres à chaque champ (casts, ctype,
 * checkdate, jeton CSRF) restent aux sites d'appel.
 *
 * filter_input() lit l'entrée HTTP originale du SAPI, pas les
 * superglobales : les scripts CLI qui alimentent $_POST directement ne
 * sont donc pas vus — les tests de parcours passent par HTTP
 * (tests/test_e2e_xampp.sh), comme une vraie requête.
 */
function entree_post(string $cle, mixed $defaut = null): mixed
{
    return entree_filtree(INPUT_POST, $cle, $defaut);
}

function entree_get(string $cle, mixed $defaut = null): mixed
{
    return entree_filtree(INPUT_GET, $cle, $defaut);
}

/**
 * Lit une entrée filtrée : filter_input() refuse les tableaux sans
 * FILTER_REQUIRE_ARRAY et refuse les scalaires avec — d'où les deux
 * passages (cas des cases à cocher multiples).
 */
function entree_filtree(int $source, string $cle, mixed $defaut): mixed
{
    $valeur = filter_input($source, $cle, FILTER_DEFAULT);
    if ($valeur === false || $valeur === null) {
        $valeur = filter_input($source, $cle, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    }
    return $valeur ?? $defaut;
}

/** Toutes les entrées POST, pour les parcours comples d'un formulaire. */
function entrees_post(): array
{
    return (array) filter_input_array(INPUT_POST, FILTER_DEFAULT);
}

/** La requête courante utilise-t-elle la méthode POST ? */
function requete_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
