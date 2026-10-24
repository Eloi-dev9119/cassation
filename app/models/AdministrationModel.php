<?php
/**
 * Modèle de l'espace d'administration : compteurs, comptes utilisateurs
 * et journal d'activité (colonnes d'audit des tables métier).
 */

declare(strict_types=1);

final class AdministrationModel
{
    /**
     * Indicateurs du tableau de bord administrateur.
     */
    public function compteurs(): array
    {
        $db = Database::connexion();
        $attenteNouvelles = (int) $db->query('SELECT COUNT(*) FROM pgcc_new_client WHERE Admin_confirm = 0')->fetchColumn();
        $attenteHistoriques = (int) $db->query('SELECT COUNT(*) FROM pgcc_client WHERE valide = 0')->fetchColumn();

        return [
            'total'              => (int) $db->query('SELECT COUNT(*) FROM pgcc_client')->fetchColumn(),
            'actifs'             => (int) $db->query('SELECT COUNT(*) FROM pgcc_client WHERE valide = 1 AND (status IS NULL OR status <> "Retraite")')->fetchColumn(),
            'retraites'          => (int) $db->query('SELECT COUNT(*) FROM pgcc_client WHERE status = "Retraite"')->fetchColumn(),
            'attenteNouvelles'   => $attenteNouvelles,
            'attenteHistoriques' => $attenteHistoriques,
            'enAttente'          => $attenteNouvelles + $attenteHistoriques,
        ];
    }

