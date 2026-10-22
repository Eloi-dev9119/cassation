<?php
/**
 * Contrôleur du tableau de bord : compteurs, graphique et activité récente.
 */

declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        $utilisateur = Auth::exiger();
        $stats = new StatsModel();

        View::afficher('dashboard/accueil.php', [
            'titrePage' => 'Tableau de bord — ' . APP_NOM,
            'utilisateur' => $utilisateur,
            'compteurs'   => $stats->compteurs(),
            'parAnnee'    => $stats->dossiersParAnnee(),
        ]);
    }
}
