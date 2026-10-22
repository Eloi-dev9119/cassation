<?php
/**
 * Modèle de gestion des membres du Parquet (comptes pgcc_client).
 */

declare(strict_types=1);

final class ClientModel
{
    /**
     * Membres selon un filtre de liste : 'attente', 'actifs', 'retraites', 'tous'.
     *
     * Onglet « attente » : les demandes d'inscription sont posées dans
     * pgcc_new_client (Admin_confirm = 0) ; des comptes historiques de
     * pgcc_client peuvent aussi être encore non validés (valide = 0).
     * Les deux sources sont normalisées sur les mêmes clés, avec une
     * colonne « source » qui pilote l'action de validation.
     */
    public function liste(string $filtre = 'tous'): array
    {
        $db = Database::connexion();

        if ($filtre === 'attente') {
            $sql = "SELECT id_client, nom, prenom, sexe, login, poste, sigle_AG,
                           IM, num_bureau, 0 AS valide, 'En attente' AS status, 'new' AS source
                    FROM pgcc_new_client WHERE Admin_confirm = 0
                    UNION ALL
                    SELECT id_client, nom, prenom, sexe, login, poste, sigle_AG,
                           IM, num_bureau, valide, status, 'client' AS source
                    FROM pgcc_client WHERE valide = 0
                    ORDER BY nom, prenom";
            $req = $db->query($sql);
            return $req->fetchAll();
        }

        $sql    = 'SELECT id_client, nom, prenom, sexe, login, poste, sigle_AG, IM, num_bureau, valide, status,
                          \'client\' AS source
                   FROM pgcc_client';
        $params = [];

        switch ($filtre) {
            case 'actifs':
                $sql .= ' WHERE valide = 1 AND status = "Actifs"';
                break;
            case 'retraites':
                $sql .= ' WHERE status = "Retraite"';
                break;
        }
        $sql .= ' ORDER BY nom, prenom';

        $req = $db->prepare($sql);
        $req->execute($params);
        return $req->fetchAll();
    }

    /**
     * Valide un compte en attente et le rend actif.
     *
     * Deux sources possibles :
     *  - 'new'    : la demande d'inscription est dans pgcc_new_client
     *               (Admin_confirm = 0). La demande est confirmée PUIS le
     *               compte est copié dans pgcc_client comme membre actif
     *               (comportement de la v1, mot de passe MD5 conservé).
     *  - 'client' : compte historique de pgcc_client encore non validé.
     */
    public function valider(int $id, string $source = 'client'): bool
    {
        $db = Database::connexion();

        if ($source === 'new') {
            $req = $db->prepare(
                'SELECT nom, prenom, sexe, login, mdp, poste, sigle_AG, IM, num_bureau
                 FROM pgcc_new_client WHERE id_client = :id AND Admin_confirm = 0 LIMIT 1'
            );
            $req->execute(['id' => $id]);
            $demande = $req->fetch();
            if ($demande === false) {
                return false;
            }

            $db->beginTransaction();
            try {
                $db->prepare('UPDATE pgcc_new_client SET Admin_confirm = 1 WHERE id_client = :id')
                   ->execute(['id' => $id]);

                // Un compte du même login peut déjà exister (recréation) :
                // on ne duplique pas, on le réactive en appliquant le mot
                // de passe choisi lors de la demande (sinon le membre
                // garderait son ancien mot de passe sans le savoir).
                $existant = $db->prepare('SELECT id_client FROM pgcc_client WHERE login = :login LIMIT 1');
                $existant->execute(['login' => $demande['login']]);
                $idExistant = $existant->fetchColumn();

                if ($idExistant !== false) {
                    $db->prepare('UPDATE pgcc_client SET valide = 1, status = :statut, mdp = :mdp, modifie_par = :par WHERE id_client = :id')
                       ->execute(['statut' => 'Actifs', 'mdp' => $demande['mdp'], 'par' => utilisateur_login(), 'id' => (int) $idExistant]);
                } else {
                    $db->prepare(
                        'INSERT INTO pgcc_client
                            (nom, prenom, sexe, login, mdp, poste, sigle_AG, IM, num_bureau, valide, status, date_creation, cree_par)
                         VALUES
                            (:nom, :prenom, :sexe, :login, :mdp, :poste, :sigle, :im, :bureau, 1, :statut, NOW(), :par)'
                    )->execute([
                        'nom'    => $demande['nom'],
                        'prenom' => $demande['prenom'],
                        'sexe'   => $demande['sexe'],
                        'login'  => $demande['login'],
                        'mdp'    => $demande['mdp'],
                        'poste'  => $demande['poste'],
                        'sigle'  => $demande['sigle_AG'],
                        'im'     => $demande['IM'],
                        'bureau' => $demande['num_bureau'],
                        'statut' => 'Actifs',
                        'par'    => utilisateur_login(),
                    ]);
                }
                $db->commit();
                return true;
            } catch (Throwable $e) {
                $db->rollBack();
                throw $e;
            }
        }

        return $db->prepare('UPDATE pgcc_client SET valide = 1, status = :statut, modifie_par = :par WHERE id_client = :id')
                  ->execute(['statut' => 'Actifs', 'par' => utilisateur_login(), 'id' => $id]);
    }

    /**
     * Fait passer un membre en retraite (ou le réactive).
     */
    public function changerStatus(int $id, string $status): bool
    {
        return Database::connexion()
            ->prepare('UPDATE pgcc_client SET status = :s, modifie_par = :par WHERE id_client = :id')
            ->execute(['s' => $status, 'par' => utilisateur_login(), 'id' => $id]);
    }

    /**
     * Fiche complète d'un membre.
     */
    public function trouver(int $id): ?array
    {
        $req = Database::connexion()->prepare(
            'SELECT id_client, nom, prenom, sexe, login, poste, sigle_AG, IM, num_bureau, valide, status
             FROM pgcc_client WHERE id_client = :id'
        );
        $req->execute(['id' => $id]);
        $ligne = $req->fetch();
        return $ligne === false ? null : $ligne;
    }

    /**
     * Met à jour les informations propres d'un compte (sans le mot de passe).
     */
    public function mettreAJourProfil(int $id, array $donnees): bool
    {
        $donnees['modifie_par'] = utilisateur_login();
        $affectations = implode(', ', array_map(fn ($c) => "`$c` = :$c", array_keys($donnees)));
        return Database::connexion()
            ->prepare("UPDATE pgcc_client SET $affectations WHERE id_client = :id")
            ->execute($donnees + ['id' => $id]);
    }

    /**
     * Change le mot de passe d'un compte et trace l'opération.
     */
    public function changerMotDePasse(int $id, string $clair): bool
    {
        return Database::connexion()
            ->prepare('UPDATE pgcc_client SET mdp = :mdp, modifie_par = :par WHERE id_client = :id')
            ->execute(['mdp' => Password::creer($clair), 'par' => utilisateur_login(), 'id' => $id]);
    }
}
