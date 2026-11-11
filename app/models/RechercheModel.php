<?php
/**
 * Recherche globale multi-registres.
 *
 * Interroge en une seule passe tous les registres déclarés dans
 * app/models/Registres.php, ainsi que le registre des mémoires.
 * Chaque groupe renvoie son total réel et un extrait limité de lignes
 * (pour ne pas surcharger l'affichage du tableau de bord).
 *
 * Les colonnes interrogées sont exactement celles déclarées dans la clé
 * « recherche » de chaque registre : aucun nom de colonne n'est fourni
 * par l'utilisateur. Les valeurs passent par des requêtes préparées.
 */

declare(strict_types=1);

final class RechercheModel
{
    /** Longueur minimale du terme pour lancer la recherche. */
    public const MINIMUM = 2;

    /** Nombre maximal de lignes affichées par groupe. */
    public const LIMITE_MAX = 25;

    /**
     * Recherche un terme dans tous les registres et les mémoires.
     *
     * @return array{
     *     terme: string,
     *     groupes: array<int, array<string, mixed>>,
     *     total: int,
     *     tronque: bool
     * }
     */
    public function rechercher(string $terme, int $limite = 6): array
    {
        $terme = trim((string) (preg_replace('/\s+/u', ' ', $terme) ?? $terme));
        $limite = max(1, min(self::LIMITE_MAX, $limite));

        $resultat = [
            'terme'   => $terme,
            'groupes' => [],
            'total'   => 0,
            'tronque' => false,
        ];
        if (mb_strlen($terme) < self::MINIMUM) {
            return $resultat;
        }

        foreach (registres_definitions() as $code => $definition) {
            $groupe = $this->chercherRegistre((string) $code, $definition, $terme, $limite);
            if ($groupe !== null) {
                $resultat['groupes'][] = $groupe;
                $resultat['total'] += $groupe['total'];
                $resultat['tronque'] = $resultat['tronque']
                    || $groupe['total'] > count($groupe['lignes']);
            }
        }

        $memoires = $this->chercherMemoires($terme, $limite);
        if ($memoires !== null) {
            $resultat['groupes'][] = $memoires;
            $resultat['total'] += $memoires['total'];
            $resultat['tronque'] = $resultat['tronque']
                || $memoires['total'] > count($memoires['lignes']);
        }

        return $resultat;
    }

    // ------------------------------------------------------------------ //
    //  Registres déclaratifs
    // ------------------------------------------------------------------ //

    private function chercherRegistre(string $code, array $definition, string $terme, int $limite): ?array
    {
        $colonnes = array_values(array_filter(
            (array) ($definition['recherche'] ?? []),
            static fn (mixed $c): bool => is_string($c) && $c !== ''
        ));
        if ($colonnes === []) {
            return null;
        }

        // Index FULLTEXT dès 3 caractères (les colonnes MATCH sont celles de
        // l'index de chaque registre). Repli LIKE si l'index manque ou si le
        // plein texte ne trouve rien (recherche au milieu d'un mot).
        if (mb_strlen($terme) >= 3 && self::termePleinTexte($terme) !== '') {
            try {
                $resultat = $this->interrogerRegistre($code, $definition, $colonnes, $terme, $limite, true);
                if ($resultat !== null) {
                    return $resultat;
                }
            } catch (PDOException) {
                // Index FULLTEXT absent : repli LIKE ci-dessous.
            }
        }

        try {
            return $this->interrogerRegistre($code, $definition, $colonnes, $terme, $limite, false);
        } catch (PDOException) {
            // Table ou colonne absente (migration non exécutée) : le groupe
            // est ignoré, la recherche des autres registres continue.
            return null;
        }
    }

