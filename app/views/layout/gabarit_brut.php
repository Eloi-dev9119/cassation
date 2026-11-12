<?php /* Gabarit « brut » (connexion / inscription) : sans menu. */ ?>
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
    <script src="<?= e(asset('connexion.js')) ?>" defer></script>
</head>
<body class="page-auth">
<main class="auth-contenu">
    <div class="auth-marque">
        <span class="marque-monogramme" aria-hidden="true">PG</span>
        <span class="marque-identite">
            <span class="marque-nom">GESTION DES REGISTRES</span>
            <span class="marque-organe">Parquet Général · Ministère de la Justice</span>
        </span>
        <button type="button" class="bouton-theme bouton-theme-auth" data-theme-toggle
                title="Changer le thème" aria-label="Changer le thème">
            <span aria-hidden="true">◐</span>
        </button>
    </div>
<?php $flash = recuperer_flash(); require __DIR__ . '/../partiels/flash.php'; ?>

