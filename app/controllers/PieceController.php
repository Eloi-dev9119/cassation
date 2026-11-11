<?php
/**
 * Contrôleur des pièces jointes : dépôt, téléchargement et suppression des
 * documents scannés rattachés à un mémoire ou à un dossier de registre.
 *
 * Les fichiers ne sont jamais servis directement par Apache (storage/ est
 * bloqué par le .htaccess) : chaque téléchargement passe par ce contrôleur,
 * qui vérifie la session et renvoie le bon type MIME.
 */

declare(strict_types=1);

final class PieceController
{
    public function dispatch(array $route): void
    {
        Auth::exiger();

        match ($route[1] ?? '') {
            'ajouter'      => $this->ajouter(),
            'telecharger'  => $this->telecharger(),
            'supprimer'    => $this->supprimer(),
            default        => page_404('Action de pièce jointe inconnue'),
        };
    }

    /**
     * Dépôt d'un fichier (POST multipart). Retourne à la page d'origine
     * (paramètre « retour ») avec un message de succès ou d'erreur.
     */
    private function ajouter(): void
    {
        $entite = (string) entree_post('entite', '');
        $entiteId = (int) entree_post('entite_id', 0);
        $retour = self::retourSur();

        if (!requete_post()) {
            flash('erreur', 'Cette action doit être envoyée depuis le formulaire prévu.');
            rediriger($retour);
        }
        if (!Auth::verifierJeton(entree_post('jeton'))) {
            flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
            rediriger($retour);
        }
        if (!self::entiteValide($entite) || $entiteId < 1) {
            flash('erreur', 'Pièce jointe : entité de rattachement invalide.');
            rediriger($retour);
        }

        try {
            (new PieceModel())->ajouter($entite, $entiteId, (array) ($_FILES['fichier'] ?? []));
            flash('succes', 'La pièce jointe a été ajoutée.');
        } catch (RuntimeException $e) {
            flash('erreur', $e->getMessage());
        }
        rediriger($retour);
    }

    /**
     * Téléchargement d'une pièce : affichage dans le navigateur pour les
     * PDF et images, téléchargement pur pour les autres formats.
     */
    private function telecharger(): void
    {
        $modele = new PieceModel();
        $piece = $modele->trouver((int) entree_get('id', 0));
        if ($piece === null) {
            page_404('Pièce jointe introuvable');
            return;
        }

        $chemin = $modele->cheminFichier($piece);
        if (!is_file($chemin)) {
            http_response_code(404);
            View::afficher('erreurs/404.php', ['titrePage' => 'Fichier introuvable']);
            return;
        }

        $mime = (string) ($piece['mime'] ?? 'application/octet-stream');
        $affichable = in_array($mime, ['application/pdf', 'image/png', 'image/jpeg', 'image/gif'], true);
        $nom = (string) ($piece['nom_original'] ?? 'document');
        $nom = str_replace(['"', "\r", "\n"], '', $nom);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . ($affichable ? 'inline' : 'attachment') . '; filename="' . $nom . '"');
        header('Content-Length: ' . (string) filesize($chemin));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0');

        readfile($chemin);
        exit;
    }

    /** Suppression d'une pièce (POST). */
    private function supprimer(): void
    {
        $retour = self::retourSur();

        if (!requete_post()) {
            flash('erreur', 'Cette action doit être envoyée depuis le formulaire prévu.');
            rediriger($retour);
        }
        if (!Auth::verifierJeton(entree_post('jeton'))) {
            flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
            rediriger($retour);
        }

        $supprimee = (new PieceModel())->supprimer((int) entree_post('id', 0));
        flash($supprimee ? 'succes' : 'erreur',
            $supprimee ? 'La pièce jointe a été supprimée.' : 'Pièce jointe introuvable.');
        rediriger($retour);
    }

    /**
     * Entités de rattachement autorisées : les mémoires et les dossiers des
     * registres déclarés ('dossier_' + code de registre).
     */
    private static function entiteValide(string $entite): bool
    {
        if ($entite === 'memoire') {
            return true;
        }
        return str_starts_with($entite, 'dossier_')
            && registre_definition(substr($entite, 8)) !== null;
    }

    /**
     * URL de retour fournie par le formulaire : uniquement un chemin local
     * (pas de « :// » ni de « // » initial, pour éviter toute redirection
     * vers un site externe).
     */
    private static function retourSur(): string
    {
        $retour = trim((string) (entree_post('retour') ?? entree_get('retour') ?? ''));
        if ($retour === '' || !str_starts_with($retour, '/')
                || str_starts_with($retour, '//') || str_contains($retour, '://')) {
            return '/tableau-de-bord';
        }
        return $retour;
    }
}
