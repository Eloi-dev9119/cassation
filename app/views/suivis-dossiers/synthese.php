<?php
/**
 * Suivi des dossiers par Avocat Général. Variables : $ags,
 * $sigleChoisi, $synthese (null si aucun AG), $graphe,
 * $filtre (facultatif : conclusion-redigee | civils-attente).
 */

// Filtres ouverts par les cartes de mesures cliquables : chaque carte
// conduit à la liste correspondante, réduite au sous-ensemble annoncé.
$filtre = in_array($filtre ?? '', ['conclusion-redigee', 'civils-attente'], true) ? (string) $filtre : '';
$penauxAffiches = $filtre === 'conclusion-redigee'
    ? array_values(array_filter($synthese['penaux'] ?? [], fn (array $l): bool => !empty($l['conclusion'])))
    : $synthese['penaux'] ?? [];
$civilsAffiches = $filtre === 'civils-attente'
    ? $synthese['civilsEnAttente'] ?? []
    : $synthese['civils'] ?? [];
$lien = static fn (string $ancre, array $extra = []): string
    => url('/suivis-dossiers?' . http_build_query(array_merge(['ag' => $sigleChoisi], $extra))) . $ancre;
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
    <form method="get" action="<?= e(url('/suivis-dossiers')) ?>" class="filtres" style="margin-bottom:0" data-auto-filtre>
        <input type="hidden" name="r" value="/suivis-dossiers">
        <label>Avocat Général :</label>
        <select name="ag">
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
    <div class="carte"><p class="texte-doux">Aucun Avocat Général actif n'a été trouvé.</p></div>
<?php else: ?>

<div class="cartes-mesures">
    <a class="mesure bordeaux" href="<?= e($lien('#penaux')) ?>" title="Voir la liste des dossiers pénaux distribués">
        <div class="valeur"><?= (int) $synthese['nbPenaux'] ?></div>
        <div class="libelle">Dossiers pénaux distribués</div>
    </a>
    <a class="mesure" href="<?= e($lien('#civils')) ?>" title="Voir la liste des dossiers civils distribués">
        <div class="valeur"><?= (int) $synthese['nbCivils'] ?></div>
        <div class="libelle">Dossiers civils distribués</div>
    </a>
    <a class="mesure vert" href="<?= e($lien('#penaux', ['filtre' => 'conclusion-redigee'])) ?>" title="Voir les dossiers pénaux dont la conclusion est rédigée">
        <div class="valeur"><?= (int) $synthese['nbConclusionsRedigees'] ?></div>
        <div class="libelle">Conclusions rédigées (pénal)</div>
    </a>
    <a class="mesure orange" href="<?= e($lien('#civils', ['filtre' => 'civils-attente'])) ?>" title="Voir les civils en attente de frappe au secrétariat">
        <div class="valeur"><?= count($synthese['civilsEnAttente']) ?></div>
        <div class="libelle">Civils en attente de frappe au secrétariat</div>
    </a>
</div>

<div class="carte">
    <h2>Dossiers pour conclusion par Avocat Général</h2>
    <p class="texte-doux">Pénaux et civils distribués dont la conclusion n’a pas encore été transmise pour frappe au secrétariat.</p>
    <?= $graphe ?>
</div>

<div class="carte" id="penaux">
    <h2>Dossiers pénaux distribués à <?= e($sigleChoisi) ?> (<?= count($penauxAffiches) ?>)</h2>
    <?php if ($filtre === 'conclusion-redigee'): ?>
        <p class="texte-doux">Filtre actif : conclusions rédigées uniquement.
            <a href="<?= e($lien('#penaux')) ?>">Voir tous les dossiers pénaux</a></p>
    <?php endif; ?>
    <?php if ($penauxAffiches === []): ?>
        <p class="texte-doux"><?= $filtre === 'conclusion-redigee'
            ? 'Aucune conclusion rédigée pour les dossiers pénaux distribués à cet Avocat Général.'
            : 'Aucun dossier pénal distribué à cet Avocat Général.' ?></p>
    <?php else: ?>
        <div class="tableau-enveloppe">
            <table class="liste">
                <thead>
                    <tr><th>N° dossier PGCS</th><th>Conclusion</th><th>Audience</th><th>N° arrêt</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($penauxAffiches as $dossier): ?>
                        <tr>
                            <td><?= e($dossier['num_dossier_PGCS']) ?></td>
                            <td class="rogne"><?= !empty($dossier['conclusion']) ? badge_oui_non(true, 'Rédigée', 'Rédigée') : badge_oui_non(false, 'Rédigée', 'Non rédigée') ?></td>
                            <td><?= e(date_fr($dossier['date_audience'] ?? null)) ?></td>
                            <td><?= e($dossier['num_arret'] ?: '—') ?></td>
                            <td class="cellule-actions">
                                <?php if (!empty($dossier['id_reg_pourvoi'])): ?>
                                    <a class="bouton bouton-mini bouton-secondaire"
                                       href="<?= e(url('/registres/penaux/fiche?id=' . (int) $dossier['id_reg_pourvoi'])) ?>">Fiche</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="carte" id="civils">
    <h2>Dossiers civils distribués à <?= e($sigleChoisi) ?> (<?= count($civilsAffiches) ?>)</h2>
    <p class="texte-doux"><?= $filtre === 'civils-attente'
        ? 'Filtre actif : dossiers en attente de frappe uniquement. <a href="' . e($lien('#civils')) . '">Voir tous les dossiers civils</a>'
        : 'Liste complète des dossiers civils suivis : la colonne « Statut » indique si le dossier a été transmis pour frappe au secrétariat.' ?></p>
    <?php if ($civilsAffiches === []): ?>
        <p class="texte-doux"><?= $filtre === 'civils-attente'
            ? 'Aucun dossier civil en attente de frappe : tout a été transmis au secrétariat.'
            : 'Aucun dossier civil distribué à cet Avocat Général.' ?></p>
    <?php else: ?>
        <div class="tableau-enveloppe">
            <table class="liste">
                <thead>
                    <tr><th>N° dossier PGCS</th><th>Transmission pour frappe</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($civilsAffiches as $dossier):
                        $dateTransmission = $dossier['date_de_transmission_pour_frappe_au_secretariat'] ?? null;
                        $enAttente = $dateTransmission === null || $dateTransmission === '0000-00-00';
                    ?>
                        <tr>
                            <td><?= e($dossier['num_dossier_PGCS']) ?></td>
                            <td><?= e(date_fr($dateTransmission)) ?></td>
                            <td><?= $enAttente
                                ? '<span class="badge badge-orange">En attente de frappe</span>'
                                : '<span class="badge badge-vert">Transmis</span>' ?></td>
                            <td class="cellule-actions">
                                <?php if (!empty($dossier['id_reg_pourvoi'])): ?>
                                    <a class="bouton bouton-mini bouton-secondaire"
                                       href="<?= e(url('/registres/civils/fiche?id=' . (int) $dossier['id_reg_pourvoi'])) ?>">Fiche</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>
