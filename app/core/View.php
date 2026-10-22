<?php
/**
 * Moteur de rendu des vues.
 *
 * Une vue est un fichier PHP placé dans le dossier « views ». Le rendu
 * s'effectue toujours à l'intérieur d'un gabarit (layout) commun qui
 * affiche l'en-tête, le menu de navigation et le pied de page.
 */

declare(strict_types=1);

final class View
{
    /**
     * Affiche une vue dans le gabarit principal.
     *
     * @param string $vue      Chemin de la vue relatif au dossier views/
     *                         (ex. 'registres/liste.php').
     * @param array  $donnees  Variables rendues disponibles dans la vue.
     */
    public static function afficher(string $vue, array $donnees = []): void
    {
        $contenu = self::contenu($vue, $donnees);
        $titre = $donnees['titrePage'] ?? APP_NOM;
        require APP_CHEMIN . '/app/views/layout/gabarit.php';
    }

    /**
     * Affiche une vue sans le gabarit (ex. écran de connexion).
     */
    public static function afficherBrut(string $vue, array $donnees = []): void
    {
        $titre = $donnees['titrePage'] ?? APP_NOM;
        require APP_CHEMIN . '/app/views/layout/gabarit_brut.php';
        echo self::contenu($vue, $donnees);
        require APP_CHEMIN . '/app/views/layout/pied.php';
    }

    /**
     * Produit le contenu d'une vue et le retourne sous forme de chaîne.
     * Le paramètre s'appelle volontairement $variables (et non $donnees)
     * pour éviter qu'une clé « données » homonyme ne soit ignorée par
     * extract() en mode EXTR_SKIP.
     */
    private static function contenu(string $vue, array $variables): string
    {
        extract($variables, EXTR_SKIP);
        ob_start();
        require APP_CHEMIN . '/app/views/' . $vue;
        return (string) ob_get_clean();
    }
}
