# Comparaison PGCC v1 → PGCC v2 : termes et fonctionnalités

Document établi à partir de l'analyse des sources v1 (`C:\xampp\htdocs\pgcc`, templates Smarty) et de la capture d'écran v1 fournie. Indique, pour chaque module, les termes exacts de la v1 et leur reprise dans la v2.

**Statuts** : ✅ repris à l'identique · 🔁 repris avec un libellé plus précis · ➕ ajout v2 · ⏳ évolution prévue

---

## 1. Registres Pénaux (`registrePenaux.tpl` → `/registres/penaux`)

| # | Terme v1 | V2 — liste | V2 — formulaire/fiche |
|---|---|---|---|
| 1 | N° d'ordre | ✅ N° d'ordre | ✅ |
| 2 | Année | ✅ Année | ✅ |
| 3 | Date d'entrée au parquet | ✅ ajouté à la liste (date_EP) | ✅ |
| 4 | N° Dossier | ✅ N° dossier | ✅ N° dossier PGCS (fiche) |
| 5 | Parties | ✅ Parties | ✅ |
| 6 | Inculpation | ✅ ajouté à la liste | ✅ |
| 7 | N° et date de l'arrêt attaqué | ✅ ajouté (arret_attaque) | ✅ |
| 8 | Juridiction | ✅ ajouté | ✅ |
| 9 | Date du pourvoi | ✅ ajouté | ✅ |
| 10 | B.E Porte 05 | ✅ ajouté — **corrigé le 31/10** : la v1 affiche dans cette colonne le champ `dispositif_arret_CA` (8 102/8 148 dossiers remplis), pas `consignation_amende` (vérifié dans `registrePenaux.tpl` : liste → `reg_penauxArret_CA2`, formulaire → `ttaDispositif2`) ; « Consignation de l'amende » reste un champ séparé du formulaire | ✅ |
| 11 | Conclusion | ✅ ajouté (colonne tronquée) | ✅ éditeur de conclusion dédié + modèle prérempli |

Colonnes supplémentaires v2 (non présentes en v1) : Détention, Mémoire ampliatif/défense déposés, Archivé, Actions (Fiche / Modifier / Archiver).

## 2. Registres Civils (`registreCivil.tpl` → `/registres/civils`)

| # | Terme v1 | V2 |
|---|---|---|
| 1 | N° d'ordre | ✅ N° d'ordre |
| 2 | Année | ✅ Année |
| 3 | B.E | ✅ ajouté à la liste (BE) |
| 4 | N° du dossier | 🔁 N° du dossier (fiche : N° du dossier PGCC) |
| 5 | Parties | 🔁 Parties (fiche : Noms des parties) |
| 6 | N° et date de l'arrêt attaqué | ✅ ajouté (arret_attaque_et_date) |
| 7 | Date de dépôt de la requête | ✅ ajouté |
| 8 | Date du mémoire ampliatif | ✅ date en liste (le booléen passe en formulaire) |
| 9 | Date du mémoire en défense | ✅ date en liste |
| 10 | Conclusion | ✅ ajouté |

## 3. Registres Arrivés (`registreArrive.tpl` → `/registres/arrivees`)

| # | Terme v1 | V2 |
|---|---|---|
| 1 | N°ordre | ✅ N° d'ordre |
| 2 | Juridiction | ✅ (remonté en 2ᵉ position comme la v1) |
| 3 | Bordereaux d'arrivée | ✅ libellé aligné |
| 4 | N° dossier et les personnes concernées | ✅ ajouté à la liste |
| 5 | N° du dossier à la Cour de cassation | ✅ (libellé déjà conforme) |
| 6 | Date de détention | ✅ ajouté |

## 4. Registres Déssaisissements (`registreDes.tpl` → `/registres/dessaisissements`)

