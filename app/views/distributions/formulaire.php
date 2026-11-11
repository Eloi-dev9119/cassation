<?php
/**
 * Formulaire de modification d'une distribution (une ligne de liste) :
 * année, liste, parties, sigle et dates de remise — comme la v1.
 * Variables : $type, $distribution, $erreurs.
 */
$distribution = $distribution ?? [];
$erreurs = $erreurs ?? [];
$baseUrl = '/distributions'; // route interne brute, passée dans url() à l'affichage
$id = (int) ($distribution['id_distribution'] ?? 0);

/** Valeur du champ : saisie conservée, sinon ligne existante (dates « 0000-00-00 » vides). */
$valeurChamp = function (string $nom) use ($distribution): string {
    $brute = (string) ($_POST[$nom] ?? $distribution[$nom] ?? '');
    return $brute === '0000-00-00' ? '' : $brute;
};
?>
<div class="titre-page titre-page-formulaire">
    <h1>Modifier la distribution — <?= e($distribution['num_dossier_PGCS'] ?? '') ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?type=' . e($type))) ?>">← Retour à la liste</a>
    </div>
</div>

<?php if ($erreurs !== []): ?>
    <div class="alerte alerte-erreur" role="alert" aria-live="assertive">
        <ul style="margin:0;padding-left:18px">
            <?php foreach ($erreurs as $message): ?><li><?= e($message) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="carte carte-formulaire">
    <form method="post" action="<?= e(url($baseUrl . '?a=modifier&id=' . $id . '&type=' . e($type))) ?>"
          data-confirm-title="Modifier la distribution"
          data-confirm="Les modifications apportées à la distribution du dossier <?= e($distribution['num_dossier_PGCS'] ?? '') ?> seront enregistrées. Confirmez-vous ?"
          data-confirm-label="Enregistrer les modifications">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <div class="grille-form">
            <div class="champ">
                <label>Année <span class="etoile-obligatoire" aria-hidden="true">*</span></label>
                <input type="number" name="annee" min="1990" max="2100" required value="<?= e($valeurChamp('annee')) ?>">
            </div>
            <div class="champ">
                <label>Liste <span class="etoile-obligatoire" aria-hidden="true">*</span></label>
                <input type="text" name="nom_liste" required value="<?= e($valeurChamp('nom_liste')) ?>">
            </div>
            <div class="champ champ-large">
                <label>Parties</label>
                <input type="text" name="parties" value="<?= e($valeurChamp('parties')) ?>">
            </div>
            <div class="champ">
                <label>Magistrat instructeur (sigle AG) <span class="etoile-obligatoire" aria-hidden="true">*</span></label>
                <input type="text" name="sigle" required value="<?= e($valeurChamp('sigle')) ?>">
            </div>
            <div class="champ">
                <label>Remise au secrétariat</label>
                <input type="date" name="date_remise_secr" value="<?= e($valeurChamp('date_remise_secr')) ?>">
            </div>
            <div class="champ">
                <label>Remise à l’AG</label>
                <input type="date" name="date_remise_AG" value="<?= e($valeurChamp('date_remise_AG')) ?>">
            </div>
        </div>
        <div class="actions-formulaire">
            <button type="submit" class="bouton bouton-vert">Enregistrer les modifications</button>
            <a class="bouton bouton-secondaire" href="<?= e(url($baseUrl . '?type=' . e($type))) ?>">Annuler</a>
        </div>
    </form>
</div>
