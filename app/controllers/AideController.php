<?php
/**
 * Contrôleur de la page d'aide intégrée : condensé du guide utilisateur
 * (docs/guide-utilisateur.md), accessible à tout membre connecté.
 */

declare(strict_types=1);

final class AideController
{
    public function index(): void
    {
        Auth::exiger();

        View::afficher('aide/index.php', [
            'titrePage' => 'Aide — ' . APP_NOM,
        ]);
    }
}
