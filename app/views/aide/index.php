<?php /* Aide intégrée : version condensée de docs/guide-utilisateur.md. */ ?>
<div class="titre-page">
    <h1>Aide de l'application</h1>
    <div class="actions"><a class="bouton bouton-secondaire" href="<?= e(url('/tableau-de-bord')) ?>">← Retour au tableau de bord</a></div>
</div>

<div class="alerte alerte-info">
    Ce guide présente les gestes courants, dans l'ordre où vous les utilisez au quotidien.
    Le guide complet, qui détaille chaque champ, reste disponible dans le dépôt :
    <strong>docs/guide-utilisateur.md</strong>.
</div>

<div class="carte">
    <h2 id="sommaire">Sommaire</h2>
    <ul class="aide-sommaire">
        <li><a href="#tableau">Tableau de bord</a></li>
        <li><a href="#connexion">Connexion et comptes</a></li>
        <li><a href="#registres">Registres et dossiers</a></li>
        <li><a href="#memoires">Mémoires</a></li>
        <li><a href="#suivis">Suivis, synthèses et distributions</a></li>
        <li><a href="#administration">Administration et sauvegardes</a></li>
        <li><a href="#profil">Mes informations</a></li>
        <li><a href="#recherche">Recherche, exports et impression</a></li>
        <li><a href="#faq">Questions fréquentes</a></li>
    </ul>
</div>

