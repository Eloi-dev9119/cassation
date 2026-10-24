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

<div class="alerte alerte-info">
    Le journal reprend les créations et modifications enregistrées automatiquement dans les tables
    métier (colonnes <code>date_creation</code>, <code>date_modification</code>, <code>cree_par</code>
    et <code>modifie_par</code>). Les 200 activités les plus récentes sont affichées.
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
                <tr><td colspan="7" style="text-align:center;color:var(--gris);padding:26px">Aucune activité enregistrée.</td></tr>
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