    /**
     * Exécute la recherche sur un registre déclaratif.
     *
     * @param string[] $colonnes
     */
    private function interrogerRegistre(
        string $code,
        array $definition,
        array $colonnes,
        string $terme,
        int $limite,
        bool $fulltext
    ): ?array {
        $table = nom_sql($definition['table']);
        $archive = nom_sql(registre_colonne_archive($definition));

        $conditions = [];
        $params = [];
        if ($fulltext) {
            $conditions[] = 'MATCH (' . implode(', ', array_map('nom_sql', $colonnes))
                . ') AGAINST (:qft IN BOOLEAN MODE)';
            $params['qft'] = self::termePleinTexte($terme);
        } else {
            foreach ($colonnes as $i => $colonne) {
                $conditions[] = nom_sql($colonne) . ' LIKE :q' . $i;
                $params['q' . $i] = '%' . $terme . '%';
            }
            $conditions = ['(' . implode(' OR ', $conditions) . ')'];
        }
        $conditions[] = "COALESCE($archive, '0') <> '1'";
        $where = implode(' AND ', $conditions);

        $bdd = Database::connexion();

        $req = $bdd->prepare("SELECT COUNT(*) FROM $table WHERE $where");
        $req->execute($params);
        $total = (int) $req->fetchColumn();
        if ($total === 0) {
            return null;
        }

        $req = $bdd->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY " . ordre_sql($definition['ordre'])
            . ' LIMIT ' . (int) $limite
        );
        $req->execute($params);
        $lignes = [];
        foreach ($req->fetchAll() as $ligne) {
            $lignes[] = $this->formaterLigneRegistre($code, $definition, $ligne);
        }

