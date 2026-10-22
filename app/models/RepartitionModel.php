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

        $reqPen = $bdd->prepare(
            'SELECT COUNT(*) FROM pgcc_distribution_pg
             WHERE nom_liste LIKE :mois AND annee = :annee AND sigle = :sigle AND type_dossier = "pen"'
        );
        $reqCiv = $bdd->prepare(
            'SELECT COUNT(*) FROM pgcc_distribution_pg
             WHERE nom_liste LIKE :mois AND annee = :annee AND sigle = :sigle AND type_dossier = "civ"'
        );

        $penaux = [];
        $civils = [];
        foreach ($ags as $sigle) {
            $reqPen->execute(['mois' => "%$mois%", 'annee' => $annee, 'sigle' => $sigle]);
            $penaux[] = (int) $reqPen->fetchColumn();
            $reqCiv->execute(['mois' => "%$mois%", 'annee' => $annee, 'sigle' => $sigle]);
            $civils[] = (int) $reqCiv->fetchColumn();
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
