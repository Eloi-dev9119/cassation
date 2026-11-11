<?php
/**
 * Liste du Registre des Mémoires. Variables : $resultat, $recherche,
 * $type, $etat, $vueComplete.
 */
$vueComplete = $vueComplete ?? false;
$baseUrl = '/memoires'; // route interne brute, passée dans url() à l'affichage
$parametresVue = array_filter(['q' => $recherche, 'type' => $type, 'etat' => $etat, 'vue' => $vueComplete ? null : 'complet']);
?>
<div class="titre-page">
    <h1>Registre des Mémoires</h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?export=csv&' . http_build_query(array_filter(['q' => $recherche, 'type' => $type, 'etat' => $etat])))) ?>">Télécharger CSV</a>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?export=pdf&' . http_build_query(array_filter(['q' => $recherche, 'type' => $type, 'etat' => $etat])))) ?>">Télécharger PDF</a>
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?' . http_build_query($parametresVue))) ?>"
           title="Basculer entre le contenu tronqué et tout le contenu de chaque mémoire">
            <?= $vueComplete ? 'Vue compacte' : 'Voir tout le contenu' ?>
        </a>
        <a class="bouton bouton-vert" href="<?= e(url($baseUrl . '/creer')) ?>">+ Nouveau mémoire</a>
    </div>
</div>

<div class="barre-filtres">
    <form class="filtres" method="get" action="<?= e(url($baseUrl)) ?>" data-auto-filtre role="search">
        <input type="hidden" name="r" value="<?= e($baseUrl) ?>">
        <?php if ($vueComplete): ?><input type="hidden" name="vue" value="complet"><?php endif; ?>
        <input type="search" name="q" placeholder="N° dossier, pièces, déposant, juridiction…" value="<?= e($recherche) ?>" aria-label="Rechercher un mémoire">
        <select name="type">
            <option value="">Tous les types</option>
            <?php foreach (MemoireModel::TYPES as $code => $libelle): ?>
                <option value="<?= e($code) ?>" <?= $type === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="etat">
            <option value="">Tous les états</option>
            <?php foreach (MemoireModel::ETATS as $code => $libelle): ?>
                <option value="<?= e($code) ?>" <?= $etat === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bouton">Filtrer</button>
        <span class="compte"><?= number_format((float) $resultat['total'], 0, ',', ' ') ?> mémoire(s)</span>
    </form>
</div>

<div class="tableau-enveloppe">
    <table class="liste<?= $vueComplete ? ' vue-complete' : '' ?>">
        <thead>
            <tr>
                <th>N° ordre</th><th>Année</th><th>Type</th><th>Origine</th><th>N° dossier PGCS</th>
                <th>Pièces</th><th>Déposant</th><th>Juridiction</th><th>Dépôt</th><th>État</th>
                <th style="width:210px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultat['lignes'] === []): ?>
                <tr><td colspan="11" class="etat-vide">
                    <strong>Aucun mémoire enregistré</strong>
                    Aucun mémoire ne correspond aux critères de recherche.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($resultat['lignes'] as $memoire): ?>
                <tr>
                    <td><?= (int) $memoire['num_ordre'] ?></td>
                    <td><?= (int) $memoire['annee'] ?></td>
                    <td><?= badge_oui_non($memoire['type_memoire'] === 'ampliatif', 'Ampliatif', 'Défense') ?></td>
                    <td><?= e(MemoireModel::ORIGINES[$memoire['origine']] ?? $memoire['origine']) ?></td>
                    <td><?= e($memoire['num_dossier_PGCS']) ?></td>
                    <td class="rogne"><?= e($memoire['parties'] ?: '—') ?></td>
                    <td class="rogne"><?= e($memoire['avocat'] ?: '—') ?></td>
                    <td class="rogne"><?= e($memoire['juridiction'] ?? '') !== '' ? e($memoire['juridiction']) : '—' ?></td>
                    <td><?= e(date_fr($memoire['date_depot'])) ?></td>
                    <td>
                        <span class="badge <?= match ($memoire['etat']) {
                            'depose' => 'badge-gris', 'transmis' => 'badge-bleu',
                            'rappore' => 'badge-orange', default => 'badge-vert',
                        } ?>"><?= e(MemoireModel::ETATS[$memoire['etat']] ?? $memoire['etat']) ?></span>
                    </td>
                    <td class="cellule-actions">
                        <?php
                        // Accès direct à la fiche du dossier source (registre
                        // pénal ou civil) lorsque le mémoire est rattaché.
                        $idDossierSource = (int) ($memoire['origine'] === 'civil'
                            ? ($memoire['id_reg_pourvoi_civ'] ?? 0)
                            : ($memoire['id_reg_pourvoi_pen'] ?? 0));
                        if ($idDossierSource > 0): ?>
                            <a class="bouton bouton-mini bouton-secondaire"
                               href="<?= e(url(($memoire['origine'] === 'civil' ? '/registres/civils' : '/registres/penaux') . '/fiche?id=' . $idDossierSource)) ?>">Fiche</a>
                        <?php endif; ?>
                        <a class="bouton bouton-mini bouton-secondaire" href="<?= e(url($baseUrl . '/modifier?id=' . $memoire['id_memoire'])) ?>">Modifier</a>
                        <form method="post" action="<?= e(url($baseUrl . '/etat?id=' . $memoire['id_memoire'])) ?>" style="display:inline">
                            <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                            <select name="etat"
                                    data-etat-initial="<?= e($memoire['etat']) ?>"
                                    data-confirm-change="Confirmez-vous le nouvel état du mémoire : {etat} ?" data-confirm-title="Modifier l’état du mémoire">
                                <?php foreach (MemoireModel::ETATS as $code => $libelle): ?>
                                    <option value="<?= e($code) ?>" <?= $memoire['etat'] === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <form method="post" action="<?= e(url($baseUrl . '/supprimer?id=' . $memoire['id_memoire'])) ?>" style="display:inline"
                              data-confirm="Le mémoire sera supprimé définitivement du registre. Cette action est irréversible. Confirmez-vous la suppression ?"
                              data-confirm-title="Supprimer le mémoire" data-confirm-label="Supprimer définitivement" data-confirm-tone="danger">
                            <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                            <button type="submit" class="bouton bouton-mini bouton-rouge">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$urlBase = $baseUrl;
$query   = http_build_query(array_filter(['q' => $recherche, 'type' => $type, 'etat' => $etat, 'vue' => $vueComplete ? 'complet' : null]));
$parametres = $query !== '' ? $query . '&' : '';
require APP_CHEMIN . '/app/views/partiels/pagination.php';
?>
