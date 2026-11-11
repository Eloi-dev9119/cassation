<?php
/**
 * Partial commun : barre de filtres + pagination d'une liste.
 * Variables attendues : $actionFiltre (URL), $routeFiltre (route brute SANS
 * « ? », ex. '/registres/penaux' — indispensable : en GET le navigateur
 * remplace la query-string de l'action par les champs, le « r » doit donc
 * voyager en champ caché), $recherche, $annees?, $annee?, $resultat,
 * $parametresSup (paramètres additionnels à conserver, ex. type).
 */
$filtresSup = $parametresSup ?? '';
$routeFiltre = $routeFiltre ?? '';
$recherche = $recherche ?? '';
$annee = $annee ?? null;
?>
<form class="filtres" method="get" action="<?= e($actionFiltre) ?>" data-auto-filtre role="search">
    <?php if ($routeFiltre !== ''): ?>
        <input type="hidden" name="r" value="<?= e($routeFiltre) ?>">
    <?php endif; ?>
    <?php foreach (array_filter(array_map('strval', (array) $filtresSup), static fn (string $v): bool => $v !== '') as $nomSupplement => $valeurSupplement): ?>
        <input type="hidden" name="<?= e($nomSupplement) ?>" value="<?= e($valeurSupplement) ?>">
    <?php endforeach; ?>
    <input type="search" name="q" placeholder="Rechercher…" value="<?= e($recherche) ?>" aria-label="Rechercher">
    <?php if (!empty($annees)): ?>
        <select name="annee" aria-label="Filtrer par année">
            <option value="">Toutes les années</option>
            <?php foreach ($annees as $a): ?>
                <option value="<?= (int) $a ?>" <?= (string) $annee === (string) $a ? 'selected' : '' ?>><?= (int) $a ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <button type="submit" class="bouton">Filtrer</button>
    <span class="compte"><?= number_format((float) $resultat['total'], 0, ',', ' ') ?> dossier(s)</span>
</form>
