<?php
/**
 * Liste d'un registre (générée depuis la définition déclarative).
 * Variables : $definition, $code, $resultat, $recherche, $annee, $annees.
 */
$colonnesListe = registre_colonnes($definition, 'liste');
$cle = $definition['cle'];
$baseUrl = '/registres/' . $code; // route interne brute, passée dans url() à l'affichage
?>
<div class="titre-page">
    <h1><?= e($definition['libelle']) ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?export=csv&' . http_build_query(array_filter(['q' => $recherche, 'annee' => $annee])))) ?>">Télécharger CSV</a>
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
        <a class="bouton bouton-vert" href="<?= e(url($baseUrl . '/creer')) ?>">+ Nouveau dossier</a>
    </div>
</div>

<div class="carte" style="padding:0;border:none;background:transparent">
    <?php
    $actionFiltre = url($baseUrl);
    $routeFiltre  = $baseUrl; // le « r » voyage en champ caché (GET remplace la query de l'action)
    $parametresSup = [];
    require APP_CHEMIN . '/app/views/partiels/filtres.php';
    ?>
</div>

<div class="tableau-enveloppe">
    <table class="liste">
        <thead>
            <tr>
                <?php foreach ($colonnesListe as $colonne): ?>
                    <th><?= e($colonne['libelle']) ?></th>
                <?php endforeach; ?>
                <th style="width:170px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultat['lignes'] === []): ?>
                <tr><td colspan="<?= count($colonnesListe) + 1 ?>" style="text-align:center;color:var(--gris);padding:26px">
                    Aucun dossier ne correspond aux critères.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($resultat['lignes'] as $ligne): ?>
                <tr>
                    <?php foreach ($colonnesListe as $colonne): ?>
                        <td class="<?= $colonne['type'] === 'textelong' ? 'rogne' : '' ?>">
                            <?php
                            $valeur = $ligne[$colonne['nom']] ?? '';
                            echo match ($colonne['type']) {
                                'booleen' => badge_oui_non(bin_vers_bool($valeur)),
                                'date'    => e(date_fr($valeur)),
                                'textelong' => e(mb_substr(strip_tags((string) $valeur), 0, 80)),
                                default   => e($valeur),
                            };
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <a class="bouton bouton-mini bouton-secondaire" href="<?= e(url($baseUrl . '/fiche?id=' . $ligne[$cle])) ?>">Fiche</a>
                        <a class="bouton bouton-mini bouton-secondaire" href="<?= e(url($baseUrl . '/modifier?id=' . $ligne[$cle])) ?>">Modifier</a>
                        <form method="post" action="<?= e(url($baseUrl . '/archiver?id=' . $ligne[$cle])) ?>" style="display:inline"
                              data-confirm-title="<?= bin_vers_bool($ligne[registre_colonne_archive($definition)] ?? null) ? 'Désarchiver le dossier' : 'Archiver le dossier' ?>"
                              data-confirm="<?= bin_vers_bool($ligne[registre_colonne_archive($definition)] ?? null) ? 'Le dossier sera réintégré dans la liste active. Confirmez-vous le désarchivage ?' : 'Le dossier sera archivé et restera conservé. Confirmez-vous son archivage ?' ?>"
                              data-confirm-label="<?= bin_vers_bool($ligne[registre_colonne_archive($definition)] ?? null) ? 'Désarchiver' : 'Archiver' ?>">
                            <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                            <button type="submit" class="bouton bouton-mini bouton-orange">
                                <?= bin_vers_bool($ligne[registre_colonne_archive($definition)] ?? null) ? 'Désarchiver' : 'Archiver' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$urlBase    = $baseUrl;
$query      = http_build_query(array_filter(['q' => $recherche, 'annee' => $annee]));
$parametres = $query !== '' ? $query . '&' : '';
require APP_CHEMIN . '/app/views/partiels/pagination.php';
?>
