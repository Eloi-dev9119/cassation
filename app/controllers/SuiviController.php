<?php
/**
 * Contrôleur des suivis de dossiers (types 321 et 107) : consultation,
 * recherche, création et archivage — même logique que la v1, avec les
 * conventions de données historiques.
 */

declare(strict_types=1);

final class SuiviController
{
    public function dispatch(array $route): void
    {
        Auth::exiger();

        $type = ($route[1] ?? '') === '107' ? '107' : '321';
        $action = $route[2] ?? 'liste';

        match ($action) {
            'liste'    => $this->liste($type),
            'creer'    => $this->creer($type),
            'archiver' => $this->archiver($type),
            default    => page_404('Action inconnue'),
        };
    }

    // ------------------------------------------------------------------ //

    private function liste(string $type): void
    {
        $page      = max(1, (int) entree_get('p', 1));
        $recherche = trim((string) entree_get('q', ''));
        $modeleSuivi = new SuiviModel();
        if (entree_get('export', '') === 'csv') {
            $definition = SuiviModel::definition($type);
            $colonnes = array_map(static fn (array $c): array => [
                'champ' => $c['nom'], 'libelle' => $c['libelle'], 'type' => $c['type'] === 'textelong' ? '' : $c['type'],
            ], $definition['champs']);
            CsvExporter::telecharger('suivi-' . $type . '-' . date('Ymd') . '.csv', $colonnes, $modeleSuivi->exporter($type, $recherche));
        }
        $resultat  = $modeleSuivi->liste($type, $page, $recherche);

        View::afficher('suivis/liste.php', [
            'titrePage' => SuiviModel::definition($type)['libelle'] . ' — ' . APP_NOM,
            'type'      => $type,
            'resultat'  => $resultat,
            'recherche' => $recherche,
        ]);
    }

    /**
     * Formulaire et traitement de création d'un suivi.
     */
    private function creer(string $type): void
    {
        $modele = SuiviModel::definition($type);

        $erreurs = [];
        if (requete_post()) {
            if (!Auth::verifierJeton(entree_post('jeton'))) {
                flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
                rediriger('/suivis/' . $type);
            }
            [$donnees, $erreurs] = (new SuiviModel())->lireFormulaire($type);
            if ($erreurs === []) {
                (new SuiviModel())->inserer($type, $donnees);
                flash('succes', 'Le suivi n° ' . $donnees['num_ordre'] . '/' . $donnees['annee'] . ' a été enregistré.');
                rediriger('/suivis/' . $type);
            }
        }

        View::afficher('suivis/formulaire.php', [
            'titrePage' => 'Nouveau suivi — ' . $modele['libelle'] . ' — ' . APP_NOM,
            'type'      => $type,
            'definition'=> $modele,
            'erreurs'   => $erreurs,
        ]);
    }

    /**
     * Bascule archivé / désarchivé d'un suivi.
     */
    private function archiver(string $type): void
    {
        if (!requete_post()) {
            flash('erreur', 'Cette action doit être envoyée depuis le formulaire prévu.');
            rediriger('/suivis/' . $type);
        }
        if (!Auth::verifierJeton(entree_post('jeton'))) {
            flash('erreur', 'Votre session a expiré. Rechargez la page puis réessayez.');
            rediriger('/suivis/' . $type);
        }
        $id = (int) entree_post('id', 0);
        $modele = new SuiviModel();

        // État courant lu depuis la ligne concernée.
        $def = SuiviModel::definition($type);
        $req = Database::connexion()->prepare(
            'SELECT archiver FROM ' . nom_sql($def['table']) . ' WHERE ' . nom_sql($def['cle']) . ' = :id'
        );
        $req->execute(['id' => $id]);
        $courant = $req->fetchColumn();

        if ($courant !== false) {
            $modele->archiver($type, $id, !bin_vers_bool($courant));
            flash('info', bin_vers_bool($courant) ? 'Suivi désarchivé.' : 'Suivi archivé.');
        }
        rediriger('/suivis/' . $type);
    }
}
