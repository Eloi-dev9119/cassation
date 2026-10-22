<?php
/**
 * Définitions des registres du PGCC.
 *
 * Chaque registre est décrit de façon déclarative : table, clé primaire,
 * colonnes (avec type et usage). Le contrôleur et le modèle génériques
 * construisent automatiquement les listes, recherches, formulaires et
 * fiches détaillées à partir de ces définitions — aucune duplication.
 *
 * Types de colonnes reconnus :
 *   'texte', 'nombre', 'date', 'booleen', 'textelong', 'choix' (options)
 */

declare(strict_types=1);

function registres_definitions(): array
{
    return [

        'arrivees' => [
            'libelle'      => 'Registres Arrivés',
            'libelleCourt' => 'Arrivées',
            'table'        => 'pgcc_reg_arrivee_pen',
            'cle'          => 'id_reg_arrivee',
            'ordre'        => 'annee DESC, num_ordre DESC',
            'recherche'    => ['num_dossier_PGCS', 'juridiction', 'bordereaux_arrivee'],
            'conclusion'   => null,
            'sections' => [
                'Identification et réception' => ['num_ordre', 'num_dossier_PGCS', 'annee', 'date_insertion', 'juridiction', 'bordereaux_arrivee', 'num_dossier_et_pers_concernee'],
                'Détention et suivi' => ['detention', 'date_detention', 'localisation', 'date_transmission', 'observation'],
            ],
            'colonnes'     => [
                ['nom' => 'num_ordre',        'libelle' => 'N° d’ordre du registre',      'type' => 'nombre',   'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'annee',             'libelle' => 'Année',         'type' => 'nombre',   'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_insertion',    'libelle' => 'Date insertion','type' => 'date',     'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'juridiction',       'libelle' => 'Juridiction',   'type' => 'texte',    'liste' => true, 'formulaire' => true],
                ['nom' => 'bordereaux_arrivee','libelle' => 'N° du bordereau d’arrivée',     'type' => 'texte',    'liste' => true, 'formulaire' => true],
                ['nom' => 'num_dossier_et_pers_concernee', 'libelle' => 'N° de dossier et personnes concernées', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'num_dossier_PGCS',  'libelle' => 'N° du dossier à la Cour de cassation', 'type' => 'texte',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'detention',         'libelle' => 'Détention',     'type' => 'booleen',  'liste' => true, 'formulaire' => true],
                ['nom' => 'date_detention',    'libelle' => 'Date détention','type' => 'date',     'liste' => false, 'formulaire' => true],
                ['nom' => 'localisation',      'libelle' => 'Localisation',  'type' => 'texte',    'liste' => false, 'formulaire' => true],
                ['nom' => 'observation',       'libelle' => 'Observation',   'type' => 'texte',    'liste' => false, 'formulaire' => true],
                ['nom' => 'date_transmission', 'libelle' => 'Date transmission', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'archiver',          'libelle' => 'Archivé',       'type' => 'booleen',  'liste' => true, 'formulaire' => false],
            ],
        ],

        'penaux' => [
            'libelle'      => 'Registres Pénaux',
            'libelleCourt' => 'Pénaux',
            'table'        => 'pgcc_reg_pourvoi_pen',
            'cle'          => 'id_reg_pourvoi',
            'ordre'        => 'annee DESC, num_ordre DESC',
            'recherche'    => ['num_dossier_PGCS', 'cassationnaire', 'juridiction'],
            'conclusion'   => 'conclusion',
            'sections' => [
                'Identification du dossier' => ['num_ordre', 'annee', 'date_insertion', 'date_EP', 'num_dossier_PGCS', 'detention', 'date_detention'],
                'Pourvoi et parties' => ['cassationnaire', 'inculpation', 'arret_attaque', 'date_pourvoi', 'juridiction', 'nature_decision', 'dispositif_arret_CA', 'consignation_amende'],
                'Mémoires' => ['memoire_ampliatif', 'date_memoire_ampliatif', 'memoire_en_defense', 'date_memoire_en_defense'],
                'Transmission et décision' => ['date_transmission_du_dossier_au_siege', 'date_retour_dossier_au_PGCS_pour_distribution', 'remarque', 'decision_AG', 'date_decision', 'date_enrolement', 'date_audience', 'num_arret', 'date_arret', 'resultat_audience', 'localisation', 'observation'],
            ],
            'colonnes'     => [
                ['nom' => 'num_ordre',     'libelle' => 'N° d’ordre du registre',  'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'annee',          'libelle' => 'Année',     'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_insertion', 'libelle' => 'Insertion', 'type' => 'date',    'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_EP', 'libelle' => 'Date d’entrée au parquet', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'num_dossier_PGCS','libelle' => 'N° dossier PGCS', 'type' => 'texte', 'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'cassationnaire', 'libelle' => 'Parties', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'inculpation',    'libelle' => 'Inculpation', 'type' => 'texte',  'liste' => false, 'formulaire' => true],
                ['nom' => 'arret_attaque',  'libelle' => 'Arrêt attaqué', 'type' => 'texte','liste' => false, 'formulaire' => true],
                ['nom' => 'date_pourvoi',   'libelle' => 'Date et lieu du pourvoi', 'type' => 'texte',  'liste' => false, 'formulaire' => true],
                ['nom' => 'juridiction',    'libelle' => 'Juridiction', 'type' => 'texte',   'liste' => false, 'formulaire' => true],
                ['nom' => 'nature_decision','libelle' => 'Nature décision', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'dispositif_arret_CA', 'libelle' => 'Dispositif de l’arrêt attaqué', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'consignation_amende', 'libelle' => 'B.E. Porte 05 / consignation de l’amende', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_transmission_du_dossier_au_siege', 'libelle' => 'Date de transmission du dossier au siège', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_retour_dossier_au_PGCS_pour_distribution', 'libelle' => 'Date de retour du dossier au PGCC pour distribution', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'remarque', 'libelle' => 'Remarque', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'detention',      'libelle' => 'Détention', 'type' => 'booleen',  'liste' => true, 'formulaire' => true],
                ['nom' => 'date_detention', 'libelle' => 'Date détention', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'memoire_ampliatif',       'libelle' => 'Mémoire ampliatif déposé',  'type' => 'booleen', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_memoire_ampliatif',  'libelle' => 'Date mémoire ampliatif',    'type' => 'date',    'liste' => false, 'formulaire' => true],
                ['nom' => 'memoire_en_defense',      'libelle' => 'Mémoire en défense déposé', 'type' => 'booleen', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_memoire_en_defense', 'libelle' => 'Date mémoire en défense',   'type' => 'date',    'liste' => false, 'formulaire' => true],
                ['nom' => 'decision_AG',    'libelle' => 'Décision AG', 'type' => 'texte',   'liste' => false, 'formulaire' => true],
                ['nom' => 'date_decision',  'libelle' => 'Date décision', 'type' => 'date',  'liste' => false, 'formulaire' => true],
                ['nom' => 'date_enrolement','libelle' => 'Enrôlement', 'type' => 'date',     'liste' => false, 'formulaire' => true],
                ['nom' => 'date_audience',  'libelle' => 'Audience', 'type' => 'date',       'liste' => false, 'formulaire' => true],
                ['nom' => 'num_arret',      'libelle' => 'N° arrêt', 'type' => 'texte',      'liste' => false, 'formulaire' => true],
                ['nom' => 'date_arret',     'libelle' => 'Date arrêt', 'type' => 'date',     'liste' => false, 'formulaire' => true],
                ['nom' => 'resultat_audience','libelle' => 'Résultat audience', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'localisation',   'libelle' => 'Localisation', 'type' => 'texte',   'liste' => false, 'formulaire' => true],
                ['nom' => 'observation',    'libelle' => 'Observation', 'type' => 'texte',    'liste' => false, 'formulaire' => true],
                ['nom' => 'archiver',       'libelle' => 'Archivé', 'type' => 'booleen',      'liste' => true, 'formulaire' => false],
            ],
        ],

        'civils' => [
            'libelle'      => 'Registres Civils',
            'libelleCourt' => 'Civils',
            'table'        => 'pgcc_reg_pourvoi_civ',
            'cle'          => 'id_reg_pourvoi',
            'ordre'        => 'annee DESC, num_ordre DESC',
            'recherche'    => ['num_dossier_PGCS', 'cassationnaire'],
            'conclusion'   => 'conclusion',
            'sections' => [
                'Bordereau et identification' => ['num_ordre', 'date_insertion', 'BE', 'annee', 'num_dossier_PGCS', 'cassationnaire', 'arret_attaque_et_date', 'date_de_depot_requete'],
                'Mémoires et distribution' => ['memoire_ampliatif', 'date_memoire_ampliatif', 'memoire_en_defense', 'date_memoire_en_defense', 'date_transmission_au_PG', 'date_de_distribution_par_PG', 'date_de_transmission_a_AG_et_nom_AG', 'date_de_transmission_pour_frappe_au_secretariat'],
                'Audience et décision' => ['decision_AG', 'date_decision', 'date_enrolement', 'date_audience', 'num_arret', 'date_arret', 'resultat_audience', 'localisation', 'observation'],
            ],
            'colonnes'     => [
                ['nom' => 'num_ordre',      'libelle' => 'N° d’ordre du registre',  'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'BE', 'libelle' => 'N° du bordereau d’envoi', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'annee',          'libelle' => 'Année',     'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_insertion', 'libelle' => 'Date du bordereau d’envoi', 'type' => 'date',    'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'num_dossier_PGCS','libelle' => 'N° du dossier PGCC', 'type' => 'texte', 'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'cassationnaire', 'libelle' => 'Noms des parties', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'arret_attaque_et_date', 'libelle' => 'Arrêt attaqué et date', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_de_depot_requete', 'libelle' => 'Dépôt requête', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'memoire_ampliatif',     'libelle' => 'Mémoire ampliatif déposé', 'type' => 'booleen', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_memoire_ampliatif','libelle' => 'Date mémoire ampliatif', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'memoire_en_defense',    'libelle' => 'Mémoire en défense déposé', 'type' => 'booleen', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_memoire_en_defense','libelle' => 'Date mémoire en défense', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_transmission_au_PG', 'libelle' => 'Date de transmission au PG', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_de_distribution_par_PG', 'libelle' => 'Date de distribution', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_de_transmission_a_AG_et_nom_AG', 'libelle' => 'Date de transmission à l’AG', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                // Colonne historique v1 du registre civil (formulaire papier :
                // « Date de transmission au procureur »), non exposée auparavant.
                ['nom' => 'date_de_transmission_pour_frappe_au_secretariat', 'libelle' => 'Transmission pour frappe (secrétariat)', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'decision_AG',     'libelle' => 'Décision AG', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_decision',   'libelle' => 'Date décision', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_enrolement', 'libelle' => 'Enrôlement', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_audience',   'libelle' => 'Audience', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'num_arret',       'libelle' => 'N° arrêt', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_arret',      'libelle' => 'Date arrêt', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'resultat_audience','libelle' => 'Résultat audience', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'localisation',    'libelle' => 'Localisation', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'observation',     'libelle' => 'Observation', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'archiver',        'libelle' => 'Archivé', 'type' => 'booleen', 'liste' => true, 'formulaire' => false],
            ],
        ],

        'dessaisissements' => [
            'libelle'      => 'Registres Déssaisissements',
            'libelleCourt' => 'Déssaisissements',
            'table'        => 'pgcc_reg_des',
            'cle'          => 'id_reg_des',
            'ordre'        => 'annee DESC, num_ordre DESC',
            'recherche'    => ['num_dossier_PGCS', 'cassationnaire'],
            'conclusion'   => 'conclusion',
            'sections' => [
                'Identification et réception' => ['num_ordre', 'annee', 'date_insertion', 'date_EP', 'num_dossier_PGCS', 'detention', 'date_detention'],
                'Pourvoi et parties' => ['cassationnaire', 'inculpation', 'arret_attaque', 'date_pourvoi', 'juridiction', 'nature_decision', 'dispositif_arret_CA'],
                'Mémoires et suivi' => ['memoire_ampliatif', 'date_memoire_ampliatif', 'memoire_en_defense', 'date_memoire_en_defense', 'date_transmission_du_dossier_au_siege', 'date_retour_dossier_au_PGCS_pour_distribution', 'remarque', 'observation'],
            ],
            'colonnes'     => [
                ['nom' => 'num_ordre',      'libelle' => 'N° d’ordre du registre',  'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'annee',          'libelle' => 'Année',     'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_insertion', 'libelle' => 'Insertion', 'type' => 'date',    'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_EP', 'libelle' => 'Date d’entrée au parquet', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'num_dossier_PGCS','libelle' => 'N° dossier PGCS', 'type' => 'texte', 'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'cassationnaire', 'libelle' => 'Parties', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'inculpation', 'libelle' => 'Inculpation', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'arret_attaque',  'libelle' => 'Arrêt attaqué', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_pourvoi', 'libelle' => 'Date et lieu du pourvoi', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'juridiction',    'libelle' => 'Juridiction', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'nature_decision','libelle' => 'Nature décision', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'dispositif_arret_CA', 'libelle' => 'Dispositif de l’arrêt attaqué', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_transmission_du_dossier_au_siege', 'libelle' => 'Date de transmission du dossier au siège', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_retour_dossier_au_PGCS_pour_distribution', 'libelle' => 'Date de retour du dossier au PGCC pour distribution', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'detention',      'libelle' => 'Détention', 'type' => 'booleen', 'liste' => true, 'formulaire' => true],
                // Champ présent sur le formulaire v1 du délaissement (« Date de
                // détention ») ; colonne ajoutée par database/migrations/patch_champs_v1.sql.
                ['nom' => 'date_detention', 'libelle' => 'Date détention', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'memoire_ampliatif',      'libelle' => 'Mémoire ampliatif déposé', 'type' => 'booleen', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_memoire_ampliatif', 'libelle' => 'Date mémoire ampliatif', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'memoire_en_defense',     'libelle' => 'Mémoire en défense déposé', 'type' => 'booleen', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_memoire_en_defense','libelle' => 'Date mémoire en défense', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'remarque',       'libelle' => 'Remarque', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'observation',    'libelle' => 'Observation', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'archiver',       'libelle' => 'Archivé', 'type' => 'booleen', 'liste' => true, 'formulaire' => false],
            ],
        ],

        'siege' => [
            'libelle'      => 'Registre du Siège (Cassation civile)',
            'libelleCourt' => 'Siège',
            'table'        => 'siege_cassation_civ',
            'cle'          => 'id_civil',
            'colonneArchive' => 'archive',
            'ordre'        => 'annee DESC, id_civil DESC',
            'recherche'    => ['num_dossier', 'nom_demandeur', 'nom_defendeur', 'rapporteur'],
            'conclusion'   => null,
            'colonnes'     => [
                ['nom' => 'id_reg_pourvoi_civ', 'libelle' => 'Dossier civil correspondant', 'type' => 'dossier_civil', 'liste' => false, 'formulaire' => true, 'requis' => true, 'afficherFiche' => false],
                ['nom' => 'num_dossier',     'libelle' => 'N° dossier', 'type' => 'texte', 'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'annee',           'libelle' => 'Année', 'type' => 'nombre', 'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'nom_demandeur',   'libelle' => 'Demandeur', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'nom_defendeur',   'libelle' => 'Défendeur', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'rapporteur',      'libelle' => 'Rapporteur', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_rapporteur', 'libelle' => 'Date rapporteur', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_notification_pourvoi', 'libelle' => 'Notification pourvoi', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_depot_memoire_defense', 'libelle' => 'Dépôt mémoire défense', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_notification_memoire_ampliatif', 'libelle' => 'Notification mémoire ampliatif', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_depot_memoire_ampliatif', 'libelle' => 'Dépôt mémoire ampliatif', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_depot_rapport', 'libelle' => 'Dépôt rapport', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_transmission', 'libelle' => 'Transmission', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_depot_conclusion', 'libelle' => 'Dépôt conclusion', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'numero_arret_cours_appel', 'libelle' => 'N° arrêt cours appel', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'numero_arret_cs', 'libelle' => 'N° arrêt CS', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'decision',        'libelle' => 'Décision', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'avocat_demandeur','libelle' => 'Avocat demandeur', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'avocat_defendeur','libelle' => 'Avocat défendeur', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'archive',         'libelle' => 'Archivé', 'type' => 'booleen', 'liste' => true, 'formulaire' => false],
            ],
        ],
    ];
}

/**
 * Retourne la définition d'un registre ou null si le code est inconnu.
 */
function registre_definition(string $code): ?array
{
    return registres_definitions()[$code] ?? null;
}

/**
 * Liste des colonnes d'un registre filtrées selon un usage.
 */
function registre_colonnes(array $definition, string $usage): array
{
    return array_values(array_filter(
        $definition['colonnes'],
        fn (array $c) => !empty($c[$usage])
    ));
}

/**
 * Nom de la colonne d'archivage du registre (conventions variables :
 * « archiver » dans les registres historiques, « archive » au siège).
 */
function registre_colonne_archive(array $definition): string
{
    return $definition['colonneArchive'] ?? 'archiver';
}
