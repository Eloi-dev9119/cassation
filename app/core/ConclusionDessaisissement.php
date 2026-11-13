<?php
declare(strict_types=1);

/**
 * Modèle de conclusion pour le registre des DÉSSAISISSEMENTS.
 *
 * Un déssaisissement est une « requête aux fins de renvoi d'une juridiction
 * à une autre » (article 96 de la loi organique n° 2004-036 du 1er octobre
 * 2004) : le Procureur Général demande à la Chambre des affaires pénales de
 * la Cour de Cassation de dessaisir une juridiction au profit d'une autre.
 *
 * Ce modèle reproduit le format officiel observé dans les conclusions du
 * registre (en-tête « REQUETE AUX FINS DE RENVOI D'UNE JURIDICTION A UNE
 * AUTRE », visa de la lettre du Garde des Sceaux, visa de l'affaire, article
 * 96, conclusions) — et non le format d'un pourvoi pénal, qui ne s'applique
 * pas à ce registre. Les mentions entre crochets sont à compléter par le
 * rédacteur.
 */
final class ConclusionDessaisissement
{
    private const MOIS = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    public static function modele(array $dossier): string
    {
        $numero      = self::texte($dossier, 'num_dossier_PGCS', '[numéro du dossier]');
        $parties     = self::texte($dossier, 'cassationnaire', '[parties]');
        $inculpation = self::texte($dossier, 'inculpation', '');
        $objet       = self::texte($dossier, 'juridiction', '[juridiction à dessaisir et juridiction de renvoi]');

        // « Vu l'affaire MP C/ X (INFRACTION) » : l'infraction n'est reprise
        // entre parenthèses que lorsqu'elle est renseignée.
        $affaire = 'Vu l’affaire ' . $parties
            . ($inculpation !== '' ? ' (' . $inculpation . ')' : '') . ',';

        $signataire    = trim(Auth::nomComplet());
        $dateSignature = self::dateLongue(date('Y-m-d'));

        $lignes = [
            'REQUETE AUX FINS DE RENVOI D’UNE JURIDICTION A UNE AUTRE',
            '',
            'DOSSIER N° ' . $numero,
            '',
            'Nous,',
            'Procureur Général près la Cour de Cassation,',
            '',
            'Vu la lettre N° [référence de la lettre] du [date de la lettre] du Garde des Sceaux, Ministre de la Justice,',
            '',
            $affaire,
            '',
            'Attendu que [exposer les faits et les motifs qui justifient le déssaisissement],',
            '',
            'Attendu que la demande de renvoi porte sur : ' . $objet . ',',
            '',
            'Vu les dispositions de l’article 96 de la loi organique n° 2004-036 du 1er octobre 2004 relative à l’organisation, aux attributions, au fonctionnement et à la procédure applicable devant la Cour Suprême et les trois Cours la composant,',
            '',
            'Pour la sauvegarde de la sûreté publique et dans l’intérêt d’une meilleure administration de la justice,',
            '',
            'Requérons qu’il plaise à la Chambre des affaires pénales de la Cour de Cassation, ordonner le dessaisissement de la juridiction de [juridiction à dessaisir] au profit de la juridiction de [juridiction de renvoi].',
            '',
            'Antananarivo, le ' . $dateSignature,
            '',
            '',
            'Le Procureur Général',
        ];
        if ($signataire !== '') {
            $lignes[] = '';
            $lignes[] = $signataire;
        }

        return implode("\n", $lignes);
    }

    /** « 2017-09-12 » → « 12 septembre 2017 » (mois en minuscules). */
    private static function dateLongue(string $date): string
    {
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
