<?php
/**
 * Modèle des suivis de dossiers (107 et 321).
 *
 * Le suivi « 321 » suit les dossiers depuis leur arrivée jusqu'à leur
 * traitement (table pgcc_suivi_arrivee) ; le suivi « 107 » suit les
 * dossiers pénaux transmis au PGCS (table pgcc_suivi_pen).
 *
 * Chaque type de suivi décrit ses champs éditables de façon déclarative
 * (même logique que les registres) : le formulaire et la validation sont
 * générés à partir de ces définitions.
 */

declare(strict_types=1);

final class SuiviModel
{
    /** @var array<string, array> définitions des types de suivi */
    private const DEFINITIONS = [
        '321' => [
            'libelle' => 'Suivi des arrivées (321)',
            'table'   => 'pgcc_suivi_arrivee',
            'cle'     => 'id_suivi_arrivee',
            'ordre'   => 'annee DESC, num_ordre DESC',
            'recherche' => ['num_dossier_PGCS', 'observation_suivi'],
            'champs'  => [
                ['nom' => 'num_ordre',        'libelle' => 'N° ordre',       'type' => 'nombre'],
                ['nom' => 'date_arrivee',     'libelle' => 'Date d\'arrivée', 'type' => 'texte'],
                ['nom' => 'num_dossier_PGCS', 'libelle' => 'N° dossier PGCS', 'type' => 'texte', 'requis' => true],
                ['nom' => 'detention',        'libelle' => 'Détention',       'type' => 'booleen'],
                ['nom' => 'date_detention',   'libelle' => 'Date détention',  'type' => 'date'],
                ['nom' => 'sigle_AG',         'libelle' => 'Sigle AG',        'type' => 'texte'],
                ['nom' => 'localisation',     'libelle' => 'Localisation',    'type' => 'texte'],
                ['nom' => 'observation_suivi','libelle' => 'Observation du suivi', 'type' => 'textelong'],
                ['nom' => 'observation',      'libelle' => 'Observation',     'type' => 'texte'],
            ],
        ],
        '107' => [
            'libelle' => 'Suivi des dossiers pénaux (107)',
            'table'   => 'pgcc_suivi_pen',
            'cle'     => 'id_suivi_arrivee',
            'ordre'   => 'annee DESC, num_ordre DESC',
            'recherche' => ['num_dossier_PGCS', 'sigle_AG'],
            'champs'  => [
                ['nom' => 'num_ordre',       'libelle' => 'N° ordre',           'type' => 'nombre'],
                ['nom' => 'date_transmission_au_PGCS', 'libelle' => 'Transmission au PGCS', 'type' => 'date'],
                ['nom' => 'date_reception',  'libelle' => 'Réception',          'type' => 'date'],
                ['nom' => 'num_dossier_PGCS','libelle' => 'N° dossier PGCS',    'type' => 'texte', 'requis' => true],
                ['nom' => 'detention',       'libelle' => 'Détention',          'type' => 'booleen'],
                ['nom' => 'date_detention',  'libelle' => 'Date détention',     'type' => 'date'],
                ['nom' => 'sigle_AG',        'libelle' => 'Sigle AG',           'type' => 'texte'],
                ['nom' => 'localisation',    'libelle' => 'Localisation',       'type' => 'texte'],
                ['nom' => 'observation',     'libelle' => 'Observation',        'type' => 'texte'],
            ],
        ],
    ];

    public static function definition(string $type): ?array
    {
        return self::DEFINITIONS[$type] ?? null;
    }