    /**
     * Comptes de pgcc_client, avec recherche et filtre de statut.
     *
     * @param string $filtre 'tous', 'actifs', 'retraites' ou 'invalides'.
     */
    public function comptes(string $recherche = '', string $filtre = 'tous'): array
    {
        $sql = 'SELECT id_client, nom, prenom, sexe, login, poste, sigle_AG, IM, num_bureau,
                       valide, status, date_creation, date_modification, cree_par, modifie_par
                FROM pgcc_client';
        $conditions = [];
        $parametres = [];

        switch ($filtre) {
            case 'actifs':
                $conditions[] = 'valide = 1 AND (status IS NULL OR status <> :retraite)';
                $parametres['retraite'] = 'Retraite';
                break;
            case 'retraites':
                $conditions[] = 'status = :retraite';
                $parametres['retraite'] = 'Retraite';
                break;
            case 'invalides':
                $conditions[] = 'valide = 0';
                break;
        }

        if ($recherche !== '') {
            $conditions[] = '(nom LIKE :recherche_nom OR prenom LIKE :recherche_prenom'
                . ' OR login LIKE :recherche_login OR sigle_AG LIKE :recherche_sigle)';
            $motif = '%' . $recherche . '%';
            $parametres['recherche_nom'] = $motif;
            $parametres['recherche_prenom'] = $motif;
            $parametres['recherche_login'] = $motif;
            $parametres['recherche_sigle'] = $motif;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY nom, prenom LIMIT 500';

        $req = Database::connexion()->prepare($sql);
        $req->execute($parametres);
        return $req->fetchAll();
    }

    /**
     * Fiche d'un compte (ou null).
     */
    public function trouver(int $id): ?array
    {
        $req = Database::connexion()->prepare(
            'SELECT id_client, nom, prenom, sexe, login, poste, sigle_AG, IM, num_bureau,
                    valide, status, date_creation, date_modification, cree_par, modifie_par
             FROM pgcc_client WHERE id_client = :id'
        );
        $req->execute(['id' => $id]);
        $ligne = $req->fetch();
        return $ligne === false ? null : $ligne;
    }

    /**
     * Un identifiant de connexion existe-t-il déjà (comptes ou demandes) ?
     */
    public function loginExiste(string $login, ?int $idExclu = null): bool
    {
        $db = Database::connexion();

        $sql = 'SELECT COUNT(*) FROM pgcc_client WHERE login = :login';
        $parametres = ['login' => $login];
        if ($idExclu !== null) {
            $sql .= ' AND id_client <> :id';
            $parametres['id'] = $idExclu;
        }
        $req = $db->prepare($sql);
        $req->execute($parametres);
        if ((int) $req->fetchColumn() > 0) {
            return true;
        }

        $req = $db->prepare('SELECT COUNT(*) FROM pgcc_new_client WHERE login = :login AND Admin_confirm = 0');
        $req->execute(['login' => $login]);
        return (int) $req->fetchColumn() > 0;
    }

    /**
     * Crée un compte actif et retourne son identifiant.
     */
    public function creer(array $donnees): int
    {
        $req = Database::connexion()->prepare(
            'INSERT INTO pgcc_client
                (nom, prenom, sexe, login, mdp, poste, sigle_AG, IM, num_bureau,
                 valide, status, date_creation, cree_par)
             VALUES
                (:nom, :prenom, :sexe, :login, :mdp, :poste, :sigle, :im, :bureau,
                 1, :status, NOW(), :par)'
        );
        $req->execute([
            'nom'    => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'sexe'   => $donnees['sexe'],
            'login'  => $donnees['login'],
            'mdp'    => Password::creer($donnees['motDePasse']),
            'poste'  => $donnees['poste'],
            'sigle'  => $donnees['sigle_AG'],
            'im'     => (int) $donnees['IM'],
            'bureau' => (int) $donnees['num_bureau'],
            'status' => $donnees['status'],
            'par'    => utilisateur_login(),
        ]);
        return (int) Database::connexion()->lastInsertId();
    }

    /**
     * Met à jour les informations d'un compte (hors mot de passe).
     */
    public function modifier(int $id, array $donnees): bool
    {
        return Database::connexion()->prepare(
            'UPDATE pgcc_client
             SET nom = :nom, prenom = :prenom, sexe = :sexe, login = :login,
                 poste = :poste, sigle_AG = :sigle, IM = :im, num_bureau = :bureau,
                 status = :status, modifie_par = :par
             WHERE id_client = :id'
        )->execute([
            'nom'    => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'sexe'   => $donnees['sexe'],
            'login'  => $donnees['login'],
            'poste'  => $donnees['poste'],
            'sigle'  => $donnees['sigle_AG'],
            'im'     => (int) $donnees['IM'],
            'bureau' => (int) $donnees['num_bureau'],
            'status' => $donnees['status'],
            'par'    => utilisateur_login(),
            'id'     => $id,
        ]);
    }

    /**
     * Active un compte ou le met à la retraite.
     */
    public function changerStatut(int $id, string $statut): bool
    {
        if (!in_array($statut, ['Actifs', 'Retraite'], true)) {
            return false;
        }
        $valide = $statut === 'Actifs' ? 1 : null;
        $sql = 'UPDATE pgcc_client SET status = :status, modifie_par = :par';
        $parametres = [
            'status' => $statut,
            'par'    => utilisateur_login(),
            'id'     => $id,
        ];
        if ($valide !== null) {
            $sql .= ', valide = :valide';
            $parametres['valide'] = $valide;
        }
        $sql .= ' WHERE id_client = :id';
        return Database::connexion()->prepare($sql)->execute($parametres);
    }

    /**
     * Valide un compte encore non validé de pgcc_client.
     */
    public function valider(int $id): bool
    {
        return Database::connexion()->prepare(
            'UPDATE pgcc_client
             SET valide = 1, status = :status, modifie_par = :par
             WHERE id_client = :id'
        )->execute([
            'status' => 'Actifs',
            'par'    => utilisateur_login(),
            'id'     => $id,
        ]);
    }

    /**
     * Réinitialise le mot de passe d'un compte.
     */
    public function reinitialiserMotDePasse(int $id, string $motDePasse): bool
    {
        return Database::connexion()->prepare(
            'UPDATE pgcc_client SET mdp = :mdp, modifie_par = :par WHERE id_client = :id'
        )->execute([
            'mdp' => Password::creer($motDePasse),
            'par' => utilisateur_login(),
            'id'  => $id,
        ]);
    }

    /**
     * Dernières activités enregistrées par les colonnes d'audit.
     *
     * Le journal agrège les tables métier et les comptes. Si une colonne
     * d'audit manque (migration non appliquée), le journal reste vide au
     * lieu de bloquer l'administration.
     */
    public function journal(int $limite = 50): array
    {
        $limite = max(1, min(200, $limite));
        $db = Database::connexion();

        $sources = [
            ['Dossiers arrivés', 'pgcc_reg_arrivee_pen', 'num_dossier_PGCS'],
            ['Pourvois pénaux', 'pgcc_reg_pourvoi_pen', 'num_dossier_PGCS'],
            ['Pourvois civils', 'pgcc_reg_pourvoi_civ', 'num_dossier_PGCS'],
            ['Dessaisissements', 'pgcc_reg_des', 'num_dossier_PGCS'],
            ['Mémoires', 'pgcc_reg_memoires', 'num_dossier_PGCS'],
            ['Suivis 321', 'pgcc_suivi_arrivee', 'num_dossier_PGCS'],
            ['Suivis 107', 'pgcc_suivi_pen', 'num_dossier_PGCS'],
            ['Distributions', 'pgcc_distribution_pg', 'nom_liste'],
            ['Registre du siège', 'siege_cassation_civ', 'num_dossier'],
            ['Comptes utilisateurs', 'pgcc_client', 'login'],
        ];

        $requetes = [];
        foreach ($sources as [$module, $table, $reference]) {
            $requetes[] = 'SELECT ' . $db->quote($module) . ' AS module, '
                . nom_sql($reference) . ' AS reference, cree_par, modifie_par, '
                . 'date_creation, date_modification, '
                . 'COALESCE(date_modification, date_creation) AS derniere_date '
                . 'FROM ' . nom_sql($table);
        }

        $sql = 'SELECT module, reference, cree_par, modifie_par, date_creation,
                       date_modification, derniere_date
                FROM (' . implode(' UNION ALL ', $requetes) . ') journal
                WHERE derniere_date IS NOT NULL
                ORDER BY derniere_date DESC
                LIMIT ' . $limite;

        try {
            return $db->query($sql)->fetchAll();
        } catch (Throwable $e) {
            error_log('[PGCC2] Journal d\'activité indisponible : ' . $e->getMessage());
            return [];
        }
    }
}
