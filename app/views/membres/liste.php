<?php
/**
 * Gestion des membres. Variables : $filtre, $membres, $utilisateur.
 * Les actions d'administration (valider, retraite, réactiver,
 * réinitialiser le mot de passe) ne s'affichent que pour les postes
 * habilités (POSTES_ADMINISTRATION).
 */
$baseUrl = '/membres'; // route brute passée dans url()
$onglets = ['attente' => 'En attente', 'actifs' => 'Actifs', 'retraites' => 'Retraités'];
$administration = est_administration($utilisateur);
$nbColonnes = $administration ? 9 : 8;
?>
<div class="titre-page">
    <h1>Membres du Parquet</h1>
</div>

<div class="onglets">
    <?php foreach ($onglets as $code => $libelle): ?>
        <?php $urlOnglet = url($baseUrl . '/' . $code) . '?' . http_build_query(array_filter(['q' => $recherche ?? '', 'poste' => $poste ?? ''])); ?>
        <a class="bouton <?= $filtre === $code ? '' : 'bouton-secondaire' ?>" href="<?= e($urlOnglet) ?>"<?= $filtre === $code ? ' aria-current="page"' : '' ?>><?= e($libelle) ?></a>
    <?php endforeach; ?>
    <span class="compte"><?= count($membres) ?> membre(s)</span>
</div>

<form class="filtres" method="get" action="<?= e(url($baseUrl . '/' . $filtre)) ?>" data-auto-filtre role="search">
    <input type="hidden" name="r" value="<?= e($baseUrl . '/' . $filtre) ?>">
    <input type="search" name="q" placeholder="Nom, prénom, e-mail, poste, sigle…" value="<?= e($recherche ?? '') ?>" aria-label="Rechercher un membre">
    <select name="poste" aria-label="Filtrer par poste">
        <option value="">Tous les postes</option>
        <option value="AG"<?= ($poste ?? '') === 'AG' ? ' selected' : '' ?>>AG actifs</option>
        <option value="secretaire_AG"<?= ($poste ?? '') === 'secretaire_AG' ? ' selected' : '' ?>>ECD / Greffe</option>
    </select>
    <button type="submit" class="bouton">Filtrer</button>
</form>

