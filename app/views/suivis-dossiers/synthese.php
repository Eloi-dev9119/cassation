<?php
/**
 * Suivi des dossiers par Avocat Général. Variables : $ags,
 * $sigleChoisi, $synthese (null si aucun AG).
 */
?>
<div class="titre-page">
    <h1>Suivis Dossiers</h1>
    <?php if ($synthese !== null): ?>
        <div class="actions">
            <a class="bouton bouton-secondaire" href="<?= e(url('/suivis-dossiers?export=csv&' . http_build_query(['ag' => $sigleChoisi]))) ?>">Télécharger CSV</a>
            <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
        </div>
    <?php endif; ?>
</div>

<div class="carte">
    <form method="get" action="<?= e(url('/suivis-dossiers')) ?>" class="filtres" style="margin-bottom:0">
        <input type="hidden" name="r" value="/suivis-dossiers">
        <label style="margin:0;font-weight:600;color:var(--ardoise)">Avocat Général :</label>
        <select name="ag" onchange="this.form.submit()">
            <?php foreach ($ags as $ag): ?>
                <option value="<?= e($ag['sigle_AG']) ?>" <?= $sigleChoisi === $ag['sigle_AG'] ? 'selected' : '' ?>>
                    <?= e(trim($ag['nom'] . ' ' . $ag['prenom'])) ?> (<?= e($ag['sigle_AG']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bouton">Afficher</button>
    </form>
</div>

<?php if ($synthese === null): ?>
    <div class="carte"><p style="color:var(--gris)">Aucun Avocat Général actif n'a été trouvé.</p></div>
<?php else: ?>

<div class="cartes-mesures">
    <div class="mesure bordeaux">
        <div class="valeur"><?= (int) $synthese['nbPenaux'] ?></div>
        <div class="libelle">Dossiers pénaux distribués</div>
    </div>
    <div class="mesure">
        <div class="valeur"><?= (int) $synthese['nbCivils'] ?></div>
        <div class="libelle">Dossiers civils distribués</div>
    </div>
    <div class="mesure vert">
        <div class="valeur"><?= (int) $synthese['nbConclusionsRedigees'] ?></div>
        <div class="libelle">Conclusions rédigées (pénal)</div>
    </div>
    <div class="mesure" style="border-left-color:var(--orange)">
        <div class="valeur"><?= count($synthese['civilsEnAttente']) ?></div>
        <div class="libelle">Civils en attente de frappe au secrétariat</div>
    </div>
</div>

<div class="carte">
    <h2>Dossiers pénaux distribués à <?= e($sigleChoisi) ?></h2>
    <?php if ($synthese['penaux'] === []): ?>
        <p style="color:var(--gris)">Aucun dossier pénal distribué à cet Avocat Général.</p>
    <?php else: ?>
        <div class="tableau-enveloppe">
            <table class="liste">
                <thead>
                    <tr><th>N° dossier PGCS</th><th>Conclusion</th><th>Audience</th><th>N° arrêt</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($synthese['penaux'] as $dossier): ?>
                        <tr>
                            <td><?= e($dossier['num_dossier_PGCS']) ?></td>
                            <td class="rogne"><?= !empty($dossier['conclusion']) ? badge_oui_non(true, 'Rédigée', 'Rédigée') : badge_oui_non(false, 'Rédigée', 'Non rédigée') ?></td>
                            <td><?= e(date_fr($dossier['date_audience'] ?? null)) ?></td>
                            <td><?= e($dossier['num_arret'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="carte">
    <h2>Dossiers civils en attente de frappe au secrétariat</h2>
    <?php if ($synthese['civilsEnAttente'] === []): ?>
        <p style="color:var(--gris)">Aucun dossier civil en attente pour cet Avocat Général.</p>
    <?php else: ?>
        <div class="tableau-enveloppe">
            <table class="liste">
                <thead><tr><th>N° dossier PGCS</th></tr></thead>
                <tbody>
                    <?php foreach ($synthese['civilsEnAttente'] as $dossier): ?>
                        <tr><td><?= e($dossier['num_dossier_PGCS']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>
