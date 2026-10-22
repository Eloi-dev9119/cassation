<?php /** Demande d'inscription : compte créé en attente de validation. */ ?>
<div class="boite-auth" style="width:560px">
    <h1>Demande d'inscription</h1>
    <p class="sous-titre">Le compte sera activé après validation par l'administration du Parquet.</p>

    <?php if (!empty($erreurs)): ?>
        <div class="erreur-liste" role="alert" aria-live="assertive">
            <ul style="margin:0;padding-left:18px">
                <?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/inscription')) ?>"
          data-confirm-title="Envoyer la demande d’inscription"
          data-confirm="Votre demande sera transmise à l’administration du Parquet. Vérifiez vos informations avant l’envoi. Le compte devra être validé avant votre première connexion."
          data-confirm-label="Envoyer ma demande">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <div class="grille-form">
            <div class="champ">
                <label>Nom</label>
                <input type="text" name="nom" required value="<?= e(ancienne('nom')) ?>">
            </div>
            <div class="champ">
                <label>Prénom</label>
                <input type="text" name="prenom" required value="<?= e(ancienne('prenom')) ?>">
            </div>
            <div class="champ">
                <label>Sexe</label>
                <select name="sexe">
                    <option value="masculin" <?= ancienne('sexe') === 'feminin' ? '' : 'selected' ?>>Masculin</option>
                    <option value="feminin" <?= ancienne('sexe') === 'feminin' ? 'selected' : '' ?>>Féminin</option>
                </select>
            </div>
            <div class="champ">
                <label>Matricule (IM)</label>
                <input type="number" name="matricule" required value="<?= e(ancienne('matricule')) ?>">
            </div>
            <div class="champ champ-large">
                <label>Adresse e-mail (identifiant)</label>
                <input type="email" name="email" required value="<?= e(ancienne('email')) ?>">
            </div>
            <div class="champ">
                <label>Mot de passe</label>
                <input type="password" name="motDePasse" required>
            </div>
            <div class="champ">
                <label>Confirmation</label>
                <input type="password" name="confirmation" required>
            </div>
            <div class="champ">
                <label>Poste</label>
                <select name="poste">
                    <?php foreach ($postes as $code => $libelle): ?>
                        <option value="<?= e($code) ?>" <?= ancienne('poste') === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label>Sigle AG</label>
                <input type="text" name="sigleAG" value="<?= e(ancienne('sigleAG')) ?>">
            </div>
            <div class="champ">
                <label>N° de bureau</label>
                <input type="number" name="bureau" value="<?= e(ancienne('bureau', '0')) ?>">
            </div>
        </div>
        <button type="submit" class="bouton bouton-bordeaux" style="width:100%;margin-top:10px">Envoyer la demande</button>
    </form>
    <div class="liens"><a href="<?= e(url('/connexion')) ?>">J'ai déjà un compte — se connecter</a></div>
</div>
