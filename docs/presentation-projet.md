---
marp: true
theme: default
paginate: true
size: 16:9
header: 'PGCC v2 — Parquet Général près la Cour Suprême'
footer: 'Justice Madagascar — MINJUS'
---

<!--
Support de présentation du projet PGCC v2.
Rendu recommandé : Marp (VS Code / CLI) ou import du Markdown dans Google Slides / PowerPoint.
Chaque `---` sépare une diapositive. Les notes de présentateur sont entre commentaires HTML.
-->

# PGCC v2

## Plateforme de gestion des registres du Parquet Général

**Parquet Général près la Cour Suprême — Justice Madagascar**

*Refonte moderne du progiciel historique PGCC v1*

---

<!-- _paginate: false -->

## Résumé exécutif

| | |
|---|---|
| **Projet** | PGCC v2 — gestion des registres judiciaires et suivi des dossiers |
| **Maître d'ouvrage** | Parquet Général près la Cour Suprême (MINJUS) |
| **Nature** | Refonte complète de l'application historique PGCC v1 |
| **Technologie** | PHP 8 + MySQL/MariaDB — sans framework, sans dépendance externe |
| **Utilisateurs** | Procureur Général, Secrétaire Général, Avocats Généraux, secrétariats |
| **Statut** | Application en production locale, en évolution continue |

> Un outil métier fiable, sécurisé et maintenable, aligné sur la pratique réelle du Parquet.

---

## 1. Contexte

Le Parquet Général près la Cour Suprême de Madagascar tient plusieurs **registres judiciaires** :

