<?php
/**
 * Liste d'un registre (générée depuis la définition déclarative).
 * Variables : $definition, $code, $resultat, $recherche, $annee, $annees,
 * $vueComplete (true : tout le contenu affiché sans troncature).
 */
$vueComplete = $vueComplete ?? false;
$detention = $detention ?? false;
$afficherMemoires = $afficherMemoires ?? false;
$memoiresParDossier = $memoiresParDossier ?? [];
$colonnesListe = registre_colonnes($definition, 'liste');
$cleDossier = $definition['cle'];
$baseUrl = '/registres/' . $code; // route interne brute, passée dans url() à l'affichage
$ongletsRegistres = array_map(
    static fn (array $def): string => $def['libelle'],
    registres_definitions()
);
// Paramètres de la bascule de vue (conserve recherche/année/détention en cours)
$parametresVue = array_filter(['q' => $recherche, 'annee' => $annee, 'detention' => $detention ? '1' : null, 'vue' => $vueComplete ? null : 'complet']);
?>
<div class="titre-page">
    <h1><?= e($definition['libelle']) ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?export=csv&' . http_build_query(array_filter(['q' => $recherche, 'annee' => $annee, 'detention' => $detention ? '1' : null])))) ?>">Télécharger CSV</a>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?export=pdf&' . http_build_query(array_filter(['q' => $recherche, 'annee' => $annee, 'detention' => $detention ? '1' : null])))) ?>">Télécharger PDF</a>
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?' . http_build_query($parametresVue))) ?>"
           title="Basculer entre le contenu tronqué et tout le contenu de chaque dossier">
            <?= $vueComplete ? 'Vue compacte' : 'Voir tout le contenu' ?>
        </a>
        <a class="bouton bouton-vert" href="<?= e(url($baseUrl . '/creer')) ?>">+ Nouveau dossier</a>
    </div>
</div>

<nav class="onglets" aria-label="Choisir un registre">
    <?php foreach ($ongletsRegistres as $codeRegistre => $libelleRegistre): ?>
        <a class="bouton <?= $codeRegistre === $code ? '' : 'bouton-secondaire' ?>" href="<?= e(url('/registres/' . $codeRegistre)) ?>"<?= $codeRegistre === $code ? ' aria-current="page"' : '' ?>><?= e($libelleRegistre) ?></a>
    <?php endforeach; ?>
</nav>

<div class="barre-filtres">
    <?php
    $actionFiltre = url($baseUrl);
    $routeFiltre  = $baseUrl; // le « r » voyage en champ caché (GET remplace la query de l'action)
    // La vue complète survit à une nouvelle recherche ou à un changement d'année.
    $parametresSup = $vueComplete ? ['vue' => 'complet'] : [];
    require APP_CHEMIN . '/app/views/partiels/filtres.php';
    ?>
</div>

<?php if ($detention && in_array('detention', array_column($definition['colonnes'], 'nom'), true)): ?>
    <p class="filtre-actif">
        Filtre actif : <strong>dossiers en détention</strong>.
        <a href="<?= e(url($baseUrl)) ?>">Afficher tous les dossiers</a>
    </p>
<?php endif; ?>

