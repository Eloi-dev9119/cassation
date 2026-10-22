<?php
/**
 * Génération d'une nouvelle liste de distribution : sélection des
 * dossiers pénaux non distribués, sigle de l'AG et nom de liste.
 * Variables : $dossiers, $recherche, $erreurs.
 */
$baseUrl = '/distributions'; // route interne brute, passée dans url() à l'affichage
?>
<div class="titre-page">
    <h1>Nouvelle liste de distribution</h1>
    <div class="actions"><a class="bouton bouton-secondaire" href="<?= e(url($baseUrl)) ?>">← Retour aux distributions</a></div>
</div>

<?php if ($erreurs !== []): ?>
    <div class="alerte alerte-erreur" role="alert" aria-live="assertive">
        <ul style="margin:0;padding-left:18px">
            <?php foreach ($erreurs as $message): ?><li><?= e($message) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="carte">
    <h2>Filtrer les dossiers</h2>
    <form method="get" action="<?= e(url($baseUrl)) ?>" class="filtres" style="margin-bottom:6px">
        <input type="hidden" name="r" value="<?= e($baseUrl) ?>">
        <input type="hidden" name="a" value="nouvelle">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="search" name="q" placeholder="Filtrer les dossiers (n°, cassationnaire)…" value="<?= e($recherche) ?>">
        <button type="submit" class="bouton bouton-secondaire">Rechercher</button>
    </form>

    <form method="post" action="<?= e(url($baseUrl . '?type=' . $type . '&a=nouvelle')) ?>"
          data-confirm-title="Créer la liste de distribution"
          data-confirm="La liste sera créée et les dossiers cochés seront attribués à l’Avocat Général indiqué. Confirmez-vous la distribution ?"
          data-confirm-label="Créer et distribuer">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <input type="hidden" name="type_dossier" value="<?= e($type) ?>">

        <h2 style="font-size:14px;color:var(--ardoise)">Identification de la liste</h2>
        <div class="grille-form" style="margin-bottom:14px">
            <div class="champ">
                <label>Nom de la liste *</label>
                <input type="text" name="nom_liste" required placeholder="Ex. : Liste A — 2e trimestre">
            </div>
            <div class="champ">
                <label>Année *</label>
                <input type="number" name="annee" min="1990" max="2100" required value="<?= e(date('Y')) ?>">
            </div>
            <div class="champ">
                <label>Sigle de l'Avocat Général *</label>
                <input type="text" name="sigle" required placeholder="Ex. : HRY">
            </div>
            <div class="champ">
                <label>Date de remise au secrétariat</label>
                <input type="date" name="date_remise_secr">
            </div>
        </div>

        <h2 style="font-size:14px;color:var(--ardoise)">
            Dossiers <?= $type === 'civ' ? 'civils' : 'pénaux' ?> non distribués
            <span style="color:var(--gris);font-weight:normal">(200 au maximum)</span></h2>

        <?php if ($dossiers === []): ?>
            <p style="color:var(--gris)">Aucun dossier non distribué ne correspond à la recherche.</p>
        <?php else: ?>
            <div class="tableau-enveloppe" style="margin-bottom:14px;max-height:420px;overflow:auto">
                <table class="liste">
                    <thead>
                        <tr>
                            <th style="width:36px"></th>
                            <th>N° ordre</th><th>Année</th><th>N° dossier PGCS</th><th>Cassationnaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dossiers as $dossier): ?>
                            <tr>
                                <td><input type="checkbox" name="dossiers[]" value="<?= (int) $dossier['id_reg_pourvoi'] ?>"></td>
                                <td><?= (int) $dossier['num_ordre'] ?></td>
                                <td><?= (int) $dossier['annee'] ?></td>
                                <td><?= e($dossier['num_dossier_PGCS']) ?></td>
                                <td class="rogne"><?= e($dossier['cassationnaire'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="bouton bouton-vert">Créer la liste avec les dossiers cochés</button>
        <?php endif; ?>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl)) ?>">Annuler</a>
    </form>
</div>
