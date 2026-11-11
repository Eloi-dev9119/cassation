<?php
/**
 * Formulaire de création (modification si $suivi est renseigné) d'un suivi.
 * Variables : $type, $definition, $erreurs, $suivi (ligne existante ou null).
 */
$suivi = $suivi ?? null;
$baseUrl = '/suivis/' . $type; // route interne brute, passée dans url() à l'affichage
$actionUrl = $suivi === null ? url($baseUrl . '/creer') : url($baseUrl . '/modifier?id=' . (int) $suivi[$definition['cle']]);

/**
 * Valeur d'un champ : la ligne existante en modification, sinon la saisie
 * conservée après erreur, sinon la valeur par défaut. Les dates
 * historiques « 0000-00-00 » s'affichent comme un champ vide.
 */
$valeurChamp = function (string $nom, string $defaut = '') use ($suivi): string {
    $brute = (string) ($_POST[$nom] ?? $suivi[$nom] ?? $defaut);
    return $brute === '0000-00-00' ? '' : $brute;
};
?>
<div class="titre-page titre-page-formulaire">
    <h1><?= $suivi === null ? 'Nouveau suivi' : 'Modifier le suivi' ?> — <?= e($definition['libelle']) ?></h1>
    <div class="actions"><a class="bouton bouton-secondaire" href="<?= e(url($baseUrl)) ?>">← Retour à la liste</a></div>
</div>

<?php if ($erreurs !== []): ?>
    <div class="alerte alerte-erreur" role="alert" aria-live="assertive">
        <ul style="margin:0;padding-left:18px">
            <?php foreach ($erreurs as $message): ?><li><?= e($message) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="carte carte-formulaire">
    <form method="post" action="<?= e($actionUrl) ?>"
          data-confirm-title="<?= $suivi === null ? 'Créer un suivi' : 'Modifier le suivi' ?>"
          data-confirm="<?= $suivi === null
              ? 'Un nouveau suivi sera ajouté au registre « ' . e($definition['libelle']) . ' ». Confirmez-vous sa création ?'
              : 'Les modifications apportées au suivi seront enregistrées. Confirmez-vous ?' ?>"
          data-confirm-label="<?= $suivi === null ? 'Créer le suivi' : 'Enregistrer les modifications' ?>">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <div class="grille-form">
            <div class="champ">
                <label>Année <span class="etoile-obligatoire" aria-hidden="true">*</span></label>
                <input type="number" name="annee" min="1990" max="2100" required value="<?= e($valeurChamp('annee', (string) date('Y'))) ?>">
            </div>
            <div class="champ">
                <label>N° ordre (auto si vide)</label>
                <input type="number" name="num_ordre" value="<?= e($valeurChamp('num_ordre')) ?>">
            </div>
            <?php foreach ($definition['champs'] as $champ): if ($champ['nom'] === 'num_ordre') continue; ?>
                <?php
                $large = in_array($champ['type'], ['textelong'], true) ? 'champ-large' : '';
                $valeur = $valeurChamp($champ['nom']);
                ?>
                <div class="champ <?= $large ?>">
                    <label><?= e($champ['libelle']) ?><?= !empty($champ['requis']) ? ' <span class="etoile-obligatoire" aria-hidden="true">*</span>' : '' ?></label>
                    <?php if ($champ['type'] === 'booleen'): ?>
                        <select name="<?= e($champ['nom']) ?>">
                            <option value="0"<?= $valeur === '1' ? '' : ' selected' ?>>Non</option>
                            <option value="1"<?= $valeur === '1' ? ' selected' : '' ?>>Oui</option>
                        </select>
                    <?php elseif ($champ['type'] === 'date'): ?>
                        <input type="date" name="<?= e($champ['nom']) ?>" value="<?= e($valeur) ?>">
                    <?php elseif ($champ['type'] === 'nombre'): ?>
                        <input type="number" name="<?= e($champ['nom']) ?>" value="<?= e($valeur) ?>">
                    <?php elseif ($champ['type'] === 'textelong'): ?>
                        <textarea name="<?= e($champ['nom']) ?>"><?= e($valeur) ?></textarea>
                    <?php else: ?>
                        <input type="text" name="<?= e($champ['nom']) ?>" value="<?= e($valeur) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="actions-formulaire">
            <button type="submit" class="bouton bouton-vert"><?= $suivi === null ? 'Enregistrer le suivi' : 'Enregistrer les modifications' ?></button>
            <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl)) ?>">Annuler</a>
        </div>
    </form>
</div>