- registres des **arrivées**, des **pourvois pénaux** et **civils** ;
- registre des **désaisissements**, registre du **Siège** (cassation civile) ;
- registre **PIL** (pourvoi dans l'intérêt de la loi) ;
- registre des **mémoires** et **listes de distribution** aux Avocats Généraux.

Ces registres étaient tenus sur **papier** et sur une application historique (**PGCC v1**) devenue difficile à maintenir.

---

## 2. Problématique — les limites de l'existant (v1)

- **Interface vieillissante** : scripts PHP procéduraux mêlant SQL et présentation.
- **Encodage hétérogène** (ancienne base latin1) : accents corrompus dans certaines listes.
- **Droits non gérés** : toute personne connectée pouvait tout faire.
- **Aucune traçabilité** des créations et modifications.
- **Fonctionnalités absentes** : mémoires, synthèses par magistrat, répartition mensuelle, journal d'activité, exports filtrés.

> La fiabilité et la sécurité des données du Parquet étaient en jeu.

---

## 3. Objectifs du projet

**Objectifs fonctionnels**

1. Informatiser et **fiabiliser** la tenue des registres.
2. **Suivre chaque dossier** de bout en bout : arrivée, pourvoi, détention, mémoire, distribution, conclusion.
3. **Distribuer** les dossiers aux Avocats Généraux et produire la **répartition mensuelle**.
4. **Gérer les comptes** : inscription, validation, retraite, réinitialisation.
5. **Tracer toute modification** (qui, quoi, quand) et fournir un journal d'activité.

**Objectifs techniques**

- Architecture **MVC claire**, sans requête SQL dans les vues.
- Base **unique, cohérente et encodée UTF-8**.
- **Sans dépendance externe** (déployable en réseau restreint).
- **Sécurité** conforme aux bonnes pratiques.

---

## 4. La solution — PGCC v2 en bref

- **Refonte complète** en architecture MVC lisible.
- Base migrée en **UTF-8 (`pgcc_utf8mb4`, InnoDB)** — accents corrects partout.
- **Partage des comptes utilisateurs avec la v1** pendant la transition, avec synchronisation des données.
- **Gestion fine des droits** et **traçabilité intégrale** des écritures.
- **Ergonomie moderne** : recherche automatique, notifications, confirmations, thèmes, exports.

> Une continuité de service assurée : les deux applications coexistent sans rupture pour les agents.

---

## 5. Périmètre fonctionnel — 17 modules

| # | Module | # | Module |
|---|---|---|---|
| 1 | Authentification / inscription | 10 | Suivi dossiers pénaux (107) |
| 2 | Tableau de bord | 11 | Synthèse par Avocat Général |
| 3 | Registre des Arrivés | 12 | Répartition mensuelle |
| 4 | Registre Pénaux | 13 | Distributions |
| 5 | Registre Civils | 14 | Membres |
| 6 | Registre Désaisissements | 15 | Administration |
| 7 | Registre du Siège | 16 | Profil |
| 8 | Registre des Mémoires | 17 | Aide |
| 9 | Suivi des arrivées (321) | | |

Chaque liste offre : **recherche**, **filtre par année**, **pagination**, **export CSV/PDF**, **impression** et actions par ligne.

---

## 6. Focus — les 6 registres, un moteur unique

Les registres sont décrits de façon **déclarative** (`app/models/Registres.php`) et partagent un **moteur générique** :

| Registre | Contenu |
|---|---|
| **Arrivées** | Réception des dossiers (bordereaux, juridiction, détention) |
| **Pénaux** | Pourvois pénaux — le registre le plus utilisé |
| **Civils** | Pourvois civils |
| **Désaisissements** | Demandes de désaisissement et suites |
| **Siège** | Cassation civile, **reliée au dossier civil d'origine** |
| **PIL** | Pourvoi dans l'intérêt de la loi |
| **Mémoires** | Mémoires ampliatifs / en défense, rattachés aux dossiers |

**Bénéfice :** ajouter un registre = ajouter une définition + une table, **sans toucher aux contrôleurs**.

---

## 7. Focus — tableau de bord & recherche globale

- **Recherche globale** : un seul terme interrogé dans les **6 registres et les mémoires**, résultats groupés par registre avec lien direct vers chaque fiche.
- **Compteurs temps réel** : dossiers par registre, détentions, membres actifs, demandes en attente.
- **Conclusions à traiter** : à rédiger / à transmettre pour frappe, par registre.
- **Derniers dossiers modifiés** (tous registres confondus).
- **Graphique d'activité** par année — généré en HTML/CSS pur, sans dépendance.

---

## 8. Focus — distributions & synthèses

- **Listes de distribution** attribuant chaque dossier à un Avocat Général.
- Création d'une liste à partir des **dossiers non encore distribués** (garde-fous anti-doublon).
- **Répartition mensuelle** par magistrat, avec graphique en barres.
- **Synthèse par Avocat Général** : dossiers distribués, conclusions rédigées, dossiers en attente de frappe.
- **Exports CSV et impression** tenant compte des filtres actifs.

---

## 9. Focus — administration & habilitations

- Espace **Administration** réservé aux postes **PG (Procureur Général)** et **SG (Secrétaire Général)**.
- **Gestion des comptes** : création, modification, retraite/réactivation, réinitialisation de mot de passe.
- **Validation des inscriptions** : demandes en attente → compte actif + e-mail de confirmation.
- **Journal d'activité** issu des colonnes d'audit (200 dernières opérations).
- **Pastille de présence** : « En ligne » si activité < 15 minutes.
- Toute tentative d'accès direct par un poste non habilité est **refusée (403)**.

---

## 10. Architecture technique

```text
Navigateur
   │  HTTP  (index.php?r=/route)
   ▼
index.php  — front controller + routeur (match)
   ├── app/core/         Auth, Database (PDO), View, Password,
   │                     Mailer, CsvExporter, PdfExporter, Graphiques
   ├── app/controllers/  un contrôleur par module (13)
   ├── app/models/       requêtes SQL + définitions déclaratives (14)
   └── app/views/        vues PHP + gabarits + partiels (33)
   ▼
MySQL / MariaDB — base pgcc_utf8mb4 (InnoDB, utf8mb4_unicode_ci)
```

- **~10 260 lignes** de PHP applicatif (hors archive v1).
- Autoloader par couche, helpers centralisés, conventions françaises cohérentes.
- Protection des sources par `.htaccess` : seuls `index.php` et `public/assets/` sont servis.

---

## 11. Modèle de données & volumétrie

**29 à 31 tables** réparties en groupes : comptes, registres, historiques (audit), suivis, distributions, référentiels.

| Registre / table | Lignes |
|---|---:|
| Registre des arrivées | 11 906 |
| Pourvois pénaux | 6 405 |
| Pourvois civils | 4 434 |
| Distributions au Parquet Général | 10 488 |
| Historiques consolidés | 35 000+ |
| Comptes utilisateurs | 75 |

- **Clés étrangères** posées par migration (mémoires ↔ dossiers, Siège ↔ civil, historiques).
- Évolutions de schéma **versionnées** dans `database/migrations/` (13 fichiers).

---

## 12. Sécurité — menaces & contre-mesures

| Menace | Contre-mesure implémentée |
|---|---|
| **XSS** | Échappement systématique des sorties (`htmlspecialchars`, ENT_QUOTES, UTF-8) |
| **Injection SQL** | Requêtes préparées PDO exclusivement ; identifiants SQL validés |
| **CSRF** | Jeton de session 64 octets vérifié sur chaque POST (`hash_equals`) |
| **Fuite de sources** | `.htaccess` : HTTP 403 sur `app/`, `config/`, `database/`, `tools/`… |
| **Détournement de session** | Cookie HttpOnly/SameSite, `session_regenerate_id`, expiration 1 h |
| **Mots de passe** | **bcrypt** ; compatibilité MD5 v1 migrée automatiquement à la connexion |
| **Brute force** | Blocage après 5 échecs pendant 15 min, messages génériques |
| **Accès non autorisé** | Contrôle de poste + redirection sur toutes les pages protégées |

*Écarts volontaires documentés (revue SonarQube) : compatibilité MD5, interpolation contrôlée d'identifiants SQL, archive v1 figée.*

---

## 13. Qualité & tests

| Niveau | Contenu | Fichier |
|---|---|---|
| **Chargement** | Config, helpers, classes, constantes — sans base | `tests/test_chargement.php` |
| **Rendus** | ~30 vues rendues avec données factices, sans base | `tests/test_rendus.php` |
| **Compatibilité** | Vérification MD5/bcrypt | `tests/test_password_compat.php` |
| **E2E HTTP** | Parcours complets sur base jetable (`pgcc_e2e_*`) | `tests/test_e2e_xampp.sh` |
| **Recette navigateur** | Parcours réel des 17 modules | `gui-test-screenshots/` (21 captures) |

**Dernière exécution : tous les tests passent** — chargement, rendus et compatibilité des mots de passe.

---

## 14. Exploitation & sauvegardes

- **Sauvegarde automatisée** de la base : dump `mysqldump` horodaté, **compression zip**, **rotation des 30 dernières**, journal d'exploitation.
- **Planification Windows** : tâche quotidienne installable via `tools/planifier-sauvegarde.bat`.
- **Outils de maintenance** : comptage des empreintes MD5, gestion des relations, test e-mail SMTP.
- **Migrations** appliquées après sauvegarde, puis vérifications relancées.
- **Journalisation** des exceptions (error_log + page 500 dédiée, détail en mode DEBUG uniquement).

---

## 15. Réalisations récentes (novembre 2026)

- **Robustesse des écritures** : élimination des erreurs HTTP 500 liées aux contraintes `UNIQUE` (inscription, comptes, registres, suivis) par **validation en amont** et filet `try/catch`.
- **Unicité déclarative** : les contraintes sont décrites dans `Registres.php`, pas codées en dur.
- **Alignement des distributions** sur la contrainte réelle (`num_dossier_PGCS`).
- **Validation des inscriptions** : collision de matricule gérée proprement.
- **Nettoyage** des données de test et **documentation** à jour.

> Un effort continu de fiabilisation et d'alignement sur les règles métier réelles.

---

## 16. Chiffres clés

| Indicateur | Valeur |
|---|---:|
| Modules fonctionnels | **17** |
| Registres gérés | **6** + mémoires |
| Tables de base | **29–31** |
| Lignes de PHP applicatif | **~10 260** |
| Fichiers PHP | **79** |
| Contrôleurs / modèles / vues | **13 / 14 / 33** |
| Dossiers en base | **33 000+** |
| Utilisateurs | **75** |
| Migrations versionnées | **13** |
| Captures de recette | **21** |

---

## 17. Feuille de route

1. **Sauvegardes** automatiques programmées — *livré*.
2. **Notification e-mail** étendue (réinitialisation, alertes de répartition).
3. **Recherche globale** multi-registres — *livré*.
4. **Statistiques avancées** : délais de traitement, taux de détention par année.
5. **Retrait de la couche MD5** dès que l'outil d'audit annonce zéro empreinte legacy.
6. **Montée de version PHP** (8.0 en fin de vie) et hébergement hors poste local.

---

## 18. Conclusion

- PGCC v2 **modernise** la tenue des registres du Parquet Général : fiabilité, sécurité, traçabilité.
- **Architecture claire** et **déclarative**, facile à maintenir et à étendre.
- **Continuité de service** assurée avec la v1 pendant la transition.
- **Qualité vérifiée** par tests automatisés et recette navigateur complète.

> Un socle logiciel durable au service de la Justice.

---

<!-- _paginate: false -->

# Merci

**PGCC v2 — Parquet Général près la Cour Suprême**

*Documentation du projet : `README.md`, `docs/cahier-des-charges.md`,
`docs/fonctionnalites.md`, `docs/guide-utilisateur.md`.*
