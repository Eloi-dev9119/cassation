<?php
/**
 * Liste des distributions (pénales ou civiles). Variables : $type,
 * $resultat, $recherche, $vueComplete.
 */
$vueComplete = $vueComplete ?? false;
$baseUrl = '/distributions'; // route interne brute, passée dans url() à l'affichage
$parametresVue = array_filter(['q' => $recherche, 'type' => $type, 'vue' => $vueComplete ? null : 'complet']);
?>
<div class="titre-page">
    <h1><?= $type === 'civ' ? 'Distributions Civiles' : 'Distributions Pénaux' ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?export=csv&' . http_build_query(array_filter(['q' => $recherche, 'type' => $type])))) ?>">Télécharger CSV</a>
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?type=' . ($type === 'civ' ? 'pen' : 'civ'))) ?>">
            Voir les distributions <?= $type === 'civ' ? 'Pénaux' : 'Civiles' ?>
        </a>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?' . http_build_query($parametresVue))) ?>"
           title="Basculer entre le contenu tronqué et tout le contenu de chaque distribution">
            <?= $vueComplete ? 'Vue compacte' : 'Voir tout le contenu' ?>
        </a>
        <a class="bouton bouton-vert" href="<?= e(url($baseUrl . '?a=nouvelle&type=' . $type)) ?>">+ Nouvelle liste</a>
    </div>
</div>

<div class="barre-filtres">
    <?php $actionFiltre = url($baseUrl); $routeFiltre = $baseUrl; $parametresSup = $vueComplete ? ['type' => $type, 'vue' => 'complet'] : ['type' => $type]; ?>
    <?php require APP_CHEMIN . '/app/views/partiels/filtres.php'; ?>
</div>

<div class="tableau-enveloppe">
    <table class="liste<?= $vueComplete ? ' vue-complete' : '' ?>">
        <thead>
            <tr>
                <th>Année</th><th>Liste</th><th>N° dossier PGCS</th><th>Parties</th>
                <th>Magistrat instructeur</th><th>Remise secrétariat</th><th>Remise AG</th>
                <th style="width:90px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultat['lignes'] === []): ?>
                <tr><td colspan="8" class="etat-vide">
                    <strong>Aucune distribution enregistrée</strong>
                    Aucune distribution ne correspond aux critères de recherche.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($resultat['lignes'] as $ligne): ?>
                <tr>
                    <td><?= (int) $ligne['annee'] ?></td>
                    <td><?= e($ligne['nom_liste'] ?? '—') ?></td>
                    <td><?= e($ligne['num_dossier_PGCS'] ?? '') ?></td>
                    <td class="rogne"><?= e($ligne['parties'] ?? '—') ?></td>
                    <td><?= e($ligne['sigle'] ?? '—') ?></td>
                    <td><?= e(date_fr($ligne['date_remise_secr'] ?? null)) ?></td>
                    <td><?= e(date_fr($ligne['date_remise_AG'] ?? null)) ?></td>
                    <td class="cellule-actions">
                        <a class="bouton bouton-mini bouton-secondaire" href="<?= e(url($baseUrl . '?a=modifier&id=' . (int) $ligne['id_distribution'] . '&type=' . $type)) ?>">Modifier</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$urlBase = $baseUrl;
$query   = http_build_query(array_filter(['q' => $recherche, 'type' => $type, 'vue' => $vueComplete ? 'complet' : null]));
$parametres = $query !== '' ? $query . '&' : '';
require APP_CHEMIN . '/app/views/partiels/pagination.php';
?>
