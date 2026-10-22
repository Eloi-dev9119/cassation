<?php
/**
 * Liste des distributions (pénales ou civiles). Variables : $type,
 * $resultat, $recherche.
 */
$baseUrl = '/distributions'; // route interne brute, passée dans url() à l'affichage
?>
<div class="titre-page">
    <h1><?= $type === 'civ' ? 'Distributions Civiles' : 'Distributions Pénaux' ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?export=csv&' . http_build_query(array_filter(['q' => $recherche, 'type' => $type])))) ?>">Télécharger CSV</a>
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?type=' . ($type === 'civ' ? 'pen' : 'civ'))) ?>">
            Voir les distributions <?= $type === 'civ' ? 'Pénaux' : 'Civiles' ?>
        </a>
        <a class="bouton bouton-vert" href="<?= e(url($baseUrl . '?a=nouvelle&type=' . $type)) ?>">+ Nouvelle liste</a>
    </div>
</div>

<div class="carte" style="padding:0;border:none;background:transparent">
    <?php $actionFiltre = url($baseUrl); $routeFiltre = $baseUrl; $parametresSup = ['type' => $type]; ?>
    <?php require APP_CHEMIN . '/app/views/partiels/filtres.php'; ?>
</div>

<div class="tableau-enveloppe">
    <table class="liste">
        <thead>
            <tr>
                <th>Année</th><th>Liste</th><th>N° dossier PGCS</th><th>Parties</th>
                <th>Sigle AG</th><th>Remise secrétariat</th><th>Remise AG</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultat['lignes'] === []): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--gris);padding:26px">Aucune distribution enregistrée.</td></tr>
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
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$urlBase = $baseUrl;
$query   = http_build_query(array_filter(['q' => $recherche, 'type' => $type]));
$parametres = $query !== '' ? $query . '&' : '';
require APP_CHEMIN . '/app/views/partiels/pagination.php';
?>
