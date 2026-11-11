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
            'recherche'    => ['num_dossier_PGCS', 'juridiction', 'bordereaux_arrivee', 'num_dossier_et_pers_concernee'],
            'conclusion'   => null,
            'sections' => [
                'Identification et réception' => ['num_ordre', 'num_dossier_PGCS', 'annee', 'date_insertion', 'juridiction', 'bordereaux_arrivee', 'num_dossier_et_pers_concernee'],
                'Détention et suivi' => ['detention', 'date_detention', 'localisation', 'date_transmission', 'observation'],
            ],
            // Colonnes de liste alignées sur PGCC v1 (Registres Arrivés) :
            // N°ordre, Juridiction, Bordereaux d'arrivée, N° dossier et les
            // personnes concernées, N° du dossier à la Cour de cassation,
            // Date de détention.
            'colonnes'     => [
                ['nom' => 'num_ordre',        'libelle' => 'N° d’ordre',      'type' => 'nombre',   'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'juridiction',       'libelle' => 'Juridiction',   'type' => 'texte',    'liste' => true, 'formulaire' => true],
                ['nom' => 'bordereaux_arrivee','libelle' => 'Bordereaux d’arrivée',     'type' => 'texte',    'liste' => true, 'formulaire' => true],
                ['nom' => 'num_dossier_et_pers_concernee', 'libelle' => 'N° dossier et les personnes concernées', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'num_dossier_PGCS',  'libelle' => 'N° du dossier à la Cour de cassation', 'type' => 'texte',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_detention',    'libelle' => 'Date de détention','type' => 'date',     'liste' => true, 'formulaire' => true],
                ['nom' => 'annee',             'libelle' => 'Année',         'type' => 'nombre',   'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_insertion',    'libelle' => 'Date d’insertion','type' => 'date',     'liste' => false, 'formulaire' => true, 'requis' => true],
                ['nom' => 'detention',         'libelle' => 'Détention',     'type' => 'booleen',  'liste' => true, 'formulaire' => true],
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
            'recherche'    => ['num_dossier_PGCS', 'cassationnaire', 'juridiction', 'inculpation', 'arret_attaque'],
            'conclusion'   => 'conclusion',
            'sections' => [
                'Identification du dossier' => ['num_ordre', 'annee', 'date_insertion', 'date_EP', 'num_dossier_PGCS', 'detention', 'date_detention'],
                'Pourvoi et parties' => ['cassationnaire', 'inculpation', 'arret_attaque', 'date_pourvoi', 'juridiction', 'nature_decision', 'dispositif_arret_CA', 'consignation_amende'],
                'Mémoires' => ['memoire_ampliatif', 'date_memoire_ampliatif', 'memoire_en_defense', 'date_memoire_en_defense'],
                'Transmission et décision' => ['date_transmission_du_dossier_au_siege', 'date_retour_dossier_au_PGCS_pour_distribution', 'remarque', 'decision_AG', 'date_decision', 'date_de_transmission_pour_frappe_au_secretariat', 'date_enrolement', 'date_audience', 'num_arret', 'date_arret', 'resultat_audience', 'localisation', 'observation'],
            ],
            // Libellés et colonnes de liste alignés sur la terminologie et
            // l'ordre de PGCC v1 (Registres Pénaux) : N° d'ordre, Année,
            // Date d'entrée au parquet, N° dossier, Parties, Inculpation,
            // N° et date de l'arrêt attaqué, Juridiction, Date du pourvoi,
            // B.E Porte 05, Conclusion — puis les indicateurs propres à v2.
            'colonnes'     => [
                ['nom' => 'num_ordre',     'libelle' => 'N° d’ordre',  'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'annee',          'libelle' => 'Année',     'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_EP', 'libelle' => 'Date d’entrée au parquet', 'type' => 'date', 'liste' => true, 'formulaire' => true],
                ['nom' => 'num_dossier_PGCS','libelle' => 'N° dossier', 'type' => 'texte', 'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'cassationnaire', 'libelle' => 'Parties', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'inculpation',    'libelle' => 'Inculpation', 'type' => 'texte',  'liste' => true, 'formulaire' => true],
                ['nom' => 'arret_attaque',  'libelle' => 'N° et date de l’arrêt attaqué', 'type' => 'texte','liste' => true, 'formulaire' => true],
                ['nom' => 'juridiction',    'libelle' => 'Juridiction', 'type' => 'texte',   'liste' => true, 'formulaire' => true],
                ['nom' => 'date_pourvoi',   'libelle' => 'Date du pourvoi', 'type' => 'texte',  'liste' => true, 'formulaire' => true],
                ['nom' => 'dispositif_arret_CA', 'libelle' => 'B.E Porte 05', 'type' => 'textelong', 'liste' => true, 'formulaire' => true],
                ['nom' => 'consignation_amende', 'libelle' => 'Consignation de l’amende', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'conclusion', 'libelle' => 'Conclusion', 'type' => 'textelong', 'liste' => true, 'formulaire' => false],
                ['nom' => 'date_insertion', 'libelle' => 'Date d’insertion', 'type' => 'date',    'liste' => false, 'formulaire' => true, 'requis' => true],
                ['nom' => 'nature_decision','libelle' => 'Nature décision', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
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
                // Champ historique v1 (sous-formulaire « Décision AG » du
                // registre pénal) : date de transmission pour frappe au
                // secrétariat, utilisée par la synthèse des conclusions.
                ['nom' => 'date_de_transmission_pour_frappe_au_secretariat', 'libelle' => 'Transmission pour frappe (secrétariat)', 'type' => 'date', 'liste' => false, 'formulaire' => true],
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
            'recherche'    => ['num_dossier_PGCS', 'cassationnaire', 'arret_attaque_et_date'],
            'conclusion'   => 'conclusion',
            'sections' => [
                'Bordereau et identification' => ['num_ordre', 'date_insertion', 'BE', 'annee', 'num_dossier_PGCS', 'cassationnaire', 'arret_attaque_et_date', 'date_de_depot_requete'],
                'Mémoires et distribution' => ['memoire_ampliatif', 'date_memoire_ampliatif', 'memoire_en_defense', 'date_memoire_en_defense', 'date_transmission_au_PG', 'date_de_distribution_par_PG', 'date_de_transmission_a_AG_et_nom_AG', 'date_de_transmission_pour_frappe_au_secretariat'],
                'Audience et décision' => ['decision_AG', 'date_decision', 'date_enrolement', 'date_audience', 'num_arret', 'date_arret', 'resultat_audience', 'localisation', 'observation'],
            ],
            // Colonnes de liste alignées sur PGCC v1 (Registres Civils) :
            // N° d'ordre, Année, B.E, N° du dossier, Parties, N° et date de
            // l'arrêt attaqué, Date de dépôt de la requête, Date du mémoire
            // ampliatif, Date du mémoire en défense, Conclusion.
            'colonnes'     => [
                ['nom' => 'num_ordre',      'libelle' => 'N° d’ordre',  'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'annee',          'libelle' => 'Année',     'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'BE', 'libelle' => 'B.E', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_insertion', 'libelle' => 'Date du bordereau d’envoi', 'type' => 'date',    'liste' => false, 'formulaire' => true, 'requis' => true],
                ['nom' => 'num_dossier_PGCS','libelle' => 'N° du dossier', 'type' => 'texte', 'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'cassationnaire', 'libelle' => 'Parties', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'arret_attaque_et_date', 'libelle' => 'N° et date de l’arrêt attaqué', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_de_depot_requete', 'libelle' => 'Date de dépôt de la requête', 'type' => 'date', 'liste' => true, 'formulaire' => true],
                ['nom' => 'memoire_ampliatif',     'libelle' => 'Mémoire ampliatif déposé', 'type' => 'booleen', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_memoire_ampliatif','libelle' => 'Date du mémoire ampliatif', 'type' => 'date', 'liste' => true, 'formulaire' => true],
                ['nom' => 'memoire_en_defense',    'libelle' => 'Mémoire en défense déposé', 'type' => 'booleen', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_memoire_en_defense','libelle' => 'Date du mémoire en défense', 'type' => 'date', 'liste' => true, 'formulaire' => true],
                ['nom' => 'conclusion', 'libelle' => 'Conclusion', 'type' => 'textelong', 'liste' => true, 'formulaire' => false],
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
            'recherche'    => ['num_dossier_PGCS', 'cassationnaire', 'inculpation', 'arret_attaque'],
            'conclusion'   => 'conclusion',
            'sections' => [
                'Identification et réception' => ['num_ordre', 'annee', 'date_insertion', 'date_EP', 'num_dossier_PGCS', 'detention', 'date_detention'],
                'Pourvoi et parties' => ['cassationnaire', 'inculpation', 'arret_attaque', 'date_pourvoi', 'juridiction', 'nature_decision', 'dispositif_arret_CA'],
                'Mémoires et suivi' => ['memoire_ampliatif', 'date_memoire_ampliatif', 'memoire_en_defense', 'date_memoire_en_defense', 'date_transmission_du_dossier_au_siege', 'date_retour_dossier_au_PGCS_pour_distribution', 'remarque', 'observation'],
                // Sous-formulaires 3 et 4 de la v1 (registreDes.php) :
                // décision AG + transmission pour frappe au secrétariat,
                // puis transmission au siège et suivi de l'audience.
                'Décision et audience' => ['decision_AG', 'date_decision', 'date_de_transmission_pour_frappe_au_secretariat', 'consignation_amende', 'date_transmission_au_siege', 'date_de_retour_apres_rapport', 'date_enrolement', 'date_audience', 'num_arret', 'date_arret', 'resultat_audience'],
            ],
            // Colonnes de liste alignées sur PGCC v1 (Registres
            // Déssaisissements) : N° d'ordre, Date d'entrée au parquet,
            // Numéro du dossier, Parties, Inculpation, N° et date de l'arrêt
            // attaqué, Juridiction, Date du pourvoi, Dispositif de l'arrêt
            // attaqué, Conclusion.
            'colonnes'     => [
                ['nom' => 'num_ordre',      'libelle' => 'N° d’ordre',  'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_EP', 'libelle' => 'Date d’entrée au parquet', 'type' => 'date', 'liste' => true, 'formulaire' => true],
                ['nom' => 'num_dossier_PGCS','libelle' => 'Numéro du dossier', 'type' => 'texte', 'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'cassationnaire', 'libelle' => 'Parties', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'inculpation', 'libelle' => 'Inculpation', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'arret_attaque',  'libelle' => 'N° et date de l’arrêt attaqué', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'juridiction',    'libelle' => 'Juridiction', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_pourvoi', 'libelle' => 'Date du pourvoi', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'nature_decision','libelle' => 'Nature décision', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'dispositif_arret_CA', 'libelle' => 'Dispositif de l’arrêt attaqué', 'type' => 'textelong', 'liste' => true, 'formulaire' => true],
                ['nom' => 'conclusion', 'libelle' => 'Conclusion', 'type' => 'textelong', 'liste' => true, 'formulaire' => false],
                ['nom' => 'annee',          'libelle' => 'Année',     'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_insertion', 'libelle' => 'Date d’insertion', 'type' => 'date',    'liste' => false, 'formulaire' => true, 'requis' => true],
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
                // Champs des sous-formulaires v1 (registreDes.php, iEnreg3 et
                // iEnreg4) : décision AG, transmission pour frappe au
                // secrétariat, transmission au siège et suivi de l'audience.
                ['nom' => 'decision_AG',    'libelle' => 'Décision AG', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_decision',  'libelle' => 'Date décision', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_de_transmission_pour_frappe_au_secretariat', 'libelle' => 'Transmission pour frappe (secrétariat)', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'consignation_amende', 'libelle' => 'Consignation de l’amende', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_transmission_au_siege', 'libelle' => 'Date de transmission au siège', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_de_retour_apres_rapport', 'libelle' => 'Date de retour après rapport', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_enrolement','libelle' => 'Enrôlement', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_audience',  'libelle' => 'Audience', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'num_arret',      'libelle' => 'N° arrêt', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_arret',     'libelle' => 'Date arrêt', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'resultat_audience','libelle' => 'Résultat audience', 'type' => 'texte', 'liste' => false, 'formulaire' => true],
                ['nom' => 'archiver',       'libelle' => 'Archivé', 'type' => 'booleen', 'liste' => true, 'formulaire' => false],
            ],
        ],

        'siege' => [
            'libelle'      => 'Registre du Siège ',
            'libelleCourt' => 'Siège',
            'table'        => 'siege_cassation_civ',
            'cle'          => 'id_civil',
            'colonneArchive' => 'archive',
            'ordre'        => 'annee DESC, id_civil DESC',
            'recherche'    => ['num_dossier', 'nom_demandeur', 'nom_defendeur', 'rapporteur'],
            'conclusion'   => null,
            'sections'     => [
                'Dossier et parties' => ['id_reg_pourvoi_civ', 'num_dossier', 'annee', 'nom_demandeur', 'nom_defendeur', 'avocat_demandeur', 'avocat_defendeur'],
                'Rapporteur et pourvoi' => ['rapporteur', 'date_rapporteur', 'date_notification_pourvoi'],
                'Mémoires et transmission' => ['date_depot_memoire_defense', 'date_notification_memoire_ampliatif', 'date_depot_memoire_ampliatif', 'date_depot_rapport', 'date_transmission'],
                'Conclusion et décision' => ['date_depot_conclusion', 'numero_arret_cours_appel', 'numero_arret_cs', 'decision'],
            ],
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

        // Registre PIL — « Tableau de Suivi des Pourvois dans l'intérêt de
        // la loi » (pgcsPil.php en v1). La table pgcc_reg_pil existait en
        // base sans être exposée ; les colonnes d'audit sont ajoutées par
        // database/migrations/pil_audit.sql.
        'pil' => [
            'libelle'      => 'Registres PIL ',
            'libelleCourt' => 'PIL',
            'table'        => 'pgcc_reg_pil',
            'cle'          => 'id_pil',
            'ordre'        => 'annee DESC, num_ordre DESC',
            'recherche'    => ['num_pil', 'requete', 'magistrat_instructeur', 'lettre_GSMJ'],
            'conclusion'   => null,
            'sections' => [
                'Identification et distribution' => ['num_ordre', 'annee', 'date_insertion', 'num_pil', 'lettre_GSMJ', 'date_de_distribution', 'magistrat_instructeur'],
                'Requête et mémoires' => ['date_de_la_requete_pil', 'requete', 'memoire_ampliatif', 'date_memoire_ampliatif', 'memoire', 'memoire_en_defense', 'date_memoire_en_defense'],
                'Audience et transmission' => ['consignation_amende', 'date_audience', 'resultat_audience', 'date_de_transmission_pour_frappe_au_secretariat', 'date_transmission_au_siege', 'num_arret', 'date_arret'],
                'Suivi' => ['localisation', 'observation'],
            ],
            'colonnes'     => [
                ['nom' => 'num_ordre',      'libelle' => 'N° d’ordre',  'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'annee',          'libelle' => 'Année',       'type' => 'nombre',  'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'num_pil',        'libelle' => 'N° PIL',      'type' => 'texte',   'liste' => true, 'formulaire' => true, 'requis' => true],
                ['nom' => 'date_insertion', 'libelle' => 'Date d’insertion', 'type' => 'date', 'liste' => false, 'formulaire' => true, 'requis' => true],
                ['nom' => 'lettre_GSMJ',    'libelle' => 'Lettre du Ministère (GSMJ)', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_de_distribution', 'libelle' => 'Date de distribution', 'type' => 'date', 'liste' => true, 'formulaire' => true],
                ['nom' => 'magistrat_instructeur', 'libelle' => 'Magistrat instructeur', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_de_la_requete_pil', 'libelle' => 'Date de la requête PIL', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'requete',        'libelle' => 'Requête',     'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'memoire_ampliatif',      'libelle' => 'Mémoire ampliatif déposé', 'type' => 'booleen', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_memoire_ampliatif', 'libelle' => 'Date mémoire ampliatif', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'memoire',        'libelle' => 'Mémoire',     'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'memoire_en_defense',     'libelle' => 'Mémoire en défense déposé', 'type' => 'booleen', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_memoire_en_defense','libelle' => 'Date mémoire en défense', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'consignation_amende', 'libelle' => 'Consignation de l’amende', 'type' => 'textelong', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_audience',  'libelle' => 'Audience',    'type' => 'date',    'liste' => true, 'formulaire' => true],
                ['nom' => 'resultat_audience', 'libelle' => 'Résultat audience', 'type' => 'texte', 'liste' => true, 'formulaire' => true],
                ['nom' => 'date_de_transmission_pour_frappe_au_secretariat', 'libelle' => 'Transmission pour frappe (secrétariat)', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'date_transmission_au_siege', 'libelle' => 'Date de transmission au siège', 'type' => 'date', 'liste' => false, 'formulaire' => true],
                ['nom' => 'num_arret',      'libelle' => 'N° arrêt',    'type' => 'texte',   'liste' => false, 'formulaire' => true],
                ['nom' => 'date_arret',     'libelle' => 'Date arrêt',  'type' => 'date',    'liste' => false, 'formulaire' => true],
                ['nom' => 'localisation',   'libelle' => 'Localisation', 'type' => 'texte',  'liste' => false, 'formulaire' => true],
                ['nom' => 'observation',    'libelle' => 'Observation', 'type' => 'texte',   'liste' => false, 'formulaire' => true],
                ['nom' => 'archiver',       'libelle' => 'Archivé',     'type' => 'booleen', 'liste' => true, 'formulaire' => false],
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
