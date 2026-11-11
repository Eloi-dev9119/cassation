# PGCC v2

Application PHP 8 de gestion des registres et du suivi des dossiers du Parquet Général près la Cour Suprême. Elle partage la base `pgcc_utf8mb4` et les comptes utilisateurs avec PGCC v1.

> **Guide utilisateur** : [`docs/guide-utilisateur.md`](docs/guide-utilisateur.md) — mode d'emploi complet pour les membres du Parquet (connexion, registres, mémoires, distributions, administration). Un condensé est intégré dans l'application, page **Aide** du menu principal.

## Fonctionnalités

- **Six registres** gérés de façon déclarative dans `app/models/Registres.php` : Registres Arrivés, Registres Pénaux, Registres Civils, Registres Désaisissements, Registre du Siège (Cassation civile) et Registre PIL (intérêt de la loi) — listes, recherche, formulaires et fiches détaillées générés à partir des définitions, sans duplication de code.
- **Mémoires** (ampliatif et en défense) rattachés aux dossiers de pourvoi.
- **Recherche globale** depuis le tableau de bord : un seul terme interrogé dans les six registres et les mémoires, résultats groupés par registre avec liens directs vers les fiches.
- **Suivi « § 321 »** des dossiers (`SuiviModel`, `SuiviDossierModel`) : suivi de l'état d'avancement des dossiers.
- **Distributions** : répartition des dossiers et synthèse par Avocat Général.
- **Tableau de bord** : statistiques d'activité (`app/models/StatsModel.php`), compteurs de conclusions à rédiger / à transmettre pour frappe, derniers dossiers modifiés et **accès aux modules toujours visibles** (registres, suivis, synthèse des suivis, répartition, distributions).
- **Administration** : gestion des comptes utilisateurs, validation des inscriptions et journal d'activité, réservée aux postes **PG** et **SG**.
- **Exports CSV et PDF** des listes et rapports (tenant compte des filtres actifs) et impression des listes, rapports et fiches depuis le navigateur. Le PDF, document de consultation, est limité aux 1 000 premières lignes ; le CSV fournit l'export complet.
- **Sauvegardes automatiques** de la base (dump horodaté, rotation, journal, tâche planifiée Windows).
- **E-mails** de confirmation d'inscription via SMTP (désactivé par défaut, configuration ci-dessous).

## Démarrage local

1. Démarrer Apache et MySQL dans XAMPP.
2. Vérifier les paramètres de connexion dans `config/app.php` ou définir les variables d’environnement `PGCC_DB_HOST`, `PGCC_DB_NAME`, `PGCC_DB_USER` et `PGCC_DB_PASSWORD`.
3. Ouvrir <http://localhost/pgcc2/>.

Le projet garde `index.php` à sa racine pour conserver cette URL historique. Les sources internes sont rangées sous `app/` et protégées des requêtes HTTP par `.htaccess`. Les ressources publiques se trouvent sous `public/assets/`.

L'URL `http://localhost/pgcc2/` est servie par une **jonction** : `C:\xampp\htdocs\pgcc2` pointe directement vers le dossier du projet, sans copie de fichiers. Le script `tools/deployer-site.bat` contrôle cette jonction (et la recrée avec `tools\deployer-site.bat reparer` si elle a été supprimée ou détournée). Aucune synchronisation de fichiers n'est donc nécessaire pour publier le site local.

## Configuration e-mail

L'envoi d'e-mails est désactivé par défaut. Sous XAMPP, le plus simple est de copier `config/mail.local.php.example` en `config/mail.local.php` puis d'y renseigner les valeurs ci-dessous. Ce fichier local est protégé par `.htaccess`. Les variables d'environnement restent prioritaires si elles sont définies :

- `PGCC_MAIL_ACTIF=true`
- `PGCC_MAIL_HOST=smtp.exemple.mg`
- `PGCC_MAIL_PORT=587` (`0` choisit automatiquement 587 en TLS, 465 en SSL et 25 sans chiffrement)
- `PGCC_MAIL_SECURITE=tls` (`tls`, `ssl` ou `none`)
- `PGCC_MAIL_USER=...`
- `PGCC_MAIL_PASSWORD=...`
- `PGCC_MAIL_FROM=no-reply@exemple.mg`
- `PGCC_MAIL_FROM_NAME=PGCC - Parquet Général`
- `PGCC_MAIL_VERIFY_CERT=true`

Tester la configuration : `php tools/maintenance/tester_email.php destinataire@example.com`.

