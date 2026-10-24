# Guide utilisateur — PGCC v2

Application de gestion des registres et du suivi des dossiers du
Parquet Général près la Cour Suprême. Ce guide s'adresse aux membres du
Parquet : secrétaires, Avocats Généraux, Secrétaire Général et Procureur
Général.

Une version condensée de ce guide est disponible dans l'application,
page **Aide** (menu principal).

## Sommaire

1. [Premiers pas](#1-premiers-pas)
2. [Tableau de bord](#2-tableau-de-bord)
3. [Registres (arrivées, pénaux, civils, dessaisissements, siège)](#3-registres)
4. [Registre des Mémoires](#4-registre-des-mémoires)
5. [Suivis 321 et 107](#5-suivis-321-et-107)
6. [Distributions aux Avocats Généraux](#6-distributions-aux-avocats-généraux)
7. [Répartition des dossiers](#7-répartition-des-dossiers)
8. [Suivis Dossiers par Avocat Général](#8-suivis-dossiers-par-avocat-général)
9. [Espace d'administration](#9-espace-dadministration)
10. [Mes informations et mot de passe](#10-mes-informations-et-mot-de-passe)
11. [Recherche, filtres, exports et impression](#11-recherche-filtres-exports-et-impression)
12. [Questions fréquentes](#12-questions-fréquentes)

---

## 1. Premiers pas

### Se connecter

1. Ouvrir l'adresse de l'application (en local : <http://localhost/pgcc2/>).
2. Saisir votre **e-mail** et votre **mot de passe**, puis valider.
3. La case « Mémoriser mon adresse » pré-remplira votre e-mail lors de
   vos prochaines visites sur ce poste (elle ne mémorise jamais le mot
   de passe).

Votre mot de passe est commun aux applications **PGCC v1 et v2** : le
changer ici le change partout.

Bon à savoir :

- Après **une heure d'inactivité**, la session se ferme automatiquement ;
  il suffit de se reconnecter.
- Les actions sensibles (suppression, archivage, déconnexion…)
  demandent toujours une **confirmation** avant de s'exécuter.

### Mot de passe oublié

1. Sur la page de connexion, cliquer **« Mot de passe oublié ? »**.
2. Saisir votre e-mail : votre **question secrète** s'affiche.
3. Répondre à la question et choisir un nouveau mot de passe.

Si aucune question secrète n'est définie pour votre compte, contactez
l'administration (voir §9) : elle peut réinitialiser votre mot de passe.

### Demander un compte (inscription)

1. Sur la page de connexion, cliquer **« Créer un compte »**.
2. Renseigner nom, prénom, e-mail, matricule (IM), poste et bureau.
3. La demande est enregistrée **en attente de validation** : un membre
   de l'administration (PG ou SG) la valide depuis Administration >
   Comptes utilisateurs (ou depuis Membres).
   Vous recevrez votre accès une fois la demande acceptée.

## 2. Tableau de bord

La page d'accueil après connexion affiche :

- des **compteurs** : dossiers par registre (arrivées, pénaux, civils,
  dessaisissements, mémoires), nombre de détenus, membres actifs,
  demandes d'inscription en attente ;
- un **graphique** du volume de dossiers par année sur les huit
  dernières années (chaque barre indique son total au survol).

C'est la page la plus rapide pour vérifier l'activité générale du
Parquet avant d'entrer dans un registre.

## 3. Registres

Le menu **Registres** regroupe les cinq registres :

| Registre | Contenu |
|---|---|
| **Arrivés** | Réception des dossiers pénaux au Parquet (bordereaux, juridiction, détention) |
| **Pénaux** | Pourvois pénaux : parties, mémoires, audience, arrêt, conclusion |
| **Civils** | Pourvois civils : bordereau d'envoi, mémoires, distribution, arrêt, conclusion |
| **Déssaisissements** | Dossiers déssaisis transmis au siège |
| **Siège (Cassation civile)** | Suivi des dossiers civils côté siège, reliés à un dossier civil du registre |

Chaque registre fonctionne de la même façon :

- **Liste** : dossiers triés par année puis numéro d'ordre, avec
  recherche, filtre par année et pagination (25 lignes par page).
- **Nouveau dossier** : le formulaire est découpé en sections ; les
  champs marqués `*` sont obligatoires. Le **numéro d'ordre** et
  l'**année** identifient la ligne du registre ; la **date
  d'insertion** marque l'entrée au registre.
- **Fiche** : toutes les informations du dossier, les mémoires rattachés
  (pénaux et civils) et, pour le siège, le dossier civil lié.
- **Modifier** : reprend le formulaire pré-rempli.
- **Archiver / Désarchiver** : range un dossier traité sans le
  supprimer ; l'opération est réversible.
- **Conclusion** (pénaux, civils, dessaisissements) : rédaction ou
  modification du texte de conclusion du dossier.

Cas particulier du **registre du Siège** : chaque dossier doit être
relié à un **dossier civil existant** du registre Civils (liste
déroulante). En venant depuis la fiche d'un dossier civil (« Ouvrir un
dossier au siège »), la liaison est déjà présélectionnée.

Traçabilité : chaque enregistrement note **qui a créé** la ligne et
**qui l'a modifiée** en dernier, avec les dates (visibles sur la fiche
et le formulaire de modification).

## 4. Registre des Mémoires

Le registre centralise les mémoires déposés dans les pourvois.

- **Type** : *mémoire ampliatif* (demandeur) ou *mémoire en défense*
  (défendeur).
- **Origine** : dossier *pénal* ou *civil*.
- **Numéro de dossier PGCS** : obligatoire. Si plusieurs dossiers
  portent le même numéro, l'application demande de **choisir le dossier
  précis** dans la liste ; si un seul correspond, la liaison est
  automatique.
- **N° d'ordre** : laissé vide en création, il est **attribué
  automatiquement** (numérotation continue par année). En modification,
  il devient obligatoire.
- **États** : *Déposé* → *Transmis au rapporteur* → *Rapport établi* →
  *Classé*. Le changement d'état se fait depuis la liste (menu de chaque
  ligne) et demande confirmation.

Depuis la **fiche d'un dossier** pénal ou civil, le lien « Ajouter un
mémoire » ouvre le formulaire pré-rempli avec le numéro et l'origine du
dossier : c'est la façon recommandée de créer un mémoire, la liaison
étant garantie.

La suppression d'un mémoire est possible mais demandée avec
confirmation ; les dossiers du registre lié ne sont jamais touchés.

## 5. Suivis 321 et 107

Deux registres de suivi hérités de la pratique du Parquet :

- **Suivi des arrivées (321)** : suivi des dossiers depuis leur arrivée
  jusqu'à leur traitement (date d'arrivée, détention, AG suivi,
  observations).
- **Suivi des dossiers pénaux (107)** : dossiers pénaux transmis au
  PGCS (dates de transmission et de réception, AG, localisation).

Dans les deux cas : liste avec recherche, création (le **numéro
d'ordre** est attribué automatiquement si laissé vide), et
**archivage/désarchivage** réversible par ligne.

## 6. Distributions aux Avocats Généraux

Les distributions attribuent des dossiers de pourvoi à un Avocat
Général, par **listes**.

### Consulter

Menu **Distributions** → *Distributions Pénaux* ou *Distributions
Civiles*. La liste affiche année, nom de liste, dossier, parties, sigle
de l'AG et dates de remise ; elle est consultable par tout membre.

### Créer une nouvelle liste

1. Cliquer **« Nouvelle liste »**.
2. L'application propose **uniquement les dossiers non encore
   distribués** du type choisi (pénaux ou civils), avec recherche.
3. Renseigner le **nom de la liste** — convention importante : inclure
   **le mois** dans le nom (ex. « Liste Octobre 2026 »), car la
   Répartition (§7) rattache les listes aux mois par leur nom.
4. Renseigner l'année, le **sigle de l'Avocat Général**, éventuellement
   la date de remise au secrétariat, puis cocher les dossiers.
5. Valider : une ligne par dossier est créée.

Garde-fous : un dossier déjà distribué ne peut pas l'être deux fois —
si la page est restée ouverte pendant qu'un collègue distribuait, seuls
les dossiers encore disponibles sont attribués et le message indique le
nombre réellement attribué.

## 7. Répartition des dossiers

Page de comptage **mensuel** des distributions par Avocat Général,
séparant pénaux et civils, avec graphique comparatif.

- Choisir le **mois** et l'**année** : le comptage retient les listes
  dont le **nom contient le mois choisi** — d'où l'importance de la
  convention de nommage du §6.
- L'année proposée par défaut est la dernière année comportant des
  distributions.

## 8. Suivis Dossiers par Avocat Général

Page de synthèse de l'activité d'un AG précis (le premier actif est
affiché par défaut) :

- nombres de dossiers **pénaux** et **civils** distribués ;
- liste des dossiers pénaux avec état de la **conclusion** (rédigée ou
  non), date d'audience et numéro d'arrêt ;
- liste des dossiers **civils en attente de frappe** au secrétariat
  (date de transmission non renseignée).

La synthèse est exportable en CSV (§11).

## 9. Espace d'administration

Le menu **Administration** n'apparaît que pour les postes **PG**
(Procureur Général) et **SG** (Secrétaire Général). Il donne accès à un
tableau de bord, à la gestion des comptes et au journal d'activité.
Toute tentative d'accès direct à ces pages par un autre poste affiche
une page introuvable.

### 9.1 Tableau de bord

Le tableau de bord présente le nombre de comptes actifs, de demandes en
attente, de retraités et de comptes au total, ainsi que les volumes de
dossiers (pourvois pénaux et civils, mémoires, dossiers en détention).
Il affiche également les dernières activités enregistrées et un accès
direct aux demandes d'inscription à traiter.

### 9.2 Comptes utilisateurs

La page **Comptes utilisateurs** liste les comptes avec recherche par
nom, prénom, e-mail ou sigle, et quatre filtres : *Tous*, *Actifs*,
*Retraités*, *En attente*.

- **Créer un compte** : renseigner nom, prénom, sexe, e-mail
  (identifiant unique), poste, sigle, matricule, bureau, statut et mot
  de passe initial. Le compte est immédiatement actif ; aucun passage
  par la validation n'est nécessaire.
- **Modifier** : corriger l'identité, le poste, le sigle, le matricule,
  le bureau ou le statut. Un e-mail déjà utilisé par un autre compte est
  refusé.
- **Retraite** / **Réactiver** : coupe ou restaure l'accès sans
  supprimer les données du membre.
- **Réinit. mdp** : remplace le mot de passe par « `pgcc` » suivi de
  l'année courante (ex. `pgcc2026`). Transmettez-le par un canal sûr ;
  le membre le changera dans **Mes Informations**.

Le mot de passe n'est jamais affiché en clair et n'est pas modifiable
depuis le formulaire d'édition : seule la réinitialisation est prévue.

### 9.3 Journal d'activité

Le journal reprend les 200 créations et modifications les plus récentes
enregistrées par les colonnes d'audit des tables métier
(`date_creation`, `date_modification`, `cree_par`, `modifie_par`). Il
est consultable à l'écran et imprimable.

### 9.4 Validation des inscriptions

Trois onglets restent disponibles depuis **Membres** :

- **En attente de validation** : demandes d'inscription (table
  `pgcc_new_client`) et anciens comptes jamais validés.
  - *Valider* : le compte devient actif et peut se connecter. Si un
    compte actif existait déjà avec le même e-mail, il est réactivé et
    **le mot de passe choisi à l'inscription est appliqué**.
- **Membres actifs** :
  - *Mettre à la retraite* : le membre ne peut plus se connecter ; ses
    données sont conservées.
  - *Réinitialiser le mot de passe* : attribue le mot de passe
    temporaire affiché à l'écran — « `pgcc` » suivi de l'année courante
    (ex. `pgcc2026`). **Transmettez-le au membre par un canal sûr** ;
    il le changera depuis sa page Mes Informations.
- **Retraités** : *Réactiver* remet le membre en activité.

## 10. Mes informations et mot de passe

Page **Mes Informations** :

- modifier son **nom**, **prénom** et **numéro de bureau** ;
- changer son **mot de passe** : saisir le mot de passe actuel, le
  nouveau (4 caractères minimum) et sa confirmation. Le nouveau mot de
  passe est immédiatement valable **dans les deux applications PGCC**.

## 11. Recherche, filtres, exports et impression

### Recherche et filtres

Toutes les listes proposent :

- une **recherche plein texte** sur les colonnes principales (numéro de
  dossier, parties, juridiction…) ;
- pour les registres, un **filtre par année** ;
- une **pagination** (25 lignes par page).

Les filtres actifs restent appliqués pendant la navigation entre les
pages de résultats.

### Export CSV

Le bouton **« Exporter CSV »** télécharge un fichier ouvrable dans
Excel (séparateur `;`, accents préservés). L'export respecte **les
filtres et la recherche en cours** — pour exporter tout, videz d'abord
la recherche. Sont exportables : les registres, les mémoires, les
suivis, les distributions, la répartition et les synthèses par AG.

### Impression

Toutes les listes, fiches et rapports s'impriment depuis le navigateur
(**Ctrl+P**) : la feuille de style masque menus et boutons pour
n'imprimer que le contenu utile.

## 12. Questions fréquentes

**Je ne peux pas me connecter.**
Vérifiez l'e-mail et le mot de passe. Trois autres causes possibles :
compte pas encore validé par l'administration, membre à la retraite, ou
session expirée (reconnectez-vous). Au bout d'un échec persistant,
contactez le PG ou le SG.

**Mon mot de passe v1 fonctionne-t-il ici ?**
Oui. Les deux applications partagent les comptes. À votre première
connexion réussie ici, votre mot de passe est automatiquement
converti au format sécurisé actuel.

**Un dossier apparaît dans deux registres ?**
Les registres sont indépendants ; en revanche un mémoire (registre des
mémoires) peut être relié à un dossier pénal **et** le siège est relié
aux dossiers civils — les fiches affichent ces liaisons.

**J'ai supprimé un mémoire par erreur.**
La suppression ne touche que la ligne du registre des mémoires. Il
suffit de recréer le mémoire ; en cas de doublon de numéro de dossier,
choisissez le dossier source précis.

**Comment imprimer un registre complet ?**
Filtrez si besoin, videz la recherche pour tout inclure, exportez en
CSV ou imprimez page par page depuis le navigateur.

**Qui contacter pour un accès ?**
L'administration du Parquet (postes PG ou SG) via le menu
Administration puis Comptes utilisateurs.

---

*Document maintenu dans le dépôt : `docs/guide-utilisateur.md`. Toute
évolution de l'interface doit être répercutée ici.*
