<?php /** Page 500 — erreur serveur (détail visible uniquement en DEBUG). */ ?>
<div class="carte" style="text-align:center;padding:40px">
    <h1 style="font-size:42px;margin:0;color:var(--rouge)">Erreur interne</h1>
    <p>Une erreur inattendue s'est produite. L'événement a été consigné dans le journal du serveur.</p>
    <?php if (DEBUG && !empty($exception)): ?>
        <pre style="text-align:left;background:var(--gris-clair);padding:12px;border-radius:var(--rayon);overflow:auto"><?= e($exception) ?></pre>
    <?php endif; ?>
    <a class="bouton" href="<?= e(url('/tableau-de-bord')) ?>">Retour au tableau de bord</a>
</div>
