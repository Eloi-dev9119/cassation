<?php
/**
 * Formulaire de création d'un suivi. Variables : $type, $definition,
 * $erreurs.
 */
$baseUrl = '/suivis/' . $type; // route interne brute, passée dans url() à l'affichage
?>
<div class="titre-page">
    <h1>Nouveau suivi — <?= e($definition['libelle']) ?></h1>
    <div class="actions"><a class="bouton bouton-secondaire" href="<?= e(url($baseUrl)) ?>">← Retour à la liste</a></div>
</div>

<?php if ($erreurs !== []): ?>
    <div class="alerte alerte-erreur" role="alert" aria-live="assertive">
        <ul style="margin:0;padding-left:18px">
            <?php foreach ($erreurs as $message): ?><li><?= e($message) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="carte">
    <form method="post" action="<?= e(url($baseUrl . '/creer')) ?>"
          data-confirm-title="Créer un suivi"
          data-confirm="Un nouveau suivi sera ajouté au registre « <?= e($definition['libelle']) ?> ». Confirmez-vous sa création ?"
          data-confirm-label="Créer le suivi">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <div class="grille-form">
            <div class="champ">
                <label>Année *</label>
                <input type="number" name="annee" min="1990" max="2100" required value="<?= e(date('Y')) ?>">
            </div>
            <div class="champ">
                <label>N° ordre (auto si vide)</label>
                <input type="number" name="num_ordre">
            </div>
            <?php foreach ($definition['champs'] as $champ): if ($champ['nom'] === 'num_ordre') continue; ?>
                <?php
                $large = in_array($champ['type'], ['textelong'], true) ? 'champ-large' : '';
                ?>
                <div class="champ <?= $large ?>">
                    <label><?= e($champ['libelle']) ?><?= !empty($champ['requis']) ? ' *' : '' ?></label>
                    <?php if ($champ['type'] === 'booleen'): ?>
                        <select name="<?= e($champ['nom']) ?>">
                            <option value="0">Non</option>
                            <option value="1">Oui</option>
                        </select>
                    <?php elseif ($champ['type'] === 'date'): ?>
                        <input type="date" name="<?= e($champ['nom']) ?>">
                    <?php elseif ($champ['type'] === 'nombre'): ?>
                        <input type="number" name="<?= e($champ['nom']) ?>">
                    <?php elseif ($champ['type'] === 'textelong'): ?>
                        <textarea name="<?= e($champ['nom']) ?>"></textarea>
                    <?php else: ?>
                        <input type="text" name="<?= e($champ['nom']) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:14px">
            <button type="submit" class="bouton bouton-vert">Enregistrer le suivi</button>
            <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl)) ?>">Annuler</a>
        </div>
    </form>
</div>
