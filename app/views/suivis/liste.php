<?php
/**
 * Liste d'un suivi (321 ou 107). Variables : $type, $resultat, $recherche.
 */
$def = SuiviModel::definition($type);
$baseUrl = '/suivis/' . $type; // route interne brute, passée dans url() à l'affichage
?>
<div class="titre-page">
    <h1><?= e($def['libelle']) ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?export=csv&' . http_build_query(array_filter(['q' => $recherche])))) ?>">Télécharger CSV</a>
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
        <a class="bouton bouton-vert" href="<?= e(url($baseUrl . '/creer')) ?>">+ Nouveau suivi</a>
        <a class="bouton bouton-secondaire" href="<?= e(url('/suivis/' . ($type === '321' ? '107' : '321'))) ?>">
            Voir l'autre suivi
        </a>
    </div>
</div>

<div class="carte" style="padding:0;border:none;background:transparent">
    <?php $actionFiltre = url($baseUrl); $routeFiltre = $baseUrl; $parametresSup = []; ?>
    <?php require APP_CHEMIN . '/app/views/partiels/filtres.php'; ?>
</div>

<div class="tableau-enveloppe">
    <table class="liste">
        <thead>
        <tr>
            <?php if ($type === '321'): ?>
                <th>N° ordre</th><th>Année</th><th>Arrivée</th><th>N° dossier PGCS</th>
                <th>Détention</th><th>Date détention</th><th>Sigle AG</th><th>Localisation</th><th>Observation</th>
                <th style="width:110px">Actions</th>
            <?php else: ?>
                <th>N° ordre</th><th>Année</th><th>Transm. PGCS</th><th>Réception</th><th>N° dossier PGCS</th>
                <th>Détention</th><th>Sigle AG</th><th>Transmission</th><th>Observation</th><th style="width:110px">Actions</th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
            <?php if ($resultat['lignes'] === []): ?>
                <tr><td colspan="10" style="text-align:center;color:var(--gris);padding:26px">Aucun suivi ne correspond aux critères.</td></tr>
            <?php endif; ?>
            <?php foreach ($resultat['lignes'] as $ligne): ?>
                <tr>
                    <td><?= (int) $ligne['num_ordre'] ?></td>
                    <td><?= (int) $ligne['annee'] ?></td>
                    <?php if ($type === '321'): ?>
                        <td><?= e(date_fr($ligne['date_arrivee'] ?? null)) ?></td>
                        <td><?= e($ligne['num_dossier_PGCS'] ?? '') ?></td>
                        <td><?= badge_oui_non(bin_vers_bool($ligne['detention'] ?? null)) ?></td>
                        <td><?= e(date_fr($ligne['date_detention'] ?? null)) ?></td>
                        <td><?= e($ligne['sigle_AG'] ?? '—') ?></td>
                        <td><?= e($ligne['localisation'] ?? '—') ?></td>
                        <td class="rogne"><?= e($ligne['observation_suivi'] ?? '—') ?></td>
                        <td>
                            <form method="post" action="<?= e(url($baseUrl . '/archiver')) ?>" style="display:inline"
                                  data-confirm-title="<?= bin_vers_bool($ligne['archiver'] ?? null) ? 'Désarchiver le suivi' : 'Archiver le suivi' ?>"
                                  data-confirm="<?= bin_vers_bool($ligne['archiver'] ?? null) ? 'Le suivi sera réintégré dans la liste active. Confirmez-vous le désarchivage ?' : 'Le suivi sera archivé et restera conservé. Confirmez-vous son archivage ?' ?>"
                                  data-confirm-label="<?= bin_vers_bool($ligne['archiver'] ?? null) ? 'Désarchiver' : 'Archiver' ?>">
                                <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $ligne['id_suivi_arrivee'] ?>">
                                <button type="submit" class="bouton bouton-mini <?= bin_vers_bool($ligne['archiver'] ?? null) ? 'bouton-secondaire' : 'bouton-orange' ?>">
                                    <?= bin_vers_bool($ligne['archiver'] ?? null) ? 'Désarchiver' : 'Archiver' ?>
                                </button>
                            </form>
                        </td>
                    <?php else: ?>
                        <td><?= e(date_fr($ligne['date_transmission_au_PGCS'] ?? null)) ?></td>
                        <td><?= e(date_fr($ligne['date_reception'] ?? null)) ?></td>
                        <td><?= e($ligne['num_dossier_PGCS'] ?? '') ?></td>
                        <td><?= badge_oui_non(bin_vers_bool($ligne['detention'] ?? null)) ?></td>
                        <td><?= e($ligne['sigle_AG'] ?? '—') ?></td>
                        <td><?= e(date_fr($ligne['date_transmission'] ?? null)) ?></td>
                        <td class="rogne"><?= e($ligne['observation'] ?? '—') ?></td>
                        <td>
                            <form method="post" action="<?= e(url($baseUrl . '/archiver')) ?>" style="display:inline"
                                  data-confirm="<?= bin_vers_bool($ligne['archiver'] ?? null) ? 'Confirmer le d&eacute;sarchivage de ce suivi ?' : 'Confirmer l&#39;archivage de ce suivi ?' ?>">
                                <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $ligne['id_suivi_arrivee'] ?>">
                                <button type="submit" class="bouton bouton-mini <?= bin_vers_bool($ligne['archiver'] ?? null) ? 'bouton-secondaire' : 'bouton-orange' ?>">
                                    <?= bin_vers_bool($ligne['archiver'] ?? null) ? 'Désarchiver' : 'Archiver' ?>
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$urlBase = $baseUrl;
$query   = http_build_query(array_filter(['q' => $recherche]));
$parametres = $query !== '' ? $query . '&' : '';
require APP_CHEMIN . '/app/views/partiels/pagination.php';
?>
