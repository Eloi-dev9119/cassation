<?php
/**
 * Gestion des comptes utilisateurs. Variables : $filtre, $recherche, $comptes.
 */
$baseUrl = '/administration/comptes';
$onglets = [
    'tous'      => 'Tous',
    'actifs'    => 'Actifs',
    'retraites' => 'Retraités',
    'attente'   => 'En attente',
];
$jeton = Auth::jeton();
?>
<div class="titre-page">
    <h1>Comptes utilisateurs</h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url('/administration')) ?>">← Administration</a>
        <a class="bouton bouton-vert" href="<?= e(url($baseUrl . '/creer')) ?>">+ Créer un compte</a>
    </div>
</div>

<div class="filtres">
    <?php foreach ($onglets as $code => $libelle): ?>
        <a class="bouton <?= $filtre === $code ? '' : 'bouton-secondaire' ?>" href="<?= e(url($baseUrl . '?filtre=' . $code)) ?>"><?= e($libelle) ?></a>
    <?php endforeach; ?>
    <span class="compte"><?= count($comptes) ?> compte(s)</span>
</div>

<form class="filtres" method="get" action="<?= e(url($baseUrl)) ?>">
    <input type="hidden" name="r" value="<?= e($baseUrl) ?>">
    <input type="hidden" name="filtre" value="<?= e($filtre) ?>">
    <input type="search" name="q" placeholder="Nom, prénom, e-mail ou sigle…" value="<?= e($recherche) ?>">
    <button type="submit" class="bouton">Rechercher</button>
</form>

<div class="tableau-enveloppe">
    <table class="liste">
        <thead>
            <tr>
                <th>Nom</th><th>Prénom</th><th>E-mail</th><th>Poste</th><th>Sigle</th>
                <th>Matricule</th><th>Bureau</th><th>Statut</th><th style="width:240px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($comptes === []): ?>
                <tr><td colspan="9" style="text-align:center;color:var(--gris);padding:26px">Aucun compte ne correspond aux critères.</td></tr>
            <?php endif; ?>
            <?php foreach ($comptes as $compte): ?>
                <?php
                $id = (int) $compte['id_client'];
                $source = (string) ($compte['source'] ?? 'client');
                $valide = (int) $compte['valide'];
                $statut = (string) ($compte['status'] ?? '');
                ?>
                <tr>
                    <td><?= e($compte['nom']) ?></td>
                    <td><?= e($compte['prenom']) ?></td>
                    <td><?= e($compte['login']) ?></td>
                    <td><?= e(POSTES_UTILISATEUR[$compte['poste']] ?? ($compte['poste'] ?: '—')) ?></td>
                    <td><?= e($compte['sigle_AG'] ?: '—') ?></td>
                    <td><?= (int) $compte['IM'] ?></td>
                    <td><?= (int) $compte['num_bureau'] ?></td>
                    <td>
                        <?php if ($valide === 0): ?>
                            <span class="badge badge-orange">À valider</span>
                        <?php elseif ($statut === 'Retraite'): ?>
                            <span class="badge badge-gris">Retraité</span>
                        <?php else: ?>
                            <span class="badge badge-vert">Actif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($valide === 0): ?>
                            <form method="post" action="<?= e(url($baseUrl . '/action')) ?>" style="display:inline"
                                  data-confirm-title="Valider le compte"
                                  data-confirm="Le compte sera activé et son titulaire pourra se connecter à PGCC. Confirmez-vous la validation ?"
                                  data-confirm-label="Valider le compte">
                                <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="source" value="<?= e($source) ?>">
                                <button name="action" value="valider" class="bouton bouton-mini bouton-vert">Valider</button>
                            </form>
                            <?php if ($source === 'new'): ?>
                                <form method="post" action="<?= e(url($baseUrl . '/action')) ?>" style="display:inline"
                                      data-confirm-title="Refuser l’inscription"
                                      data-confirm="La demande sera définitivement supprimée. L’intéressé pourra refaire une demande s’il le souhaite. Confirmez-vous le refus ?"
                                      data-confirm-label="Refuser" data-confirm-tone="danger">
                                    <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <input type="hidden" name="source" value="new">
                                    <button name="action" value="refuser" class="bouton bouton-mini bouton-bordeaux">Refuser</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <a class="bouton bouton-mini bouton-secondaire" href="<?= e(url($baseUrl . '/modifier?id=' . $id)) ?>">Modifier</a>
                            <?php if ($statut === 'Retraite'): ?>
                                <form method="post" action="<?= e(url($baseUrl . '/action')) ?>" style="display:inline"
                                      data-confirm-title="Réactiver le membre"
                                      data-confirm="Le compte sera réactivé et son titulaire pourra de nouveau se connecter à PGCC. Confirmez-vous la réactivation ?"
                                      data-confirm-label="Réactiver">
                                    <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button name="action" value="reactiver" class="bouton bouton-mini bouton-vert">Réactiver</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= e(url($baseUrl . '/action')) ?>" style="display:inline"
                                      data-confirm-title="Mettre le membre à la retraite"
                                      data-confirm="Le membre perdra son accès à PGCC jusqu’à sa réactivation. Confirmez-vous cette mise à la retraite ?"
                                      data-confirm-label="Mettre à la retraite" data-confirm-tone="danger">
                                    <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button name="action" value="retraite" class="bouton bouton-mini bouton-orange">Retraite</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url($baseUrl . '/action')) ?>" style="display:inline"
                                  data-confirm-title="Réinitialiser le mot de passe"
                                  data-confirm="Le mot de passe sera remplacé par « pgcc » suivi de l’année en cours. L’ancien mot de passe ne fonctionnera plus. Confirmez-vous la réinitialisation ?"
                                  data-confirm-label="Réinitialiser" data-confirm-tone="danger">
                                <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button name="action" value="motdepasse" class="bouton bouton-mini bouton-secondaire">Réinit. mdp</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($filtre === 'attente'): ?>
    <div class="carte" style="margin-top:16px">
        <h2>Validation des inscriptions</h2>
        <p style="color:var(--gris)">La validation active le compte ; le refus supprime définitivement la demande.
        Un compte validé est immédiatement utilisable dans les deux applications PGCC.</p>
    </div>
<?php endif; ?>
