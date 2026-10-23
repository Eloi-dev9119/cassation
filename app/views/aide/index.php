<?php /* Aide intégrée : condensé de docs/guide-utilisateur.md. */ ?>
<div class="titre-page">
    <h1>Aide de l'application</h1>
    <div class="actions"><a class="bouton bouton-secondaire" href="<?= e(url('/tableau-de-bord')) ?>">← Retour au tableau de bord</a></div>
</div>

<div class="alerte alerte-info">
    Ce guide condensé couvre les gestes courants. Le guide complet (détail de chaque
    champ, conventions de nommage, questions fréquentes) est maintenu dans le dépôt :
    <strong>docs/guide-utilisateur.md</strong>.
</div>

<div class="carte">
    <h2 id="sommaire">Sommaire</h2>
    <ul class="aide-sommaire">
        <li><a href="#connexion">Connexion, mot de passe et comptes</a></li>
        <li><a href="#registres">Registres et dossiers</a></li>
        <li><a href="#memoires">Mémoires</a></li>
        <li><a href="#distributions">Distributions et répartition</a></li>
        <li><a href="#membres">Membres (administration)</a></li>
        <li><a href="#profil">Mes informations et mot de passe</a></li>
        <li><a href="#listes">Recherche, export CSV et impression</a></li>
        <li><a href="#faq">Questions fréquentes</a></li>
    </ul>
</div>

<div class="carte">
    <h2 id="connexion">Connexion, mot de passe et comptes</h2>
    <ul>
        <li>Le mot de passe est <strong>commun aux applications PGCC v1 et v2</strong> ; le changer ici le change partout. À la première connexion réussie, un ancien mot de passe est converti automatiquement au format sécurisé actuel.</li>
        <li><strong>Mot de passe oublié</strong> : depuis la page de connexion, saisir votre e-mail puis répondre à votre question secrète. Sans question secrète définie, contactez l'administration.</li>
        <li><strong>Nouveau compte</strong> : « Créer un compte » enregistre une demande ; elle devient active après validation par l'administration (PG ou SG).</li>
        <li>La session se ferme automatiquement après <strong>une heure d'inactivité</strong>.</li>
        <li>Chaque action sensible (suppression, archivage, déconnexion) demande une <strong>confirmation</strong>.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="registres">Registres et dossiers</h2>
    <p>Les cinq registres (Arrivés, Pénaux, Civils, Déssaisissements, Siège) partagent les mêmes gestes :</p>
    <div class="tableau-enveloppe">
        <table class="liste">
            <thead><tr><th>Geste</th><th>Comment</th></tr></thead>
            <tbody>
                <tr><td>Créer un dossier</td><td class="rogne">« Nouveau dossier » — champs <strong>*</strong> obligatoires ; numéro d'ordre et année identifient la ligne du registre.</td></tr>
                <tr><td>Consulter</td><td class="rogne">« Fiche » — toutes les informations, mémoires rattachés et liaisons (siège ↔ dossier civil).</td></tr>
                <tr><td>Modifier</td><td class="rogne">« Modifier » — formulaire pré-rempli ; la date d'insertion d'origine est conservée.</td></tr>
                <tr><td>Archiver</td><td class="rogne">« Archiver » range le dossier traité sans le supprimer — l'opération est réversible (« Désarchiver »).</td></tr>
                <tr><td>Conclure</td><td class="rogne">Pénaux, Civils et Déssaisissements : « Conclusion » pour rédiger le texte de conclusion.</td></tr>
            </tbody>
        </table>
    </div>
    <p><strong>Siège (Cassation civile)</strong> : chaque dossier doit être relié à un dossier civil existant ; depuis la fiche d'un dossier civil, « Ouvrir un dossier au siège » présélectionne la liaison.</p>
    <p>Chaque ligne mémorise <strong>qui l'a créée et qui l'a modifiée en dernier</strong> (visible en fiche et en modification).</p>
</div>

