<?php
declare(strict_types=1);

/**
 * Modèle de conclusions PÉNALES (registres pénaux et déssaisissements),
 * reproduisant le format officiel du Parquet Général :
 * PARQUET GENERAL / COUR DE CASSATION / DOSSIER N°… / Affaire : … c/ … /
 * CONCLUSIONS / LE PROCUREUR GENERAL… / A / Madame LE PRESIDENT / …
 */
final class ConclusionPenale
{
    private const MOIS = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    public static function modele(array $dossier): string
    {
        $numero = self::texte($dossier, 'num_dossier_PGCS', '[numéro du dossier]');

        // « RAMANANTSOA Joël (Prévenu) C/ M.P et Société VIMA » → demandeur /
        // défendeur. Le champ est d'abord aplati : les retours à la ligne et
        // les séparateurs « C: » ou « c/ » historiques séparent tout aussi
        // bien les deux parties que « C/ » sur une seule ligne.
        $parties = self::texte($dossier, 'cassationnaire', '[parties]');
        $partiesAplaties = trim(preg_replace('/\s+/', ' ', $parties) ?? $parties);
        if (preg_match('/^(.*?)\s+(?:C\/|C:|c\/|c:)\s+(.*)$/u', $partiesAplaties, $m) === 1) {
            $demandeur = trim($m[1]);
            $defendeur = trim($m[2]);
        } else {
            $demandeur = $partiesAplaties;
            $defendeur = '[partie adverse]';
        }

        $inculpation = self::texte($dossier, 'inculpation', '[inculpation]');
        $arret        = self::texte($dossier, 'arret_attaque', '[N° et date de l’arrêt attaqué]');
        $juridiction  = self::texte($dossier, 'juridiction', '[juridiction]');
        $datePourvoi  = self::dateLongue($dossier['date_pourvoi'] ?? null);
        $dispositif   = self::texte($dossier, 'dispositif_arret_CA', '');

        // Contenu du « B.E Porte 05 » : chaque ligne du registre devient une
        // phrase normale, sans tiret ni puce, telle que rédigée au greffe.
        $decisions = [];
        foreach (preg_split('/\r\n|\r|\n/', $dispositif) ?: [] as $ligne) {
            $ligne = trim($ligne, " \t-;•");
            if ($ligne !== '') {
                $decisions[] = $ligne;
            }
        }

        $signataire = trim(Auth::nomComplet());
        $dateSignature = self::dateSignature(date('Y-m-d'));

        $memoireAmpliatif = bin_vers_bool($dossier['memoire_ampliatif'] ?? null);

        $lignes = [
            'PARQUET GENERAL',
            '',
            'COUR DE CASSATION',
            '',
            'DOSSIER N° ' . $numero,
            '',
            'Affaire :',
            $demandeur,
            '',
            'c/',
            '',
            $defendeur,
            '',
            'CONCLUSIONS',
            '',
            'LE PROCUREUR GENERAL PRES LA COUR DE CASSATION',
            '',
            'A',
            '',
            'Madame LE PRESIDENT',
            '',
            'Mesdames et Messieurs LES MEMBRES DE LA COUR DE CASSATION, CHAMBRE DES AFFAIRES PENALES',
            '',
            'Suivant déclaration du pourvoi enregistrée le ' . ($datePourvoi !== '' ? $datePourvoi : '[date de la déclaration de pourvoi]') . ' au greffe de la ' . $juridiction . ', Maître [avocat du demandeur], Avocat, agissant au nom et pour le compte de ' . $demandeur . ', prévenu, s’est pourvu en cassation contre l’arrêt ' . $arret . ' rendu par la ' . $juridiction . ' qui :',
            '',
            implode("\n", $decisions ?: ['[décision de l’arrêt attaqué — contenu du « B.E Porte 05 »]']),
            '',
            'Faits et procédure',
            '',
            'Suivant la procédure d’information sommaire, ' . $demandeur . ' est traduit devant le tribunal correctionnel de [ville] pour ' . $inculpation . '. Par [jugement n°… du …], le tribunal a :',
            '- [déclaré … coupable],',
            '- [l’a condamné à …],',
            '- [l’a condamné au paiement de … à la partie civile à titre de dommages-intérêts],',
            '',
            'Par déclaration d’appel du conseil du prévenu en date du [date d’appel], la Cour, par ' . $arret . ' a :',
            '- En la forme : [reçu l’appel],',
            '- Au fond : [le déclare mal fondé / …],',
            '',
            'C’est l’arrêt attaqué.',
            '',
            'Sur le pourvoi',
            '',
            'Fait dans les conditions de forme et délai prescrites par la loi, le pourvoi est régulier et recevable,',
            'L’amende de cassation est consignée,',
            $memoireAmpliatif
                ? 'Le demandeur a produit un mémoire ampliatif qui propose [nombre] moyens de cassation.'
                : 'Le demandeur a produit un mémoire qui propose [nombre] moyens de cassation.',
            '',
            '- Sur le premier moyen tiré de la violation de [texte de loi visé].',
            '',
            'En ce que [exposé du moyen].',
            '',
            'Discussion',
            '',
            '[Discussion du premier moyen. Le premier moyen n’est pas fondé.]',
            '',
            '- Sur le deuxième moyen tiré de [texte de loi visé].',
            '',
            'En ce que [exposé du deuxième moyen].',
            '',
            'Discussion',
            '',
            '[Discussion du deuxième moyen. Le deuxième moyen n’est pas fondé.]',
            '',
            '[Ajouter autant de moyens que nécessaire.]',
            '',
            'De tout ce qui précède, nous concluons au rejet du pourvoi.',
            '',
            '',
            'Antananarivo, le ' . $dateSignature,
            '',
            '',
            'P. LE PROCUREUR GENERAL',
            '',
            '',
            'L’AVOCAT GENERAL,',
        ];
        if ($signataire !== '') {
            $lignes[] = '';
            $lignes[] = $signataire;
        }

        return implode("\n", $lignes);
    }

    /** « 2017-09-12 » → « 12 septembre 2017 » (mois en minuscules, format officiel). */
    private static function dateSignature(string $date): string
    {
        $horodatage = strtotime($date);
        if ($horodatage === false) {
            return $date;
        }
        $mois = self::MOIS[(int) date('n', $horodatage)];
        return date('j', $horodatage) . ' ' . $mois . ' ' . date('Y', $horodatage);
    }

    /** « 2018-02-06 » → « 6 février 2018 » ; '' si la date est absente. */
    private static function dateLongue(mixed $date): string
    {
        $date = trim((string) $date);
        if ($date === '' || str_starts_with($date, '0000')) {
            return '';
        }
        $horodatage = strtotime($date);
        if ($horodatage === false) {
            return $date;
        }
        return date('j', $horodatage) . ' ' . self::MOIS[(int) date('n', $horodatage)] . ' ' . date('Y', $horodatage);
    }

    private static function texte(array $dossier, string $cle, string $defaut = ''): string
    {
        $valeur = trim((string) ($dossier[$cle] ?? ''));
        return $valeur === '' || str_starts_with($valeur, '0000-00-00') ? $defaut : $valeur;
    }
}
