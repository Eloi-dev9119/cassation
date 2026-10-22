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
?>
<form class="filtres" method="get" action="<?= e($actionFiltre) ?>">
    <?php if ($routeFiltre !== ''): ?>
        <input type="hidden" name="r" value="<?= e($routeFiltre) ?>">
    <?php endif; ?>
    <?php foreach (array_filter(array_map('strval', $filtresSup)) as $cle => $val): ?>
        <input type="hidden" name="<?= e($cle) ?>" value="<?= e($val) ?>">
    <?php endforeach; ?>
    <input type="search" name="q" placeholder="Rechercher…" value="<?= e($recherche) ?>">
    <?php if (!empty($annees)): ?>
        <select name="annee">
            <option value="">Toutes les années</option>
            <?php foreach ($annees as $a): ?>
                <option value="<?= (int) $a ?>" <?= (string) $annee === (string) $a ? 'selected' : '' ?>><?= (int) $a ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <button type="submit" class="bouton">Filtrer</button>
    <span class="compte"><?= number_format((float) $resultat['total'], 0, ',', ' ') ?> dossier(s)</span>
</form>