<div class="carte">
    <h2 id="tableau">Tableau de bord</h2>
    <p>Le tableau de bord rassemble la recherche, les chiffres clés et tous les modules de l'application.</p>
    <ul>
        <li><strong>Recherche globale</strong> : saisissez au moins 2 caractères dans le champ situé en haut de la page (numéro de dossier, nom de partie, juridiction, arrêt attaqué, requête…). La recherche porte sur les <strong>six registres</strong> et sur les <strong>mémoires</strong>. Les résultats sont regroupés par registre, avec le nombre total de correspondances ; chaque ligne ouvre directement la fiche du dossier et un lien permet d'ouvrir la liste complète du registre sur ce terme.</li>
        <li><strong>Accès aux modules</strong> : les boutons sont toujours visibles, sans menu à dérouler. Ils sont classés en trois groupes :
            <ul>
                <li><strong>Registres</strong> : Arrivées, Pénaux, Civils, Dessaisissements, Siège, PIL et Mémoires, avec le volume de dossiers de chacun ;</li>
                <li><strong>Suivis et synthèses</strong> : Suivi 321, Suivi 107, Synthèse des suivis et Répartition ;</li>
                <li><strong>Distributions</strong> : listes de dossiers confiés aux Avocats Généraux.</li>
            </ul>
        </li>
        <li><strong>Compteurs principaux</strong> : dossiers d'arrivée, pourvois pénaux et pourvois civils. Les compteurs secondaires indiquent les dessaisissements, les dossiers PIL, les dossiers en détention, les mémoires enregistrés et les membres actifs.</li>
        <li><strong>Conclusions à traiter</strong> : pour les registres Pénaux, Civils et Dessaisissements, deux chiffres sont affichés : les conclusions encore <strong>à rédiger</strong> et celles qui sont rédigées mais restent <strong>à transmettre pour frappe</strong> au secrétariat. Chaque bloc ouvre le registre correspondant.</li>
        <li><strong>Derniers dossiers modifiés</strong> : les six dossiers les plus récemment créés ou modifiés, tous registres confondus, avec un lien direct vers leur fiche.</li>
        <li><strong>Graphique annuel</strong> : volume de dossiers par année pour les arrivées, les pourvois pénaux et les pourvois civils.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="connexion">Connexion et comptes</h2>
    <ul>
        <li>La connexion se fait avec votre <strong>adresse e-mail</strong> et votre mot de passe. Le mot de passe est <strong>commun aux applications PGCC v1 et v2</strong> : le modifier ici le modifie partout. Un ancien mot de passe est converti automatiquement au format sécurisé lors de la première connexion réussie.</li>
        <li><strong>Mot de passe oublié</strong> : depuis la page de connexion, saisissez votre e-mail puis répondez à votre question secrète. Si aucune question secrète n'est définie, contactez l'administration (PG ou SG).</li>
        <li><strong>Demande de compte</strong> : le formulaire d'inscription enregistre une demande. Le compte devient actif après validation par l'administration.</li>
        <li>La session se ferme automatiquement après <strong>une heure d'inactivité</strong>.</li>
        <li>Les actions sensibles (archivage, suppression, déconnexion) demandent une <strong>confirmation</strong> avant d'être exécutées.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="registres">Registres et dossiers</h2>
    <p>L'application gère <strong>six registres de dossiers</strong>, présentés dans le tableau ci-dessous.</p>
    <div class="tableau-enveloppe">
        <table class="liste">
            <thead><tr><th>Registre</th><th>Contenu</th></tr></thead>
            <tbody>
                <tr><td><strong>Arrivées</strong></td><td class="rogne">Dossiers reçus au Parquet : bordereau d'arrivée, juridiction, numéro de dossier et personnes concernées.</td></tr>
                <tr><td><strong>Pénaux</strong></td><td class="rogne">Pourvois pénaux : parties, inculpation, arrêt attaqué, juridiction, audience, arrêt et conclusion.</td></tr>
                <tr><td><strong>Civils</strong></td><td class="rogne">Pourvois civils : parties, arrêt attaqué, mémoire, distribution, audience et conclusion.</td></tr>
                <tr><td><strong>Dessaisissements</strong></td><td class="rogne">Demandes de dessaisissement et suites données.</td></tr>
                <tr><td><strong>Siège</strong></td><td class="rogne">Cassations civiles suivies au Siège, reliées à un dossier civil du registre Civils.</td></tr>
                <tr><td><strong>PIL</strong></td><td class="rogne">Pourvois dans l'intérêt de la loi : lettre du ministère, magistrat instructeur, requête, audience et arrêt.</td></tr>
            </tbody>
        </table>
    </div>
    <p>Tous les registres fonctionnent de la même manière :</p>
    <div class="tableau-enveloppe">
        <table class="liste">
            <thead><tr><th>Geste</th><th>Comment faire</th></tr></thead>
            <tbody>
                <tr><td>Créer un dossier</td><td class="rogne">Bouton « + Nouveau dossier ». Les champs marqués <strong>*</strong> sont obligatoires ; le numéro d'ordre et l'année identifient la ligne du registre.</td></tr>
                <tr><td>Consulter</td><td class="rogne">Bouton « Fiche » : toutes les informations du dossier, les mémoires rattachés, les pièces jointes et les liaisons éventuelles.</td></tr>
                <tr><td>Modifier</td><td class="rogne">Bouton « Modifier » : le formulaire s'ouvre pré-rempli et la date d'insertion d'origine est conservée.</td></tr>
                <tr><td>Archiver</td><td class="rogne">Bouton « Archiver » : le dossier traité est retiré de la liste active sans être supprimé. L'opération est réversible avec « Désarchiver ».</td></tr>
                <tr><td>Rédiger la conclusion</td><td class="rogne">Registres Pénaux, Civils et Dessaisissements : bouton « Conclusion » pour rédiger ou corriger le texte, puis le transmettre pour frappe au secrétariat.</td></tr>
            </tbody>
        </table>
    </div>
    <p><strong>Cas du registre du Siège</strong> : chaque dossier doit être relié à un dossier civil existant. En créant le suivi depuis la fiche d'un dossier civil, la liaison est déjà présélectionnée.</p>
    <p>Chaque enregistrement conserve <strong>qui l'a créé</strong> et <strong>qui l'a modifié en dernier</strong>, avec les dates correspondantes (visibles sur la fiche et dans le formulaire de modification).</p>