<div class="tableau-enveloppe">
    <table class="liste<?= $vueComplete ? ' vue-complete' : '' ?>">
        <thead>
            <tr>
                <?php foreach ($colonnesListe as $colonne): ?>
                    <th><?= e($colonne['libelle']) ?></th>
                <?php endforeach; ?>
                <?php if ($afficherMemoires): ?><th title="Mémoires rattachés à ce dossier">Mémoires</th><?php endif; ?>
                <th style="width:170px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultat['lignes'] === []): ?>
                <tr><td colspan="<?= count($colonnesListe) + 1 + ($afficherMemoires ? 1 : 0) ?>" class="etat-vide">
                    <strong>Aucun dossier trouvé</strong>
                    Aucun dossier ne correspond aux critères de recherche.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($resultat['lignes'] as $ligne): ?>
                <?php
                // La conclusion existe-t-elle pour ce dossier ? (registres
                // qui en possèdent une : pénaux, civils, déssaisissements)
                $aConclusion = $definition['conclusion'] !== null
                    && trim(strip_tags((string) ($ligne[$definition['conclusion']] ?? ''))) !== '';
                ?>
                <tr>
                    <?php foreach ($colonnesListe as $colonne): ?>
                        <?php
                        $valeur = $ligne[$colonne['nom']] ?? '';
                        // Colonne B.E. : contenu affiché sur une seule ligne
                        // (format horizontal), sans troncature, même en vue
                        // compacte. Concerne « BE » (civils) et « B.E Porte 05 »
                        // (pénaux, champ dispositif_arret_CA).
                        $estColonneBE = $colonne['nom'] === 'BE'
                            || str_contains($colonne['libelle'], 'B.E');
                        // Infobulle : le texte complet est consultable au
                        // survol ; en vue complète, tout s'affiche en clair.
                        $brut = trim(strip_tags((string) $valeur));
                        $complet = in_array($colonne['type'], ['texte', 'textelong'], true) ? $brut : '';
                        $titre = $complet !== '' && !$vueComplete && !$estColonneBE ? ' title="' . e($complet) . '"' : '';
                        ?>
                        <td class="<?= $colonne['type'] === 'textelong' && !$vueComplete && !$estColonneBE ? 'rogne ' : '' ?><?= $colonne['nom'] === 'cassationnaire' ? 'colonne-parties ' : '' ?><?= $estColonneBE ? 'colonne-be' : '' ?>"<?= $titre ?>>
                            <?php
                            echo match ($colonne['type']) {
                                'booleen' => badge_oui_non(bin_vers_bool($valeur)),
                                'date'    => e(date_fr($valeur)),
                                'textelong' => e($estColonneBE || $vueComplete ? $brut : mb_substr($brut, 0, 80)),
                                default   => e($valeur),
                            };
                            // Le bouton d'accès à la conclusion se trouve dans
                            // la case « Conclusion » elle-même : vert si une
                            // conclusion est rédigée, bordeaux si à rédiger.
                            if ($colonne['nom'] === $definition['conclusion']) {
                                echo '<div style="margin-top:4px"><a class="bouton bouton-mini ' . ($aConclusion ? 'bouton-vert' : 'bouton-bordeaux') . '"'
                                    . ' href="' . e(url($baseUrl . '/conclusion?id=' . $ligne[$cleDossier])) . '">Conclusion</a></div>';
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <?php if ($afficherMemoires): ?>
                        <?php $nbMemoires = (int) ($memoiresParDossier[(int) $ligne[$cleDossier]] ?? 0); ?>
                        <td>
                            <?php if ($nbMemoires > 0): ?>
                                <span class="badge badge-vert" title="<?= $nbMemoires ?> mémoire(s) rattaché(s) à ce dossier"><?= $nbMemoires ?></span>
                            <?php else: ?>
                                <span class="texte-doux" title="Aucun mémoire rattaché à ce dossier">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td class="cellule-actions">
                        <a class="bouton bouton-mini bouton-secondaire" href="<?= e(url($baseUrl . '/fiche?id=' . $ligne[$cleDossier])) ?>">Fiche</a>
                        <a class="bouton bouton-mini bouton-secondaire" href="<?= e(url($baseUrl . '/modifier?id=' . $ligne[$cleDossier])) ?>">Modifier</a>
                        <form method="post" action="<?= e(url($baseUrl . '/archiver?id=' . $ligne[$cleDossier])) ?>" style="display:inline"
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
$query      = http_build_query(array_filter(['q' => $recherche, 'annee' => $annee, 'detention' => $detention ? '1' : null]));
$parametres = $query !== '' ? $query . '&' : '';
require APP_CHEMIN . '/app/views/partiels/pagination.php';
?>
