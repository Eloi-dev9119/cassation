<?php
declare(strict_types=1);

final class ConclusionCivile
{
    private const MOIS = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    public static function modele(array $dossier): string
    {
        $numero = self::texte($dossier, 'num_dossier_PGCS');
        if ($numero === '') {
            $ordre = self::texte($dossier, 'num_ordre');
            $annee = self::texte($dossier, 'annee');
            $numero = ($ordre !== '' && $annee !== '') ? $ordre . '/' . $annee : '[numéro du dossier]';
        }

        $parties = self::texte($dossier, 'cassationnaire', '[parties]');
        $arret = self::texte($dossier, 'arret_attaque_et_date', '[arrêt ou ordonnance attaqué(e) et sa date]');
        $dateRequete = self::dateLongue($dossier['date_de_depot_requete'] ?? null, '[date d’enregistrement de la requête]');

        $pourvoi = [
            '- Fait dans les conditions légales de forme et de délai, le pourvoi est régulier et recevable.',
            '- [Amende de cassation : consignée / à vérifier]',
        ];
        if (bin_vers_bool($dossier['memoire_ampliatif'] ?? null)) {
            $pourvoi[] = '- Un mémoire ampliatif a été produit dans le délai imparti.';
        } else {
            $pourvoi[] = '- [Mémoire ampliatif : à préciser]';
        }
        if (bin_vers_bool($dossier['memoire_en_defense'] ?? null)) {
            $pourvoi[] = '- La partie défenderesse a répliqué dans le sens du rejet du pourvoi.';
        } else {
            $pourvoi[] = '- [Réplique de la défense : à préciser]';
        }

        $signataire = trim(Auth::nomComplet());
        $dateSignature = self::dateLongue(date('Y-m-d'));

        $lignes = [
            'COUR SUPREME',
            '',
            'PARQUET GENERAL',
            '',
            'DOSSIER N° ' . $numero,
            '',
            'Affaire :',
            $parties,
            '',
            'CONCLUSIONS',
            '',
            'LE PROCUREUR GENERAL PRES LA COUR DE CASSATION',
            'à',
            'MADAME LE PRESIDENT, MESDAMES ET MESSIEURS LES MEMBRES DE LA COUR DE CASSATION, CHAMBRE DES AFFAIRES CIVILES',
            '',
            'Par requête enregistrée au greffe de la Cour Suprême le ' . $dateRequete . ', un pourvoi en cassation a été formé contre ' . $arret . ', dans le litige opposant ' . $parties . '.',
            '',
            'FAITS ET PROCEDURE :',
            '',
            '[Exposer les faits et la procédure.]',
            '',
            '',
            'SUR LE POURVOI :',
            '',
            implode("\n", $pourvoi),
            '',
            '[Présenter le moyen unique de cassation et les textes visés.]',
            '',
            '',
            'DISCUSSION :',
            '',
            '[Développer la discussion du moyen, branche par branche.]',
            '',
            '',
            'Nous concluons, en conséquence, à [préciser : rejet du pourvoi / cassation / autre].',
            '',
            '',
            'Antananarivo, le ' . $dateSignature,
            '',
            '',
            'P. LE PROCUREUR GENERAL',
            '',
            '',
            "L'AVOCAT GENERAL",
        ];
        if ($signataire !== '') {
            $lignes[] = $signataire;
        }

        return implode("\n", $lignes);
    }

    public static function dateLongue(?string $date, string $defaut = ''): string
    {
        $date = trim((string) $date);
        if ($date === '' || str_starts_with($date, '0000-00-00')) {
            return $defaut;
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
