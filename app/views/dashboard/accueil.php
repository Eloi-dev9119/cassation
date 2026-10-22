<?php
/** Tableau de bord : activité des registres et raccourcis vers les modules. */
$utilisateur = Auth::utilisateur();
$prenom = trim((string) ($utilisateur['prenom'] ?? ''));
?>
<section class="tableau-intro" aria-labelledby="titre-tableau">
    <div class="tableau-intro__texte">
        <p class="surtitre">PARQUET GÉNÉRAL · VUE D’ENSEMBLE</p>
        <h1 id="titre-tableau"><?= $prenom !== '' ? 'Bienvenue, ' . e($prenom) : 'Bienvenue dans votre espace' ?></h1>
        <p class="sous-titre">Consultez l’activité des registres et accédez rapidement à vos dossiers.</p>
    </div>
    <div class="tableau-intro__actions" aria-label="Actions principales">
        <a class="bouton" href="<?= e(url('/registres/penaux/creer')) ?>">Créer un dossier pénal</a>
        <a class="bouton bouton-secondaire" href="<?= e(url('/memoires/creer')) ?>">Ajouter un mémoire</a>
    </div>
</section>

<section class="cartes-mesures cartes-mesures-principales" aria-label="Indicateurs principaux">
    <article class="mesure mesure-principale" aria-labelledby="indicateur-arrivees">
        <p class="valeur"><?= number_format((float) ($compteurs['arrivees'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-arrivees">Dossiers d’arrivée</h2>
    </article>
    <article class="mesure mesure-principale" aria-labelledby="indicateur-penaux">
        <p class="valeur"><?= number_format((float) ($compteurs['penaux'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-penaux">Pourvois pénaux</h2>
    </article>
    <article class="mesure mesure-principale" aria-labelledby="indicateur-civils">
        <p class="valeur"><?= number_format((float) ($compteurs['civils'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-civils">Pourvois civils</h2>
    </article>
</section>

<section class="cartes-mesures cartes-mesures-secondaires" aria-label="Autres indicateurs">
    <article class="mesure" aria-labelledby="indicateur-dessaisissements">
        <p class="valeur"><?= number_format((float) ($compteurs['dessaisissements'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-dessaisissements">Dessaisissements</h2>
    </article>
    <article class="mesure" aria-labelledby="indicateur-detentions">
        <p class="valeur"><?= number_format((float) ($compteurs['detentions'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-detentions">Dossiers en détention</h2>
    </article>
    <article class="mesure" aria-labelledby="indicateur-memoires">
        <p class="valeur"><?= number_format((float) ($compteurs['memoires'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-memoires">Mémoires enregistrés</h2>
    </article>
    <article class="mesure" aria-labelledby="indicateur-membres">
        <p class="valeur"><?= number_format((float) ($compteurs['membresActifs'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-membres">Membres actifs</h2>
    </article>
</section>

<?php if ((int) ($compteurs['enAttente'] ?? 0) > 0): ?>
    <aside class="tableau-alerte" role="status">
        <p><strong><?= (int) $compteurs['enAttente'] ?> demande<?= (int) $compteurs['enAttente'] > 1 ? 's' : '' ?> d’inscription</strong> en attente de validation.</p>
        <a class="bouton bouton-secondaire" href="<?= e(url('/membres/attente')) ?>">Consulter les demandes</a>
    </aside>
<?php endif; ?>

<section class="carte dashboard-section" aria-labelledby="titre-activite">
    <div class="carte-entete">
        <div>
            <p class="surtitre">ACTIVITÉ ANNUELLE</p>
            <h2 id="titre-activite">Volume des dossiers par année</h2>
        </div>
    </div>
    <?= \Graphiques::barresParAnnee($parAnnee['annees'], $parAnnee['series']) ?>
</section>

<section class="carte dashboard-section acces-modules" aria-labelledby="titre-acces">
    <div class="carte-entete">
        <div>
            <p class="surtitre">NAVIGATION</p>
            <h2 id="titre-acces">Accès aux modules</h2>
        </div>
    </div>
    <details class="acces-deroulant">
        <summary>Afficher les registres, suivis et distributions</summary>
        <nav class="actions-rapides" aria-label="Accès aux modules" id="liste-acces-rapides">
            <a class="bouton bouton-secondaire" href="<?= e(url('/registres/arrivees')) ?>">Registre des arrivées</a>
            <a class="bouton bouton-secondaire" href="<?= e(url('/registres/penaux')) ?>">Registre pénal</a>
            <a class="bouton bouton-secondaire" href="<?= e(url('/registres/civils')) ?>">Registre civil</a>
            <a class="bouton bouton-secondaire" href="<?= e(url('/registres/dessaisissements')) ?>">Dessaisissements</a>
            <a class="bouton bouton-secondaire" href="<?= e(url('/memoires')) ?>">Registre des mémoires</a>
            <a class="bouton bouton-secondaire" href="<?= e(url('/suivis/321')) ?>">Suivi 321</a>
            <a class="bouton bouton-secondaire" href="<?= e(url('/suivis/107')) ?>">Suivi 107</a>
            <a class="bouton bouton-secondaire" href="<?= e(url('/distributions')) ?>">Distributions</a>
        </nav>
    </details>
</section>