| # | Terme v1 | V2 |
|---|---|---|
| 1 | N° d'ordre | ✅ |
| 2 | Date d'entrée au parquet | ✅ ajouté à la liste |
| 3 | Numéro du dossier | ✅ libellé aligné |
| 4 | Parties | ✅ |
| 5 | Inculpation | ✅ ajouté |
| 6 | N° et date de l'arrêt attaqué | ✅ ajouté |
| 7 | Juridiction | ✅ ajouté |
| 8 | Date du pourvoi | ✅ ajouté |
| 9 | Dispositif de l'arrêt attaqué | ✅ ajouté |
| 10 | Conclusion | ✅ ajouté |

## 5. Suivis (`suivi321.tpl` / `suivi107.tpl` → `/suivis/321`, `/suivis/107`)

| Terme v1 (321) | V2 | | Terme v1 (107) | V2 |
|---|---|---|---|---|
| N°ordre | ✅ N° ordre | | N°ordre | ✅ N° ordre |
| Date d'arrivée (+ BE) | ✅ Date d'arrivée (+ B.E) | | Date de transmission | ✅ Date de transmission |
| Dossier N° | ✅ Dossier N° | | Dossier N° | ✅ Dossier N° |
| AG instructeur | ✅ AG instructeur | | AG Instructeur | ✅ AG instructeur |
| Observation de suivi | ✅ Observation de suivi | | Date de réception | ✅ Date de réception |

Colonnes supplémentaires v2 : Année, Détention (+ date), Localisation, Transmission, Actions.

## 6. Distributions (`distribution.tpl` / `distributionCiv.tpl` → `/distributions`)

| Terme v1 | V2 |
|---|---|
| N° Dossier | ✅ N° dossier PGCS |
| Parties | ✅ Parties |
| Magistrat Instructeur | ✅ **Magistrat instructeur** (renommé depuis « Sigle AG ») |
| Nom de la liste | ✅ Liste |
| Année | ✅ Année |

## 7. Suivi des dossiers par AG (`suiviDossier.tpl` → `/suivis-dossiers`)

✅ v2 reprend la même synthèse : compteurs pénaux/civils distribués par Avocat Général, conclusions rédigées, civils en attente de frappe au secrétariat — avec en plus export CSV et impression.

## 8. Répartition (`repartitionDossier.tpl` → `/repartition`)

✅ v2 reprend la répartition mensuelle par AG avec graphique — avec en plus export CSV/impression et filtrage automatique.

---

## Fonctionnalités v1 : état de la reprise dans v2

| Fonctionnalité v1 | V2 | Commentaire |
|---|---|---|
| Sélecteur d'année en haut de registre | ✅ | Filtre « Toutes les années » + filtrage automatique |
| Recherche par colonne (champs sous les en-têtes + bouton Rechercher) | 🔁 couvert | La recherche unique de la v2 couvre les mêmes champs (N° dossier, Parties, Inculpation, Arrêt attaqué, Juridiction, Bordereau…) **et se déclenche automatiquement à la frappe** — plus rapide qu'une recherche colonne par colonne |
| Actions par ligne : voir 🔍, modifier ✏️ | ✅ | Fiche + Modifier (liens) |
| Supprimer 🗑️ | 🔁 remplacé | v2 archive/rétablit (réversible, aucune perte) — plus sûr qu'une suppression définitive |
| **Pièces jointes** (icônes dossier, `uploadFile/`) | ⏳ évolution prévue | Non reprise : nécessite table de fichiers, upload sécurisé et stockage. À implémenter |
| Conclusions (éditeur + 3 gabarits) | ✅ | Éditeur de conclusion + modèle prérempli (en-tête, parties, dates, chambre) et impression |
| Graphiques d'activité (jpgraph) | ✅ | Graphiques natifs HTML/CSS (par année, répartition par AG) — sans dépendance |
| Suivi des dossiers par AG | ✅ | Synthèse + compteurs + **graphe « dossiers pour conclusion » par AG** (ajouté le 01/11, parité avec `grapheSuiviDossier.php`) |
| Répartition mensuelle | ✅ | Avec graphique |
| Membres : validation, retraite, réactivation, mot de passe temporaire `pgcc+année` | ✅ | Identiques + pastille de présence |
| Inscription avec question secrète | ✅ | Identique |
| Comptes partagés v1/v2 (MD5 → bcrypt) | ✅ | Migration transparente à la connexion |
| Registre PIL (`pgcsPil.php`) | ✅ | **Ajouté le 01/11** : exposé dans v2 comme sixième registre déclaratif (`/registres/pil`) sur la table `pgcc_reg_pil` — colonnes d'audit et index d'année ajoutés par `database/migrations/pil_audit.sql` (la grille statique de la v1 ne contenait aucune donnée ni SQL) |
| Siège — Cassation civile (`siegePenaux.tpl`) | 🔁 partiel | v2 gère le suivi au Siège lié au dossier civil (sans toutes les colonnes détaillées du modèle v1 : conseiller rapporteur, dates mémoires côté Siège) |
| Impressions | ✅ | Feuilles de style print + bouton Imprimer sur toutes les listes/fiches |
| Messages / correspondances (`pgcc_message`) | ⏳ évolution prévue | Non reprise |