Après validation d'une inscription, un e-mail de confirmation est envoyé au membre. Si l'envoi échoue, le compte reste validé et l'administrateur reçoit un avertissement.

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
│   └── backups/            Sauvegardes automatiques et journal (sauvegarde.log)
├── public/
│   └── assets/             CSS, JavaScript et images servies au navigateur
├── storage/
│   └── legacy/              Copies de l’ancienne application conservées
├── tests/                   Vérifications de chargement, rendu et parcours
├── tools/
│   ├── maintenance/        Outils CLI de maintenance ponctuelle
│   ├── sauvegarde-bdd.bat  Dump horodaté, rotation et journal
│   └── planifier-sauvegarde.bat  Tâche Windows quotidienne
├── config.php               Chargeur de compatibilité pour les scripts
├── index.php                Point d’entrée HTTP et routeur
└── README.md
```

Le routeur délègue aux contrôleurs. Les contrôleurs valident les entrées et appellent les modèles ; les vues ne portent pas les requêtes SQL. Les six registres sont décrits dans `app/models/Registres.php` et partagent `RegistreModel` et `RegistreController`.

## Base de données

Avant une mise à jour, sauvegarder la base. Pour appliquer en une fois les évolutions de PGCC v2 sur une base PGCC existante, importer `database/migrations/LogicielCssation.sql` dans phpMyAdmin. Le script cible `pgcc_utf8mb4`, ajoute le registre des mémoires, les champs manquants et les relations. Il préserve les historiques orphelins et affiche les cas qui demandent une correction manuelle.

`LogicielCssation.sql` est une migration, pas une sauvegarde complète ni un installateur pour une base vide. Pour sauvegarder les données actuelles, utiliser `tools/sauvegarde-bdd.bat` (voir [Sauvegardes automatiques](#sauvegardes-automatiques)).

Les deux applications partagent la table des comptes. Les mots de passe récents sont stockés avec `password_hash()` ; les anciens MD5 sont convertis après une connexion réussie.

L’outil `tools/maintenance/executer_relations.php` est conservé pour une intervention ciblée. Il supprime les traces d’historique sans dossier correspondant ; faire une sauvegarde avant de l’utiliser. La migration unifiée est préférable pour une mise à jour qui doit préserver ces traces.

L’outil `tools/maintenance/compter_mdp_legacy.php` compte, en lecture seule, les empreintes de mots de passe encore stockées en MD5 dans les tables de comptes. La vérification MD5 de `app/core/Password.php` — nécessaire tant que PGCC v1 écrit encore du MD5 dans la table partagée — pourra être retirée quand cet outil annonce zéro et que v1 n’est plus utilisée.

## Sauvegardes automatiques

Les sauvegardes sont écrites dans `database/backups/`. Deux scripts les pilotent :

- `tools/sauvegarde-bdd.bat` : dump complet de `pgcc_utf8mb4` avec `mysqldump` (routines et événements, `utf8mb4`), fichier horodaté à la seconde (`pgcc_utf8mb4_AAAAMMJJ_HHMMSS.sql`), puis **compression `.zip`** (environ 10 fois plus petite). Le script vérifie d'abord que la base est accessible, supprime le fichier en cas d'échec, journalise chaque opération dans `database/backups/sauvegarde.log` (rotation au-delà de 1 Mo) et conserve les **30** sauvegardes les plus récentes (`.sql` ou `.zip`). La compression se désactive avec `COMPRESSER=0`.
- `tools/planifier-sauvegarde.bat` : installe, contrôle ou retire une tâche Windows quotidienne.

```bat
tools\planifier-sauvegarde.bat creer 18:00   rem installe la tâche (défaut : 18:00)
tools\planifier-sauvegarde.bat statut        rem affiche l'état de la tâche
tools\planifier-sauvegarde.bat executer      rem lance la tâche immédiatement
tools\planifier-sauvegarde.bat supprimer     rem retire la tâche
```

Les variables `MYSQLBIN`, `DB`, `USERMYSQL`, `MYSQLPWD`, `KEEP` et `COMPRESSER` peuvent être redéfinies en tête de `sauvegarde-bdd.bat`. La création de la tâche peut exiger une console ouverte en administrateur. Les dossiers `backups` et les fichiers `*.log` sont exclus du versionnement et des sauvegardes.

## Espace d'administration

Le menu **Administration** n'est affiché qu'aux postes habilités (`POSTES_ADMINISTRATION`, par défaut **PG** et **SG**). Il regroupe le tableau de bord administrateur, la gestion des comptes utilisateurs (création, modification, retraite/réactivation, réinitialisation du mot de passe) et le journal d'activité issu des colonnes d'audit. Les demandes d'inscription restent traitables depuis **Membres** ou depuis **Administration > Comptes utilisateurs**. Toute tentative d'accès direct par un autre poste est refusée.

## Vérifications

Les scripts de vérification se trouvent dans `tests/`. Le parcours E2E dépend d’une instance XAMPP et d’une base de test jetable. Il refuse toute base qui ne commence pas par `pgcc_e2e_` et vérifie la base cible avant ses écritures. Consulter `tests/test_e2e_xampp.sh` avant de le lancer.

## Impression et exports

Les listes des registres, mémoires, suivis et distributions proposent un export CSV tenant compte des filtres actifs. La répartition et la synthèse par Avocat Général sont aussi exportables. Les listes, rapports et fiches peuvent être imprimés depuis le navigateur.

## Revue de sécurité — écarts volontaires

Trois écarts aux recommandations des analyses automatiques (SonarQube) sont assumés ; les justifications ci-dessous font foi pour la revue des rapports :

1. **Vérification MD5 dans `app/core/Password.php`** — couche de compatibilité avec PGCC v1, qui partage la table des comptes et écrit encore du MD5 sans jamais le migrer. Tout nouveau mot de passe est stocké en bcrypt (`password_hash()`), et chaque connexion validée sur une empreinte MD5 la réécrit immédiatement en bcrypt. La branche se retire quand `tools/maintenance/compter_mdp_legacy.php` annonce zéro empreinte « hex32 » et que v1 est hors service.
2. **Identifiants SQL interpolés** (`nom_sql()`, `ordre_sql()` dans `app/core/helpers.php`, `identifiant()` dans l'outil CLI) — PDO ne paramètre pas les identifiants (tables, colonnes, ORDER BY). Chaque nom interpolé provient des définitions déclaratives du code et est validé sur `[A-Za-z0-9_]` avant interpolation ; toutes les valeurs de données passent par des requêtes préparées. Les requêtes contenant de l'interpolation utilisent systématiquement `prepare()`/`execute()`.
3. **`storage/legacy/`** — archive figée de l'application v1, conservée pour référence et inaccessible par le web (règle `[F]` du `.htaccess`). Exclue des analyses SonarQube via `sonar-project.properties` (`sonar.exclusions=**/storage/**`) ; aucun correctif ne doit y être apporté.
