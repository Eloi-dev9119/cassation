<?php /* Gabarit principal : en-tête, menu, contenu, pied de page. */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titre) ?></title>
    <script src="<?= e(asset('theme.js') . '?v=3') ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('style.css') . '?v=10') ?>">
    <script src="<?= e(asset('confirmations.js')) ?>" defer></script>
</head>
<body>
<header class="barre-haut">
    <div class="marque">
        <a href="<?= e(url('/tableau-de-bord')) ?>" class="marque-lien" aria-label="Accueil — Justice Madagascar">
            <span class="marque-monogramme" aria-hidden="true">MINJUS</span>
            <span class="marque-identite">
                <span class="marque-nom">GESTION DES REGISTRES</span>
                <span class="marque-organe">Parquet Général · MINJUS</span>
            </span>
        </a>
    </div>
    <nav class="menu">
        <a href="<?= e(url('/tableau-de-bord')) ?>">Tableau de bord</a>

        <div class="menu-groupe">
            <a href="#" onclick="return deroulerMenu(event, 'menu-registres')">Registres ▾</a>
            <div class="menu-deroulant" id="menu-registres">
                <a href="<?= e(url('/registres/arrivees')) ?>">Registres Arrivés</a>
                <a href="<?= e(url('/registres/penaux')) ?>">Registres Pénaux</a>
                <a href="<?= e(url('/registres/civils')) ?>">Registres Civils</a>
                <a href="<?= e(url('/registres/dessaisissements')) ?>">Registres Déssaisissements</a>
                <a href="<?= e(url('/registres/siege')) ?>">Registre du Siège</a>
                <a href="<?= e(url('/memoires')) ?>"><strong>Registre des Mémoires</strong></a>
            </div>
        </div>

        <div class="menu-groupe">
            <a href="#" onclick="return deroulerMenu(event, 'menu-suivis')">Suivis ▾</a>
            <div class="menu-deroulant" id="menu-suivis">
                <a href="<?= e(url('/suivis/321')) ?>">Suivis Arrivés (321)</a>
                <a href="<?= e(url('/suivis/107')) ?>">Suivis Pénaux (107)</a>
                <a href="<?= e(url('/suivis-dossiers')) ?>">Suivis Dossiers</a>
                <a href="<?= e(url('/repartition')) ?>">Répartition des dossiers</a>
            </div>
        </div>

        <div class="menu-groupe">
            <a href="#" onclick="return deroulerMenu(event, 'menu-distributions')">Distributions ▾</a>
            <div class="menu-deroulant" id="menu-distributions">
                <a href="<?= e(url('/distributions?type=pen')) ?>">Distributions Pénaux</a>
                <a href="<?= e(url('/distributions?type=civ')) ?>">Distributions Civiles</a>
            </div>
        </div>

        <div class="menu-groupe">
            <a href="#" onclick="return deroulerMenu(event, 'menu-membres')">Membres ▾</a>
            <div class="menu-deroulant" id="menu-membres">
                <a href="<?= e(url('/membres/attente')) ?>">En attente de validation</a>
                <a href="<?= e(url('/membres/actifs')) ?>">Membres actifs</a>
                <a href="<?= e(url('/membres/retraites')) ?>">Retraités</a>
            </div>
        </div>

        <a href="<?= e(url('/profil')) ?>">Mes Informations</a>
        <a href="<?= e(url('/aide')) ?>">Aide</a>
    </nav>
    <div class="utilisateur">
        <span class="utilisateur-nom"><?= e(Auth::nomComplet()) ?></span>
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

<main class="conteneur">
    <?php $flash = recuperer_flash(); if ($flash !== null): ?>
        <div class="alerte alerte-<?= e($flash['type']) ?>"
             role="<?= $flash['type'] === 'erreur' ? 'alert' : 'status' ?>"
             aria-live="<?= $flash['type'] === 'erreur' ? 'assertive' : 'polite' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?= $contenu ?>
</main>

<footer class="pied">
    <span>Parquet Général — Justice Madagascar · Gestion des registres judiciaires</span>
    <span>© <?= date('Y') ?></span>
</footer>

<script>
/** Ouvre / ferme un sous-menu déroulant. */
function deroulerMenu(evenement, id) {
    evenement.preventDefault();
    document.querySelectorAll('.menu-deroulant.ouvert').forEach(m => {
        if (m.id !== id) m.classList.remove('ouvert');
    });
    document.getElementById(id).classList.toggle('ouvert');
    return false;
}
document.addEventListener('click', function (e) {
    if (!e.target.closest('.menu-groupe')) {
        document.querySelectorAll('.menu-deroulant.ouvert').forEach(m => m.classList.remove('ouvert'));
    }
});
</script>
</body>
</html>
