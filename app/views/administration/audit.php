<?php
/**
 * Journal d'activité issu des colonnes d'audit.
 * Variables : $journal.
 */
$heure = static function (?string $date): string {
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts === false ? '—' : date('d/m/Y H:i', $ts);
};
?>
<div class="titre-page">
    <h1>Journal d’activité</h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url('/administration')) ?>">← Administration</a>
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
    </div>
</div>

<div class="tableau-enveloppe">
    <table class="liste">
        <thead>
            <tr>
                <th>Date</th><th>Module</th><th>Référence</th><th>Créé par</th><th>Modifié par</th>
                <th>Créé le</th><th>Modifié le</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($journal === []): ?>
                <tr><td colspan="7" class="etat-vide">
                    <strong>Aucune activité enregistrée</strong>
                    Le journal se remplira dès la première action enregistrée.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($journal as $entree): ?>
                <tr>
                    <td><?= e($heure($entree['derniere_date'] ?? null)) ?></td>
                    <td><?= e($entree['module'] ?? '—') ?></td>
                    <td class="rogne"><?= e($entree['reference'] ?? '—') ?></td>
                    <td><?= e($entree['cree_par'] ?: '—') ?></td>
                    <td><?= e($entree['modifie_par'] ?: '—') ?></td>
                    <td><?= e($heure($entree['date_creation'] ?? null)) ?></td>
                    <td><?= e($heure($entree['date_modification'] ?? null)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
