<?php
/**
 * Contrôleur du profil personnel : consultation et modification de ses
 * informations, changement de son propre mot de passe.
 */

declare(strict_types=1);

final class ProfilController
{
    public function index(): void
    {
        $utilisateur = Auth::exiger();
        $modele = new ClientModel();
        $membre = $modele->trouver((int) $utilisateur['id']);

        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Session expirée, veuillez réessayer.');
                rediriger('/profil');
            }
            $action = (string) entree_post('action', '');

            if ($action === 'infos') {
                $nom = trim((string) entree_post('nom', ''));
                $prenom = trim((string) entree_post('prenom', ''));
                if ($nom === '' || $prenom === '') {
                    flash('erreur', 'Le nom et le prénom sont obligatoires.');
                    rediriger('/profil');
                }

                $modele->mettreAJourProfil((int) $utilisateur['id'], [
                    'nom'        => $nom,
                    'prenom'     => $prenom,
                    'num_bureau' => (int) entree_post('bureau', 0),
                ]);
                flash('succes', 'Vos informations ont été mises à jour.');
                rediriger('/profil');
            }

            if ($action === 'motdepasse') {
                $actuel   = (string) entree_post('motDePasseActuel', '');
                $nouveau  = (string) entree_post('motDePasseNouveau', '');
                $confirm  = (string) entree_post('motDePasseConfirm', '');

                $req = Database::connexion()->prepare('SELECT mdp FROM pgcc_client WHERE id_client = :id');
                $req->execute(['id' => $utilisateur['id']]);
                $hashActuel = (string) $req->fetchColumn();

                if (!Password::verifierEtMigrer(Database::connexion(), (int) $utilisateur['id'], $hashActuel, $actuel)) {
                    flash('erreur', 'Le mot de passe actuel est incorrect.');
                } elseif (strlen($nouveau) < 4) {
                    flash('erreur', 'Le nouveau mot de passe doit contenir au moins 4 caractères.');
                } elseif ($nouveau !== $confirm) {
                    flash('erreur', 'La confirmation ne correspond pas au nouveau mot de passe.');
                } else {
                    $modele->changerMotDePasse((int) $utilisateur['id'], $nouveau);
                    flash('succes', 'Votre mot de passe a été changé. Il est valable dans les deux applications PGCC.');
                }
                rediriger('/profil');
            }

            flash('erreur', 'L’action demandée n’est pas reconnue.');
            rediriger('/profil');
        }

        View::afficher('profil/fiche.php', [
            'titrePage' => 'Mes informations — ' . APP_NOM,
            'membre'    => $membre,
        ]);
    }
}
