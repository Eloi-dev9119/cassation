<?php
/**
 * Générateur PDF autonome (sans bibliothèque externe).
 *
 * Produit des documents A4 portrait avec les polices standard Helvetica
 * (embarquées dans tous les lecteurs PDF) : titres, tableaux avec
 * en-têtes gris et retour à la ligne automatique, texte long avec
 * numérotation des pages. L'encodage WinAnsi gère les accents français.
 *
 * Deux points d'entrée, calqués sur CsvExporter :
 *  - telechargerTableau() : liste de lignes (registres, mémoires) ;
 *  - telechargerTexte()   : document long (conclusion d'un dossier).
 */

declare(strict_types=1);

final class PdfExporter
{
    private const LARGEUR = 595.28;   // A4 portrait, points
    private const HAUTEUR = 841.89;
    private const MARGE = 48.0;
    private const INTERLIGNE = 1.45;

    /**
     * Garde-fous des exports tabulaires. Les registres contiennent des
     * colonnes LONGTEXT (conclusions Word de plusieurs dizaines de Ko) :
     * sans plafond, un export de 8 000 lignes produit un PDF de plusieurs
     * milliers de pages et dépasse le temps d'exécution maximal.
     * L'export CSV reste disponible pour la liste complète.
     */
    private const MAX_LIGNES = 1000;
    private const MAX_CARACTERES_CELLULE = 300;
    /** Nombre maximal de lignes imprimées par cellule (au-delà : « … »). */
    private const MAX_LIGNES_CELLULE = 3;

    /** Cache des largeurs de caractères (unités Helvetica /1000). */
    private static array $largeursCache = [];

    /** Largeurs Helvetica (unités /1000) pour l'ASCII imprimable ; les
     *  caractères accentués héritent de la largeur de leur lettre de base. */
    private const LARGEURS = [
        ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889,
        '&' => 667, "'" => 191, '(' => 333, ')' => 333, '*' => 389, '+' => 584,
        ',' => 278, '-' => 333, '.' => 278, '/' => 278, '0' => 556, '1' => 556,
        '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556,
        '8' => 556, '9' => 556, ':' => 278, ';' => 278, '<' => 584, '=' => 584,
        '>' => 584, '?' => 556, '@' => 1015, 'A' => 667, 'B' => 667, 'C' => 722,
        'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722, 'I' => 278,
        'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778,
        'P' => 667, 'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722,
        'V' => 667, 'W' => 944, 'X' => 667, 'Y' => 667, 'Z' => 611, '[' => 278,
        '\\' => 278, ']' => 278, '^' => 469, '_' => 556, '`' => 333, 'a' => 556,
        'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556, 'f' => 278, 'g' => 556,
        'h' => 556, 'i' => 222, 'j' => 222, 'k' => 500, 'l' => 222, 'm' => 833,
        'n' => 556, 'o' => 556, 'p' => 556, 'q' => 556, 'r' => 333, 's' => 500,
        't' => 278, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500, 'y' => 500,
        'z' => 500, '{' => 334, '|' => 260, '}' => 334, '~' => 584,
    ];

    /** Suppléments ASCII 7 bits pour les lettres accentuées (cp1252 → base). */
    private const BASE_LATINE = ['à' => 'a', 'â' => 'a', 'ä' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e',
        'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u',
        'ü' => 'u', 'ÿ' => 'y', 'œ' => 'oe', 'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'À' => 'A', 'Â' => 'A',
        'Ç' => 'C', 'Ô' => 'O', 'Î' => 'I', 'Û' => 'U', 'Ù' => 'U', 'Œ' => 'OE', '’' => "'"];

    /** @var string[] Contenu (commandes PDF) de chaque page, index 0 = page 1. */
    private array $pages = [];
    /** @var string[] Lignes en cours de la page. */
    private array $courant = [];
    private float $y = 0.0;
    /** @var string[] Libellés d'en-têtes du tableau courant (répétés à chaque page). */
    private array $libellesTableau = [];

    // ------------------------------------------------------------------ //
    // Points d'entrée
    // ------------------------------------------------------------------ //

