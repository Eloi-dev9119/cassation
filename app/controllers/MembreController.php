<?php
/**
 * Contrôleur de gestion des membres du Parquet.
 *
 * Onglets : membres en attente de validation, actifs, retraités.
 * Actions réservées à l'administration (constante POSTES_ADMINISTRATION) :
 * validation de compte, mise à la retraite / réactivation,
 * réinitialisation de mot de passe.
 */

declare(strict_types=1);

final class MembreController
{
    public function dispatch(array $route): void
    {
        $utilisateur = Auth::exiger();

        $filtre = $route[1] ?? 'attente';
        if (!in_array($filtre, ['attente', 'actifs', 'retraites'], true)) {
            $filtre = 'attente';
        }

        // Les actions d'administration ne sont permises qu'aux postes
        // habilités ; la consultation des listes reste ouverte à tous.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!est_administration($utilisateur)) {
                flash('erreur', 'Seule l\'administration du Parquet peut effectuer cette action.');
                rediriger('/membres/' . $filtre);
            }
            $this->traiterAction(
                (int) ($_POST['id'] ?? 0),
                $filtre,
                (string) ($_POST['source'] ?? 'client')
            );
        }

        View::afficher('membres/liste.php', [
            'titrePage'   => 'Membres — ' . APP_NOM,
            'filtre'      => $filtre,
            'membres'     => (new ClientModel())->liste($filtre),
            'utilisateur' => $utilisateur,
        ]);
    }

    /**
     * Traite une action d'administration sur un membre, puis revient à
     * l'onglet d'où l'action a été déclenchée.
     */
    private function traiterAction(int $id, string $filtre, string $source = 'client'): void
    {
        if (!Auth::verifierJeton($_POST['jeton'] ?? null)) {
            flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
            rediriger('/membres/' . $filtre);
        }
        if ($id <= 0) {
            flash('erreur', 'Le membre demandé est introuvable. La liste a été actualisée.');
            rediriger('/membres/' . $filtre);
        }

        $modele = new ClientModel();
        $action = (string) ($_POST['action'] ?? '');

        switch ($action) {
            case 'valider':
                $modele->valider($id, $source === 'new' ? 'new' : 'client');
                flash('succes', 'Le compte a été validé. Le membre peut désormais se connecter.');
                break;

            case 'retraite':
                $modele->changerStatus($id, 'Retraite');
                flash('info', 'Le membre a été mis à la retraite.');
                break;

            case 'reactiver':
                $modele->changerStatus($id, 'Actifs');
                flash('succes', 'Le membre a été réactivé.');
                break;

            case 'motdepasse':
                // Mot de passe par défaut : « pgcc » suivi de l'année
                // courante (convention de la v1). Il est calculé côté
                // serveur et n'apparaît jamais dans le code HTML.
                $nouveau = 'pgcc' . date('Y');
                $modele->changerMotDePasse($id, $nouveau);
                flash('succes', 'Le mot de passe temporaire est « ' . $nouveau . ' ». Transmettez-le au membre par un canal sécurisé.');
                break;

            default:
                flash('erreur', 'Action inconnue.');
        }
        rediriger('/membres/' . $filtre);
    }
}
