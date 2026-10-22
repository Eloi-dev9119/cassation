<?php
/**
 * Éditeur de conclusion d'un dossier (registres pénaux / civils /
 * dessaisissements). Variables : $definition, $code, $dossier.
 */
$baseUrl = '/registres/' . $code; // route brute passée dans url()
$conclusion = (string) ($dossier[$definition['conclusion']] ?? '');
?>
<div class="titre-page">
    <h1>Conclusion — dossier <?= e((string) ($dossier['num_dossier_PGCS'] ?? '')) ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '/fiche?id=' . $dossier[$definition['cle']])) ?>">← Retour à la fiche</a>
    </div>
</div>

<div class="carte">
    <form method="post" action="<?= e(url($baseUrl . '/conclusion?id=' . $dossier[$definition['cle']])) ?>"
          data-confirm-title="Enregistrer la conclusion"
          data-confirm="La conclusion de ce dossier sera enregistrée. Confirmez-vous cette modification ?"
          data-confirm-label="Enregistrer la conclusion">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <div class="champ">
            <label for="conclusion">Texte de la conclusion</label>
            <textarea id="conclusion" name="conclusion" style="min-height:260px;font-family:Georgia,'Times New Roman',serif;font-size:15px"><?= e($conclusion) ?></textarea>
        </div>
        <button type="submit" class="bouton bouton-vert">Enregistrer la conclusion</button>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '/fiche?id=' . $dossier[$definition['cle']])) ?>">Annuler</a>
    </form>
</div>