    /**
     * Exporte un tableau : titre, date d'édition, en-têtes gris, lignes avec
     * retour à la ligne automatique, numéros de page.
     *
     * @param array<int, array{champ: string, libelle: string}> $colonnes
     * @param iterable<array<string, mixed>> $lignes
     */
    public static function telechargerTableau(string $nomFichier, string $titre, array $colonnes, iterable $lignes): never
    {
        $pdf = new self();
        $pdf->enteteDocument($titre, 'Édition du ' . date('d/m/Y à H:i'));

        $libelles = array_column($colonnes, 'libelle');
        $largeurs = self::largeursColonnes($libelles);
        $pdf->libellesTableau = $libelles;
        $pdf->ligneEntetes($libelles, $largeurs);

        $pdf->espace(6);
        $gris = false;
        $exportees = 0;
        $tronque = false;
        foreach ($lignes as $ligne) {
            if ($exportees >= self::MAX_LIGNES) {
                $tronque = true;
                break;
            }
            $cellules = [];
            foreach ($colonnes as $colonne) {
                $brut = $ligne[$colonne['champ']] ?? '';
                if ($brut instanceof DateTimeInterface) {
                    $brut = $brut->format('d/m/Y');
                }
                $brut = (string) $brut;
                // Cellule contenant du HTML (conclusion collée depuis Word) :
                // le PDF doit imprimer le texte, pas les balises.
                if (str_contains($brut, '<') && preg_match('/<\/?[a-z][^>]*>/i', $brut) === 1) {
                    $brut = self::htmlVersTexte($brut);
                }
                $brut = trim((string) (preg_replace('/\s+/u', ' ', $brut) ?? $brut));
                if (mb_strlen($brut) > self::MAX_CARACTERES_CELLULE) {
                    $brut = mb_substr($brut, 0, self::MAX_CARACTERES_CELLULE - 1) . '…';
                }
                $cellules[] = $brut !== '' ? $brut : '—';
            }
            $pdf->ligneTableau($cellules, $largeurs, $gris);
            $gris = !$gris;
            $exportees++;
        }

        if ($tronque) {
            $pdf->noteExport(
                'Export limité aux ' . self::MAX_LIGNES . ' premières lignes. '
                . 'Utilisez l’export CSV pour obtenir la liste complète.'
            );
        }

        $pdf->envoyer($nomFichier);
    }

    /**
     * Exporte un document texte long (conclusion). Les balises HTML issues
     * de Word sont converties en retours à la ligne avant impression.
     */
    public static function telechargerTexte(string $nomFichier, string $titre, string $texte): never
    {
        $texte = self::htmlVersTexte($texte);

        $pdf = new self();
        $pdf->enteteDocument($titre, 'Édition du ' . date('d/m/Y à H:i'));
        $pdf->espace(8);
        $pdf->paragraphe($texte, 11.0);

        $pdf->envoyer($nomFichier);
    }

    // ------------------------------------------------------------------ //
    // Construction du document
    // ------------------------------------------------------------------ //

    private function enteteDocument(string $titre, string $sousTitre): void
    {
        $this->nouvellePage();
        $this->texte($titre, self::MARGE, $this->y, 16.0, 'F2');
        $this->y -= 4;
        $this->ligne(self::MARGE, $this->y, self::LARGEUR - self::MARGE, $this->y, 0.55);
        $this->y -= 14;
        $this->texte($sousTitre, self::MARGE, $this->y, 9.0, 'F1', 0.45);
        $this->y -= 16;
    }

    private function ligneEntetes(array $libelles, array $largeurs): void
    {
        $this->rect(self::MARGE, $this->y + 3, self::LARGEUR - 2 * self::MARGE, 18.0, 0.88);
        $x = self::MARGE;
        foreach ($libelles as $i => $libelle) {
            $this->texte($libelle, $x + 3, $this->y - 2, 8.5, 'F2');
            $x += $largeurs[$i];
        }
        $this->y -= 15;
    }

