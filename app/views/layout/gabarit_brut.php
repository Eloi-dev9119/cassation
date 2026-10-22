<?php /* Gabarit « brut » (connexion / inscription) : sans menu. */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titre) ?></title>
    <script src="<?= e(asset('theme.js') . '?v=3') ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('style.css') . '?v=9') ?>">
    <script src="<?= e(asset('confirmations.js')) ?>" defer></script>
    <script src="<?= e(asset('connexion.js')) ?>" defer></script>
</head>
<body class="page-auth">
<main class="auth-contenu">
    <div class="auth-marque">
        <span class="marque-monogramme" aria-hidden="true">MINJUS</span>
        <span class="marque-identite">
            <span class="marque-nom">GESTION DES REGISTRES</span>
            <span class="marque-organe">Parquet Général · MINJUS</span>
        </span>
        <button type="button" class="bouton-theme bouton-theme-auth" data-theme-toggle
                title="Changer le thème" aria-label="Changer le thème">
            <span aria-hidden="true">◐</span>
        </button>
    </div>
<?php $flash = recuperer_flash(); if ($flash !== null): ?>
    <div class="alerte alerte-<?= e($flash['type']) ?>"
         role="<?= $flash['type'] === 'erreur' ? 'alert' : 'status' ?>"
         aria-live="<?= $flash['type'] === 'erreur' ? 'assertive' : 'polite' ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
