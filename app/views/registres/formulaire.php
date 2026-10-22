<?php
/**
 * Formulaire de création ou de modification d’un dossier de registre.
 * Variables : $definition, $code, $dossier, $erreurs, $valeursFormulaire.
 */
$colonnesForm = registre_colonnes($definition, 'formulaire');
$baseUrl = '/registres/' . $code;
$enCreation = $dossier === null;

$colonnesParNom = [];
foreach ($colonnesForm as $colonne) {
    $colonnesParNom[$colonne['nom']] = $colonne;
}

$sectionsForm = [];
$colonnesPlacees = [];
foreach (($definition['sections'] ?? []) as $libelleSection => $nomsColonnes) {
    $colonnesSection = [];
    foreach ($nomsColonnes as $nomColonne) {
        if (isset($colonnesParNom[$nomColonne])) {
            $colonnesSection[] = $colonnesParNom[$nomColonne];
            $colonnesPlacees[$nomColonne] = true;
        }
    }
    if ($colonnesSection !== []) {
        $sectionsForm[] = ['libelle' => $libelleSection, 'colonnes' => $colonnesSection];
    }
}

$colonnesRestantes = array_values(array_filter(
    $colonnesForm,
    static fn (array $colonne): bool => !isset($colonnesPlacees[$colonne['nom']])
));
if ($colonnesRestantes !== []) {
    $sectionsForm[] = ['libelle' => 'Autres informations', 'colonnes' => $colonnesRestantes];
}
if ($sectionsForm === []) {
    $sectionsForm[] = ['libelle' => 'Informations du dossier', 'colonnes' => $colonnesForm];
}

$referenceDossier = $dossier['num_dossier_PGCS']
    ?? $dossier['num_dossier']
    ?? $dossier[$definition['cle']]
    ?? '';
$titreFormulaire = $enCreation
    ? 'Nouveau dossier — ' . $definition['libelle']
    : 'Modifier le dossier ' . $referenceDossier;
$confirmationTitre = $enCreation ? 'Ajouter un dossier au registre' : 'Enregistrer les modifications';
$confirmationMessage = $enCreation
    ? 'Le dossier sera ajouté au registre « ' . $definition['libelle'] . ' ». Confirmez-vous son enregistrement ?'
    : 'Les modifications du dossier « ' . $referenceDossier . ' » seront enregistrées. Confirmez-vous cette mise à jour ?';
$confirmationBouton = $enCreation ? 'Ajouter le dossier' : 'Enregistrer les modifications';
?>
<div class="titre-page">
    <h1><?= e($titreFormulaire) ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl)) ?>">← Retour à la liste</a>
    </div>
</div>

