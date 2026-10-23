<?php
/**
 * Graphiques SVG générés côté serveur, sans dépendance externe.
 */

declare(strict_types=1);

final class Graphiques
{
    /**
     * Affiche le volume annuel de dossiers dans un graphique à barres groupées.
     *
     * @param int[] $annees
     * @param array<string, int[]> $series Libellé => valeurs, dans l'ordre des années.
     */
    public static function barresParAnnee(array $annees, array $series): string
    {
        if ($annees === []) {
            return '<p class="graphique-vide">Aucune donnée à afficher.</p>';
        }

        $nbGroupes = count($annees);
        $nbSeries = count($series);
        $largeurLegende = 0;
        foreach (array_keys($series) as $libelle) {
            $largeurLegende += max(130, strlen((string) $libelle) * 8 + 38);
        }

        $largeur = max(920, 120 * $nbGroupes + 88, $largeurLegende + 64);
        $hauteur = 350;
        $margeG = 64;
        $margeD = 24;
        $margeH = 48;
        $margeB = 44;
        $largeurGroupe = ($largeur - $margeG - $margeD) / $nbGroupes;
        $largeurBarre = $nbSeries > 0 ? $largeurGroupe / ($nbSeries + 1) : 0;

        $maximum = 1;
        foreach ($series as $valeurs) {
            foreach ($valeurs as $valeur) {
                $maximum = max($maximum, (int) $valeur);
            }
        }

        $couleurs = [
            'Pénaux' => '#8A4E52',
            'Civils' => '#43566A',
            'Arrivées' => '#A57C4B',
        ];
        $echelleY = ($hauteur - $margeH - $margeB) / $maximum;
        $svg = self::ouvrirSvg($largeur, $hauteur, 'Volume de dossiers par année');

        for ($g = 0; $g <= 4; $g++) {
            $valeur = (int) round($maximum * $g / 4);
            $y = $hauteur - $margeB - $valeur * $echelleY;
            $svg .= '<line x1="' . $margeG . '" y1="' . $y . '" x2="' . ($largeur - $margeD)
                  . '" y2="' . $y . '" stroke="#D6D5CE" stroke-width="1"/>';
            $svg .= '<text x="' . ($margeG - 9) . '" y="' . ($y + 5)
                  . '" text-anchor="end" font-size="13" fill="#5F6A75">' . $valeur . '</text>';
        }

        foreach ($annees as $position => $annee) {
            $xGroupe = $margeG + $position * $largeurGroupe;
            $numeroSerie = 0;

            foreach ($series as $libelle => $valeurs) {
                $valeur = max(0, (int) ($valeurs[$position] ?? 0));
                $hauteurBarre = $valeur * $echelleY;
                $x = $xGroupe + ($numeroSerie + 0.5) * $largeurBarre;
                $y = $hauteur - $margeB - $hauteurBarre;
                $couleur = $couleurs[$libelle] ?? '#6E7883';
                $titre = self::echapper($libelle . ' ' . (int) $annee . ' : ' . $valeur);
                $svg .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1)
                      . '" width="' . round($largeurBarre * 0.82, 1)
                      . '" height="' . round($hauteurBarre, 1)
                      . '" fill="' . $couleur . '" rx="3"><title>' . $titre . '</title></rect>';
                $numeroSerie++;
            }

            $svg .= '<text x="' . ($xGroupe + $largeurGroupe / 2) . '" y="' . ($hauteur - 15)
                  . '" text-anchor="middle" font-size="14" font-weight="600" fill="#323A44">'
                  . (int) $annee . '</text>';
        }

        $xLegende = $margeG;
        foreach ($series as $libelle => $_) {
            $couleur = $couleurs[$libelle] ?? '#6E7883';
            $svg .= '<rect x="' . $xLegende . '" y="14" width="14" height="14" fill="'
                  . $couleur . '" rx="3"/>';
            $svg .= '<text x="' . ($xLegende + 21) . '" y="26" font-size="14" fill="#323A44">'
                  . self::echapper((string) $libelle) . '</text>';
            $xLegende += max(130, strlen((string) $libelle) * 8 + 38);
        }