    /**
     * Liste paginée d'un type de suivi.
     * @return array{lignes: array, total: int, pages: int, page: int}
     */
    public function liste(string $type, int $page = 1, string $recherche = ''): array
    {
        $def  = self::definition($type);
        if ($def === null) {
            throw new InvalidArgumentException('Type de suivi inconnu.');
        }
        $bdd  = Database::connexion();
        $params = [];

        $condition = '';
        $recherche = trim($recherche);
        if ($recherche !== '') {
            // Un placeholder par colonne recherchée (un même nom ne peut pas
            // être répété : PDO refuserait les paramètres en double).
            $parts = [];
            foreach ($def['recherche'] as $i => $colonne) {
                $parts[] = nom_sql($colonne) . ' LIKE :q' . $i;
                $params["q$i"] = "%$recherche%";
            }
            $condition = ' WHERE (' . implode(' OR ', $parts) . ')';
        }

        $req = $bdd->prepare('SELECT COUNT(*) FROM ' . nom_sql($def['table']) . $condition);
        $req->execute($params);
        $total = (int) $req->fetchColumn();

        $pages  = max(1, (int) ceil($total / LIGNES_PAR_PAGE));
        $page   = min(max(1, $page), $pages);
        $offset = ($page - 1) * LIGNES_PAR_PAGE;

        $req = $bdd->prepare(
            'SELECT * FROM ' . nom_sql($def['table']) . $condition . ' ORDER BY ' . ordre_sql($def['ordre'])
            . ' LIMIT ' . LIGNES_PAR_PAGE . " OFFSET $offset"
        );
        $req->execute($params);

        return ['lignes' => $req->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** Toutes les lignes filtrées d'un suivi, sans limite de pagination. */
    public function exporter(string $type, string $recherche = ''): iterable
    {
        $def = self::definition($type);
        if ($def === null) {
            throw new InvalidArgumentException('Type de suivi inconnu.');
        }
        $params = [];
        $condition = '';
        $recherche = trim($recherche);
        if ($recherche !== '') {
            $parts = [];
            foreach ($def['recherche'] as $i => $colonne) {
                $parts[] = nom_sql($colonne) . ' LIKE :q' . $i;
                $params["q$i"] = "%$recherche%";
            }
            $condition = ' WHERE (' . implode(' OR ', $parts) . ')';
        }
        $req = Database::connexionPourExport()->prepare(
            'SELECT * FROM ' . nom_sql($def['table']) . $condition . ' ORDER BY ' . ordre_sql($def['ordre'])
        );
        $req->execute($params);
        return $req;
    }

    /**
     * Enregistre un nouveau suivi à partir des données validées.
     * `annee` et `date_insertion` sont ajoutées ici (champs techniques).
     */
    public function inserer(string $type, array $donnees): int
    {
        $def = self::definition($type);
        if ($def === null) {
            throw new InvalidArgumentException('Type de suivi inconnu.');
        }
        $donnees['annee']          = (int) $donnees['annee'];
        $donnees['date_insertion'] = date('Y-m-d');
        $donnees['cree_par']       = utilisateur_login();

        $colonnes = array_keys($donnees);
        $sql = 'INSERT INTO ' . nom_sql($def['table'])
             . ' (' . implode(', ', array_map('nom_sql', $colonnes)) . ')'
             . ' VALUES (:' . implode(', :', $colonnes) . ')';
        $req = Database::connexion()->prepare($sql);
        $req->execute($donnees);
        return (int) Database::connexion()->lastInsertId();
    }

    /**
     * Archive ou désarchive un suivi (traçé dans modifie_par).
     */
    public function archiver(string $type, int $id, bool $archiver): bool
    {
        $def = self::definition($type);
        if ($def === null) {
            throw new InvalidArgumentException('Type de suivi inconnu.');
        }
        return Database::connexion()
            ->prepare('UPDATE ' . nom_sql($def['table']) . ' SET archiver = :a, modifie_par = :par WHERE '
                . nom_sql($def['cle']) . ' = :id')
            ->execute(['a' => $archiver ? '1' : '0', 'par' => utilisateur_login(), 'id' => $id]);
    }

    /**
     * Prochain numéro d'ordre pour une année (numérotation continue,
     * même convention que la v1).
     */
    public function prochainNumero(string $type, int $annee): int
    {
        $def = self::definition($type);
        if ($def === null) {
            throw new InvalidArgumentException('Type de suivi inconnu.');
        }
        $req = Database::connexion()->prepare(
            'SELECT COALESCE(MAX(num_ordre), 0) + 1 FROM ' . nom_sql($def['table']) . ' WHERE annee = :annee'
        );
        $req->execute(['annee' => $annee]);
        return (int) $req->fetchColumn();
    }

    /**
     * Lit et valide les champs du formulaire de suivi.
     * @return array{0: array, 1: string[]} données + erreurs
     */
    public function lireFormulaire(string $type): array
    {
        $def = self::definition($type);
        if ($def === null) {
            throw new InvalidArgumentException('Type de suivi inconnu.');
        }
        $erreurs = [];
        $donnees = [];

        $annee = (int) entree_post('annee', 0);
        if ($annee < 1990 || $annee > 2100) {
            $erreurs[] = 'L\'année doit être comprise entre 1990 et 2100.';
        }
        $donnees['annee'] = $annee;

        foreach ($def['champs'] as $champ) {
            $nom = $champ['nom'];
            $valeur = trim((string) entree_post($nom, ''));

            switch ($champ['type']) {
                case 'nombre':
                    $donnees[$nom] = ctype_digit($valeur) ? (int) $valeur : 0;
                    break;

                case 'date':
                    $donnees[$nom] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valeur) ? $valeur : '0000-00-00';
                    break;

                case 'booleen':
                    $donnees[$nom] = !empty(entree_post($nom)) ? '1' : '0';
                    break;

                default:
                    if ($valeur === '' && !empty($champ['requis'])) {
                        $erreurs[] = 'Le champ « ' . $champ['libelle'] . ' » est obligatoire.';
                    }
                    $donnees[$nom] = $valeur;
            }
        }

        // Numérotation automatique si le numéro d'ordre est laissé vide.
        if (empty($donnees['num_ordre'])) {
            $donnees['num_ordre'] = $this->prochainNumero($type, $annee);
        }

        return [$donnees, $erreurs];
    }
}
