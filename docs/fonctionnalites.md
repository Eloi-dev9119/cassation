# Fonctionnalités de l'application PGCC v2

**Plateforme de gestion des registres du Parquet Général près la Cour Suprême — Justice Madagascar**

Document de référence des fonctionnalités livrées. Chaque fonctionnalité est vérifiée en exploitation (parcours navigateur du 28–29/10/2026). Complément du [cahier des charges](cahier-des-charges.md) et du [guide utilisateur](guide-utilisateur.md).

---

## 1. Connexion et comptes

| Fonctionnalité | Détail |
|---|---|
| Connexion sécurisée | Identifiant (adresse e-mail) + mot de passe, jeton CSRF, cookie de session durci (HttpOnly, SameSite) |
| Afficher / masquer le mot de passe | Bouton « Afficher / Masquer » sur chaque champ de mot de passe (connexion, inscription, mot de passe oublié, profil, création de compte) pour vérifier sa saisie |
| Mémorisation de l'e-mail | Option « Mémoriser mon adresse e-mail sur cet appareil » (cookie local) |
| Déconnexion confirmée | Boîte de dialogue avant fermeture de session |
| Expiration automatique | Déconnexion après 1 h d'inactivité |
| Demande d'inscription | Formulaire complet (identité, poste, sigle AG, matricule, bureau, question/réponse secrète) ; compte créé **en attente de validation** |
| Mot de passe oublié | Réinitialisation par réponse à la question secrète, sans intervention de l'administrateur |
| Migration transparente des mots de passe | Les anciens mots de passe MD5 (v1) sont convertis en bcrypt à la première connexion réussie |
| E-mail de confirmation | Notification au membre après validation de son inscription (si SMTP configuré) ; l'échec d'envoi ne bloque pas la validation |

## 2. Tableau de bord

| Fonctionnalité | Détail |
|---|---|
| **Recherche globale** | Champ de recherche unique en haut du tableau de bord : le terme est cherché dans les six registres de dossiers et dans les mémoires (n° de dossier, parties, juridiction, arrêt attaqué, requête…). Résultats **groupés par registre**, avec le total réel par groupe, un lien direct vers chaque fiche et un lien « Voir dans le registre ». Saisie d'au moins 2 caractères. |
| **Accès aux modules** | Bloc toujours visible (sans menu à dérouler), groupé en **Registres** (7 entrées avec leur volume), **Suivis et synthèses** (321, 107, synthèse des suivis, répartition) et **Distributions** |
| Compteurs principaux | Dossiers d'arrivée, pourvois pénaux, pourvois civils (volumes temps réel) |
| Compteurs secondaires | Dessaisissements, dossiers PIL, dossiers en détention, mémoires enregistrés, membres actifs |
| **Conclusions à traiter** | Par registre (pénaux, civils, dessaisissements) : nombre de conclusions à rédiger et nombre de conclusions rédigées restant à transmettre pour frappe au secrétariat, avec lien direct vers le registre |
| **Derniers dossiers modifiés** | Tableau des 6 dossiers les plus récemment créés ou modifiés, tous registres confondus : registre, référence, parties/objet, date d'activité et lien vers la fiche |
| Graphique d'activité | Volume des dossiers par année (pénaux / civils / arrivées), défilant, généré en HTML/CSS pur |
| Actions rapides | Boutons « Créer un dossier pénal » et « Ajouter un mémoire » |
| Accueil personnalisé | Salutation au nom de l'utilisateur connecté |

## 3. Registres (6 registres, moteur générique)

Le même moteur sert les **6 registres de dossiers** et le **registre des mémoires**, piloté par des définitions déclaratives (table, colonnes, sections de formulaire, tri) :

| Registre | Contenu |
|---|---|
| **Registres Arrivés** | Dossiers à l'arrivée au Parquet (bordereau, juridiction, dossier à la Cour) |
| **Registres Pénaux** | Pourvois pénaux : parties, inculpation, arrêt attaqué, date et lieu du pourvoi, juridiction, nature de décision, dispositive |
| **Registres Civils** | Pourvois civils : mémoire ampliatif/en défense déposés, arrêt attaqué et date |
| **Registres Déssaisissements** | Demandes de déssaisissement |
| **Registre du Siège** | Suivi des cassations civiles au Siège, **lié au dossier civil d'origine** (lien croisé dans les deux sens) — liste à parité v1 (16 colonnes détaillées : parties et avocats, n° de l'arrêt attaqué, conseiller rapporteur, dates de dépôt/notification des mémoires, transmission au ministère public, n° d'arrêt CS, motif de décision) |
| **Registre PIL** | Tableau de suivi des pourvois dans l'intérêt de la loi (lettre du ministère, magistrat instructeur, requête, audience, arrêt) |
| **Registre des Mémoires** | Mémoires ampliatifs / en défense : type, origine (pénal/civil), parties, avocat, juridiction, notification, dépôt, transmission au secrétaire, rapporteur, état |

