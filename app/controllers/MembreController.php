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
        $recherche = trim((string) (entree_post('q') ?? entree_get('q', '')));
        // Filtre par poste (parité avec la v1 : « AG Actifs » et
        // « ECD / Greffe Actifs » étaient deux listes séparées).
        $poste = (string) entree_get('poste', '');
        if (!in_array($poste, ['', 'AG', 'secretaire_AG'], true)) {
            $poste = '';
        }
        $retour = '/membres/' . $filtre . '?' . http_build_query(array_filter([
            'q' => $recherche, 'poste' => $poste,
        ]));

        // Les actions d'administration ne sont permises qu'aux postes
        // habilités ; la consultation des listes reste ouverte à tous.
        // Traitées avant le chargement de la liste : elles redirigent.
        if (requete_post()) {
            if (!est_administration($utilisateur)) {
                flash('erreur', 'Seule l\'administration du Parquet peut effectuer cette action.');
                rediriger($retour);
            }
            $this->traiterAction(
                (int) entree_post('id', 0),
                $filtre,
                $retour,
                (string) entree_post('source', 'client')
            );
        }

        // Recherche libre : filtre en mémoire (listes courtes) sur les
        // champs d'identité, de poste et de sigle. Multi-mots : chaque mot
        // saisi doit apparaître, dans n'importe quel ordre.
        $membres = (new ClientModel())->liste($filtre);
        if ($recherche !== '') {
            $mots = preg_split('/\s+/u', $recherche, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $membres = array_values(array_filter(
                $membres,
                static function (array $m) use ($mots): bool {
                    $tout = mb_strtolower($m['nom'] . ' ' . $m['prenom'] . ' ' . $m['login']
                        . ' ' . $m['poste'] . ' ' . $m['sigle_AG']);
                    foreach ($mots as $mot) {
                        if (!str_contains($tout, mb_strtolower($mot))) {
                            return false;
                        }
                    }
                    return true;
                }
            ));
        }
        if ($poste !== '') {
            $membres = array_values(array_filter(
                $membres,
                static fn (array $m): bool => $m['poste'] === $poste
            ));
        }

        View::afficher('membres/liste.php', [
            'titrePage'   => 'Membres — ' . APP_NOM,
            'filtre'      => $filtre,
            'membres'     => $membres,
            'recherche'   => $recherche,
            'poste'       => $poste,
            'presence'    => (new PresenceModel())->carte(),
            'utilisateur' => $utilisateur,
        ]);
    }

    /**
     * Traite une action d'administration sur un membre, puis revient à
     * l'onglet d'où l'action a été déclenchée (recherche conservée).
     */
    private function traiterAction(int $id, string $filtre, string $retour, string $source = 'client'): void
    {
        if (!Auth::verifierJeton(entree_post('jeton'))) {
            flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
            rediriger($retour);
        }
        if ($id <= 0) {
            flash('erreur', 'Le membre demandé est introuvable. La liste a été actualisée.');
            rediriger($retour);
        }

        $modele = new ClientModel();
        $action = (string) entree_post('action', '');

        switch ($action) {
            case 'valider':
                $source = $source === 'new' ? 'new' : 'client';
                $membre = $modele->donneesNotification($id, $source);
                if ($modele->valider($id, $source)) {
                    flash('succes', 'Le compte a été validé. Le membre peut désormais se connecter. '
                        . Mailer::notifierValidationCompte($membre));
                } else {
                    flash('erreur', 'Impossible de valider ce compte. Il a peut-être déjà été traité.');
                }
                break;

            case 'refuser':
                if ($modele->refuser($id, $source === 'new' ? 'new' : 'client')) {
                    flash('info', 'La demande d\'inscription a été refusée et supprimée.');
                } else {
                    flash('erreur', 'Impossible de refuser cette demande. Elle a peut-être déjà été traitée.');
                }
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
        rediriger($retour);
    }
}