        return [
            'code'     => $code,
            'libelle'  => (string) $definition['libelle'],
            'route'    => '/registres/' . $code,
            'total'    => $total,
            'lignes'   => $lignes,
        ];
    }

    private function formaterLigneRegistre(string $code, array $definition, array $ligne): array
    {
        $id = (int) ($ligne[$definition['cle']] ?? 0);
        $reference = '';
        foreach (['num_dossier_PGCS', 'num_dossier', 'num_pil'] as $colonne) {
            if (trim((string) ($ligne[$colonne] ?? '')) !== '') {
                $reference = trim((string) $ligne[$colonne]);
                break;
            }
        }
        if ($reference === '') {
            $reference = 'Dossier #' . $id;
        }

        $resume = $this->resumer($ligne);

        return [
            'id'        => $id,
            'reference' => $reference,
            'resume'    => $resume,
            'annee'     => (int) ($ligne['annee'] ?? 0),
            'url'       => '/registres/' . $code . '/fiche?id=' . $id,
            'conclusion' => $this->etatConclusion($definition, $ligne),
        ];
    }

    private function resumer(array $ligne): string
    {
        $demandeur = trim((string) ($ligne['nom_demandeur'] ?? ''));
        $defendeur = trim((string) ($ligne['nom_defendeur'] ?? ''));
        if ($demandeur !== '' || $defendeur !== '') {
            return self::extrait(trim($demandeur . ($defendeur !== '' ? ' c/ ' . $defendeur : '')));
        }

        foreach (['cassationnaire', 'num_dossier_et_pers_concernee', 'requete', 'bordereaux_arrivee', 'observation'] as $colonne) {
            $valeur = trim((string) ($ligne[$colonne] ?? ''));
            if ($valeur !== '') {
                return self::extrait($valeur);
            }
        }
        return '';
    }

    private function etatConclusion(array $definition, array $ligne): ?string
    {
        $colonne = $definition['conclusion'] ?? null;
        if ($colonne === null) {
            return null;
        }
        return trim(strip_tags((string) ($ligne[$colonne] ?? ''))) !== ''
            ? 'redigee'
            : 'a_rediger';
    }

    // ------------------------------------------------------------------ //
    //  Mémoires
    // ------------------------------------------------------------------ //

    private function chercherMemoires(string $terme, int $limite): ?array
    {
        $colonnes = ['num_dossier_PGCS', 'parties', 'avocat', 'juridiction', 'observation'];

        if (mb_strlen($terme) >= 3 && self::termePleinTexte($terme) !== '') {
            try {
                $resultat = $this->interrogerMemoires($colonnes, $terme, $limite, true);
                if ($resultat !== null) {
                    return $resultat;
                }
            } catch (PDOException) {
                // Index FULLTEXT absent : repli LIKE ci-dessous.
            }
        }

        try {
            return $this->interrogerMemoires($colonnes, $terme, $limite, false);
        } catch (PDOException) {
            return null;
        }
    }

    /**
     * @param string[] $colonnes
     */
    private function interrogerMemoires(array $colonnes, string $terme, int $limite, bool $fulltext): ?array
    {
        $conditions = [];
        $params = [];
        if ($fulltext) {
            $conditions[] = 'MATCH (' . implode(', ', array_map('nom_sql', $colonnes))
                . ') AGAINST (:qft IN BOOLEAN MODE)';
            $params['qft'] = self::termePleinTexte($terme);
        } else {
            foreach ($colonnes as $i => $colonne) {
                $conditions[] = nom_sql($colonne) . ' LIKE :q' . $i;
                $params['q' . $i] = '%' . $terme . '%';
            }
            $conditions = ['(' . implode(' OR ', $conditions) . ')'];
        }
        $conditions[] = "COALESCE(archiver, '0') <> '1'";
        $where = implode(' AND ', $conditions);

        $bdd = Database::connexion();
        $req = $bdd->prepare('SELECT COUNT(*) FROM pgcc_reg_memoires WHERE ' . $where);
        $req->execute($params);
        $total = (int) $req->fetchColumn();
        if ($total === 0) {
            return null;
        }

        $req = $bdd->prepare(
            'SELECT * FROM pgcc_reg_memoires WHERE ' . $where
            . ' ORDER BY annee DESC, num_ordre DESC LIMIT ' . (int) $limite
        );
        $req->execute($params);

        $lignes = [];
        foreach ($req->fetchAll() as $ligne) {
            $type = ($ligne['type_memoire'] ?? '') === 'ampliatif'
                ? 'Mémoire ampliatif'
                : 'Mémoire en défense';
            $parties = trim((string) ($ligne['parties'] ?? ''));
            $avocat = trim((string) ($ligne['avocat'] ?? ''));
            $resume = $type;
            if ($parties !== '') {
                $resume .= ' · ' . $parties;
            }
            if ($avocat !== '') {
                $resume .= ' · Me ' . $avocat;
            }
            $lignes[] = [
                'id'         => (int) ($ligne['id_memoire'] ?? 0),
                'reference'  => trim((string) ($ligne['num_dossier_PGCS'] ?? '')) ?: 'Mémoire',
                'resume'     => self::extrait($resume),
                'annee'      => (int) ($ligne['annee'] ?? 0),
                'url'        => '/memoires/modifier?id=' . (int) ($ligne['id_memoire'] ?? 0),
                'conclusion' => null,
            ];
        }

        return [
            'code'    => 'memoires',
            'libelle' => 'Registre des Mémoires',
            'route'   => '/memoires',
            'total'   => $total,
            'lignes'  => $lignes,
        ];
    }

    // ------------------------------------------------------------------ //
    //  Utilitaires
    // ------------------------------------------------------------------ //

    private static function extrait(string $texte): string
    {
        $texte = trim((string) (preg_replace('/\s+/u', ' ', strip_tags($texte)) ?? $texte));
        return mb_strlen($texte) > 160
            ? mb_substr($texte, 0, 157) . '…'
            : $texte;
    }

    /**
     * Neutralise les opérateurs du mode BOOLEAN de MySQL : un utilisateur
     * peut saisir « C/ », « A+B » ou des guillemets sans provoquer d'erreur
     * de syntaxe FULLTEXT.
     */
    private static function termePleinTexte(string $terme): string
    {
        $nettoye = preg_replace('/[+\-><()~*"@]+/u', ' ', $terme) ?? $terme;
        return trim((string) (preg_replace('/\s+/u', ' ', $nettoye) ?? $nettoye));
    }
}
