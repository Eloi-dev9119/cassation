<?php
/**
 * Formulaire création / modification d'un mémoire.
 * Variables : $memoire (null en création), $erreurs, $prefillDossier (n°
 * de dossier pré-rempli en création, depuis la fiche d'un dossier).
 */
$enCreation = $memoire === null;
$valeursFormulaire = $valeursFormulaire ?? [];
$idDossierSourceSelectionne = $idDossierSourceSelectionne ?? 0;
$v = fn (string $champ, mixed $defaut = '') => e($valeursFormulaire[$champ] ?? $memoire[$champ] ?? $defaut);
$origineSelectionnee = $valeursFormulaire['origine'] ?? ($memoire['origine'] ?? ($prefillOrigine ?? 'penal'));
$numeroMemoire = !$enCreation
    ? (int) ($memoire['num_ordre'] ?? 0) . '/' . (int) ($memoire['annee'] ?? 0)
    : '';
$confirmationTitre = $enCreation ? 'Ajouter un mémoire' : 'Enregistrer les modifications';
$confirmationMessage = $enCreation
    ? 'Le mémoire sera ajouté au registre. Confirmez-vous son enregistrement ?'
    : 'Les modifications du mémoire n° ' . $numeroMemoire . ' seront enregistrées. Confirmez-vous cette mise à jour ?';
$confirmationBouton = $enCreation ? 'Ajouter le mémoire' : 'Enregistrer les modifications';
?>
<div class="titre-page">
    <h1><?= $enCreation ? 'Nouveau mémoire' : 'Modifier le mémoire n° ' . (int) ($memoire['num_ordre'] ?? 0) ?></h1>
    <div class="actions"><a class="bouton bouton-secondaire" href="<?= e(url('/memoires')) ?>">← Retour à la liste</a></div>
</div>

<?php if (!$enCreation && !empty($memoire['date_creation'])): ?>
    <div class="alerte alerte-info">
        Traçabilité — créé le <?= e(mb_substr((string) $memoire['date_creation'], 0, 16)) ?>
        par <?= e($memoire['cree_par'] ?: '—') ?>
        <?php if (!empty($memoire['date_modification'])): ?>
            · dernière modification le <?= e(mb_substr((string) $memoire['date_modification'], 0, 16)) ?>
            par <?= e($memoire['modifie_par'] ?: '—') ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($erreurs !== []): ?>
    <div class="alerte alerte-erreur" role="alert" aria-live="assertive">
        <ul style="margin:0;padding-left:18px">
            <?php foreach ($erreurs as $message): ?><li><?= e($message) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="carte">
    <form method="post" action="<?= e($enCreation ? url('/memoires/creer') : url('/memoires/modifier?id=' . $memoire['id_memoire'])) ?>"
          data-confirm-title="<?= e($confirmationTitre) ?>"
          data-confirm="<?= e($confirmationMessage) ?>"
          data-confirm-label="<?= e($confirmationBouton) ?>">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <div class="grille-form">
            <div class="champ">
                <label>Année *</label>
                <input type="number" name="annee" min="1990" max="2100" required
                       value="<?= $v('annee', date('Y')) ?>">
            </div>
            <div class="champ">
                <label>N° d'ordre <?= $enCreation ? '(auto si vide)' : '*' ?></label>
                <input type="number" name="num_ordre" value="<?= $v('num_ordre') ?>">
            </div>
            <div class="champ">
                <label>Type de mémoire *</label>
                <select name="type_memoire">
                    <?php foreach (MemoireModel::TYPES as $code => $libelle): ?>
                        <option value="<?= e($code) ?>" <?= ($valeursFormulaire['type_memoire'] ?? ($memoire['type_memoire'] ?? 'ampliatif')) === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label>Origine du dossier *</label>
                <select name="origine">
                    <?php foreach (MemoireModel::ORIGINES as $code => $libelle): ?>
                        <option value="<?= e($code) ?>" <?= $origineSelectionnee === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label>N° dossier PGCS *</label>
                <input type="text" name="num_dossier_PGCS" required value="<?= $v('num_dossier_PGCS', $prefillDossier ?? '') ?>">
            </div>
            <?php if (count($dossiersCorrespondants ?? []) > 1): ?>
                <div class="champ champ-large">
                    <label for="memoire-dossier-source">Dossier précis correspondant à ce numéro *</label>
                    <select id="memoire-dossier-source" name="id_dossier_source" required>
                        <option value="">Sélectionner le dossier concerné</option>
                        <?php foreach ($dossiersCorrespondants as $dossierSource): ?>
                            <?php
                            $libelleSource = 'Registre n° ' . $dossierSource['num_ordre']
                                . ' — ' . $dossierSource['num_dossier_PGCS']
                                . ' (' . $dossierSource['annee'] . ') — '
                                . ($dossierSource['cassationnaire'] ?: 'Parties non renseignées');
                            ?>
                            <option value="<?= (int) $dossierSource['id_reg_pourvoi'] ?>" <?= (int) $idDossierSourceSelectionne === (int) $dossierSource['id_reg_pourvoi'] ? 'selected' : '' ?>><?= e($libelleSource) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Ce numéro figure plusieurs fois dans le registre. Choisissez le dossier exact pour éviter une liaison erronée.</small>
                </div>
            <?php endif; ?>
            <div class="champ champ-large">
                <label>Parties</label>
                <input type="text" name="parties" value="<?= $v('parties') ?>">
            </div>
            <div class="champ">
                <label>Avocat</label>
                <input type="text" name="avocat" value="<?= $v('avocat') ?>">
            </div>
            <div class="champ">
                <label>Rapporteur</label>
                <input type="text" name="rapporteur" value="<?= $v('rapporteur') ?>">
            </div>
            <div class="champ">
                <label>Date de dépôt</label>
                <input type="date" name="date_depot" value="<?= $v('date_depot') ?>">
            </div>
            <div class="champ">
                <label>Date de notification</label>
                <input type="date" name="date_notification" value="<?= $v('date_notification') ?>">
            </div>
            <div class="champ">
                <label>Transmission au secrétariat</label>
                <input type="date" name="date_transmission_secretaire" value="<?= $v('date_transmission_secretaire') ?>">
            </div>
            <div class="champ">
                <label>État</label>
                <select name="etat">
                    <?php foreach (MemoireModel::ETATS as $code => $libelle): ?>
                        <option value="<?= e($code) ?>" <?= ($memoire['etat'] ?? 'depose') === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ champ-large">
                <label>Observation</label>
                <textarea name="observation"><?= $v('observation') ?></textarea>
            </div>
        </div>
        <div style="margin-top:14px">
            <button type="submit" class="bouton bouton-vert">Enregistrer</button>
            <a class="bouton bouton-secondaire" href="<?= e(url('/memoires')) ?>">Annuler</a>
        </div>
    </form>
</div>
