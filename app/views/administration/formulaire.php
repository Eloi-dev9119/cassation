<?php
/**
 * Formulaire de création / modification d'un compte.
 * Variables : $mode, $compte, $valeurs, $erreurs, $postes.
 */
$creation = $mode === 'creer';
$valeur = static fn (string $cle, string $defaut = ''): string => (string) ($valeurs[$cle] ?? $defaut);
$sexe = $valeur('sexe', 'masculin');
$sexe = in_array($sexe, ['feminin', 'F', 'f'], true) ? 'feminin' : 'masculin';
$statut = $valeur('status', 'Actifs');
$posteActuel = $valeur('poste', '');
$action = $creation
    ? url('/administration/comptes/creer')
    : url('/administration/comptes/modifier?id=' . (int) ($compte['id_client'] ?? 0));
?>
<div class="titre-page">
    <h1><?= $creation ? 'Créer un compte' : 'Modifier un compte' ?></h1>
    <div class="actions">
        <a class="bouton bouton-secondaire" href="<?= e(url('/administration/comptes')) ?>">← Comptes utilisateurs</a>
    </div>
</div>

<?php if ($erreurs !== []): ?>
    <div class="erreur-liste" role="alert">
        <strong>Le formulaire contient des erreurs :</strong>
        <ul style="margin:8px 0 0 18px">
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= e($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="carte">
    <h2><?= $creation ? 'Informations du nouveau compte' : 'Informations du compte' ?></h2>
    <form method="post" action="<?= e($action) ?>"
          data-confirm-title="<?= $creation ? 'Créer le compte' : 'Enregistrer les modifications' ?>"
          data-confirm="<?= $creation
              ? 'Le compte sera immédiatement actif et utilisable dans les deux applications PGCC. Confirmez-vous la création ?'
              : 'Les informations du compte seront mises à jour. Confirmez-vous l’enregistrement ?' ?>"
          data-confirm-label="<?= $creation ? 'Créer le compte' : 'Enregistrer' ?>">
        <input type="hidden" name="jeton" value="<?= e(Auth::jeton()) ?>">

        <div class="grille-form">
            <div class="champ">
                <label for="nom">Nom <span aria-hidden="true">*</span></label>
                <input type="text" id="nom" name="nom" value="<?= e($valeur('nom')) ?>" required autocomplete="family-name">
            </div>
            <div class="champ">
                <label for="prenom">Prénom <span aria-hidden="true">*</span></label>
                <input type="text" id="prenom" name="prenom" value="<?= e($valeur('prenom')) ?>" required autocomplete="given-name">
            </div>
            <div class="champ">
                <label for="sexe">Sexe</label>
                <select id="sexe" name="sexe">
                    <option value="masculin" <?= $sexe === 'masculin' ? 'selected' : '' ?>>Masculin</option>
                    <option value="feminin" <?= $sexe === 'feminin' ? 'selected' : '' ?>>Féminin</option>
                </select>
            </div>
            <div class="champ">
                <label for="email">Adresse e-mail (identifiant) <span aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email" value="<?= e($valeur('login')) ?>" required autocomplete="email">
            </div>
            <div class="champ">
                <label for="poste">Poste</label>
                <select id="poste" name="poste">
                    <?php if ($posteActuel !== '' && !isset($postes[$posteActuel])): ?>
                        <option value="<?= e($posteActuel) ?>" selected><?= e($posteActuel) ?> (poste existant)</option>
                    <?php endif; ?>
                    <?php foreach ($postes as $code => $libelle): ?>
                        <option value="<?= e($code) ?>" <?= $posteActuel === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label for="sigleAG">Sigle Avocat Général</label>
                <input type="text" id="sigleAG" name="sigleAG" value="<?= e($valeur('sigle_AG')) ?>" maxlength="20">
            </div>
            <div class="champ">
                <label for="matricule">Matricule <span aria-hidden="true">*</span></label>
                <input type="number" id="matricule" name="matricule" value="<?= e($valeur('IM')) ?>" required min="0">
            </div>
            <div class="champ">
                <label for="bureau">N° de bureau</label>
                <input type="number" id="bureau" name="bureau" value="<?= e($valeur('num_bureau', '0')) ?>" min="0">
            </div>
            <div class="champ">
                <label for="status">Statut</label>
                <select id="status" name="status">
                    <option value="Actifs" <?= $statut === 'Actifs' ? 'selected' : '' ?>>Actif</option>
                    <option value="Retraite" <?= $statut === 'Retraite' ? 'selected' : '' ?>>Retraité</option>
                </select>
            </div>
            <?php if ($creation): ?>
                <div class="champ">
                    <label for="motDePasse">Mot de passe <span aria-hidden="true">*</span></label>
                    <input type="password" id="motDePasse" name="motDePasse" required minlength="4" autocomplete="new-password">
                </div>
                <div class="champ">
                    <label for="confirmation">Confirmation du mot de passe <span aria-hidden="true">*</span></label>
                    <input type="password" id="confirmation" name="confirmation" required minlength="4" autocomplete="new-password">
                </div>
            <?php endif; ?>
        </div>

        <div class="actions-formulaire">
            <button type="submit" class="bouton bouton-vert"><?= $creation ? 'Créer le compte' : 'Enregistrer' ?></button>
            <a class="bouton bouton-secondaire" href="<?= e(url('/administration/comptes')) ?>">Annuler</a>
        </div>
    </form>
</div>

<?php if (!$creation && $compte !== null): ?>
    <div class="carte">
        <h2>Traçabilité</h2>
        <dl class="fiche-definition">
            <dt>Compte créé le</dt>
            <dd><?= e(date_fr($compte['date_creation'] ?? null)) ?><?= !empty($compte['cree_par']) ? ' par ' . e($compte['cree_par']) : '' ?></dd>
            <dt>Dernière modification</dt>
            <dd><?= e(date_fr($compte['date_modification'] ?? null)) ?><?= !empty($compte['modifie_par']) ? ' par ' . e($compte['modifie_par']) : '' ?></dd>
        </dl>
        <p style="color:var(--gris);margin-top:12px">Le mot de passe n’est pas modifiable ici :
        utilisez « Réinit. mdp » depuis la liste des comptes pour générer un mot de passe temporaire.</p>
    </div>
<?php endif; ?>
