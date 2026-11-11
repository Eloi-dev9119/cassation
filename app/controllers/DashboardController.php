<?php
/**
 * Contrôleur du tableau de bord : compteurs, graphique, recherche globale,
 * conclusions à traiter et activité récente.
 */

declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        $utilisateur = Auth::exiger();
        $stats = new StatsModel();

        // Recherche globale multi-registres déclenchée depuis le tableau de
        // bord (paramètre « q »). Le terme est nettoyé et sa longueur est
        // vérifiée par RechercheModel.
        $terme = entree_get('q', '');
        $recherche = is_string($terme) ? trim($terme) : '';
        $resultats = null;
        if ($recherche !== '') {
            $resultats = (new RechercheModel())->rechercher($recherche, 6);
        }

        View::afficher('dashboard/accueil.php', [
            'titrePage'   => 'Tableau de bord — ' . APP_NOM,
            'utilisateur' => $utilisateur,
            'compteurs'   => $stats->compteurs(),
            'parAnnee'    => $stats->dossiersParAnnee(),
            'recherche'   => $recherche,
            'resultats'   => $resultats,
            'conclusions' => $stats->conclusionsArediger(),
            'recents'     => $stats->dossiersRecents(6),
        ]);
    }
}
