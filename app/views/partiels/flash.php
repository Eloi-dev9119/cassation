<?php
if (!isset($flash) || $flash === null) {
    return;
}

$type = (string) ($flash['type'] ?? 'info');
if (!in_array($type, ['succes', 'info', 'erreur'], true)) {
    $type = 'info';
}
$titre = trim((string) ($flash['titre'] ?? ''));
if ($titre === '') {
    $titre = match ($type) {
        'succes' => 'Confirmation',
        'erreur' => 'Action impossible',
        default  => 'Information',
    };
}
$icone = match ($type) {
    'succes' => '✓',
    'erreur' => '!',
    default  => 'i',
};
$duree = 6500;
?>
<?php if ($type === 'erreur'): ?>
    <div class="alerte alerte-erreur" role="alert" aria-live="assertive">
        <?= e((string) ($flash['message'] ?? '')) ?>
    </div>
<?php else: ?>
    <div class="notification-toast notification-<?= e($type) ?>"
         role="status" aria-live="polite" aria-atomic="true"
         data-toast data-toast-duration="<?= (int) $duree ?>"
         style="--toast-duree: <?= (int) $duree ?>ms">
        <span class="notification-icone" aria-hidden="true"><?= $icone ?></span>
        <div class="notification-corps">
            <strong class="notification-titre"><?= e($titre) ?></strong>
            <p class="notification-message"><?= e((string) ($flash['message'] ?? '')) ?></p>
        </div>
        <button type="button" class="notification-fermer" data-toast-close
                aria-label="Fermer la notification">&times;</button>
        <span class="notification-progression" aria-hidden="true"></span>
    </div>
<?php endif; ?>
