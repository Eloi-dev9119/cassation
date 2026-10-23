# PGCC v2

Application PHP 8 de gestion des registres et du suivi des dossiers du Parquet Général près la Cour Suprême. Elle partage la base `pgcc_utf8mb4` et les comptes utilisateurs avec PGCC v1.

> **Guide utilisateur** : [`docs/guide-utilisateur.md`](docs/guide-utilisateur.md) — mode d'emploi complet pour les membres du Parquet (connexion, registres, mémoires, distributions, administration). Un condensé est intégré dans l'application, page **Aide** du menu principal.

## Démarrage local

1. Démarrer Apache et MySQL dans XAMPP.
2. Vérifier les paramètres de connexion dans `config/app.php` ou définir les variables d’environnement `PGCC_DB_HOST`, `PGCC_DB_NAME`, `PGCC_DB_USER` et `PGCC_DB_PASSWORD`.
3. Ouvrir <http://localhost/pgcc2/>.

Le projet garde `index.php` à sa racine pour conserver cette URL historique. Les sources internes sont rangées sous `app/` et protégées des requêtes HTTP par `.htaccess`. Les ressources publiques se trouvent sous `public/assets/`.

## Organisation du projet

```text
.
├── app/
│   ├── controllers/       Traitement des routes et orchestration
│   ├── core/              Services communs : base, session, vues, helpers
│   ├── models/             Requêtes SQL et règles d’accès aux données
│   └── views/              Pages, gabarits et composants réutilisables
├── config/
│   └── app.php             Configuration de l’application et de la base
├── database/
│   ├── migrations/         Schéma et évolutions SQL
│   └── backups/            Export historique des tables d’audit
├── public/
│   └── assets/             CSS, JavaScript et images servies au navigateur
├── storage/
│   └── legacy/              Copies de l’ancienne application conservées
├── tests/                   Vérifications de chargement, rendu et parcours
├── tools/
│   └── maintenance/         Outils CLI de maintenance ponctuelle
├── config.php               Chargeur de compatibilité pour les scripts
├── index.php                Point d’entrée HTTP et routeur
└── README.md
```

Le routeur délègue aux contrôleurs. Les contrôleurs valident les entrées et appellent les modèles ; les vues ne portent pas les requêtes SQL. Les cinq variantes de registre sont décrites dans `app/models/Registres.php` et partagent `RegistreModel` et `RegistreController`.

## Base de données

Avant une mise à jour, sauvegarder la base. Pour appliquer en une fois les évolutions de PGCC v2 sur une base PGCC existante, importer `database/migrations/LogicielCssation.sql` dans phpMyAdmin. Le script cible `pgcc_utf8mb4`, ajoute le registre des mémoires, les champs manquants et les relations. Il préserve les historiques orphelins et affiche les cas qui demandent une correction manuelle.

`LogicielCssation.sql` est une migration, pas une sauvegarde complète ni un installateur pour une base vide. L’export historique de `database/backups/` ne contient que des tables d’historique. Pour sauvegarder les données actuelles, démarrer MySQL puis exporter la base, par exemple avec `mysqldump`.

Les deux applications partagent la table des comptes. Les mots de passe récents sont stockés avec `password_hash()` ; les anciens MD5 sont convertis après une connexion réussie.

L’outil `tools/maintenance/executer_relations.php` est conservé pour une intervention ciblée. Il supprime les traces d’historique sans dossier correspondant ; faire une sauvegarde avant de l’utiliser. La migration unifiée est préférable pour une mise à jour qui doit préserver ces traces.

L’outil `tools/maintenance/compter_mdp_legacy.php` compte, en lecture seule, les empreintes de mots de passe encore stockées en MD5 dans les tables de comptes. La vérification MD5 de `app/core/Password.php` — nécessaire tant que PGCC v1 écrit encore du MD5 dans la table partagée — pourra être retirée quand cet outil annonce zéro et que v1 n’est plus utilisée.

## Vérifications

Les scripts de vérification se trouvent dans `tests/`. Le parcours E2E dépend d’une instance XAMPP et d’une base de test jetable. Il refuse toute base qui ne commence pas par `pgcc_e2e_` et vérifie la base cible avant ses écritures. Consulter `tests/test_e2e_xampp.sh` avant de le lancer.

## Impression et exports

Les listes des registres, mémoires, suivis et distributions proposent un export CSV tenant compte des filtres actifs. La répartition et la synthèse par Avocat Général sont aussi exportables. Les listes, rapports et fiches peuvent être imprimés depuis le navigateur.

## Revue de sécurité — écarts volontaires

Trois écarts aux recommandations des analyses automatiques (SonarQube) sont assumés ; les justifications ci-dessous font foi pour la revue des rapports :

1. **Vérification MD5 dans `app/core/Password.php`** — couche de compatibilité avec PGCC v1, qui partage la table des comptes et écrit encore du MD5 sans jamais le migrer. Tout nouveau mot de passe est stocké en bcrypt (`password_hash()`), et chaque connexion validée sur une empreinte MD5 la réécrit immédiatement en bcrypt. La branche se retire quand `tools/maintenance/compter_mdp_legacy.php` annonce zéro empreinte « hex32 » et que v1 est hors service.
2. **Identifiants SQL interpolés** (`nom_sql()`, `ordre_sql()` dans `app/core/helpers.php`, `identifiant()` dans l'outil CLI) — PDO ne paramètre pas les identifiants (tables, colonnes, ORDER BY). Chaque nom interpolé provient des définitions déclaratives du code et est validé sur `[A-Za-z0-9_]` avant interpolation ; toutes les valeurs de données passent par des requêtes préparées. Les requêtes contenant de l'interpolation utilisent systématiquement `prepare()`/`execute()`.
3. **`storage/legacy/`** — archive figée de l'application v1, conservée pour référence et inaccessible par le web (règle `[F]` du `.htaccess`). Exclue des analyses SonarQube via `sonar-project.properties` (`sonar.exclusions=**/storage/**`) ; aucun correctif ne doit y être apporté.
