<?php
/**
 * Bloc « Pièces jointes » d'une entité (mémoire ou dossier de registre).
 * Variables attendues : $entite (string), $entiteId (int), $pieces (array),
 * $retour (chemin local de retour après l'action).
 */
$entite = $entite ?? '';
$entiteId = (int) ($entiteId ?? 0);
$pieces = $pieces ?? null;
$retour = $retour ?? '/tableau-de-bord';
?>
<div class="carte carte-pieces">
    <h2>Pièces jointes</h2>

    <?php if ($pieces === null): ?>
        <p class="texte-doux">Fonctionnalité non installée sur cette base : exécutez la migration
            <code>database/migrations/pieces_jointes_et_fulltext.sql</code> pour activer le dépôt de documents.</p>
    <?php else: ?>
    <p class="texte-doux">Mémoires signés, arrêts, décisions scannés… PDF, images et documents Office — 10 Mo maximum par fichier.</p>

    <?php if ($pieces === []): ?>
        <p class="texte-doux" style="margin-bottom:10px">Aucune pièce jointe pour l'instant.</p>
    <?php else: ?>
        <div class="tableau-enveloppe" style="max-height:280px">
            <table class="liste">
                <thead>
                    <tr><th>Fichier</th><th>Taille</th><th>Ajouté le</th><th>Par</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pieces as $piece): ?>
                        <tr>
                            <td class="rogne">
                                <a href="<?= e(url('/pieces/telecharger?id=' . (int) $piece['id_piece'])) ?>">
                                    <?= e($piece['nom_original']) ?>
                                </a>
                            </td>
                            <td><?= e(PieceModel::tailleLisible((int) $piece['taille'])) ?></td>
                            <td><?= e(date_fr(mb_substr((string) $piece['date_ajout'], 0, 10))) ?></td>
                            <td><?= e($piece['ajoute_par'] ?: '—') ?></td>
                            <td class="cellule-actions">
                                <form method="post" action="<?= e(url('/pieces/supprimer')) ?>" style="display:inline"
                                      data-confirm="La pièce jointe « <?= e($piece['nom_original']) ?> » sera supprimée définitivement. Confirmez-vous ?"
                                      data-confirm-title="Supprimer la pièce jointe" data-confirm-label="Supprimer"
                                      data-confirm-tone="danger">
                                    <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $piece['id_piece'] ?>">
                                    <input type="hidden" name="retour" value="<?= e($retour) ?>">
                                    <button type="submit" class="bouton bouton-mini bouton-rouge">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/pieces/ajouter')) ?>" enctype="multipart/form-data" class="forme-piece">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <input type="hidden" name="entite" value="<?= e($entite) ?>">
        <input type="hidden" name="entite_id" value="<?= $entiteId ?>">
        <input type="hidden" name="retour" value="<?= e($retour) ?>">
        <input type="file" name="fichier" required
               accept=".pdf,.png,.jpg,.jpeg,.gif,.doc,.docx,.xls,.xlsx"
               aria-label="Choisir un fichier à joindre">
        <button type="submit" class="bouton">Joindre le fichier</button>
    </form>
    <?php endif; ?>
</div>