<?php if ($erreurs !== []): ?>
    <div class="alerte alerte-erreur" role="alert" aria-live="assertive">
        <ul style="margin:0;padding-left:18px">
            <?php foreach ($erreurs as $message): ?><li><?= e($message) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="carte">
    <form class="formulaire-registre" method="post"
          action="<?= e(url($enCreation ? $baseUrl . '/creer' : $baseUrl . '/modifier?id=' . $dossier[$definition['cle']])) ?>"
          data-confirm-title="<?= e($confirmationTitre) ?>"
          data-confirm="<?= e($confirmationMessage) ?>"
          data-confirm-label="<?= e($confirmationBouton) ?>">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">

        <?php foreach ($sectionsForm as $section): ?>
            <fieldset>
                <legend><?= e($section['libelle']) ?></legend>
                <div class="grille-form">
                    <?php foreach ($section['colonnes'] as $colonne): ?>
                        <?php
                        $nom = $colonne['nom'];
                        $idChamp = 'registre-' . $nom;
                        $valeur = $valeursFormulaire[$nom] ?? ($dossier[$nom] ?? '');
                        if ($valeur === '0000-00-00') {
                            $valeur = '';
                        }
                        if ($colonne['type'] === 'booleen') {
                            $valeur = bin_vers_bool($valeur);
                        }
                        $obligatoire = !empty($colonne['requis']);
                        $champLarge = $colonne['type'] === 'textelong' || !empty($colonne['multiligne']);
                        ?>
                        <div class="champ <?= $champLarge ? 'champ-large' : '' ?>">
                            <label for="<?= e($idChamp) ?>">
                                <?= e($colonne['libelle']) ?><?= $obligatoire ? ' *' : '' ?>
                            </label>

                            <?php if ($colonne['type'] === 'dossier_civil'): ?>
                                <select id="<?= e($idChamp) ?>" name="<?= e($nom) ?>" required>
                                    <option value="">Sélectionner un dossier civil</option>
                                    <?php foreach (($dossiersCivils ?? []) as $dossierCivilOption): ?>
                                        <?php $libelleCivil = $dossierCivilOption['num_dossier_PGCS'] . ' — ' . ($dossierCivilOption['cassationnaire'] ?: 'Parties non renseignées') . ' (' . $dossierCivilOption['annee'] . ')'; ?>
                                        <option value="<?= (int) $dossierCivilOption['id_reg_pourvoi'] ?>" <?= (string) $valeur === (string) $dossierCivilOption['id_reg_pourvoi'] ? 'selected' : '' ?>><?= e($libelleCivil) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($colonne['type'] === 'booleen'): ?>
                                <select id="<?= e($idChamp) ?>" name="<?= e($nom) ?>"<?= $obligatoire ? ' required' : '' ?>>
                                    <option value="0" <?= !$valeur ? 'selected' : '' ?>>Non</option>
                                    <option value="1" <?= $valeur ? 'selected' : '' ?>>Oui</option>
                                </select>
                            <?php elseif ($nom === 'annee'): ?>
                                <?php
                                $anneeCourante = (int) date('Y');
                                $anneeSelectionnee = filter_var($valeur, FILTER_VALIDATE_INT);
                                if ($anneeSelectionnee === false || $anneeSelectionnee < 1900) {
                                    $anneeSelectionnee = $anneeCourante;
                                }
                                $anneeMax = max($anneeCourante + 1, $anneeSelectionnee);
                                ?>
                                <select id="<?= e($idChamp) ?>" name="<?= e($nom) ?>"<?= $obligatoire ? ' required' : '' ?>>
                                    <?php for ($anneeOption = $anneeMax; $anneeOption >= 1900; $anneeOption--): ?>
                                        <option value="<?= $anneeOption ?>" <?= $anneeSelectionnee === $anneeOption ? 'selected' : '' ?>>
                                            <?= $anneeOption ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            <?php elseif ($colonne['type'] === 'date'): ?>
                                <input id="<?= e($idChamp) ?>" type="date" name="<?= e($nom) ?>"
                                       value="<?= e($valeur) ?>"<?= $obligatoire ? ' required' : '' ?>>
                            <?php elseif ($colonne['type'] === 'textelong' || !empty($colonne['multiligne'])): ?>
                                <textarea id="<?= e($idChamp) ?>" name="<?= e($nom) ?>"
                                          rows="4"<?= isset($colonne['maxlength']) ? ' maxlength="' . (int) $colonne['maxlength'] . '"' : '' ?><?= $obligatoire ? ' required' : '' ?>><?= e($valeur) ?></textarea>
                            <?php elseif ($colonne['type'] === 'nombre'): ?>
                                <input id="<?= e($idChamp) ?>" type="number" name="<?= e($nom) ?>"
                                       value="<?= e($valeur) ?>"<?= $obligatoire ? ' min="1" required' : '' ?>>
                            <?php else: ?>
                                <input id="<?= e($idChamp) ?>" type="text" name="<?= e($nom) ?>"
                                       value="<?= e($valeur) ?>"<?= isset($colonne['maxlength']) ? ' maxlength="' . (int) $colonne['maxlength'] . '"' : '' ?><?= $obligatoire ? ' required' : '' ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        <?php endforeach; ?>

        <div class="actions-formulaire">
            <button type="submit" class="bouton bouton-vert">Enregistrer le dossier</button>
            <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl)) ?>">Annuler</a>
        </div>
    </form>
</div>
