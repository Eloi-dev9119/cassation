<?php
/**
 * Modèle des statistiques du tableau de bord.
 *
 * Les volumes sont calculés directement en base (compteurs SQL) puis
 * mis en forme pour un graphique SVG généré côté serveur.
 */

declare(strict_types=1);

final class StatsModel
{
    /**
     * Compteurs généraux affichés sur le tableau de bord.
     */
    public function compteurs(): array
    {
        $bdd = Database::connexion();
        return [
            'arrivees'        => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_reg_arrivee_pen')->fetchColumn(),
            'penaux'          => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_reg_pourvoi_pen')->fetchColumn(),
            'civils'          => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_reg_pourvoi_civ')->fetchColumn(),
            'dessaisissements'=> (int) $bdd->query('SELECT COUNT(*) FROM pgcc_reg_des')->fetchColumn(),
            'memoires'        => (int) @$bdd->query('SELECT COUNT(*) FROM pgcc_reg_memoires')->fetchColumn(),
            'detentions'      => (int) $bdd->query("SELECT COUNT(*) FROM pgcc_reg_pourvoi_pen WHERE detention = 0x31")->fetchColumn(),
            'membresActifs'   => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_client WHERE valide = 1 AND status = "Actifs"')->fetchColumn(),
            'enAttente'       => (int) $bdd->query('SELECT COUNT(*) FROM pgcc_client WHERE valide = 0')->fetchColumn(),
        ];
    }

    /**
     * Volume de dossiers par année et par registre (8 dernières années).
     *
     * @return array{ annees: int[], series: array<string, int[]> }
     */
    public function dossiersParAnnee(): array
    {
        $bdd = Database::connexion();

        $annees = [];
        $req = $bdd->query(
            'SELECT annee FROM (
                SELECT annee FROM pgcc_reg_pourvoi_pen
                UNION SELECT annee FROM pgcc_reg_pourvoi_civ
                UNION SELECT annee FROM pgcc_reg_arrivee_pen
             ) t ORDER BY annee DESC LIMIT 8'
        );
        foreach ($req->fetchAll(PDO::FETCH_COLUMN) as $a) {
            $annees[] = (int) $a;
        }
        $annees = array_values(array_unique(array_filter($annees, fn ($a) => $a > 0)));
        sort($annees);

        $series = [];
        foreach (['pgcc_reg_pourvoi_pen' => 'Pénaux', 'pgcc_reg_pourvoi_civ' => 'Civils', 'pgcc_reg_arrivee_pen' => 'Arrivées'] as $table => $libelle) {
            $req = $bdd->prepare("SELECT annee, COUNT(*) n FROM `$table` GROUP BY annee");
            $req->execute();
            $parAnnee = [];
            foreach ($req->fetchAll() as $l) {
                $parAnnee[(int) $l['annee']] = (int) $l['n'];
            }
            $series[$libelle] = array_map(fn ($a) => $parAnnee[$a] ?? 0, $annees);
        }

        return ['annees' => $annees, 'series' => $series];
    }
}
