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
| Conclusions (éditeur + 3 gabarits) | ✅ | Éditeur de conclusion + modèle prérempli **propre à chaque registre** — `ConclusionPenale` (pourvoi pénal), `ConclusionCivile` (pourvoi civil), `ConclusionDessaisissement` (requête aux fins de renvoi d'une juridiction à une autre) — et impression |
| Graphiques d'activité (jpgraph) | ✅ | Graphiques natifs HTML/CSS (par année, répartition par AG) — sans dépendance |
| Suivi des dossiers par AG | ✅ | Synthèse + compteurs + **graphe « dossiers pour conclusion » par AG** (ajouté le 01/11, parité avec `grapheSuiviDossier.php`) |
| Répartition mensuelle | ✅ | Avec graphique |
| Membres : validation, retraite, réactivation, mot de passe temporaire `pgcc+année` | ✅ | Identiques + pastille de présence |
| Inscription avec question secrète | ✅ | Identique |
| Comptes partagés v1/v2 (MD5 → bcrypt) | ✅ | Migration transparente à la connexion |
| Registre PIL (`pgcsPil.php`) | ✅ | **Ajouté le 01/11** : exposé dans v2 comme sixième registre déclaratif (`/registres/pil`) sur la table `pgcc_reg_pil` — colonnes d'audit et index d'année ajoutés par `database/migrations/pil_audit.sql` (la grille statique de la v1 ne contenait aucune donnée ni SQL) |
| Siège — Cassation civile (`siegePenaux.tpl`) | ✅ | v2 reprend l'intégralité du modèle v1 : liste des 16 colonnes détaillées (parties et avocats, n° de l'arrêt attaqué, conseiller rapporteur, dates de dépôt/notification des mémoires, transmission au ministère public, n° d'arrêt CS, motif de décision) **et** liaison au dossier civil d'origine (lien croisé dans les deux sens) |
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

## Complément de parité — registre du Siège (12/11/2026)

La table `siege_cassation_civ` et le formulaire v2 étaient déjà complets (schéma identique à la v1, 18 champs v1 exposés) ; le seul écart réel restait la **liste**, qui n'affichait que 6 colonnes contre 16 dans `siegePenaux.tpl`. Le registre du Siège est désormais à parité :

1. **Liste du Siège** : portée à 19 colonnes — les 16 colonnes détaillées de la v1 (N° dossier, Nom et domicile du demandeur, Avocat du demandeur, Nom et domicile du défendeur, Avocat du défendeur, N° de l'arrêt (cour d'appel), Nom conseiller rapporteur, Date de dépôt au rapporteur, Date de notification du mémoire de pourvoi, Dates de dépôt/notification du mémoire ampliatif, Date de dépôt du mémoire en défense, Date de dépôt du rapport, Date de transmission au ministère public, Date de dépôt de la conclusion au ministère public, N° d'arrêt CS, Motif de décision), plus l'Année et l'indicateur d'Archivé propres à v2, et la colonne d'actions.
2. **Libellés alignés** sur ceux du modèle papier v1.
3. **Recherche élargie** au `numero_arret_cours_appel`.
4. **Tests** : `tests/test_rendus.php` couvre le rendu de la liste du Siège et vérifie la présence des 14 libellés détaillés v1 ; la boucle des formulaires inclut désormais `siege` et `pil`.

*Aucune donnée existante modifiée : le registre du Siège reste vide en base (`siege_cassation_civ` = 0 ligne).*

## Correction — conclusion du registre des déssaisissements (13/11/2026)

La conclusion d'un dossier de déssaisissement préremplissait à tort le **modèle pénal** (`ConclusionPenale`) : en-tête « CHAMBRE DES AFFAIRES PÉNALES », « … prévenu, s'est pourvu en cassation », « tribunal correctionnel », « rejet du pourvoi ». Or un déssaisissement est une **« requête aux fins de renvoi d'une juridiction à une autre »** (article 96 de la loi organique n° 2004-036).

1. **Nouveau modèle `ConclusionDessaisissement`** (`app/core/ConclusionDessaisissement.php`) reproduisant le format officiel des conclusions du registre : titre « REQUETE AUX FINS DE RENVOI D'UNE JURIDICTION A UNE AUTRE », visa de la lettre du Garde des Sceaux, visa de l'affaire (parties + infraction), article 96, demande de déssaisissement, signature.
2. **Aiguillage par registre** dans `RegistreController` (méthode `modeleConclusion`) : `civils → ConclusionCivile`, `dessaisissements → ConclusionDessaisissement`, `penaux → ConclusionPenale` (éditeur, réinsertion du modèle, export PDF et annexe).
3. **Tests** : `tests/test_rendus.php` vérifie que la conclusion des déssaisissements produit la requête aux fins de renvoi et **ne contient plus** les mentions pénales ; `tests/test_chargement.php` charge et contrôle les trois classes de conclusion.
