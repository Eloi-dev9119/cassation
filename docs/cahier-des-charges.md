# CAHIER DES CHARGES

## PGCC v2 — Plateforme de Gestion des Registres du Parquet Général

**Parquet Général près la Cour Suprême — Justice Madagascar**

| | |
|---|---|
| **Projet** | PGCC v2 (refonte du progiciel PGCC v1) |
| **Application** | Gestion des registres judiciaires et suivi des dossiers |
| **Organisme** | Parquet Général près la Cour Suprême — MINJUS |
| **Version du document** | 1.0 — 28 octobre 2026 |
| **Statut** | Application en production locale, en évolution |
| **Document associé** | `docs/guide-utilisateur.md` (guide d'utilisation) |

---

## Sommaire

1. [Contexte et origine du projet](#1-contexte-et-origine-du-projet)
2. [Objectifs du projet](#2-objectifs-du-projet)
3. [Acteurs et profils utilisateurs](#3-acteurs-et-profils-utilisateurs)
4. [Périmètre fonctionnel](#4-périmètre-fonctionnel)
5. [Description fonctionnelle détaillée](#5-description-fonctionnelle-détaillée)
6. [Règles de gestion](#6-règles-de-gestion)
7. [Exigences non fonctionnelles](#7-exigences-non-fonctionnelles)
8. [Architecture technique](#8-architecture-technique)
9. [Modèle de données](#9-modèle-de-données)
10. [Environnement et dépendances](#10-environnement-et-dépendances)
11. [Sécurité](#11-sécurité)
12. [Tests et recette](#12-tests-et-recette)
13. [Déploiement et exploitation](#13-déploiement-et-exploitation)
14. [Limites actuelles et évolutions envisagées](#14-limites-actuelles-et-évolutions-envisagées)

---

## 1. Contexte et origine du projet

Le Parquet Général près la Cour Suprême de Madagascar tient, sur support papier et sur une application historique (PGCC v1), plusieurs registres judiciaires : les registres des arrivées, des pourvois pénaux, des pourvois civils, des déssaisissements, le registre du Siège, ainsi que les registres de suivi associés et les listes de distribution des dossiers aux Avocats Généraux.

### 1.1 Limites de l'existant (PGCC v1)

- Interface vieillissante, difficile à maintenir : scripts PHP procéduraux imbriquant SQL et présentation.
- Encodage des données hétérogène (ancienne base latin1) ; les accents sont corrompus dans certaines listes.
- Pas de gestion fine des droits : toute personne connectée peut faire toutes les actions.
- Pas de traçabilité des modifications.
- Fonctionnalités absentes : registre des mémoires, synthèse de suivi par Avocat Général, répartition mensuelle, journal d'activité, exports filtrés.

### 1.2 Réponse du projet (PGCC v2)

PGCC v2 est la **refonte complète** de l'application, organisée en architecture MVC lisible, avec une base de données migrée en **UTF-8 (`pgcc_utf8mb4`, InnoDB)**, tout en **partageant la table des comptes utilisateurs avec PGCC v1** pendant la période de transition (synchronisation des données entre les deux applications).

### 1.3 Volumétrie de référence

Données réelles portées par la base au 28/10/2026 :

| Registre / table | Lignes |
|---|---:|
| Registre des arrivées (`pgcc_reg_arrivee_pen`) | 11 906 |
| Pourvois pénaux (`pgcc_reg_pourvoi_pen`) | 6 405 |
| Pourvois civils (`pgcc_reg_pourvoi_civ`) | 4 434 |
| Déssaisissements (`pgcc_reg_des`) | 31 |
| Distributions au Parquet Général (`pgcc_distribution_pg`) | 10 488 |
| Historiques consolidés | 35 000+ |
| Comptes utilisateurs (`pgcc_client`) | 75 |

---

## 2. Objectifs du projet

### 2.1 Objectifs fonctionnels

1. **Informatiser et fiabiliser** la tenue des registres du Parquet Général (insertion, modification, recherche, archivage).
2. **Suivre les dossiers** à chaque étape : arrivée, pourvoi, détention, mémoire, distribution, conclusion, transmission.
3. **Distribuer les dossiers** aux Avocats Généraux et produire la répartition mensuelle et la synthèse par magistrat.
4. **Gérer les comptes** : demandes d'inscription, validation par l'administration, mise à la retraite, réinitialisation de mot de passe.
5. **Tracer toute modification** (qui, quoi, quand) et proposer un journal d'activité.

### 2.2 Objectifs techniques

1. Architecture **MVC claire** : contrôleurs (routes), modèles (SQL), vues (rendu) — aucune requête SQL dans les vues.
2. Base de données **unique, cohérente et encodée UTF-8**, partagée avec la v1 pendant la transition.
3. Application **sans dépendance externe** (PHP pur, aucun framework, aucun paquet Composer) pour rester déployable dans un environnement à réseau restreint.
4. Sécurité conforme aux bonnes pratiques : CSRF, XSS, injection SQL, sessions durcies, mots de passe hachés.

---

## 3. Acteurs et profils utilisateurs

Les profils correspondent aux codes de postes stockés dans `pgcc_client.poste` :

| Code | Profil | Rôle dans l'application |
|---|---|---|
| **PG** | Procureur Général | Accès total, y compris l'espace **Administration** |
| **SG** | Secrétaire Général | Accès total, y compris l'espace **Administration** |
| **AG** | Avocat Général | Consultation et saisie sur les registres ; concerné par la répartition et la synthèse de ses dossiers |
| **secretaire_AG** | Secrétaire AG | Saisie et suivi des dossiers de son Avocat Général |
| **Secretaire particulier PGCC** | Secrétaire particulier | Saisie et suivi des dossiers |

### 3.1 Règle d'habilitation

- L'espace **Administration** (tableau de bord admin, comptes utilisateurs, journal d'activité) n'est accessible **qu'aux postes PG et SG** (`POSTES_ADMINISTRATION`). Toute tentative d'accès direct par un autre poste est refusée ; le menu correspondant n'est pas affiché.
- Les demandes d'inscription peuvent être traitées depuis **Membres** ou **Administration > Comptes utilisateurs**.
- Chaque utilisateur gère ses informations et son mot de passe depuis **Profil**.

---

## 4. Périmètre fonctionnel

| # | Module | Route principale | Contenu |
|---|---|---|---|
| 1 | Authentification | `/connexion`, `/inscription`, `/mot-de-passe-oublie` | Connexion e-mail + mot de passe, demande d'inscription, mot de passe oublié par question secrète |
| 2 | Tableau de bord | `/tableau-de-bord` | Compteurs par registre, graphique d'activité par année, accès rapides |
| 3 | Registre des Arrivés | `/registres/arrivees` | Dossiers à l'arrivée au Parquet |
| 4 | Registre Pénaux | `/registres/penaux` | Pourvois pénaux (le registre le plus utilisé) |
| 5 | Registre Civils | `/registres/civils` | Pourvois civils |
| 6 | Registre Déssaisissements | `/registres/dessaisissements` | Demandes de déssaisissement |
| 7 | Registre du Siège | `/registres/siege` | Suivi des cassations civiles au Siège, lié aux dossiers civils |
| 8 | Registre des Mémoires | `/memoires` | Mémoires ampliatifs / en défense, notification, dépôt, rapporteur, état |
| 9 | Suivi des arrivées (321) | `/suivis/321` | Suivi des dossiers d'arrivée, localisation, détention |
| 10 | Suivi des dossiers pénaux (107) | `/suivis/107` | Transmission et réception des dossiers pénaux |
| 11 | Suivis Dossiers | `/suivis-dossiers` | Synthèse par Avocat Général : dossiers distribués, conclusions rédigées |
| 12 | Répartition | `/repartition` | Répartition mensuelle des dossiers par AG, graphique |
| 13 | Distributions | `/distributions` | Listes de distribution pénale et civile au Parquet Général |
| 14 | Membres | `/membres` | Demandes en attente, membres actifs, retraités |
| 15 | Administration | `/administration` | Tableau de bord admin, comptes utilisateurs, journal d'activité |
| 16 | Profil | `/profil` | Informations personnelles, changement de mot de passe |
| 17 | Aide | `/aide` | Guide intégré condensé |

Chaque module de liste offre : **recherche texte**, **filtre par année**, **pagination** (25 lignes/page), **export CSV tenant compte des filtres**, **impression navigateur**, **actions par ligne** (fiche, modifier, archiver/désarchiver).

---

## 5. Description fonctionnelle détaillée

### 5.1 Authentification et comptes

- **Connexion** : e-mail (identifiant) + mot de passe + option « mémoriser mon adresse » (cookie local 30 jours). Session expirée automatiquement après **1 heure** d'inactivité (renouvelée à chaque requête). Chaque champ de mot de passe dispose d'un bouton **« Afficher / Masquer »** pour vérifier sa saisie.
- **Inscription** : formulaire de demande (nom, prénom, sexe, e-mail, mot de passe, poste, sigle AG, matricule IM, bureau, question et réponse secrètes). La demande est stockée dans `pgcc_new_client` avec `Admin_confirm = 0` et n'active pas de compte.
- **Validation** : un administrateur (PG/SG) valide la demande ; le compte est recopié dans `pgcc_client` (actif). Une notification e-mail de confirmation est envoyée si le SMTP est configuré ; l'échec d'envoi ne bloque pas la validation.
- **Mot de passe oublié** : saisie de l'e-mail, puis réponse à la question secrète ; en cas de succès, définition d'un nouveau mot de passe. Sans question secrète définie, l'utilisateur doit passer par l'administration.
- **Mots de passe** : hachés avec `password_hash()` (bcrypt). **Compatibilité v1** : les empreintes MD5 de la table partagée sont vérifiées puis **réécrites en bcrypt à la première connexion réussie** (migration transparente, voir §9.3).

### 5.2 Registres (5 variantes + mémoires)

Les cinq registres partagent un **moteur générique** piloté par des définitions déclaratives (`app/models/Registres.php`) : nom de table, colonnes, types, sections du formulaire, colonnes de liste, colonnes de recherche, clause de tri. Cela garantit un comportement identique (validation, affichage, export) sur tous les registres.

Chaque dossier porte :

- les **champs métier** propres au registre (n° d'ordre, année, n° dossier PGCS, parties, juridiction, dates, détention, observation…) ;
- les **colonnes de traçabilité** : `date_creation`, `cree_par`, `date_modification`, `modifie_par`.

Opérations : créer, consulter (fiche), modifier, archiver/désarchiver (avec confirmation). L'archivage n'efface rien : le dossier reste consultable et marqué « Archivé ».

**Cas particulier — Registre du Siège** : les dossiers de Siège peuvent être **rattachés à un pourvoi civil** (création depuis la fiche civil, lien croisé dans les deux sens, lien « Créer le suivi au Siège »).

**Cas particulier — Registre des Mémoires** : chaque mémoire (ampliatif ou en défense) peut être **rattaché à un dossier pénal ou civil** (préremplissage du n° dossier depuis la fiche du dossier), avec suivi de l'état (notifié, déposé, transmis au secrétaire…), du rapporteur et de la juridiction d'origine.

**Conclusion civile** : depuis la fiche d'un pourvoi civil, génération d'un **modèle de conclusion publique prérempli** (en-tête COUR SUPRÊME, référence du dossier, parties, date française, chambre concernée) prêt à imprimer.

### 5.3 Suivis

- **Suivi des arrivées (321)** : localisation du dossier, détention (oui/non + date), sigle de l'AG en charge, observations. Chaque ligne peut être archivée.
- **Suivi des dossiers pénaux (107)** : dates de transmission au PGCS, réception, transmission, détention.
- **Suivis Dossiers (synthèse)** : pour un Avocat Général donné, compteurs (dossiers pénaux distribués, civils distribués, conclusions rédigées, civils en attente de frappe au secrétariat) et liste détaillée avec état des conclusions.
- **Répartition** : répartition mensuelle des dossiers distribués par AG, avec filtre mois/année et graphique en barres ; le rattachement au mois se fait par le nom de la liste de distribution.

### 5.4 Distributions

- Gestion des **listes de distribution** (par liste : Juin 2017, Décembre 2017…) attribuant chaque dossier à un AG (sigle), avec dates de remise au secrétariat et de remise à l'AG.
- Création d'une nouvelle liste par sélection de dossiers filtrés (pénal ou civil).
- Export CSV et impression de la liste filtrée.

### 5.5 Administration

- **Tableau de bord admin** : compteurs (comptes actifs, demandes en attente, retraités, total ; volumes par registre), bandeau d'alerte sur les demandes en attente, journal d'activité récent.
- **Comptes utilisateurs** : liste filtrable et recherchable ; créer, modifier (traçabilité affichée), mettre à la retraite, réactiver, réinitialiser le mot de passe ; **pastille de présence** à côté de chaque compte (« En ligne » si activité de moins de 15 minutes, dernier passage connu sinon).
- **Journal d'activité** : dernières modifications par module et référence, auteur, dates.

### 5.6 Ergonomie transversale

- **Notifications flash** (toast auto-dismiss + alertes d'erreur persistantes) après chaque action.
- **Confirmations** avant toute action sensible (déconnexion, archivage, retraite d'un compte) via boîte de dialogue dédiée.
- **Thème Institutionnel / Neutre chaud** commutable et mémorisé — design sobre à accent unique (bleu institutionnel / cuivre chaud), sur demande du Parquet.
- Design responsive (palette monochrome zinc, aplats neutres), adapté aux écrans de bureau du Parquet.
- Page **Aide** intégrée reprenant le guide utilisateur condensé.

---

## 6. Règles de gestion

| # | Règle |
|---|---|
| RG-01 | Seul un compte **validé** (`valide = 1`) peut se connecter. |
| RG-02 | Un compte **mis à la retraite** (`status = 'Retraite'`) ne peut plus se connecter. Les comptes historiques sans statut restent autorisés (compatibilité v1). |
| RG-03 | Les actions d'administration des comptes sont réservées aux postes **PG et SG**. |
| RG-04 | Toute écriture en base renseigne `cree_par` / `modifie_par` avec le login de session (ou `system` hors session, cas des outils CLI). |
| RG-05 | L'archivage d'un dossier ou d'un suivi est réversible et ne supprime aucune donnée. |
| RG-06 | Un dossier du registre des Mémoires peut être rattaché à un dossier pénal **ou** civil (un seul à la fois) ; le préremplissage du n° de dossier se fait depuis la fiche source. |
| RG-07 | La numérotation d'ordre (`num_ordre`) est saisie par l'utilisateur ; l'année et le n° de dossier PGCS sont requis sur les registres de pourvois. |
| RG-08 | Les dates sont saisies et affichées au format JJ/MM/AAAA ; stockage ISO AAAA-MM-JJ. |
| RG-09 | Les exports CSV reflètent exactement les filtres et la recherche actifs de la liste. |
| RG-10 | La session expire après 1 h d'inactivité ; les formulaires soumis après expiration renvoient vers la connexion. |
| RG-11 | Tout formulaire embarque un **jeton CSRF** de session ; toute action POST le vérifie. |
| RG-12 | Les mots de passe MD5 hérités de la v1 sont convertis en bcrypt à la première connexion réussie (condition de retrait : zéro empreinte MD5 restante et v1 hors service — outil `compter_mdp_legacy.php`). |
| RG-13 | La répartition mensuelle rattache les distributions au mois par le **nom de la liste** de distribution (ex. « Octobre 2017 »). |
| RG-14 | Un utilisateur non-administrateur voit la liste des membres sans boutons d'action. |

---

## 7. Exigences non fonctionnelles

### 7.1 Performance

- Pagination systématique (25 lignes/page) sur toutes les listes ; index sur les clés de recherche principales.
- Temps d'affichage cible < 1 s sur les listes paginées avec la volumétrie actuelle (11 000+ lignes).
- Requêtes préparées exclusivement ; aucune requête N+1 dans les listes.

### 7.2 Compatibilité

- **PHP ≥ 8.0** (syntaxe `match`, `str_contains`, types stricts `declare(strict_types=1)`).
- Navigateurs récents (Chrome, Edge, Firefox) ; impression navigateur native (feuilles de style print).
- Fonctionne sous **Apache + mod_rewrite** (XAMPP) comme avec le **serveur PHP intégré** (`php -S`).

### 7.3 Internationalisation et encodage

- Application **entièrement en français** ; fuseau `Indian/Antananarivo`.
- Chaîne complète UTF-8 : base `utf8mb4_unicode_ci`, en-têtes HTML `charset=utf-8`, échappement via `htmlspecialchars` (ENT_QUOTES, UTF-8).

### 7.4 Maintenabilité

- Structure MVC normalisée, autoloader par couche, fonctions utilitaires centralisées (`app/core/helpers.php`).
- Définitions **déclaratives** des registres : ajouter un registre = ajouter une définition + une table, sans toucher aux contrôleurs.
- Conventions de nommage françaises cohérentes ; en-têtes de fichiers documentant les variables de chaque vue.
- Journalisation des exceptions (error_log + page 500 dédiée, détail affiché uniquement en mode DEBUG).

---

## 8. Architecture technique

### 8.1 Schéma général

```text
Navigateur
   │  HTTP (index.php?r=/route)
   ▼
index.php (front controller, routeur match())
   ├── app/core/       Auth, Database (PDO), View, Password, Mailer,
   │                   CsvExporter, Graphiques, ConclusionCivile, helpers
   ├── app/controllers/ un contrôleur par module (dispatch des sous-routes)
   ├── app/models/      requêtes SQL + définitions déclaratives
   └── app/views/       vues PHP + gabarits + partiels (flash, filtres…)
   ▼
MySQL / MariaDB — base pgcc_utf8mb4 (InnoDB, utf8mb4_unicode_ci)
```

### 8.2 Routage

- Routeur unique dans `index.php` : route lue dans le paramètre `r`, décomposée en segments, dirigée vers le contrôleur.
- Exemples : `?r=/registres/penaux` (liste), `?r=/registres/penaux/fiche&id=42`, `?r=/suivis/321/creer`.
- Génération des URLs par la fonction `url()` (indépendante du répertoire d'installation) ; assets par `asset()`.

### 8.3 Sécurité des accès fichiers

Le `.htaccess` interdit tout accès HTTP direct aux sources : `app/`, `config/`, `database/`, `storage/`, `tools/`, `tests/`, `.git/`, `config.php`. Seuls `index.php` et `public/assets/` sont servis. Les dossiers sensibles renvoient HTTP 403.

### 8.4 Notifications e-mail (optionnel)

- `app/core/Mailer.php` : client SMTP maison (sockets natifs, TLS/SSL selon port, aucun paquet externe).
- Configuration via variables d'environnement ou `config/mail.local.php` (protégé par `.htaccess`, non versionné — voir `config/mail.local.php.example`).
- Usage actuel : e-mail de confirmation après validation d'une inscription. L'échec d'envoi n'interrompt jamais le processus métier.
- Outil de test : `php tools/maintenance/tester_email.php destinataire@exemple.mg`.

---

## 9. Modèle de données

### 9.1 Vue d'ensemble (29 tables)

| Groupe | Tables | Rôle |
|---|---|---|
| **Comptes** | `pgcc_client`, `pgcc_new_client`, `pgcc_session`, `pgcc_presence` | Comptes actifs (partagés avec v1), demandes d'inscription, sessions, présence (dernière activité par utilisateur) |
| **Registres** | `pgcc_reg_arrivee_pen`, `pgcc_reg_pourvoi_pen`, `pgcc_reg_pourvoi_civ`, `pgcc_reg_des`, `siege_cassation_civ`, `pgcc_reg_pil`, `pgcc_reg_memoires` | Dossiers des six registres + mémoires |
| **Historiques** | `pgcc_historique_reg_*`, `pgcc_historique_suivi_*`, `pgcc_historique_pil`, `pgcc_historique_enrolement` | Traces des modifications (audit par table) |
| **Suivis** | `pgcc_suivi_arrivee`, `pgcc_suivi_pen`, `pgcc_suivi_civ` | Suivi des dossiers (321 / 107 / civils) |
| **Distributions** | `pgcc_distribution_pg`, `pgcc_liste_distribution` | Dossiers distribués et listes mensuelles |
| **Support** | `pgcc_types_decisions`, `pgcc_conclusion_publique`, `pgcc_correspondance`, `pgcc_message`, `pgcc_observation`, `pgcc_enrolement`, `pgcc_model_pil` | Référentiels et annexes |

### 9.2 Table des comptes (partagée avec v1)

`pgcc_client` : `id_client` (PK), `nom`, `prenom`, `sexe`, `login` (e-mail, unique), `mdp` (bcrypt ou MD5 hérité), `poste` (PG/AG/SG/…), `sigle_AG`, `IM` (matricule, unique), `num_bureau`, `question`/`reponse` (mot de passe oublié), `valide`, `status` (Actifs / Retraite / vide), `date_creation`, `date_modification`.

### 9.3 Migration des mots de passe (transition v1 → v2)

1. Nouveau mot de passe → **toujours** `password_hash()` (bcrypt).
2. À la connexion : bcrypt vérifié par `password_verify()` ; une empreinte hex32 (MD5) est vérifiée en temps constant puis, si correcte, **réécrite en bcrypt** immédiatement (UPDATE conditionné à l'empreinte d'origine).
3. Outil de suivi : `tools/maintenance/compter_mdp_legacy.php` (lecture seule) — la couche MD5 se retire quand l'outil annonce zéro et que la v1 n'est plus utilisée.

### 9.4 Intégrité et relations

- Clés étrangères posées par la migration `database/migrations/relations_cles_etrangeres.sql` (liens mémoires ↔ dossiers, Siège ↔ civil, historiques ↔ dossiers).
- Les traces d'historique orphelines (dossier supprimé dans l'ancienne application) sont préservées et signalées ; l'outil `tools/maintenance/executer_relations.php` permet une purge ciblée après sauvegarde.

### 9.5 Migrations

`database/migrations/` contient le schéma de référence (`LogicielCssation.sql`) et les correctifs incrémentaux (champs mémoires, audit, clés étrangères). Toute évolution de schéma doit passer par un fichier de migration versionné et documenté, appliqué après sauvegarde (`mysqldump`).

---

## 10. Environnement et dépendances

### 10.1 Environnement cible (production locale)

| Composant | Version / valeur | Statut |
|---|---|---|
| OS | Windows 10 (XAMPP) ou serveur Linux | OK |
| Apache | 2.4.58 avec **mod_rewrite** activé | OK |
| PHP | **≥ 8.0** (installé : 8.0.30) | OK |
| MySQL / MariaDB | XAMPP (InnoDB) | OK |
| Base | `pgcc_utf8mb4` (utf8mb4_unicode_ci) | OK |

### 10.2 Dépendances PHP (audit du 28/10/2026)

L'application est **autonome : aucun paquet Composer, aucun framework**. Les extensions PHP ci-dessous sont les seules requises — **toutes sont présentes et activées** :

| Extension | Usage dans le projet | Statut |
|---|---|---|
| `pdo_mysql` | Connexion base (PDO, requêtes préparées) | ✅ Activée |
| `mbstring` | Chaînes multi-octets (`mb_strtoupper`, `mb_substr`…) | ✅ Activée |
| `openssl` | TLS pour le client SMTP (`stream_socket_enable_crypto`) | ✅ Activée |
| `session` | Sessions durcies (cookie HttpOnly/SameSite) | ✅ Activée |
| `filter` | Entrées HTTP (`filter_input`) | ✅ Activée |
| `ctype` | Validation de caractères (`ctype_digit`) | ✅ Activée |
| `json` | Échanges internes | ✅ Activée |
| `curl` | Outils de test CLI | ✅ Activée |

Extensions **non requises** : `gd` et `intl` ne sont utilisées par aucun code source (les graphiques sont générés en HTML/CSS pur) ; `pcntl`/`posix` sont des extensions Unix sans équivalent ni utilité sur Windows. **Conclusion de l'audit : aucune dépendance manquante.**

### 10.3 Paramètres applicatifs (`config/app.php`)

| Paramètre | Valeur | Description |
|---|---|---|
| `DB_HOTE/DB_NOM/DB_USER/DB_MDP` | localhost / pgcc_utf8mb4 / root | Surchargables par variables d'environnement `PGCC_DB_*` |
| `SESSION_DUREE` | 3600 s | Expiration d'inactivité |
| `LIGNES_PAR_PAGE` | 25 | Pagination des listes |
| `POSTES_ADMINISTRATION` | PG, SG | Habilitation espace Administration |
| `POSTES_UTILISATEUR` | PG, AG, SG, secretaire_AG, Secretaire particulier PGCC | Postes proposés à l'inscription / création |
| `DEBUG` | false | Affichage du détail des erreurs (dev uniquement) |
| `MAIL_ACTIF` | false par défaut | Activation SMTP (`PGCC_MAIL_*` ou `config/mail.local.php`) |

---

## 11. Sécurité

| Menace | Contre-mesure implémentée |
|---|---|
| **XSS** | Échappement systématique des sorties par `e()` (`htmlspecialchars`, ENT_QUOTES, UTF-8) dans toutes les vues |
| **Injection SQL** | Requêtes préparées PDO exclusivement ; identifiants SQL interpolés validés par `nom_sql()`/`ordre_sql()` (`[A-Za-z0-9_]`) ; valeurs jamais interpolées |
| **CSRF** | Jeton de session 64 octets (`random_bytes`), vérifié par `hash_equals` sur chaque POST |
| **Fuite de sources** | `.htaccess` : 403 sur `app/`, `config/`, `database/`, `storage/`, `tools/`, `tests/`, `.git/`, `config.php` ; `Options -Indexes` |
| **Détournement de session** | Cookie `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS ; `session_regenerate_id(true)` à la connexion ; expiration d'inactivité 1 h |
| **Mots de passe** | Bcrypt (`password_hash`) ; compatibilité MD5 encadrée et migration progressive (§9.3) ; comparaison MD5 en temps constant |
| **Énumération / brute force** | Messages d'erreur génériques à la connexion ; question secrète obligatoire pour le parcours « mot de passe oublié » |
| **Accès non autorisé** | Contrôle de poste sur l'espace Administration ; redirection vers connexion sur toutes les pages protégées |

### 11.1 Écarts documentés (revue de sécurité)

Trois écarts aux recommandations d'analyse statique (SonarQube) sont **assumés et documentés** dans le README : la couche de compatibilité MD5 (§9.3), l'interpolation contrôlée d'identifiants SQL (PDO ne paramètre pas les identifiants), et l'archive `storage/legacy/` figée et inaccessible par le web.

---

## 12. Tests et recette

### 12.1 Stratégie

| Niveau | Contenu | Où |
|---|---|---|
| **Syntaxe** | `php -l` sur tous les fichiers | CI manuelle / pre-commit |
| **Chargement** | Configuration, helpers, classes, constantes — sans base | `tests/test_chargement.php` |
| **Rendus** | 30 vues rendues avec données factices, sans base ; détecte variable manquante, warning, fatal | `tests/test_rendus.php` |
| **E2E HTTP** | Parcours complets via HTTP sur base jetable (`pgcc_e2e_*` uniquement) | `tests/test_e2e_xampp.sh` |
| **Recette navigateur** | Parcours réel : connexion, chaque module, formulaire, export, confirmation, déconnexion | Manuel (12+ points vérifiés — captures dans `gui-test-screenshots/`) |

### 12.2 Résultats de la dernière revue (28/10/2026)

- 16/16 routes opérationnelles ; 29/29 pages authentifiées en HTTP 200 ; 404, redirections et protections 403 conformes.
- Audit statique : aucune vue manquante ni orpheline, 30 classes chargées, 4 assets présents.
- Correctifs issus de l'audit : onglets de registres ajoutés aux listes, liens vers Suivis Dossiers / Répartition rétablis, caractère corrompu corrigé, `.htaccess` restauré.

---

## 13. Déploiement et exploitation

### 13.1 Installation (XAMPP)

1. Démarrer Apache et MySQL (panneau XAMPP ou scripts `apache_start.bat` / `mysql_start.bat`).
2. Vérifier la configuration dans `config/app.php` ou définir les variables `PGCC_DB_*`.
3. Le code est servi depuis le répertoire du projet (déployé ici par lien symbolique `C:\xampp\htdocs\pgcc2` → dossier projet).
4. Ouvrir `http://localhost/pgcc2/`.

### 13.2 Mise à jour

1. **Sauvegarder la base** (`mysqldump pgcc_utf8mb4 > sauvegarde_YYYYMMDD.sql`).
2. Importer les nouvelles migrations de `database/migrations/` dans phpMyAdmin.
3. Relancer les vérifications (§12.1).

### 13.3 Exploitation courante

- Outils de maintenance : `tools/maintenance/` (comptage MD5, relations, test e-mail).
- Sauvegardes : `database/backups/` contient la **sauvegarde complète de la base en un seul fichier** (`pgcc_utf8mb4_complet_AAAA-MM-JJ.sql`, ~174 Mo : 31 tables, données, déclencheurs et routines, régénérable par `mysqldump`) ; la restaurer via `mysql -u root < fichier.sql`.
- Logs applicatifs : journal Apache (`php:notice [PGCC2]`) ; mode DEBUG pour le détail des exceptions.

---

## 14. Limites actuelles et évolutions envisagées

### 14.1 Limites connues

1. **Coexistence v1/v2** : la v1 écrit encore du MD5 dans la table partagée ; la couche de compatibilité doit rester tant que la v1 est utilisée.
2. **Tests automatisés partiels** : les scripts `tests/` ont été retirés de la copie de travail ; ils restent dans l'historique git et méritent d'être restaurés (`git restore tests tools`).
3. **Dépendance à XAMPP** : pas d'hébergement externe à ce stade ; aucune authentification à deux facteurs.
4. PHP 8.0 est en fin de vie en amont ; une montée de version PHP est à planifier.

### 14.2 Évolutions pressenties

1. Sauvegardes automatiques programmées (mysqldump quotidien + rotation).
2. Notification e-mail étendue (réinitialisation de mot de passe par l'admin, alertes de répartition).
3. Recherche globale multi-registres depuis le tableau de bord.
4. Statistiques avancées (délais de traitement, taux de détention par année).
5. Retrait de la couche MD5 dès que l'outil d'audit annonce zéro empreinte legacy.

---

*Document généré à partir de l'analyse du code source et de la base de production locale. Toute évolution du périmètre doit être répercutée dans ce document et dans `docs/guide-utilisateur.md`.*
