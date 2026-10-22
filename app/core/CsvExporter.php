<?php
/**
 * Génération de fichiers CSV compatibles avec les tableurs courants.
 */

declare(strict_types=1);

final class CsvExporter
{
    /**
     * Envoie un CSV en flux afin de ne pas charger tout le résultat en mémoire.
     * Chaque colonne contient un nom de champ, un libellé et éventuellement un type.
     *
     * @param array<int, array{champ: string, libelle: string, type?: string, valeurs?: array}> $colonnes
     * @param iterable<array<string, mixed>> $lignes
     */
    public static function telecharger(string $nomFichier, array $colonnes, iterable $lignes): never
    {
        if (headers_sent()) {
            throw new RuntimeException('Le téléchargement CSV ne peut pas commencer après l’envoi du contenu.');
        }

        $nomFichier = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nomFichier) ?: 'export.csv';
        if (!str_ends_with(strtolower($nomFichier), '.csv')) {
            $nomFichier .= '.csv';
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');

        $sortie = fopen('php://output', 'wb');
        if ($sortie === false) {
            throw new RuntimeException('Impossible de préparer le fichier CSV.');
        }

        // Le BOM permet à Excel d’ouvrir correctement les caractères français.
        fwrite($sortie, "\xEF\xBB\xBF");
        fputcsv($sortie, array_column($colonnes, 'libelle'), ';', '"', '\\');

        foreach ($lignes as $ligne) {
            $valeurs = [];
            foreach ($colonnes as $colonne) {
                $valeur = $ligne[$colonne['champ']] ?? '';
                $valeur = self::formater($valeur, $colonne);
                $valeurs[] = self::protegerFormule($valeur);
            }
            fputcsv($sortie, $valeurs, ';', '"', '\\');
        }

        fclose($sortie);
        exit;
    }

    /** Empêche un tableur d’interpréter une donnée comme une formule. */
    public static function protegerFormule(mixed $valeur): string
    {
        $texte = (string) $valeur;
        if (!is_numeric($texte) && preg_match('/^[\s=+@\-]/u', $texte) === 1) {
            return "'" . $texte;
        }
        return $texte;
    }

    private static function formater(mixed $valeur, array $colonne): string
    {
        if ($valeur === null) {
            return '';
        }

        if (isset($colonne['valeurs']) && is_array($colonne['valeurs'])) {
            return (string) ($colonne['valeurs'][(string) $valeur] ?? $valeur);
        }

        return match ($colonne['type'] ?? '') {
            'date' => date_fr((string) $valeur),
            'booleen' => bin_vers_bool($valeur) ? 'Oui' : 'Non',
            default => (string) $valeur,
        };
    }
}
