<?php
/**
 * Liste du Registre des Mémoires. Variables : $resultat, $recherche,
 * $type, $etat.
 */
$baseUrl = '/memoires'; // route interne brute, passée dans url() à l'affichage
?>
<div class="titre-page">
    <h1>Registre des Mémoires</h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?export=csv&' . http_build_query(array_filter(['q' => $recherche, 'type' => $type, 'etat' => $etat])))) ?>">Télécharger CSV</a>
        <button class="bouton bouton-secondaire" type="button" onclick="window.print()">Imprimer</button>
        <a class="bouton bouton-vert" href="<?= e(url($baseUrl . '/creer')) ?>">+ Nouveau mémoire</a>
    </div>
</div>

<div class="carte" style="padding:0;border:none;background:transparent">
    <form class="filtres" method="get" action="<?= e(url($baseUrl)) ?>">
        <input type="hidden" name="r" value="<?= e($baseUrl) ?>">
        <input type="search" name="q" placeholder="N° dossier, parties, avocat…" value="<?= e($recherche) ?>">
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
    <table class="liste">
        <thead>
            <tr>
                <th>N° ordre</th><th>Année</th><th>Type</th><th>Origine</th><th>N° dossier PGCS</th>
                <th>Parties</th><th>Avocat</th><th>Dépôt</th><th>Rapporteur</th><th>État</th>
                <th style="width:210px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultat['lignes'] === []): ?>
                <tr><td colspan="11" style="text-align:center;color:var(--gris);padding:26px">Aucun mémoire enregistré.</td></tr>
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
                    <td><?= e(date_fr($memoire['date_depot'])) ?></td>
                    <td class="rogne"><?= e($memoire['rapporteur'] ?: '—') ?></td>
                    <td>
                        <span class="badge <?= match ($memoire['etat']) {
                            'depose' => 'badge-gris', 'transmis' => 'badge-bleu',
                            'rappore' => 'badge-orange', default => 'badge-vert',
                        } ?>"><?= e(MemoireModel::ETATS[$memoire['etat']] ?? $memoire['etat']) ?></span>
                    </td>
                    <td>
                        <a class="bouton bouton-mini bouton-secondaire" href="<?= e(url($baseUrl . '/modifier?id=' . $memoire['id_memoire'])) ?>">Modifier</a>
                        <form method="post" action="<?= e(url($baseUrl . '/etat?id=' . $memoire['id_memoire'])) ?>" style="display:inline">
                            <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                            <select name="etat"
                                    data-etat-initial="<?= e($memoire['etat']) ?>"
                                    data-confirm-change="Confirmez-vous le nouvel état du mémoire : {etat} ?" data-confirm-title="Modifier l’état du mémoire"
                                    style="padding:3px 6px;font-size:12px">
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
$query   = http_build_query(array_filter(['q' => $recherche, 'type' => $type, 'etat' => $etat]));
$parametres = $query !== '' ? $query . '&' : '';
require APP_CHEMIN . '/app/views/partiels/pagination.php';
?>