<div class="tableau-enveloppe">
    <table class="liste">
        <thead>
            <tr>
                <th>Nom</th><th>Prénom</th><th>E-mail</th><th>Poste</th><th>Sigle</th>
                <th>Matricule</th><th>Bureau</th><th>Statut</th>
                <?php if ($administration): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($membres === []): ?>
                <tr><td colspan="<?= $nbColonnes ?>" class="etat-vide">
                    <?php if (($recherche ?? '') !== ''): ?>
                        <strong>Aucun résultat</strong>
                        Aucun membre ne correspond à « <?= e($recherche) ?> ».
                    <?php else: ?>
                        <strong>Aucun membre</strong>
                        Aucun membre ne figure dans cette catégorie.
                    <?php endif; ?>
                </td></tr>
            <?php endif; ?>
            <?php foreach ($membres as $membre): ?>
                <tr>
                    <td><?= e($membre['nom']) ?> <?= badge_presence($presence[(int) $membre['id_client']] ?? null) ?></td>
                    <td><?= e($membre['prenom']) ?></td>
                    <td><?= e($membre['login']) ?></td>
                    <td><?= e($membre['poste'] ?: '—') ?></td>
                    <td><?= e($membre['sigle_AG'] ?: '—') ?></td>
                    <td><?= (int) $membre['IM'] ?></td>
                    <td><?= (int) $membre['num_bureau'] ?></td>
                    <td>
                        <?php if ((int) $membre['valide'] === 0): ?>
                            <span class="badge badge-orange">À valider</span>
                        <?php elseif ($membre['status'] === 'Retraite'): ?>
                            <span class="badge badge-gris">Retraité</span>
                        <?php else: ?>
                            <span class="badge badge-vert">Actif</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($administration): ?>
                    <td class="cellule-actions">
                        <?php if ($filtre === 'attente'): ?>
                            <form method="post" action="<?= e(url($baseUrl . '/attente')) ?>" style="display:inline"
                                  data-confirm-title="Valider l’inscription"
                                  data-confirm="Le compte sera activé et son titulaire pourra se connecter à PGCC. Confirmez-vous la validation ?"
                                  data-confirm-label="Valider le compte">
                                <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                                <input type="hidden" name="q" value="<?= e($recherche ?? '') ?>">
                                <input type="hidden" name="id" value="<?= (int) $membre['id_client'] ?>">
                                <input type="hidden" name="source" value="<?= e($membre['source'] ?? 'client') ?>">
                                <button name="action" value="valider" class="bouton bouton-mini bouton-vert">Valider</button>
                            </form>
                            <?php if (($membre['source'] ?? 'client') === 'new'): ?>
                            <form method="post" action="<?= e(url($baseUrl . '/attente')) ?>" style="display:inline"
                                  data-confirm-title="Refuser l’inscription"
                                  data-confirm="La demande sera définitivement supprimée. L’intéressé pourra refaire une demande s’il le souhaite. Confirmez-vous le refus ?"
                                  data-confirm-label="Refuser" data-confirm-tone="danger">
                                <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                                <input type="hidden" name="q" value="<?= e($recherche ?? '') ?>">
                                <input type="hidden" name="id" value="<?= (int) $membre['id_client'] ?>">
                                <input type="hidden" name="source" value="<?= e($membre['source'] ?? 'client') ?>">
                                <button name="action" value="refuser" class="bouton bouton-mini bouton-bordeaux">Refuser</button>
                            </form>
                            <?php endif; ?>
                        <?php elseif ($filtre === 'actifs'): ?>
                            <form method="post" action="<?= e(url($baseUrl . '/actifs')) ?>" style="display:inline"
                                  data-confirm-title="Mettre le membre à la retraite"
                                  data-confirm="Le membre perdra son accès à PGCC jusqu’à sa réactivation. Confirmez-vous cette mise à la retraite ?"
                                  data-confirm-label="Mettre à la retraite" data-confirm-tone="danger">
                                <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                                <input type="hidden" name="q" value="<?= e($recherche ?? '') ?>">
                                <input type="hidden" name="id" value="<?= (int) $membre['id_client'] ?>">
                                <button name="action" value="retraite" class="bouton bouton-mini bouton-orange">Retraite</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= e(url($baseUrl . '/retraites')) ?>" style="display:inline"
                                  data-confirm-title="Réactiver le membre"
                                  data-confirm="Le compte sera réactivé et son titulaire pourra de nouveau se connecter à PGCC. Confirmez-vous la réactivation ?"
                                  data-confirm-label="Réactiver">
                                <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                                <input type="hidden" name="q" value="<?= e($recherche ?? '') ?>">
                                <input type="hidden" name="id" value="<?= (int) $membre['id_client'] ?>">
                                <button name="action" value="reactiver" class="bouton bouton-mini bouton-vert">Réactiver</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($filtre !== 'attente'): ?>
                        <form method="post" action="<?= e(url($baseUrl . '/' . $filtre)) ?>" style="display:inline"
                              data-confirm-title="Réinitialiser le mot de passe"
                              data-confirm="Le mot de passe sera remplacé par « pgcc » suivi de l’année en cours. L’ancien mot de passe ne fonctionnera plus. Confirmez-vous la réinitialisation ?"
                              data-confirm-label="Réinitialiser" data-confirm-tone="danger">
                            <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
                                <input type="hidden" name="q" value="<?= e($recherche ?? '') ?>">
                            <input type="hidden" name="id" value="<?= (int) $membre['id_client'] ?>">
                            <button name="action" value="motdepasse" class="bouton bouton-mini bouton-secondaire">Réinit. mdp</button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($filtre === 'attente' && $membres !== [] && $administration): ?>
    <div class="carte carte-espacee">
        <h2>Validation des inscriptions en attente</h2>
        <p class="texte-doux">La validation active le compte : le membre pourra se connecter
        aux deux applications PGCC avec l'identifiant et le mot de passe saisis lors de son inscription.</p>
    </div>
<?php elseif ($filtre === 'attente' && $membres !== []): ?>
    <div class="carte carte-espacee">
        <h2>Validation des inscriptions en attente</h2>
        <p class="texte-doux">Seule l'administration du Parquet (postes <?= e(implode(', ', POSTES_ADMINISTRATION)) ?>)
        peut valider les inscriptions, mettre à la retraite ou réinitialiser un mot de passe.</p>
    </div>
<?php endif; ?>
