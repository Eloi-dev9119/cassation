<?php
/**
 * Profil personnel. Variables : $membre.
 */
?>
<div class="titre-page">
    <h1>Mes informations</h1>
</div>

<?php if ($membre === null): ?>
    <div class="alerte alerte-erreur">Compte introuvable.</div>
    <?php return; endif; ?>

<div class="carte">
    <h2>Informations du compte</h2>
    <form method="post" action="<?= e(url('/profil')) ?>"
          data-confirm-title="Enregistrer mes informations"
          data-confirm="Vos nom, prénom et numéro de bureau seront mis à jour. Confirmez-vous l’enregistrement ?"
          data-confirm-label="Enregistrer">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <input type="hidden" name="action" value="infos">
        <div class="grille-form">
            <div class="champ">
                <label>Nom</label>
                <input type="text" name="nom" value="<?= e($membre['nom']) ?>" required>
            </div>
            <div class="champ">
                <label>Prénom</label>
                <input type="text" name="prenom" value="<?= e($membre['prenom']) ?>" required>
            </div>
            <div class="champ">
                <label>Adresse e-mail (identifiant)</label>
                <input type="email" value="<?= e($membre['login']) ?>" disabled>
            </div>
            <div class="champ">
                <label>Poste</label>
                <input type="text" value="<?= e($membre['poste'] ?: '—') ?>" disabled>
            </div>
            <div class="champ">
                <label>Matricule</label>
                <input type="number" value="<?= (int) $membre['IM'] ?>" disabled>
            </div>
            <div class="champ">
                <label>N° de bureau</label>
                <input type="number" name="bureau" value="<?= (int) $membre['num_bureau'] ?>">
            </div>
        </div>
        <button type="submit" class="bouton bouton-vert" style="margin-top:8px">Enregistrer</button>
    </form>
</div>

<div class="carte">
    <h2>Changer mon mot de passe</h2>
    <form method="post" action="<?= e(url('/profil')) ?>"
          data-confirm-title="Changer mon mot de passe"
          data-confirm="Votre mot de passe actuel sera remplacé. Vous utiliserez le nouveau mot de passe pour vos prochaines connexions à PGCC. Confirmez-vous le changement ?"
          data-confirm-label="Changer le mot de passe" data-confirm-tone="danger">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <input type="hidden" name="action" value="motdepasse">
        <div class="grille-form">
            <div class="champ">
                <label>Mot de passe actuel</label>
                <input type="password" name="motDePasseActuel" required>
            </div>
            <div class="champ">
                <label>Nouveau mot de passe</label>
                <input type="password" name="motDePasseNouveau" required>
            </div>
            <div class="champ">
                <label>Confirmation</label>
                <input type="password" name="motDePasseConfirm" required>
            </div>
        </div>
        <button type="submit" class="bouton" style="margin-top:8px">Changer le mot de passe</button>
    </form>
</div>