---

## Corrections appliquées lors de cet alignement (31/10/2026)

1. **Pénaux** : liste conforme v1 (11 colonnes, ordre v1) — libellés exacts.
2. **Civils** : B.E, dates des mémoires et Conclusion en liste ; libellés v1.
3. **Arrivés** : colonnes v1 remises dans l'ordre v1 (Juridiction, Bordereaux, N° dossier et personnes concernées, Date de détention).
4. **Déssaisissements** : Date d'entrée au parquet, Dispositif de l'arrêt attaqué, Conclusion en liste.
5. **Suivis 321/107** : « Date d'arrivée (+ B.E) », « Dossier N° », « AG instructeur », « Observation de suivi », « Date de transmission/réception ».
6. **Distributions** : « Magistrat instructeur ».
7. Recherches élargies : inculpation et arrêt attaqué incluses (pénaux, déssaisissements, civils, arrivées).

## Compléments de parité appliqués le 01/11/2026

Vérification exhaustive des deux applications (toutes les pages v1 × toutes les routes v2 × les 31 tables de `pgcc_utf8mb4`) ; compléments apportés :

1. **Déssaisissements** : le formulaire reprend les sous-formulaires 3 et 4 de la v1 (`registreDes.php`) — Décision AG + date, Transmission pour frappe au secrétariat, Consignation de l'amende, Transmission au siège, Retour après rapport, Enrôlement, Audience, N°/Date arrêt, Résultat d'audience (section « Décision et audience »).
2. **Pénaux** : ajout du champ v1 « Transmission pour frappe (secrétariat) » au formulaire.
3. **Suivis 321/107** : action **Modifier** ajoutée (`/suivis/<type>/modifier?id=`) — la v1 modifie les suivis 321.
4. **Distributions** : action **Modifier** ajoutée (`/distributions?a=modifier&id=`) — année, liste, parties, sigle et dates de remise secrétariat/AG, comme les deux blocs de modification de `distribution.php`.
5. **Registre PIL** : exposé comme sixième registre (`/registres/pil`, définition `'pil'` dans `app/models/Registres.php`) ; migration `database/migrations/pil_audit.sql` (colonnes d'audit + index d'année) ; compteur au tableau de bord et source du journal d'activité.
6. **Graphe « dossiers pour conclusion »** : ajouté à la synthèse `/suivis-dossiers` (SVG `Graphiques::barresDeuxSeries`, parité avec `grapheSuiviDossier.php`).
7. **Membres** : filtre par poste « AG actifs / ECD / Greffe » sur les onglets (parité avec `membreECD.php`).

*Pièces jointes et messagerie interne (`pgcc_correspondance`, `pgcc_message`) : hors parité — aucun écran dans la v1 elle-même (tables héritage sans UI).*

*Vérification de cohérence automatique : aucun doublon de colonne, chaque champ de section a sa définition — OK sur les 5 registres.*
