<?php /** Écran de connexion (utilisateurs de la base pgcc commune). */ ?>
<div class="boite-auth">
    <div class="logo-auth">
        <img src="<?= e(asset('logo-minjus.png')) ?>" width="96" height="96"
             alt="Logo du Ministère de la Justice — Repoblikan'i Madagasikara">
    </div>
    <h1>Connexion</h1>
    <p class="sous-titre"><?= e(APP_ORGANE) ?></p>

    <?php if (!empty($erreur)): ?>
        <div class="erreur-liste" role="alert" aria-live="assertive"><?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/connexion')) ?>">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
        <div class="champ">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" class="champ-authentification" name="email" required autocomplete="username"
                   value="<?= e(ancienne('email')) ?>">
        </div>
        <div class="champ">
            <label for="motDePasse">Mot de passe</label>
            <input type="password" id="motDePasse" class="champ-authentification" name="motDePasse" required autocomplete="current-password">
        </div>
        <label class="connexion-souvenir" for="memoriserEmail">
            <input type="checkbox" id="memoriserEmail" autocomplete="off">
            <span>Mémoriser mon adresse e-mail sur cet appareil</span>
        </label>
        <button type="submit" class="bouton bouton-bloc">Se connecter</button>
    </form>

    <div class="liens liens-auth-actions">
        <a class="bouton bouton-bordeaux" href="<?= e(url('/inscription')) ?>">Demander une inscription</a>
        <a class="bouton bouton-secondaire" href="<?= e(url('/mot-de-passe-oublie')) ?>">Mot de passe oublié</a>
    </div>
</div>