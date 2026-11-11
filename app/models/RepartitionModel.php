<?php
/**
 * Modèle de la « Répartition des dossiers » : comptage mensuel des
 * distributions par Avocat Général et par type (PEN / CIV), comme la
 * page repartitionDossier.php de la v1.
 */

declare(strict_types=1);

final class RepartitionModel
{
    /** Mois de l'année, libellés utilisés dans les noms de listes. */
    public const MOIS = [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
    ];

    /**
     * Comptage des distributions par AG pour un mois et une année.
     *
     * Le rattachement au mois se fait comme dans la v1 : le nom de la
     * liste de distribution contient le mois (nom_liste LIKE %Mois%).
     *
     * @return array{ ags: string[], penaux: int[], civils: int[] }
     */
    public function parAvocatGeneral(string $mois, int $annee): array
    {
        $bdd = Database::connexion();

        $ags = $bdd->query(
            'SELECT DISTINCT sigle FROM pgcc_distribution_pg WHERE sigle <> "" ORDER BY sigle ASC'
        )->fetchAll(PDO::FETCH_COLUMN);

        // Une seule requête groupée remplace les 2 × N comptages unitaires
        // (un par sigle) qui rendaient la page lente : un scan unique de la
        // table suffit, puis on complète les AG sans distribution avec 0.
        $req = $bdd->prepare(
            'SELECT sigle,
                    SUM(type_dossier = "pen") AS penaux,
                    SUM(type_dossier = "civ") AS civils
             FROM pgcc_distribution_pg
             WHERE nom_liste LIKE :mois AND annee = :annee AND sigle <> ""
             GROUP BY sigle'
        );
        $req->execute(['mois' => "%$mois%", 'annee' => $annee]);

        $compteurs = [];
        foreach ($req->fetchAll() as $ligne) {
            $compteurs[(string) $ligne['sigle']] = [
                'penaux' => (int) $ligne['penaux'],
                'civils' => (int) $ligne['civils'],
            ];
        }

        $penaux = [];
        $civils = [];
        foreach ($ags as $sigle) {
            $penaux[] = $compteurs[$sigle]['penaux'] ?? 0;
            $civils[] = $compteurs[$sigle]['civils'] ?? 0;
        }

        return ['ags' => $ags, 'penaux' => $penaux, 'civils' => $civils];
    }

    /**
     * Dernière année comportant des distributions (année par défaut).
     */
    public function derniereAnnee(): int
    {
        return (int) (Database::connexion()
            ->query('SELECT COALESCE(MAX(annee), YEAR(CURDATE())) FROM pgcc_distribution_pg')
            ->fetchColumn());
    }
}
