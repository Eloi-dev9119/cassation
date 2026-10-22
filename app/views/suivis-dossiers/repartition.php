<?php
/**
 * Répartition mensuelle des distributions par Avocat Général, avec
 * graphique. Variables : $mois (liste), $moisChoisi, $annee, $donnees.
 */
?>
<div class="titre-page">
    <h1>Répartition des dossiers</h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url('/repartition?export=csv&' . http_build_query(['mois' => $moisChoisi, 'annee' => $annee]))) ?>">Télécharger CSV</a>
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
    </div>
</div>

<div class="carte">
    <form method="get" action="<?= e(url('/repartition')) ?>" class="filtres" style="margin-bottom:0">
        <input type="hidden" name="r" value="/repartition">
        <label style="margin:0;font-weight:600;color:var(--ardoise)">Mois :</label>
        <select name="mois">
            <?php foreach ($mois as $m): ?>
                <option value="<?= e($m) ?>" <?= $moisChoisi === $m ? 'selected' : '' ?>><?= e($m) ?></option>
            <?php endforeach; ?>
        </select>
        <label style="margin:0;font-weight:600;color:var(--ardoise)">Année :</label>
        <input type="number" name="annee" min="1990" max="2100" value="<?= (int) $annee ?>" style="width:90px">
        <button type="submit" class="bouton">Afficher</button>
        <span class="compte">Le rattachement au mois se fait par le nom de la liste de distribution.</span>
    </form>
</div>

<div class="carte">
    <h2>Graphique — <?= e($moisChoisi) ?> <?= (int) $annee ?></h2>
    <?= \Graphiques::barresParAg($donnees['ags'], $donnees['penaux'], $donnees['civils'], $moisChoisi . ' ' . $annee) ?>
</div>

<div class="carte">
    <h2>Tableau de répartition — <?= e($moisChoisi) ?> <?= (int) $annee ?></h2>
    <?php if ($donnees['ags'] === []): ?>
        <p style="color:var(--gris)">Aucune distribution enregistrée.</p>
    <?php else: ?>
        <div class="tableau-enveloppe">
            <table class="liste">
                <thead>
                    <tr><th>Sigle AG</th><th>Pénaux</th><th>Civils</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <?php $totalPen = 0; $totalCiv = 0; foreach ($donnees['ags'] as $i => $sigle): ?>
                        <?php $totalPen += $donnees['penaux'][$i]; $totalCiv += $donnees['civils'][$i]; ?>
                        <tr>
                            <td><?= e($sigle) ?></td>
                            <td><?= (int) $donnees['penaux'][$i] ?></td>
                            <td><?= (int) $donnees['civils'][$i] ?></td>
                            <td><strong><?= (int) ($donnees['penaux'][$i] + $donnees['civils'][$i]) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td><strong><?= $totalPen ?></strong></td>
                        <td><strong><?= $totalCiv ?></strong></td>
                        <td><strong><?= $totalPen + $totalCiv ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