<div class="carte">
    <h2 id="memoires">Mémoires</h2>
    <ul>
        <li>Type : <strong>ampliatif</strong> (demandeur) ou <strong>en défense</strong> (défendeur) ; origine : dossier <strong>pénal</strong> ou <strong>civil</strong>.</li>
        <li>Le <strong>numéro de dossier PGCS</strong> est obligatoire. Plusieurs dossiers homonymes ? L'application demande de choisir la ligne précise ; un seul correspond ? La liaison est automatique.</li>
        <li>Numéro d'ordre <strong>attribué automatiquement</strong> si laissé vide à la création.</li>
        <li>États : Déposé → Transmis au rapporteur → Rapport établi → Classé (changement depuis la liste, avec confirmation).</li>
        <li>Recommandé : créer le mémoire depuis la fiche du dossier (« Ajouter un mémoire ») — le pré-remplissage garantit la liaison.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="distributions">Distributions et répartition</h2>
    <ul>
        <li>« Nouvelle liste » propose <strong>uniquement les dossiers non encore distribués</strong> du type choisi ; un même dossier ne peut pas être distribué deux fois.</li>
        <li><strong>Convention de nommage</strong> : inclure le mois dans le nom de la liste (ex. « Liste Octobre 2026 ») — la Répartition rattache les listes aux mois par leur nom.</li>
        <li><strong>Répartition</strong> : comptage mensuel des distributions par Avocat Général (pénaux / civils) avec graphique.</li>
        <li><strong>Suivis Dossiers</strong> : synthèse par AG — conclusions rédigées ou non, audience, arrêt, civils en attente de frappe.</li>
        <li><strong>Suivis 321 / 107</strong> : suivis d'arrivées et de dossiers pénaux transmis au PGCS ; numéro d'ordre automatique, archivage réversible.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="membres">Membres (administration)</h2>
    <ul>
        <li>Les listes sont consultables par tous ; <strong>les actions sont réservées aux postes PG et SG</strong>.</li>
        <li><strong>Valider</strong> une inscription : le compte devient actif ; si un compte du même e-mail existait déjà, il est réactivé et le mot de passe choisi à l'inscription est appliqué.</li>
        <li><strong>Refuser</strong> une inscription en attente : la demande est supprimée définitivement de <code>pgcc_new_client</code> et l'e-mail pourra faire une nouvelle demande.</li>
        <li><strong>Mettre à la retraite</strong> / <strong>Réactiver</strong> : coupe ou restaure l'accès, sans perdre les données.</li>
        <li><strong>Réinitialiser un mot de passe</strong> : le mot de passe temporaire affiché est « pgcc » suivi de l'année (ex. pgcc2026) — le transmettre au membre par un canal sûr ; il le changera dans Mes Informations.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="profil">Mes informations et mot de passe</h2>
    <ul>
        <li>Accessible depuis le menu <strong>Mes informations</strong>.</li>
        <li>Modifier son <strong>nom</strong>, <strong>prénom</strong> et <strong>numéro de bureau</strong>.</li>
        <li>Changer son mot de passe : saisir le mot de passe actuel, le nouveau (4 caractères minimum) et sa confirmation. Le nouveau mot de passe est immédiatement valable <strong>dans les deux applications PGCC</strong>.</li>
        <li>Un ancien mot de passe MD5 est automatiquement converti en format sécurisé à la première connexion réussie.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="listes">Recherche, export CSV et impression</h2>
    <ul>
        <li>Chaque liste propose une <strong>recherche plein texte</strong>, un filtre par année (registres) et une pagination de 25 lignes ; les filtres restent actifs d'une page à l'autre.</li>
        <li><strong>« Exporter CSV »</strong> télécharge un fichier Excel (séparateur « ; », accents préservés) qui respecte les filtres en cours — videz la recherche pour tout exporter. Registres, mémoires, suivis, distributions, répartition et synthèses par AG sont exportables.</li>
        <li><strong>Impression</strong> : Ctrl+P depuis le navigateur — menus et boutons sont automatiquement masqués.</li>
    </ul>
</div>

<div class="carte">
    <h2 id="faq">Questions fréquentes</h2>
    <ul>
        <li><strong>Je ne peux pas me connecter.</strong> Vérifiez l'e-mail et le mot de passe. Causes possibles : compte non encore validé, membre à la retraite, session expirée. Au bout d'un échec persistant, contactez le PG ou le SG.</li>
        <li><strong>Mon mot de passe v1 fonctionne-t-il ici ?</strong> Oui. Les deux applications partagent les comptes. À la première connexion réussie, le mot de passe est automatiquement converti au format sécurisé actuel.</li>
        <li><strong>Un dossier apparaît dans deux registres ?</strong> Les registres sont indépendants. Un mémoire peut être relié à un dossier pénal et le siège est relié aux dossiers civils ; les fiches affichent ces liaisons.</li>
        <li><strong>J'ai supprimé un mémoire par erreur.</strong> La suppression ne touche que le registre des mémoires. Il suffit de recréer le mémoire ; en cas de doublon de numéro, choisissez le dossier source précis.</li>
        <li><strong>Comment imprimer un registre complet ?</strong> Filtrez si besoin, videz la recherche pour tout inclure, exportez en CSV ou imprimez page par page depuis le navigateur.</li>
        <li><strong>Qui contacter pour un accès ?</strong> L'administration du Parquet (postes PG ou SG) via le module Membres.</li>
    </ul>
    <p><a href="#sommaire">↑ Retour au sommaire</a></p>
</div>