**Opérations sur chaque registre :**

- **Onglets de registres** : passer d'un registre à l'autre en un clic depuis la liste.
- **Créer** un dossier (formulaire organisé en sections, champs obligatoires marqués, contrôles de dates).
- **Consulter la fiche** : toutes les informations du dossier + traçabilité (créé/modifié par qui et quand) + actions contextuelles.
- **Modifier** un dossier (traçabilité automatique `cree_par` / `modifie_par`).
- **Archiver / désarchiver** avec confirmation — réversible, aucune perte de données.
- **Préremplissage des mémoires** : créer un mémoire depuis la fiche d'un dossier (n° de dossier PGCS prérempli).
- **Lien Siège ↔ Civil** : créer le « suivi au Siège » depuis une fiche civile et naviguer entre dossier civil et dossier du Siège.
- **Conclusion civile préremplie** : génération d'un modèle de conclusion publique (en-tête COUR SUPRÊME, référence, parties, date en français, chambre) prêt à imprimer depuis la fiche civil.
- **Conclusion déssaisissement préremplie** : modèle « Requête aux fins de renvoi d'une juridiction à une autre » (article 96 de la loi organique n° 2004-036) propre au registre des déssaisissements.

## 4. Recherche, filtres et exports (toutes les listes)

| Fonctionnalité | Détail |
|---|---|
| **Recherche automatique** | Sur toutes les listes : la recherche se lance toute seule après la frappe (~0,5 s) et les menus déroulants filtrent immédiatement au changement — le bouton « Filtrer » reste disponible en manuel (`public/assets/filtres.js`, formulaires `data-auto-filtre`) |
| Recherche texte | Sur les champs pertinents du registre (n° dossier, parties, juridiction…) — testée : « RAMANAMISATA » → 3 résultats |
| Recherche multi-mots (Membres) | Chaque mot saisi doit apparaître, quel que soit l'ordre (« dupont pgcc » fonctionne) ; recherche conservée lors des actions et entre onglets |
| Filtre par année | Sélecteur des années présentes dans le registre — filtrage immédiat au changement |
| Recherche Membres | Nouvelle barre de recherche sur les 3 onglets (nom, prénom, e-mail, poste, sigle) |
| Pagination | 25 lignes par page, navigation entre pages |
| **Vue complète** | Bouton « Voir tout le contenu » / « Vue compacte » sur toutes les listes (registres, mémoires, suivis, distributions) : affiche l'intégralité du contenu des colonnes longues sans troncature — le mode est conservé dans les filtres et la pagination |
| Export CSV | **Reflète exactement les filtres actifs** de la liste |
| Impression | Bouton « Imprimer » (feuilles de style print) et impression navigateur |
| Compteur de résultats | « X dossier(s) » affiché à côté des filtres |

## 5. Suivis des dossiers

| Fonctionnalité | Détail |
|---|---|
| Suivi des arrivées (321) | Localisation du dossier, détention (oui/non + date), sigle de l'AG en charge, observations |
| Suivi des dossiers pénaux (107) | Transmission au PGCS, réception, transmission, détention |
| Bascule entre suivis | Bouton « Voir l'autre suivi » (321 ↔ 107) |
| Création de suivi | Formulaire dédié par type de suivi |
| Archivage des suivis | Réversible avec confirmation |
| **Synthèse des dossiers** (par AG) | Compteurs : dossiers pénaux distribués, civils distribués, conclusions rédigées, civils en attente de frappe au secrétariat ; liste détaillée par Avocat Général |
| **Répartition des dossiers** | Répartition mensuelle par AG : filtre mois/année, graphique en barres par magistrat, rattachement du mois par nom de liste de distribution |

## 6. Distributions

| Fonctionnalité | Détail |
|---|---|
| Listes de distribution pénale et civile | Consultation avec année, liste, dossier, parties, sigle AG, remises (secrétariat / AG) |
| Création d'une liste | Sélection de dossiers filtrés pour composer une nouvelle distribution |
| Exports | CSV et impression de la liste courante |
| Navigation pénal ↔ civil | Lien « Voir les distributions Civiles / Pénaux » |

## 7. Membres

