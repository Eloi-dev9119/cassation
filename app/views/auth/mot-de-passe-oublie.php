<?php /** Réinitialisation de mot de passe par question secrète (parité pwdAG v1). */ ?>
<div class="boite-auth">
    <h1>Mot de passe oublié</h1>
    <p class="sous-titre">Répondez à votre question secrète pour définir un nouveau mot de passe.</p>

    <?php if ($erreurs !== []): ?>
        <div class="erreur-liste" role="alert" aria-live="assertive">
            <ul style="margin:0;padding-left:18px">
                <?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($etape === 'email'): ?>
        <form method="post" action="<?= e(url('/mot-de-passe-oublie')) ?>"
              data-confirm-title="Vérifier l’adresse e-mail"
              data-confirm="Une vérification sera lancée pour le compte associé à cette adresse. Voulez-vous continuer ?"
              data-confirm-label="Continuer">
            <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
            <div class="champ">
                <label for="email">Adresse e-mail du compte</label>
                <input type="email" id="email" name="email" required autofocus value="<?= e($email) ?>">
            </div>
            <button type="submit" class="bouton" style="width:100%">Continuer</button>
        </form>
    <?php else: ?>
        <form method="post" action="<?= e(url('/mot-de-passe-oublie')) ?>"
              data-confirm-title="Réinitialiser le mot de passe"
              data-confirm="Si la réponse secrète est correcte, le mot de passe sera remplacé par celui saisi. Confirmez-vous la réinitialisation ?"
              data-confirm-label="Réinitialiser" data-confirm-tone="danger">
            <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">
            <input type="hidden" name="etape" value="reponse">
            <input type="hidden" name="email" value="<?= e($email) ?>">
            <div class="champ">
                <label>Question secrète</label>
                <input type="text" value="<?= e($question) ?>" disabled>
            </div>
            <div class="champ">
                <label>Votre réponse</label>
                <input type="text" name="reponse" required autofocus>
            </div>
            <div class="champ">
                <label>Nouveau mot de passe</label>
                <input type="password" name="motDePasse" required>
            </div>
            <div class="champ">
                <label>Confirmation</label>
                <input type="password" name="confirmation" required>
            </div>
            <button type="submit" class="bouton bouton-bordeaux" style="width:100%">Réinitialiser le mot de passe</button>
        </form>
    <?php endif; ?>

    <div class="liens"><a href="<?= e(url('/connexion')) ?>">Retour à la connexion</a></div>
</div>
