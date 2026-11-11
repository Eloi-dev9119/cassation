<?php
/**
 * Tableau de bord : recherche globale, indicateurs, conclusions à traiter,
 * activité récente et raccourcis vers les modules.
 *
 * Variables : $compteurs, $parAnnee, $recherche, $resultats, $conclusions,
 * $recents (les quatre dernières sont optionnelles pour rester compatibles
 * avec les appels de test).
 */
$utilisateur = Auth::utilisateur();
$prenom = trim((string) ($utilisateur['prenom'] ?? ''));
$recherche = $recherche ?? '';
$resultats = $resultats ?? null;
$conclusions = $conclusions ?? ['registres' => [], 'totalARediger' => 0, 'totalATransmettre' => 0];
$recents = $recents ?? [];
$totalResultats = (int) ($resultats['total'] ?? 0);
?>
<div class="tableau-de-bord">
<!-- Ligne de titre compacte : le mot de bienvenue tient dans le
     sous-titre ; les actions principales descendent dans la barre de
     recherche globale, alignées sur le champ de recherche. -->
<div class="titre-page tableau-titre">
    <div>
        <h1 id="titre-tableau">Tableau de bord</h1>
        <p class="sous-titre"><?= $prenom !== ''
            ? 'Bienvenue, ' . e($prenom) . ' — consultez l’activité des registres et accédez rapidement à vos dossiers.'
            : 'Consultez l’activité des registres et accédez rapidement à vos dossiers.' ?></p>
    </div>
</div>

<section class="carte recherche-globale" aria-labelledby="titre-recherche">
    <div class="recherche-globale__entete">
        <h2 id="titre-recherche">Rechercher un dossier</h2>
        <p class="texte-doux">Tous les registres et les mémoires : n° de dossier, parties, juridiction, arrêt attaqué, requête…</p>
    </div>
    <form class="filtres" method="get" action="<?= e(url('/tableau-de-bord')) ?>" role="search">
        <input type="hidden" name="r" value="/tableau-de-bord">
        <input type="search" id="recherche-globale" name="q" value="<?= e($recherche) ?>"
               placeholder="N° de dossier, parties, juridiction…" minlength="2"
               aria-label="Terme recherché dans tous les registres" autocomplete="off">
        <button class="bouton" type="submit">Rechercher</button>
        <?php if ($recherche !== ''): ?>
            <a class="bouton bouton-secondaire" href="<?= e(url('/tableau-de-bord')) ?>">Réinitialiser</a>
        <?php endif; ?>
        <span class="filtres-separateur" aria-hidden="true"></span>
        <a class="bouton filtres-action" href="<?= e(url('/registres/penaux/creer')) ?>">Créer un dossier pénal</a>
        <a class="bouton bouton-secondaire filtres-action" href="<?= e(url('/memoires/creer')) ?>">Ajouter un mémoire</a>
    </form>
</section>

