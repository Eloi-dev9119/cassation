<?php
/**
 * Conclusion d'un dossier (registres pénaux / civils / dessaisissements) :
 * édition du texte, aperçu du document enregistré et impression.
 * Variables : $definition, $code, $dossier, $conclusion, $modeleDisponible.
 */
$baseUrl = '/registres/' . $code; // route brute passée dans url()
$cleDossier = (int) $dossier[$definition['cle']];
$conclusion = $conclusion ?? (string) ($dossier[$definition['conclusion']] ?? '');
$lienFiche = url($baseUrl . '/fiche?id=' . $cleDossier);
$lienListe = url($baseUrl);
?>
<div class="titre-page titre-page-formulaire">
    <h1>Conclusion — dossier <?= e((string) ($dossier['num_dossier_PGCS'] ?? '')) ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '/conclusion?export=pdf&id=' . $cleDossier)) ?>">Télécharger PDF</a>
        <button type="button" class="bouton bouton-secondaire" onclick="window.print()">Imprimer</button>
        <a class="bouton bouton-secondaire" href="<?= e($lienListe) ?>">← Retour à la liste</a>
        <a class="bouton bouton-secondaire" href="<?= e($lienFiche) ?>">Retour à la fiche</a>
    </div>
</div>

<div class="carte">
    <h2>Données du dossier</h2>
    <div class="tableau-enveloppe" style="max-height:360px">
        <table class="liste fiche-plate">
            <tbody>
                <?php foreach (registre_colonnes($definition, 'liste') as $colonneDossier): ?>
                    <?php if ($colonneDossier['nom'] === ($definition['conclusion'] ?? '')) { continue; } ?>
                    <?php
                    $valeurDossier = (string) ($dossier[$colonneDossier['nom']] ?? '');
                    ?>
                    <tr>
                        <th scope="row" style="width:260px"><?= e($colonneDossier['libelle']) ?></th>
                        <td><?= match ($colonneDossier['type']) {
                            'booleen' => badge_oui_non(bin_vers_bool($valeurDossier)),
                            'date'    => e(date_fr($valeurDossier)),
                            'textelong' => e(strip_tags($valeurDossier)),
                            default   => e($valeurDossier),
                        } ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="carte">
    <h2>Édition de la conclusion</h2>
    <form method="post" action="<?= e(url($baseUrl . '/conclusion?id=' . $cleDossier)) ?>"
          data-confirm-title="Enregistrer la conclusion"
          data-confirm="La conclusion de ce dossier sera enregistrée. Confirmez-vous cette modification ?"
          data-confirm-label="Enregistrer la conclusion">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <div class="champ">
            <label for="conclusion">Texte de la conclusion</label>
            <textarea id="conclusion" name="conclusion" style="min-height:260px;font-family:Georgia,'Times New Roman',serif;font-size:15px"><?= e($conclusion) ?></textarea>
        </div>
        <div class="actions-formulaire">
            <button type="submit" class="bouton bouton-vert">Enregistrer la conclusion</button>
            <?php if ($modeleDisponible ?? false): ?>
                <a class="bouton bouton-secondaire"
                   title="Remplace le texte de l'éditeur par le modèle prérempli (les modifications non enregistrées seront perdues)"
                   href="<?= e(url($baseUrl . '/conclusion?id=' . $cleDossier . '&modele=1')) ?>">Réinsérer le modèle prérempli</a>
            <?php endif; ?>
            <a class="bouton bouton-secondaire" href="<?= e($lienFiche) ?>">Annuler</a>
        </div>
    </form>
</div>

<?php if (trim(strip_tags($conclusion)) !== ''): ?>
<div class="carte">
    <h2>Aperçu de la conclusion</h2>
    <p class="texte-doux">Rendu du document tel qu'il sera imprimé. L'aperçu est rafraîchi après enregistrement.</p>
    <?php if (str_starts_with(ltrim($conclusion), '<')): ?>
        <div class="conclusion-apercu"><?= rendre_conclusion($conclusion) ?></div>
    <?php else: ?>
        <div class="conclusion-apercu"><div class="conclusion-plaintext"><?= e($conclusion) ?></div></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div id="conclusion-impression" class="conclusion-impression" aria-hidden="true"><?php
    if (str_starts_with(ltrim($conclusion), '<')) {
        echo rendre_conclusion($conclusion);
    } else {
        echo '<div class="conclusion-plaintext">' . e($conclusion) . '</div>';
    }
?></div>
