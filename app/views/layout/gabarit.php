<?php
/* Gabarit principal : en-tête, menu, contenu, pied de page. */
$routeNav = trim((string) entree_get('r', ''), '/');
$sectionNav = explode('/', $routeNav)[0] ?? '';
$estNav = static fn (array $sections): bool => in_array($sectionNav, $sections, true);
$navSous = static fn (string $route): string => $routeNav === $route
    ? ' class="actif" aria-current="page"'
    : '';
$membreConnecte = Auth::utilisateur() ?? [];
$initiales = mb_strtoupper(
    mb_substr(trim((string) ($membreConnecte['prenom'] ?? '')), 0, 1)
    . mb_substr(trim((string) ($membreConnecte['nom'] ?? '')), 0, 1)
);
if ($initiales === '') {
    $initiales = 'PG';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f7f8">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('favicon.svg')) ?>">
    <title><?= e($titre) ?></title>
    <script src="<?= e(asset('theme.js') . '?v=5') ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('style.css') . '?v=57') ?>">
    <script src="<?= e(asset('confirmations.js') . '?v=3') ?>" defer></script>
    <script src="<?= e(asset('filtres.js') . '?v=1') ?>" defer></script>
</head>
<body>
<a class="lien-evitement" href="#contenu">Aller au contenu principal</a>
<header class="barre-haut">
    <div class="marque">
        <a href="<?= e(url('/tableau-de-bord')) ?>" class="marque-lien" aria-label="Accueil — Ministère de la Justice">
            <img class="marque-logo" src="<?= e(asset('logo-minjus.png')) ?>"
                 alt="Armoiries — Ministère de la Justice de Madagascar" width="44" height="44">
            <span class="marque-identite">
                <span class="marque-nom">GESTION DES REGISTRES</span>
                <span class="marque-organe">Parquet Général · Ministère de la Justice</span>
            </span>
        </a>
    </div>
    <nav class="menu" aria-label="Navigation principale">
        <a href="<?= e(url('/tableau-de-bord')) ?>"<?= $estNav(['tableau-de-bord']) ? ' class="actif" aria-current="page"' : '' ?>>Accueil</a>
        <a href="<?= e(url('/registres/arrivees')) ?>"<?= $estNav(['registres']) ? ' class="actif" aria-current="page"' : '' ?>>Registres</a>
        <a href="<?= e(url('/memoires')) ?>"<?= $estNav(['memoires']) ? ' class="actif" aria-current="page"' : '' ?>>Mémoires</a>
        <a href="<?= e(url('/suivis/321')) ?>"<?= $estNav(['suivis', 'suivis-dossiers', 'repartition']) ? ' class="actif" aria-current="page"' : '' ?>>Suivis</a>
        <a href="<?= e(url('/distributions')) ?>"<?= $estNav(['distributions']) ? ' class="actif" aria-current="page"' : '' ?>>Distributions</a>
        <a href="<?= e(url('/membres/actifs')) ?>"<?= $estNav(['membres']) ? ' class="actif" aria-current="page"' : '' ?>>Membres</a>
        <?php if (est_administration()): ?>
        <a href="<?= e(url('/administration')) ?>"<?= $estNav(['administration']) ? ' class="actif" aria-current="page"' : '' ?>>Admin</a>
        <?php endif; ?>
        <a href="<?= e(url('/profil')) ?>"<?= $estNav(['profil']) ? ' class="actif" aria-current="page"' : '' ?>>Profil</a>
        <a href="<?= e(url('/aide')) ?>"<?= $estNav(['aide']) ? ' class="actif" aria-current="page"' : '' ?>>Aide</a>
    </nav>
    <div class="utilisateur">
        <span class="utilisateur-initiales" aria-hidden="true"><?= e($initiales) ?></span>
        <span class="utilisateur-nom"><span class="pastille-presence en-ligne" title="Connecté"><span class="pastille-point" aria-hidden="true"></span></span><?= e(Auth::nomComplet()) ?></span>
        <button type="button" class="bouton-theme" data-theme-toggle title="Changer le thème" aria-label="Changer le thème"><span aria-hidden="true">◐</span><span>Thème</span></button>
        <form method="post" action="<?= e(url('/deconnexion')) ?>"
              data-confirm-title="Déconnexion"
              data-confirm="Votre session va être fermée. Voulez-vous vous déconnecter ?"
              data-confirm-label="Se déconnecter">
            <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
            <button type="submit" class="bouton bouton-deconnexion" title="Déconnexion">Déconnexion</button>
        </form>
    </div>
</header>

<main class="conteneur" id="contenu">
    <?php $flash = recuperer_flash(); require __DIR__ . '/../partiels/flash.php'; ?>

    <?= $contenu ?>
</main>

<footer class="pied">
    <span>Parquet Général — Ministère de la Justice · Gestion des registres judiciaires</span>
    <span>© <?= date('Y') ?></span>
</footer>

<script>
/** Ouvre / ferme un sous-menu déroulant. */
function deroulerMenu(evenement, id) {
    evenement.preventDefault();
    const declencheur = evenement.currentTarget;
    document.querySelectorAll('.menu-deroulant.ouvert').forEach(m => {
        if (m.id !== id) {
            m.classList.remove('ouvert');
            const controle = document.querySelector('[aria-controls="' + m.id + '"]');
            if (controle) controle.setAttribute('aria-expanded', 'false');
        }
    });
    const menu = document.getElementById(id);
    const ouvert = menu.classList.toggle('ouvert');
    if (declencheur) declencheur.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
    return false;
}
function fermerMenus() {
    document.querySelectorAll('.menu-deroulant.ouvert').forEach(m => m.classList.remove('ouvert'));
    document.querySelectorAll('.menu-groupe > a[aria-expanded="true"]').forEach(a => a.setAttribute('aria-expanded', 'false'));
}
document.addEventListener('click', function (e) {
    if (!e.target.closest('.menu-groupe')) fermerMenus();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') fermerMenus();
});
const lienActif = document.querySelector('.menu .actif');
if (lienActif) lienActif.scrollIntoView({ block: 'nearest', inline: 'center' });
</script>
</body>
</html>

