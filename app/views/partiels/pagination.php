<?php
/**
 * Partial commun : liens de pagination.
 * Variables attendues : $resultat, $urlBase (route interne brute, sans
 * « ? »), $parametres (chaîne « a=b&c=d » conservée entre les pages).
 * Tous les liens passent par url() pour être servis par le front controller.
 */
$pageCourante = (int) $resultat['page'];
$pages        = (int) $resultat['pages'];
if ($pages <= 1) return;

/** Lien vers la page $p en conservant les paramètres de filtre courants. */
$lien = fn (int $p): string => e(url($urlBase . '?' . $parametres . 'p=' . $p));
?>
<div class="pagination">
    <?php if ($pageCourante > 1): ?>
        <a href="<?= $lien($pageCourante - 1) ?>">‹ Précédente</a>
    <?php endif; ?>

    <?php
    $debut = max(1, $pageCourante - 3);
    $fin   = min($pages, $pageCourante + 3);
    if ($debut > 1)  echo '<a href="' . $lien(1) . '">1</a>';
    if ($debut > 2)  echo '<span>…</span>';
    for ($p = $debut; $p <= $fin; $p++):
        if ($p === $pageCourante): ?>
            <span class="actif"><?= $p ?></span>
        <?php else: ?>
            <a href="<?= $lien($p) ?>"><?= $p ?></a>
        <?php endif;
    endfor;
    if ($fin < $pages - 1) echo '<span>…</span>';
    if ($fin < $pages)     echo '<a href="' . $lien($pages) . '">' . $pages . '</a>';
    ?>

    <?php if ($pageCourante < $pages): ?>
        <a href="<?= $lien($pageCourante + 1) ?>">Suivante ›</a>
    <?php endif; ?>
</div>
