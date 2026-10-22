<?php
/**
 * Modèle générique d'accès aux registres.
 *
 * Toutes les requêtes sont construites à partir de la définition
 * déclarative du registre (voir app/models/Registres.php) et exécutées
 * avec des requêtes préparées : aucun risque d'injection SQL.
 */

declare(strict_types=1);

final class RegistreModel
{
    public function __construct(private array $definition) {}

    // ------------------------------------------------------------------ //
    //  Lectures
    // ------------------------------------------------------------------ //

    /**
     * Liste paginée avec recherche plein texte optionnelle.
     *
     * @return array{ lignes: array, total: int, pages: int, page: int }
     */
    public function liste(int $page = 1, string $recherche = '', ?int $annee = null): array
    {
        $bdd    = Database::connexion();
        $table  = $this->definition['table'];
        $cle    = $this->definition['cle'];
        [$where, $params] = $this->construireFiltres($recherche, $annee);
        $condition = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Nombre total de dossiers correspondant aux filtres.
        $req = $bdd->prepare("SELECT COUNT(*) FROM `$table`$condition");
        $req->execute($params);
        $total = (int) $req->fetchColumn();

        $pages  = max(1, (int) ceil($total / LIGNES_PAR_PAGE));
        $page   = min(max(1, $page), $pages);
        $offset = ($page - 1) * LIGNES_PAR_PAGE;

        $sql = "SELECT * FROM `$table`$condition ORDER BY " . $this->definition['ordre']
             . " LIMIT " . LIGNES_PAR_PAGE . " OFFSET $offset";
        $req = $bdd->prepare($sql);
        $req->execute($params);

        return [
            'lignes' => $req->fetchAll(),
            'total'  => $total,
            'pages'  => $pages,
            'page'   => $page,
        ];
    }

    /** Retourne toutes les lignes filtrées pour un export (sans pagination). */
    public function exporter(string $recherche = '', ?int $annee = null): iterable
    {
        [$where, $params] = $this->construireFiltres($recherche, $annee);
        $condition = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT * FROM `' . $this->definition['table'] . '`' . $condition
             . ' ORDER BY ' . $this->definition['ordre'];
        $req = Database::connexionPourExport()->prepare($sql);
        $req->execute($params);
        return $req;
    }

    /**
     * Fiche complète d'un dossier.
     */
    public function trouver(int $id): ?array
    {
        $req = Database::connexion()->prepare(
            'SELECT * FROM `' . $this->definition['table'] . '` WHERE `' . $this->definition['cle'] . '` = :id'
        );
        $req->execute(['id' => $id]);
        $ligne = $req->fetch();
        return $ligne === false ? null : $ligne;
    }

    /**
     * Années disponibles dans le registre (pour le filtre).
     * @return int[]
     */
    public function annees(): array
    {
        $req = Database::connexion()->query(
            'SELECT DISTINCT annee FROM `' . $this->definition['table'] . '` ORDER BY annee DESC'
        );
        return array_map('intval', $req->fetchAll(PDO::FETCH_COLUMN));
    }

    // ------------------------------------------------------------------ //
    //  Écritures
    // ------------------------------------------------------------------ //

    /**
     * Insère un nouveau dossier à partir des données du formulaire.
     * La date de création est posée par la base (DEFAULT CURRENT_TIMESTAMP).
     * @param array $donnees colonne => valeur (déjà typées par le contrôleur)
     */
    public function inserer(array $donnees): int
    {
        $donnees['cree_par'] = utilisateur_login();
        $colonnes = array_keys($donnees);
        $sql = 'INSERT INTO `' . $this->definition['table'] . '` (`' . implode('`, `', $colonnes) . '`)
                VALUES (:' . implode(', :', $colonnes) . ')';
        $req = Database::connexion()->prepare($sql);
        $req->execute($donnees);
        return (int) Database::connexion()->lastInsertId();
    }

    /**
     * Met à jour un dossier existant. La date de modification est posée
     * par la base (ON UPDATE CURRENT_TIMESTAMP) ; le modificateur est
     * tracé dans modifie_par.
     */
    public function modifier(int $id, array $donnees): bool
    {
        $donnees['modifie_par'] = utilisateur_login();
        $affectations = implode(', ', array_map(fn ($c) => "`$c` = :$c", array_keys($donnees)));
        $sql = 'UPDATE `' . $this->definition['table'] . "` SET $affectations WHERE `"
             . $this->definition['cle'] . '` = :id';
        $req = Database::connexion()->prepare($sql);
        return $req->execute($donnees + ['id' => $id]);
    }

    /**
     * Met à jour la conclusion d'un dossier (registres pénaux/civils/dessaisis).
     */
    public function enregistrerConclusion(int $id, string $texte): bool
    {
        return $this->modifier($id, [$this->definition['conclusion'] => $texte]);
    }

    // ------------------------------------------------------------------ //
    //  Internes
    // ------------------------------------------------------------------ //

    /**
     * Construit les filtres SQL (recherche + année) avec leurs paramètres liés.
     * @return array{0: string[], 1: array}
     */
    private function construireFiltres(string $recherche, ?int $annee): array
    {
        $where  = [];
        $params = [];

        $recherche = trim($recherche);
        if ($recherche !== '') {
            $conditions = [];
            foreach ($this->definition['recherche'] as $i => $colonne) {
                $cle = 'q' . $i;
                $conditions[] = "`$colonne` LIKE :$cle";
                $params[$cle] = '%' . $recherche . '%';
            }
            $where[] = '(' . implode(' OR ', $conditions) . ')';
        }
        if ($annee !== null) {
            $where[] = 'annee = :annee';
            $params['annee'] = $annee;
        }
        return [$where, $params];
    }
}
