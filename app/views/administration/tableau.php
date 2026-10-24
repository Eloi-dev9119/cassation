<?php
/**
 * Tableau de bord de l'espace d'administration.
 * Variables : $compteurs, $dossiers, $journal.
 */
$heure = static function (?string $date): string {
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts === false ? '—' : date('d/m/Y H:i', $ts);
};
?>
<section class="tableau-intro" aria-labelledby="titre-administration">
    <div class="tableau-intro__texte">
        <p class="surtitre">PARQUET GÉNÉRAL · ESPACE ADMINISTRATION</p>
        <h1 id="titre-administration">Administration</h1>
        <p class="sous-titre">Pilotez les comptes utilisateurs, suivez les demandes en attente et consultez le journal d’activité.</p>
    </div>
    <div class="tableau-intro__actions" aria-label="Actions d’administration">
        <a class="bouton" href="<?= e(url('/administration/comptes/creer')) ?>">Créer un compte</a>
        <a class="bouton bouton-secondaire" href="<?= e(url('/administration/comptes')) ?>">Gérer les comptes</a>
    </div>
</section>

<section class="cartes-mesures cartes-mesures-principales" aria-label="Indicateurs des comptes">
    <article class="mesure vert" aria-labelledby="indicateur-actifs">
        <p class="valeur"><?= number_format((float) ($compteurs['actifs'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-actifs">Comptes actifs</h2>
    </article>
    <article class="mesure orange" aria-labelledby="indicateur-attente">
        <p class="valeur"><?= number_format((float) ($compteurs['enAttente'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-attente">Demandes en attente</h2>
    </article>
    <article class="mesure bordeaux" aria-labelledby="indicateur-retraites">
        <p class="valeur"><?= number_format((float) ($compteurs['retraites'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-retraites">Comptes retraités</h2>
    </article>
    <article class="mesure" aria-labelledby="indicateur-total">
        <p class="valeur"><?= number_format((float) ($compteurs['total'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-total">Total des comptes</h2>
    </article>
</section>

<section class="cartes-mesures cartes-mesures-secondaires" aria-label="Indicateurs des dossiers">
    <article class="mesure" aria-labelledby="indicateur-penaux">
        <p class="valeur"><?= number_format((float) ($dossiers['penaux'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-penaux">Pourvois pénaux</h2>
    </article>
    <article class="mesure" aria-labelledby="indicateur-civils">
        <p class="valeur"><?= number_format((float) ($dossiers['civils'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-civils">Pourvois civils</h2>
    </article>
    <article class="mesure" aria-labelledby="indicateur-memoires">
        <p class="valeur"><?= number_format((float) ($dossiers['memoires'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-memoires">Mémoires enregistrés</h2>
    </article>
    <article class="mesure" aria-labelledby="indicateur-detentions">
        <p class="valeur"><?= number_format((float) ($dossiers['detentions'] ?? 0), 0, ',', ' ') ?></p>
        <h2 class="libelle" id="indicateur-detentions">Dossiers en détention</h2>
    </article>
</section>

<?php if ((int) ($compteurs['enAttente'] ?? 0) > 0): ?>
    <aside class="tableau-alerte" role="status">
        <p><strong><?= (int) $compteurs['enAttente'] ?> demande<?= (int) $compteurs['enAttente'] > 1 ? 's' : '' ?></strong>
        d’inscription en attente de validation.</p>
        <a class="bouton bouton-secondaire" href="<?= e(url('/administration/comptes?filtre=attente')) ?>">Traiter les demandes</a>
    </aside>
<?php endif; ?>

<section class="carte" aria-labelledby="titre-journal">
    <div class="carte-entete">
        <div>
            <p class="surtitre">TRAÇABILITÉ</p>
            <h2 id="titre-journal">Dernières activités</h2>
        </div>
    </div>

    <?php if ($journal === []): ?>
        <p class="graphique-vide">Aucune activité d’audit n’est encore enregistrée.</p>
    <?php else: ?>
        <div class="tableau-enveloppe">
            <table class="liste">
                <thead>
                    <tr>
                        <th>Date</th><th>Module</th><th>Référence</th><th>Créé par</th><th>Modifié par</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($journal as $entree): ?>
                        <tr>
                            <td><?= e($heure($entree['derniere_date'] ?? null)) ?></td>
                            <td><?= e($entree['module'] ?? '—') ?></td>
                            <td class="rogne"><?= e($entree['reference'] ?? '—') ?></td>
                            <td><?= e($entree['cree_par'] ?: '—') ?></td>
                            <td><?= e($entree['modifie_par'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:14px">
            <a class="bouton bouton-secondaire" href="<?= e(url('/administration/audit')) ?>">Voir le journal complet</a>
        </p>
    <?php endif; ?>
</section>