    /** Une ligne du tableau ; passage à la page suivante si nécessaire. */
    private function ligneTableau(array $cellules, array $largeurs, bool $gris): void
    {
        $cellules = array_values($cellules);
        $wraps = [];
        $nbLignes = 1;
        foreach ($cellules as $i => $texte) {
            $wraps[$i] = self::couper($texte, $largeurs[$i] - 6, 8.5);
            // Une cellule trop longue est tronquée : le PDF de liste reste
            // un document imprimable (l'export CSV contient tout le texte).
            if (count($wraps[$i]) > self::MAX_LIGNES_CELLULE) {
                $wraps[$i] = array_slice($wraps[$i], 0, self::MAX_LIGNES_CELLULE);
                $derniere = array_key_last($wraps[$i]);
                $wraps[$i][$derniere] = rtrim($wraps[$i][$derniere]) . '…';
            }
            $nbLignes = max($nbLignes, count($wraps[$i]));
        }
        $hauteur = ($nbLignes * 8.5 * self::INTERLIGNE) + 5;

        if ($this->y - $hauteur < self::MARGE + 22) {
            $this->nouvellePage();
            $this->ligneEntetes($this->libellesTableau, $largeurs);
        }

        if ($gris) {
            $this->rect(self::MARGE, $this->y + 3, self::LARGEUR - 2 * self::MARGE, $hauteur - 2, 0.965);
        }

        $x = self::MARGE;
        foreach ($wraps as $i => $lignes) {
            $yy = $this->y;
            foreach ($lignes as $ligne) {
                $this->texte($ligne, $x + 3, $yy, 8.5, 'F1');
                $yy -= 8.5 * self::INTERLIGNE;
            }
            $x += $largeurs[$i];
        }
        $this->y -= $hauteur;
    }

    /** Note discrète de bas de tableau (export tronqué, avertissement). */
    private function noteExport(string $texte): void
    {
        if ($this->y - 24 < self::MARGE + 22) {
            $this->nouvellePage();
        }
        $this->espace(8);
        $this->ligne(self::MARGE, $this->y + 4, self::LARGEUR - self::MARGE, $this->y + 4, 0.8);
        $this->espace(10);
        $this->texte($texte, self::MARGE, $this->y, 8.5, 'F1', 0.4);
        $this->y -= 12;
    }

    /** Paragraphe de texte, retour à la ligne automatique, pages enchaînées. */
    private function paragraphe(string $texte, float $taille): void
    {
        $largeurUtile = self::LARGEUR - 2 * self::MARGE;
        foreach (preg_split('/\r\n|\r|\n/', $texte) ?: [] as $paragraphe) {
            $paragraphe = trim($paragraphe);
            if ($paragraphe === '') {
                $this->espace($taille * 0.6);
                continue;
            }
            foreach (self::couper($paragraphe, $largeurUtile, $taille) as $ligne) {
                if ($this->y - $taille < self::MARGE + 22) {
                    $this->nouvellePage();
                }
                $this->texte($ligne, self::MARGE, $this->y, $taille, 'F1');
                $this->y -= $taille * self::INTERLIGNE;
            }
        }
    }

    private function espace(float $points): void
    {
        $this->y -= $points;
    }

    private function nouvellePage(): void
    {
        if ($this->courant !== []) {
            $this->pages[] = $this->courant;
        }
        $this->courant = [];
        $this->y = self::HAUTEUR - self::MARGE;
    }

    // ------------------------------------------------------------------ //
    // Primitives
    // ------------------------------------------------------------------ //

    private function texte(string $texte, float $x, float $y, float $taille, string $police = 'F1', float $gris = 0.0): void
    {
        $octets = self::versWinAnsi($texte);
        if ($octets === '') {
            return;
        }
        $couleur = sprintf('%.2F %.2F %.2F rg', $gris, $gris, $gris);
        $this->courant[] = $couleur
            . sprintf(' /%s %.2F Tf ', $police, $taille)
            . sprintf('BT %.2F %.2F Td (%s) Tj ET', $x, $y, $octets);
    }