| Fonctionnalité | Détail |
|---|---|
| Trois onglets | En attente de validation / Membres actifs / Retraités |
| **Pastille de présence** | À côté du nom de chaque membre : « En ligne » (vert, activité < 15 min) ou dernier passage connu — l'utilisateur connecté voit aussi sa pastille verte dans la barre d'en-tête |
| Validation des inscriptions | Traiter une demande en attente (réservé aux postes habilités) |
| Retraite / Réactivation | Actions sur les comptes avec confirmation |
| Réinitialisation de mot de passe | Par l'administration |
| Vue restreinte | Un non-administrateur voit la liste sans boutons d'action |

## 8. Administration (réservé PG / SG)

| Fonctionnalité | Détail |
|---|---|
| Tableau de bord admin | Comptes actifs, demandes en attente, retraités, total ; volumes par registre ; bandeau d'alerte sur les demandes en attente |
| Gestion des comptes | Liste filtrable et recherchable ; créer, modifier, mettre à la retraite, réactiver, réinitialiser ; **pastille de présence** à côté de chaque compte |
| Traçabilité des comptes | Dates et auteurs de création/modification affichés dans le formulaire |
| Journal d'activité | Dernières modifications par module, référence, auteur, dates |
| Contrôle d'accès | Menu masqué et accès direct refusé pour les postes non habilités (403) |

## 9. Profil et aide

| Fonctionnalité | Détail |
|---|---|
| Mes informations | Consultation et modification des informations personnelles (identifiants verrouillés) |
| Changement de mot de passe | Avec mot de passe actuel, confirmation, réécriture en bcrypt |
| Page Aide intégrée | Guide condensé : connexion, registres, mémoires, distributions, administration, recherche/CSV/impression, FAQ |

## 10. Fonctionnalités transversales

| Fonctionnalité | Détail |
|---|---|
| Notifications flash | Toast de confirmation auto-dismiss (6,5 s) après chaque action réussie ; bandeau d'erreur persistant en cas d'échec |
| Confirmations systématiques | Archivages, déconnexion, actions sur comptes : boîte de dialogue dédiée (pas de `confirm()` natif) |
| Thème Institutionnel / Neutre chaud | Bascule instantanée, choix mémorisé entre les sessions — design sobre à accent unique (bleu institutionnel / cuivre), statuts discrets |
| Design responsive | Palette monochrome zinc, fonds neutres, adapté écran de bureau et réduit |
| Navigation active | Lien du menu en cours marqué visuellement sur chaque page |
| Sécurité transversale | CSRF sur tous les POST, échappement XSS systématique, requêtes préparées, protection des sources par .htaccess (403) |
| Encodage UTF-8 complet | Base, HTML et traitements — accents corrects partout (correctif double-encodage appliqué) |
| Traçabilité complète | `date_creation` / `cree_par` / `date_modification` / `modifie_par` sur les dossiers, mémoires et comptes |
| Compatibilité v1 | Partage de la table des comptes avec l'ancienne application, synchronisation des données |
| Thème d'impression | Listes, fiches et conclusions imprimables proprement depuis le navigateur |

## 11. Sauvegardes de la base

| Fonctionnalité | Détail |
|---|---|
| Dump horodaté | `tools/sauvegarde-bdd.bat` exporte `pgcc_utf8mb4` avec `mysqldump` (routines et événements, `utf8mb4`) vers `database/backups/pgcc_utf8mb4_AAAAMMJJ_HHMMSS.sql`, puis compresse l'archive en `.zip` (environ 10 fois plus petite) |
| Contrôles avant/après | Vérifie l'accès à la base avant le dump, contrôle que le fichier produit n'est pas vide et le supprime en cas d'échec |
| Rotation | Conserve les 30 sauvegardes les plus récentes, `.sql` ou `.zip` (`KEEP` configurable) |
| Journal | Chaque opération est tracée dans `database/backups/sauvegarde.log`, avec rotation au-delà de 1 Mo |
| Planification Windows | `tools/planifier-sauvegarde.bat` installe une tâche quotidienne (`creer [HH:MM]`), affiche son état (`statut`), la lance (`executer`) ou la retire (`supprimer`) |
| Configuration | Variables `MYSQLBIN`, `DB`, `USERMYSQL`, `MYSQLPWD`, `KEEP` et `COMPRESSER` surchargeables en tête du script |

---

*Application vérifiée complète le 29/10/2026 : 16/16 routes opérationnelles, 33/33 pages testées en HTTP 200, audits statique et visuel sans anomalie. Recherche globale, tableau de bord enrichi et sauvegardes automatiques ajoutés le 09/11/2026.*