<?php if ($recherche !== ''): ?>
    <section class="carte recherche-resultats" aria-labelledby="titre-resultats" aria-live="polite">
        <div class="carte-entete">
            <div>
                <p class="surtitre">RECHERCHE GLOBALE</p>
                <h2 id="titre-resultats">
                    <?= $totalResultats > 0
                        ? $totalResultats . ' résultat' . ($totalResultats > 1 ? 's' : '') . ' pour « ' . e($recherche) . ' »'
                        : 'Aucun résultat pour « ' . e($recherche) . ' »' ?>
                </h2>
            </div>
        </div>
        <?php if (mb_strlen($recherche) < RechercheModel::MINIMUM): ?>
            <p class="texte-doux">Saisissez au moins <?= RechercheModel::MINIMUM ?> caractères pour lancer la recherche.</p>
        <?php elseif ($resultats === null || $totalResultats === 0): ?>
            <p class="texte-doux">Aucun dossier ne correspond à ce terme. Essayez un numéro de dossier, un nom de partie ou une juridiction.</p>
        <?php else: ?>
            <div class="resultats-groupes">
                <?php foreach (($resultats['groupes'] ?? []) as $groupe): ?>
                    <article class="resultat-groupe" aria-labelledby="groupe-<?= e((string) $groupe['code']) ?>">
                        <header class="resultat-groupe__entete">
                            <h3 id="groupe-<?= e((string) $groupe['code']) ?>">
                                <?= e((string) $groupe['libelle']) ?>
                                <span class="badge badge-gris"><?= (int) $groupe['total'] ?></span>
                            </h3>
                            <a href="<?= e(url($groupe['route'] . '?q=' . rawurlencode($recherche))) ?>">Voir dans le registre →</a>
                        </header>
                        <ul class="resultat-lignes">
                            <?php foreach (($groupe['lignes'] ?? []) as $ligne): ?>
                                <li class="resultat-ligne">
                                    <div class="resultat-ligne__ligne">
                                        <a class="resultat-ligne__reference" href="<?= e(url((string) $ligne['url'])) ?>"><?= e((string) $ligne['reference']) ?></a>
                                        <?php if ((int) ($ligne['annee'] ?? 0) > 0): ?>
                                            <span class="resultat-ligne__annee"><?= (int) $ligne['annee'] ?></span>
                                        <?php endif; ?>
                                        <?php if (($ligne['conclusion'] ?? null) === 'a_rediger'): ?>
                                            <span class="badge badge-orange">Conclusion à rédiger</span>
                                        <?php elseif (($ligne['conclusion'] ?? null) === 'redigee'): ?>
                                            <span class="badge badge-vert">Conclusion rédigée</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (trim((string) ($ligne['resume'] ?? '')) !== ''): ?>
                                        <p class="resultat-ligne__resume"><?= e((string) $ligne['resume']) ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ((int) $groupe['total'] > count($groupe['lignes'] ?? [])): ?>
                            <p class="resultat-groupe__note">
                                <?= (int) $groupe['total'] - count($groupe['lignes'] ?? []) ?> autre(s) résultat(s) dans ce registre.
                            </p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="cartes-mesures cartes-mesures-principales" aria-label="Indicateurs principaux">
    <a class="mesure mesure-principale" href="<?= e(url('/registres/arrivees')) ?>" aria-labelledby="indicateur-arrivees" title="Voir la liste des dossiers d’arrivée">
        <p class="valeur"><?= number_format((float) ($compteurs['arrivees'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-arrivees">Dossiers d’arrivée</h2>
    </a>
    <a class="mesure mesure-principale" href="<?= e(url('/registres/penaux')) ?>" aria-labelledby="indicateur-penaux" title="Voir la liste des pourvois pénaux">
        <p class="valeur"><?= number_format((float) ($compteurs['penaux'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-penaux">Pourvois pénaux</h2>
    </a>
    <a class="mesure mesure-principale" href="<?= e(url('/registres/civils')) ?>" aria-labelledby="indicateur-civils" title="Voir la liste des pourvois civils">
        <p class="valeur"><?= number_format((float) ($compteurs['civils'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-civils">Pourvois civils</h2>
    </a>
</section>

<section class="cartes-mesures cartes-mesures-secondaires" aria-label="Autres indicateurs">
    <a class="mesure" href="<?= e(url('/registres/dessaisissements')) ?>" aria-labelledby="indicateur-dessaisissements" title="Voir la liste des dessaisissements">
        <p class="valeur"><?= number_format((float) ($compteurs['dessaisissements'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-dessaisissements">Dessaisissements</h2>
    </a>
    <a class="mesure" href="<?= e(url('/registres/pil')) ?>" aria-labelledby="indicateur-pil" title="Voir la liste des dossiers PIL">
        <p class="valeur"><?= number_format((float) ($compteurs['pil'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-pil">Dossiers PIL</h2>
    </a>
    <a class="mesure" href="<?= e(url('/registres/penaux?detention=1')) ?>" aria-labelledby="indicateur-detentions" title="Voir la liste des dossiers en détention">
        <p class="valeur"><?= number_format((float) ($compteurs['detentions'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-detentions">Dossiers en détention</h2>
    </a>
    <a class="mesure" href="<?= e(url('/memoires')) ?>" aria-labelledby="indicateur-memoires" title="Voir la liste des mémoires enregistrés">
        <p class="valeur"><?= number_format((float) ($compteurs['memoires'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-memoires">Mémoires enregistrés</h2>
    </a>
    <a class="mesure" href="<?= e(url('/membres/actifs')) ?>" aria-labelledby="indicateur-membres" title="Voir la liste des membres actifs">
        <p class="valeur"><?= number_format((float) ($compteurs['membresActifs'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-membres">Membres actifs</h2>
    </a>
</section>

<?php if ((int) ($compteurs['enAttente'] ?? 0) > 0): ?>
    <aside class="tableau-alerte" role="status">
        <p><strong><?= (int) $compteurs['enAttente'] ?> demande<?= (int) $compteurs['enAttente'] > 1 ? 's' : '' ?> d’inscription</strong> en attente de validation.</p>
        <a class="bouton bouton-secondaire" href="<?= e(url('/membres/attente')) ?>">Consulter les demandes</a>
    </aside>
<?php endif; ?>

<!-- Navigation rapide déroulante (natif, sans JavaScript) : repliée par
     défaut pour alléger le tableau de bord, un clic sur l'en-tête déplie
     les groupes de modules. -->
<details class="carte dashboard-section modules-section" aria-labelledby="titre-modules">
    <summary>
        <div>
            <p class="surtitre">NAVIGATION RAPIDE</p>
            <h2 id="titre-modules">Accès aux modules</h2>
        </div>
        <p class="texte-doux">Tous les modules sont accessibles en un clic, sans menu à ouvrir.</p>
    </summary>
    <div class="modules-groupes">
        <section class="modules-groupe" aria-labelledby="modules-registres">
            <h3 class="modules-groupe__titre" id="modules-registres">Registres</h3>
            <div class="modules-grille">
                <a class="module-carte" href="<?= e(url('/registres/arrivees')) ?>" title="Réception des dossiers au Parquet">
                    <span class="module-carte__haut">
                        <span class="module-carte__nom">Arrivées</span>
                        <span class="badge badge-gris"><?= number_format((float) ($compteurs['arrivees'] ?? 0), 0, ',', ' ') ?></span>
                    </span>
                    <span class="module-carte__desc">Réception des dossiers au Parquet</span>
                </a>
                <a class="module-carte" href="<?= e(url('/registres/penaux')) ?>" title="Pourvois pénaux et conclusions">
                    <span class="module-carte__haut">
                        <span class="module-carte__nom">Pénaux</span>
                        <span class="badge badge-gris"><?= number_format((float) ($compteurs['penaux'] ?? 0), 0, ',', ' ') ?></span>
                    </span>
                    <span class="module-carte__desc">Pourvois pénaux et conclusions</span>
                </a>
                <a class="module-carte" href="<?= e(url('/registres/civils')) ?>" title="Pourvois civils et conclusions">
                    <span class="module-carte__haut">
                        <span class="module-carte__nom">Civils</span>
                        <span class="badge badge-gris"><?= number_format((float) ($compteurs['civils'] ?? 0), 0, ',', ' ') ?></span>
                    </span>
                    <span class="module-carte__desc">Pourvois civils et conclusions</span>
                </a>
                <a class="module-carte" href="<?= e(url('/registres/dessaisissements')) ?>" title="Demandes de dessaisissement">
                    <span class="module-carte__haut">
                        <span class="module-carte__nom">Dessaisissements</span>
                        <span class="badge badge-gris"><?= number_format((float) ($compteurs['dessaisissements'] ?? 0), 0, ',', ' ') ?></span>
                    </span>
                    <span class="module-carte__desc">Demandes de dessaisissement</span>
                </a>
                <a class="module-carte" href="<?= e(url('/registres/siege')) ?>" title="Cassations civiles au Siège">
                    <span class="module-carte__haut">
                        <span class="module-carte__nom">Siège</span>
                        <span class="badge badge-gris"><?= number_format((float) ($compteurs['siege'] ?? 0), 0, ',', ' ') ?></span>
                    </span>
                    <span class="module-carte__desc">Cassations civiles au Siège</span>
                </a>
                <a class="module-carte" href="<?= e(url('/registres/pil')) ?>" title="Pourvois dans l’intérêt de la loi">
                    <span class="module-carte__haut">
                        <span class="module-carte__nom">PIL</span>
                        <span class="badge badge-gris"><?= number_format((float) ($compteurs['pil'] ?? 0), 0, ',', ' ') ?></span>
                    </span>
                    <span class="module-carte__desc">Pourvois dans l’intérêt de la loi</span>
                </a>
                <a class="module-carte" href="<?= e(url('/memoires')) ?>" title="Mémoires ampliatifs et en défense">
                    <span class="module-carte__haut">
                        <span class="module-carte__nom">Mémoires</span>
                        <span class="badge badge-gris"><?= number_format((float) ($compteurs['memoires'] ?? 0), 0, ',', ' ') ?></span>
                    </span>
                    <span class="module-carte__desc">Mémoires ampliatifs et en défense</span>
                </a>
            </div>
        </section>
        <section class="modules-groupe" aria-labelledby="modules-suivis">
            <h3 class="modules-groupe__titre" id="modules-suivis">Suivis et synthèses</h3>
            <div class="modules-grille">
                <a class="module-carte" href="<?= e(url('/suivis/321')) ?>" title="Suivi des dossiers à l’arrivée">
                    <span class="module-carte__nom">Suivi 321</span>
                    <span class="module-carte__desc">Suivi des dossiers à l’arrivée</span>
                </a>
                <a class="module-carte" href="<?= e(url('/suivis/107')) ?>" title="Suivi des dossiers pénaux">
                    <span class="module-carte__nom">Suivi 107</span>
                    <span class="module-carte__desc">Suivi des dossiers pénaux</span>
                </a>
                <a class="module-carte" href="<?= e(url('/suivis-dossiers')) ?>" title="Conclusions et frappes par Avocat Général">
                    <span class="module-carte__nom">Synthèse des suivis</span>
                    <span class="module-carte__desc">Conclusions et frappes par Avocat Général</span>
                </a>
                <a class="module-carte" href="<?= e(url('/repartition')) ?>" title="Charge mensuelle par Avocat Général">
                    <span class="module-carte__nom">Répartition</span>
                    <span class="module-carte__desc">Charge mensuelle par Avocat Général</span>
                </a>
            </div>
        </section>
        <section class="modules-groupe" aria-labelledby="modules-distributions">
            <h3 class="modules-groupe__titre" id="modules-distributions">Distributions</h3>
            <div class="modules-grille">
                <a class="module-carte" href="<?= e(url('/distributions')) ?>" title="Listes de dossiers confiés aux Avocats Généraux">
                    <span class="module-carte__nom">Distributions</span>
                    <span class="module-carte__desc">Listes de dossiers confiés aux Avocats Généraux</span>
                </a>
            </div>
        </section>
    </div>
</details>

<div class="dashboard-duo">
<section class="carte dashboard-section conclusions-section" aria-labelledby="titre-conclusions">
    <div class="carte-entete">
        <div>
            <p class="surtitre">SUIVI DES CONCLUSIONS</p>
            <h2 id="titre-conclusions">Conclusions à traiter</h2>
        </div>
        <p class="conclusions-total">
            <strong><?= (int) ($conclusions['totalARediger'] ?? 0) ?></strong> à rédiger
            · <strong><?= (int) ($conclusions['totalATransmettre'] ?? 0) ?></strong> à transmettre pour frappe
        </p>
    </div>
    <?php if (($conclusions['registres'] ?? []) === []): ?>
        <p class="texte-doux">Aucun registre de conclusions disponible.</p>
    <?php else: ?>
        <div class="conclusions-grille">
            <?php foreach ($conclusions['registres'] as $registre): ?>
                <a class="conclusion-item" href="<?= e(url((string) $registre['url'])) ?>">
                    <span class="conclusion-item__registre"><?= e((string) $registre['libelle']) ?></span>
                    <span class="conclusion-item__chiffres">
                        <span class="<?= (int) $registre['aRediger'] > 0 ? 'conclusion-fort' : '' ?>"><?= (int) $registre['aRediger'] ?></span> à rédiger
                        · <span class="<?= (int) $registre['aTransmettre'] > 0 ? 'conclusion-fort' : '' ?>"><?= (int) $registre['aTransmettre'] ?></span> à transmettre
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="carte dashboard-section recents-section" aria-labelledby="titre-recents">
    <div class="carte-entete">
        <div>
            <p class="surtitre">ACTIVITÉ RÉCENTE</p>
            <h2 id="titre-recents">Derniers dossiers modifiés</h2>
        </div>
    </div>
    <?php if ($recents === []): ?>
        <p class="texte-doux">Aucune activité récente enregistrée.</p>
    <?php else: ?>
        <div class="tableau-enveloppe">
            <table class="liste tableau-recents">
                <thead>
                    <tr>
                        <th scope="col">Registre</th>
                        <th scope="col">Dossier</th>
                        <th scope="col">Parties / objet</th>
                        <th scope="col">Dernière activité</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recents as $recent): ?>
                        <tr>
                            <td><span class="badge badge-gris"><?= e((string) $recent['registre']) ?></span></td>
                            <td><a href="<?= e(url((string) $recent['url'])) ?>"><?= e((string) $recent['reference']) ?></a></td>
                            <td class="recents-resume"><?= trim((string) $recent['resume']) !== '' ? e((string) $recent['resume']) : '—' ?></td>
                            <td class="recents-date"><?= e(date_fr((string) $recent['activite'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
</div>

<section class="carte dashboard-section" aria-labelledby="titre-activite">
    <div class="carte-entete">
        <div>
            <p class="surtitre">ACTIVITÉ ANNUELLE</p>
            <h2 id="titre-activite">Volume des dossiers par année</h2>
        </div>
    </div>
    <?= \Graphiques::barresParAnnee($parAnnee['annees'], $parAnnee['series']) ?>
</section>
</div>