</div>

<div class="carte">
    <h2 id="memoires">Mémoires</h2>
    <ul>
        <li>Un mémoire est soit <strong>ampliatif</strong> (déposé par le demandeur), soit <strong>en défense</strong> (déposé par le défendeur). Son origine indique le registre concerné : <strong>pénal</strong> ou <strong>civil</strong>.</li>
        <li>Le <strong>numéro de dossier PGCS</strong> est obligatoire. Si plusieurs dossiers portent le même numéro, l'application demande de choisir la ligne précise ; si un seul dossier correspond, la liaison est automatique.</li>
        <li>Le numéro d'ordre est <strong>attribué automatiquement</strong> lorsqu'il est laissé vide à la création.</li>
        <li>Le mémoire passe par les états suivants : <strong>Déposé → Transmis au rapporteur → Rapport établi → Classé</strong>. L'état se change depuis la liste, avec confirmation si nécessaire.</li>
        <li>Pour garantir la liaison, il est recommandé de créer le mémoire depuis la fiche du dossier concerné, avec le bouton « Enregistrer un mémoire ».</li>
    </ul>
</div>

<div class="carte">
    <h2 id="suivis">Suivis, synthèses et distributions</h2>
    <ul>
        <li><strong>Suivi 321</strong> : suivis d'arrivée des dossiers transmis au PGCS. <strong>Suivi 107</strong> : suivis des dossiers pénaux. Les deux listes partagent la même présentation : recherche, ajout, modification, archivage réversible et numéro d'ordre automatique.</li>
        <li><strong>Synthèse des suivis</strong> (page « Suivis Dossiers ») : pour chaque Avocat Général, nombre de dossiers pénaux et civils distribués, conclusions rédigées, civils en attente de frappe et graphique de charge. Utilisez le sélecteur pour changer d'Avocat Général.</li>
        <li><strong>Répartition</strong> : comptage mensuel des distributions par Avocat Général, avec graphique et export.</li>
        <li><strong>Distributions</strong> : « Nouvelle liste » ne propose que les dossiers du type choisi qui n'ont pas encore été distribués ; un dossier ne peut pas être distribué deux fois. Pour que la Répartition rattache une liste au bon mois, incluez le mois dans son nom (par exemple « Liste Octobre 2026 »).</li>
    </ul>
</div>