    private function ligne(float $x1, float $y1, float $x2, float $y2, float $gris): void
    {
        $this->courant[] = sprintf('%.2F %.2F %.2F RG %.2F w %.2F %.2F m %.2F %.2F l S',
            $gris, $gris, $gris, max(0.3, $gris * 0.6), $x1, $y1, $x2, $y2);
    }

    private function rect(float $x, float $y, float $l, float $h, float $gris): void
    {
        $this->courant[] = sprintf('%.2F %.2F %.2F rg %.2F %.2F %.2F %.2F re f',
            $gris, $gris, $gris, $x, $y, $l, $h);
    }

    // ------------------------------------------------------------------ //
    // Assemblage et envoi
    // ------------------------------------------------------------------ //

    private function envoyer(string $nomFichier): never
    {
        $this->pages[] = $this->courant;

        $total = count($this->pages);
        foreach ($this->pages as $numero => $contenu) {
            $libelle = 'Page ' . ($numero + 1) . ' / ' . $total;
            $largeurUtile = self::LARGEUR - 2 * self::MARGE;
            $x = self::MARGE + ($largeurUtile - self::largeurTexte($libelle, 8.0)) / 2;
            $contenu[] = sprintf('0.45 0.45 0.45 rg /F1 8.00 Tf BT %.2F %.2F Td (%s) Tj ET',
                $x, self::MARGE - 18, self::versWinAnsi($libelle));
            $this->pages[$numero] = $contenu;
        }

        $objets = [];
        $objets[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objets[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(
            static fn (int $i): string => (5 + 2 * $i) . ' 0 R',
            array_keys($this->pages)
        )) . '] /Count ' . count($this->pages) . ' >>';
        $objets[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objets[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $idPage = 5;
        foreach ($this->pages as $contenu) {
            $flux = implode("\n", $contenu);
            $objets[$idPage] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '
                . self::LARGEUR . ' ' . self::HAUTEUR . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '
                . ($idPage + 1) . ' 0 R >>';
            $objets[$idPage + 1] = '<< /Length ' . strlen($flux) . " >>\nstream\n" . $flux . "\nendstream";
            $idPage += 2;
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $positions = [0 => 0];
        foreach ($objets as $id => $corps) {
            $positions[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $corps . "\nendobj\n";
        }

        $debutXref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objets) + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objets); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $positions[$id]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objets) + 1) . " /Root 1 0 R >>\nstartxref\n" . $debutXref . "\n%%EOF";

        $nomFichier = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nomFichier) ?: 'document';
        if (!str_ends_with(strtolower($nomFichier), '.pdf')) {
            $nomFichier .= '.pdf';
        }

        if (headers_sent()) {
            throw new RuntimeException('L’export PDF ne peut pas commencer après l’envoi du contenu.');
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');

        echo $pdf;
        exit;
    }

    // ------------------------------------------------------------------ //
    // Mesure et découpage du texte
    // ------------------------------------------------------------------ //

    /** Largeur (unités Helvetica /1000) d'un caractère, avec cache. */
    private static function largeurCaractere(string $caractere): float
    {
        if (isset(self::$largeursCache[$caractere])) {
            return self::$largeursCache[$caractere];
        }
        $base = self::BASE_LATINE[$caractere] ?? $caractere;
        return self::$largeursCache[$caractere] = (float) (self::LARGEURS[$base] ?? 556);
    }

    private static function largeurTexte(string $texte, float $taille): float
    {
        $total = 0.0;
        foreach (mb_str_split($texte) as $caractere) {
            $total += self::largeurCaractere($caractere);
        }
        return $total * $taille / 1000.0;
    }

    /**
     * Découpe un texte en lignes tenant dans la largeur donnée (césure sur
     * espaces ; mot plus long que la ligne coupé caractère par caractère).
     *
     * Algorithme linéaire : la largeur est accumulée au fil des caractères,
     * sans jamais recalculer la largeur d'un préfixe (l'ancienne version
     * était en O(n²) et dépassait le temps d'exécution maximal sur les
     * conclusions Word volumineuses).
     *
     * @return string[]
     */
    private static function couper(string $texte, float $largeur, float $taille): array
    {
        $texte = trim($texte);
        if ($texte === '') {
            return [''];
        }

        $facteur = $taille / 1000.0;
        $largeurEspace = self::largeurCaractere(' ') * $facteur;

        $lignes = [];
        $courante = '';
        $largeurCourante = 0.0;

        foreach (preg_split('/\s+/u', $texte) ?: [] as $mot) {
            $largeurMot = self::largeurTexte($mot, $taille);

            // Mot plus large qu'une ligne complète : coupe dure caractère
            // par caractère (un caractère reste toujours sur la ligne pour
            // garantir la progression).
            if ($largeurMot > $largeur) {
                if ($courante !== '') {
                    $lignes[] = $courante;
                    $courante = '';
                    $largeurCourante = 0.0;
                }
                $morceau = '';
                $largeurMorceau = 0.0;
                foreach (mb_str_split($mot) as $caractere) {
                    $largeurCaractere = self::largeurCaractere($caractere) * $facteur;
                    if ($morceau !== '' && $largeurMorceau + $largeurCaractere > $largeur) {
                        $lignes[] = $morceau;
                        $morceau = $caractere;
                        $largeurMorceau = $largeurCaractere;
                    } else {
                        $morceau .= $caractere;
                        $largeurMorceau += $largeurCaractere;
                    }
                }
                $courante = $morceau;
                $largeurCourante = $largeurMorceau;
                continue;
            }

            $largeurAjout = $courante === '' ? $largeurMot : $largeurEspace + $largeurMot;
            if ($largeurCourante + $largeurAjout <= $largeur) {
                $courante = $courante === '' ? $mot : $courante . ' ' . $mot;
                $largeurCourante += $largeurAjout;
            } else {
                if ($courante !== '') {
                    $lignes[] = $courante;
                }
                $courante = $mot;
                $largeurCourante = $largeurMot;
            }
        }

        if (trim($courante) !== '') {
            $lignes[] = $courante;
        }
        return $lignes !== [] ? $lignes : [''];
    }

    /** UTF-8 → cp1252 (WinAnsi) avec échappement des délimiteurs PDF. */
    private static function versWinAnsi(string $texte): string
    {
        $conv = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texte);
        if ($conv === false) {
            $conv = preg_replace('/[^\x20-\x7E]/', '?', $texte) ?? '';
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $conv);
    }

