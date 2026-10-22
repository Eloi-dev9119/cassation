<?php
declare(strict_types=1);

/** Accès aux liens entre les dossiers civils et le registre du Siège. */
final class LiaisonRegistreModel
{
    public function dossiersCivils(): array
    {
        $req = Database::connexion()->query(
            'SELECT id_reg_pourvoi, num_dossier_PGCS, annee, cassationnaire
             FROM pgcc_reg_pourvoi_civ ORDER BY annee DESC, num_ordre DESC'
        );
        return $req->fetchAll();
    }

    public function trouverDossierCivil(int $id): ?array
    {
        $req = Database::connexion()->prepare(
            'SELECT id_reg_pourvoi, num_dossier_PGCS, annee, cassationnaire
             FROM pgcc_reg_pourvoi_civ WHERE id_reg_pourvoi = :id'
        );
        $req->execute(['id' => $id]);
        $dossier = $req->fetch();
        return $dossier === false ? null : $dossier;
    }

    public function siegesPourDossierCivil(int $id): array
    {
        $req = Database::connexion()->prepare(
            'SELECT id_civil, num_dossier, annee FROM siege_cassation_civ
             WHERE id_reg_pourvoi_civ = :id ORDER BY annee DESC, id_civil DESC'
        );
        $req->execute(['id' => $id]);
        return $req->fetchAll();
    }
}
