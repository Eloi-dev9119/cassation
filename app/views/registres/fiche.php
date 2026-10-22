<?php
/**
 * Fiche détaillée d'un dossier : toutes les colonnes, conclusion (si le
 * registre en possède une) et mémoires rattachés au même dossier PGCS.
 * Variables : $definition, $code, $dossier, $memoires.
 */
$cle = $definition['cle'];
$baseUrl = '/registres/' . $code; // route interne brute, passée dans url() à l'affichage
$colonneConclusion = $definition['conclusion'];
?>
<div class="titre-page">
    <h1>Dossier <?= e((string) ($dossier['num_dossier_PGCS'] ?? $dossier[$cle])) ?></h1>
    <div class="actions">
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer la fiche</button>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl)) ?>">← Retour à la liste</a>
        <a class="bouton" href="<?= e(url($baseUrl . '/modifier?id=' . $dossier[$cle])) ?>">Modifier</a>
        <?php if ($colonneConclusion !== null): ?>
            <a class="bouton bouton-bordeaux" href="<?= e(url($baseUrl . '/conclusion?id=' . $dossier[$cle])) ?>">Éditer la conclusion</a>
        <?php endif; ?>
    </div>
</div>

<div class="carte">
    <h2><?= e($definition['libelle']) ?></h2>
    <dl class="fiche-definition">
        <?php foreach ($definition['colonnes'] as $colonne): ?>
            <?php if (($colonne['afficherFiche'] ?? true) === false) { continue; } ?>
            <?php $valeur = $dossier[$colonne['nom']] ?? ''; ?>
            <dt><?= e($colonne['libelle']) ?></dt>
            <dd>
                <?php
                echo match ($colonne['type']) {
                    'booleen'   => badge_oui_non(bin_vers_bool($valeur)),
                    'date'      => e(date_fr($valeur)),
                    'textelong' => nl2br(e($valeur !== '' ? $valeur : '—')),
                    default     => e($valeur !== '' ? $valeur : '—'),
                };
                ?>
            </dd>
        <?php endforeach; ?>
    </dl>
</div>

<?php if ($colonneConclusion !== null && trim((string) ($dossier[$colonneConclusion] ?? '')) !== ''): ?>
<div class="carte" style="border-left:4px solid var(--bordeaux, #7a2048)">
    <h2>Conclusion</h2>
    <p style="white-space:pre-line;margin:0"><?= nl2br(e((string) $dossier[$colonneConclusion])) ?></p>
</div>
<?php endif; ?>

<div class="carte" style="border-left:4px solid var(--orange)">
    <h2>Traçabilité</h2>
    <div class="grille-form">
        <div>
            <strong>Créé le</strong>
            <div><?= e(date_fr($dossier['date_creation'] ?? null)) ?>
                <?= !empty($dossier['date_creation']) && $dossier['date_creation'] !== '0000-00-00 00:00:00'
                    ? ' à ' . e(mb_substr((string) $dossier['date_creation'], 11, 8)) : '' ?></div>
        </div>
        <div>
            <strong>Créé par</strong>
            <div><?= e($dossier['cree_par'] ?? '—') ?></div>
        </div>
        <div>
            <strong>Modifié le</strong>
            <div><?= !empty($dossier['date_modification'])
                ? e(date_fr(mb_substr((string) $dossier['date_modification'], 0, 10))) . ' à ' . e(mb_substr((string) $dossier['date_modification'], 11, 8))
                : '—' ?></div>
        </div>
        <div>
            <strong>Modifié par</strong>
            <div><?= e($dossier['modifie_par'] ?? '—') ?></div>
        </div>
    </div>
    <p style="color:var(--gris);font-size:12px;margin-bottom:0">
        Les horodatages sont tenus par la base de données (visibles également dans phpMyAdmin) ;
        le nom du créateur et du dernier modificateur est renseigné par PGCC v2.
    </p>
</div>

<?php if (in_array($code, ["penaux", "civils"], true)): ?>
<div class="carte">
    <h2>Mémoires rattachés à ce dossier</h2>
    <?php if ($memoires === []): ?>
        <p style="color:var(--gris)">Aucun mémoire rattaché à ce numéro de dossier.</p>
        <a class="bouton bouton-mini bouton-secondaire"
           href="<?= e(url('/memoires/creer?dossier=' . urlencode((string) ($dossier['num_dossier_PGCS'] ?? '')) . '&origine=' . ($code === 'civils' ? 'civil' : 'penal') . '&source_id=' . (int) $dossier[$cle])) ?>">Enregistrer un mémoire</a>
    <?php else: ?>
        <table class="liste">
            <thead>
                <tr><th>N°</th><th>Type</th><th>Date dépôt</th><th>Avocat</th><th>État</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($memoires as $memoire): ?>
                    <tr>
                        <td><?= (int) $memoire['num_ordre'] ?>/<?= (int) $memoire['annee'] ?></td>
                        <td><?= e(MemoireModel::TYPES[$memoire['type_memoire']] ?? $memoire['type_memoire']) ?></td>
                        <td><?= e(date_fr($memoire['date_depot'])) ?></td>
                        <td><?= e($memoire['avocat'] ?: '—') ?></td>
                        <td><?= e(MemoireModel::ETATS[$memoire['etat']] ?? $memoire['etat']) ?></td>
                        <td><a class="bouton bouton-mini bouton-secondaire" href="<?= e(url('/memoires/modifier?id=' . $memoire['id_memoire'])) ?>">Voir</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($code === 'civils'): ?>
<div class="carte">
    <h2>Suivi au Siège</h2>
    <?php if (($siegesCivil ?? []) === []): ?>
        <p>Aucun dossier du Siège n'est encore lié à ce pourvoi civil.</p>
        <a class="bouton bouton-secondaire" href="<?= e(url('/registres/siege/creer?dossier_civil=' . (int) $dossier[$cle])) ?>">Créer le suivi au Siège</a>
    <?php else: ?>
        <ul>
            <?php foreach ($siegesCivil as $siegeCivil): ?>
                <li><a href="<?= e(url('/registres/siege/fiche?id=' . (int) $siegeCivil['id_civil'])) ?>">Dossier du Si?ge <?= e($siegeCivil['num_dossier']) ?> (<?= (int) $siegeCivil['annee'] ?>)</a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php elseif ($code === 'siege'): ?>
<div class="carte">
    <h2>Dossier civil rattaché</h2>
    <?php if (($dossierCivilLie ?? null) === null): ?>
        <p>Aucun dossier civil n'est encore associé. Modifiez cette fiche pour choisir le dossier correspondant.</p>
    <?php else: ?>
        <p><a href="<?= e(url('/registres/civils/fiche?id=' . (int) $dossierCivilLie['id_reg_pourvoi'])) ?>">
            Dossier <?= e($dossierCivilLie['num_dossier_PGCS']) ?> — <?= e($dossierCivilLie['cassationnaire'] ?: 'Parties non renseignées') ?>
        </a></p>
    <?php endif; ?>
</div>
<?php endif; ?>