<div class="carte">
    <h2 id="administration">Administration et sauvegardes</h2>
    <ul>
        <li>Le menu <strong>Administration</strong> n'apparaît que pour les postes <strong>PG</strong> et <strong>SG</strong>. Il donne accès au tableau de bord administratif, aux comptes utilisateurs et au journal d'activité.</li>
        <li><strong>Créer un compte</strong> : le compte est immédiatement actif ; l'adresse e-mail doit être unique. <strong>Modifier un compte</strong> permet de changer l'identité, le poste, le sigle, le matricule, le bureau et le statut.</li>
        <li><strong>Journal d'activité</strong> : les 200 dernières créations et modifications enregistrées dans les tables métier ; il peut être consulté et imprimé.</li>
        <li><strong>Demandes d'inscription</strong> : « Valider » active le compte ; « Refuser » supprime définitivement la demande et permet à la personne de recommencer. Si un compte portant le même e-mail existait déjà, il est réactivé.</li>
        <li><strong>Retraite et réactivation</strong> : coupent ou rétablissent l'accès sans perdre les données.</li>
        <li><strong>Réinitialiser un mot de passe</strong> : un mot de passe temporaire est affiché (« pgcc » suivi de l'année, par exemple pgcc2026). Transmettez-le par un canal sûr ; le membre le changera dans « Mes informations ».</li>
        <li><strong>Sauvegardes automatiques</strong> : chaque jour, la tâche planifiée exécute <code>tools\sauvegarde-bdd.bat</code>. Le script crée un dump horodaté dans <code>database\backups\</code>, le compresse en <code>.zip</code>, conserve les 30 sauvegardes les plus récentes et écrit son journal dans <code>sauvegarde.log</code>. Pour installer, contrôler ou retirer la tâche, lancez <code>tools\planifier-sauvegarde.bat</code> avec l'argument <code>creer 18:00</code>, <code>statut</code>, <code>executer</code> ou <code>supprimer</code>.</li>
        <li>Les listes de membres restent consultables par tous ; <strong>les actions d'administration sont réservées aux postes PG et SG</strong>.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="profil">Mes informations</h2>
    <ul>
        <li>La page <strong>Mes informations</strong> permet de modifier votre <strong>nom</strong>, votre <strong>prénom</strong> et votre <strong>numéro de bureau</strong>.</li>
        <li>Pour changer de mot de passe : saisissez le mot de passe actuel, le nouveau (4 caractères minimum) puis sa confirmation. Le nouveau mot de passe est immédiatement valable <strong>dans les deux applications PGCC</strong>.</li>
        <li>Un ancien mot de passe MD5 est converti automatiquement en format sécurisé à la première connexion réussie.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="recherche">Recherche, exports et impression</h2>
    <ul>
        <li>Chaque liste dispose d'une <strong>recherche plein texte</strong>, d'un filtre par année (registres) et d'une pagination de 25 lignes. Les filtres restent actifs d'une page à l'autre.</li>
        <li><strong>Télécharger CSV</strong> produit un fichier compatible Excel (séparateur « ; », accents préservés) qui respecte les filtres en cours. Pour tout exporter, videz d'abord la recherche. Les registres, mémoires, suivis, distributions, répartitions et synthèses par Avocat Général sont exportables.</li>
        <li><strong>Télécharger PDF</strong> est proposé sur les registres et les mémoires ; les autres pages peuvent être imprimées depuis le navigateur. Le PDF est un document de consultation : les textes longs y sont abrégés et l'export est limité aux <strong>1 000 premières lignes</strong> (la mention figure en bas du document). Pour une extraction exhaustive, utilisez le CSV.</li>
        <li><strong>Imprimer</strong> : le bouton « Imprimer » ou <kbd>Ctrl</kbd>+<kbd>P</kbd>. Les menus, la recherche et les boutons sont automatiquement masqués à l'impression.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="faq">Questions fréquentes</h2>
    <ul>
        <li><strong>Je ne peux pas me connecter.</strong> Vérifiez l'adresse e-mail et le mot de passe. Causes possibles : compte non encore validé, membre à la retraite, session expirée. Si le problème persiste, contactez le PG ou le SG.</li>
        <li><strong>Mon mot de passe PGCC v1 fonctionne-t-il ici ?</strong> Oui. Les deux applications partagent les comptes et le mot de passe. À la première connexion réussie, il est converti au format sécurisé actuel.</li>
        <li><strong>Un dossier apparaît dans deux registres, est-ce normal ?</strong> Oui. Les registres sont indépendants. Un mémoire peut être relié à un dossier pénal, et le registre du Siège est relié aux dossiers civils. Les fiches affichent ces liaisons.</li>
        <li><strong>J'ai supprimé un mémoire par erreur.</strong> La suppression ne touche que le registre des mémoires. Recréez le mémoire ; en cas de doublon de numéro, choisissez le dossier source précis.</li>
        <li><strong>Comment imprimer un registre complet ?</strong> Videz la recherche pour inclure tous les dossiers, puis utilisez « Télécharger CSV », « Télécharger PDF » ou l'impression du navigateur.</li>
        <li><strong>Qui contacter pour un accès ?</strong> L'administration du Parquet (postes PG ou SG), via le menu Administration puis Comptes utilisateurs.</li>
    </ul>
    <p><a href="#sommaire">↑ Retour au sommaire</a></p>
</div>