        return $svg . '</svg></div>';
    }

    /**
     * Compare les distributions pénales et civiles par Avocat Général.
     *
     * @param string[] $labels Sigles des Avocats Généraux.
     * @param int[] $penaux Dossiers pénaux par Avocat Général.
     * @param int[] $civils Dossiers civils par Avocat Général.
     */
    public static function barresParAg(
        array $labels,
        array $penaux,
        array $civils,
        string $libelleMois
    ): string {
        if ($labels === []) {
            return '<p class="graphique-vide">Aucune distribution enregistrée pour cette période.</p>';
        }

        $nb = count($labels);
        $largeur = max(920, 112 * $nb + 88);
        $hauteur = 350;
        $margeG = 64;
        $margeD = 24;
        $margeH = 48;
        $margeB = 48;
        $largeurGroupe = ($largeur - $margeG - $margeD) / $nb;
        $maximum = 1;

        foreach ([$penaux, $civils] as $valeurs) {
            foreach ($valeurs as $valeur) {
                $maximum = max($maximum, (int) $valeur);
            }
        }

        $echelleY = ($hauteur - $margeH - $margeB) / $maximum;
        $largeurBarre = $largeurGroupe / 3;
        $svg = self::ouvrirSvg(
            $largeur,
            $hauteur,
            'Répartition des dossiers pénaux et civils par Avocat Général'
        );

        for ($g = 0; $g <= 4; $g++) {
            $valeur = (int) round($maximum * $g / 4);
            $y = $hauteur - $margeB - $valeur * $echelleY;
            $svg .= '<line x1="' . $margeG . '" y1="' . $y . '" x2="' . ($largeur - $margeD)
                  . '" y2="' . $y . '" stroke="#D6D5CE" stroke-width="1"/>';
            $svg .= '<text x="' . ($margeG - 9) . '" y="' . ($y + 5)
                  . '" text-anchor="end" font-size="13" fill="#5F6A75">' . $valeur . '</text>';
        }

        foreach ($labels as $i => $sigle) {
            $xGroupe = $margeG + $i * $largeurGroupe;
            $valeurPenale = max(0, (int) ($penaux[$i] ?? 0));
            $valeurCivile = max(0, (int) ($civils[$i] ?? 0));

            foreach (
                [
                    [$valeurPenale, '#8A4E52', 0.5, 'Pénaux'],
                    [$valeurCivile, '#43566A', 1.5, 'Civils'],
                ] as [$valeur, $couleur, $positionBarre, $type]
            ) {
                $hauteurBarre = $valeur * $echelleY;
                $titre = self::echapper($type . ' ' . (string) $sigle . ' : ' . $valeur);
                $svg .= '<rect x="' . round($xGroupe + $largeurBarre * $positionBarre, 1)
                      . '" y="' . round($hauteur - $margeB - $hauteurBarre, 1)
                      . '" width="' . round($largeurBarre * 0.82, 1)
                      . '" height="' . round($hauteurBarre, 1)
                      . '" fill="' . $couleur . '" rx="3"><title>' . $titre . '</title></rect>';
            }

            $svg .= '<text x="' . ($xGroupe + $largeurGroupe / 2) . '" y="' . ($hauteur - 16)
                  . '" text-anchor="middle" font-size="14" font-weight="600" fill="#323A44">'
                  . self::echapper((string) $sigle) . '</text>';
        }

        $svg .= '<rect x="' . $margeG . '" y="14" width="14" height="14" fill="#8A4E52" rx="3"/>'
              . '<text x="' . ($margeG + 21) . '" y="26" font-size="14" fill="#323A44">Pénaux</text>'
              . '<rect x="' . ($margeG + 116) . '" y="14" width="14" height="14" fill="#43566A" rx="3"/>'
              . '<text x="' . ($margeG + 137) . '" y="26" font-size="14" fill="#323A44">Civils</text>'
              . '<text x="' . ($largeur - $margeD) . '" y="26" text-anchor="end" '
              . 'font-size="14" fill="#5F6A75">' . self::echapper($libelleMois) . '</text>';

        return $svg . '</svg></div>';
    }

    private static function ouvrirSvg(int $largeur, int $hauteur, string $description): string
    {
        $descriptionEchappee = self::echapper($description);
        return '<div class="graphique-defilement" role="region" tabindex="0" aria-label="'
             . 'Graphique défilant : ' . $descriptionEchappee . '">'
             . '<svg role="img" aria-label="' . $descriptionEchappee . '" viewBox="0 0 '
             . $largeur . ' ' . $hauteur . '" style="display:block;width:100%;min-width:'
             . $largeur . 'px;height:auto;font-family:Segoe UI,sans-serif">'
             . '<title>' . $descriptionEchappee . '</title>';
    }

    private static function echapper(string $valeur): string
    {
        return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}