    /** HTML collé depuis Word → texte brut avec retours à la ligne. */
    private static function htmlVersTexte(string $html): string
    {
        $html = preg_replace('/<(br|BR)\s*\/?>/', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(p|div|tr|h[1-6]|li|table)>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(td|th)>/i', ' — ', $html) ?? $html;
        $texte = strip_tags($html);
        $texte = html_entity_decode($texte, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texte = str_replace("\xC2\xA0", ' ', $texte);
        $texte = preg_replace("/[ \t]+\n/", "\n", $texte) ?? $texte;
        $texte = preg_replace("/\n{3,}/", "\n\n", $texte) ?? $texte;
        return trim($texte);
    }

    /**
     * Répartition des largeurs de colonnes sur la largeur utile, pondérée
     * par la longueur des libellés (min 55 pt, max 180 pt).
     *
     * @param string[] $libelles
     * @return float[]
     */
    private static function largeursColonnes(array $libelles): array
    {
        $utile = self::LARGEUR - 2 * self::MARGE;
        $poids = array_map(
            static fn (string $l): float => max(12.0, (float) mb_strlen($l)),
            $libelles
        );
        $somme = array_sum($poids);
        $largeurs = array_map(
            static fn (float $p): float => max(55.0, min(180.0, $utile * $p / $somme)),
            $poids
        );
        // Ajuste la dernière colonne pour occuper exactement la largeur utile.
        $ecart = $utile - array_sum($largeurs);
        $largeurs[count($largeurs) - 1] += $ecart;
        return $largeurs;
    }
}
