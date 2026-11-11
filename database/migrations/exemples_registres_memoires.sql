-- Exemples de dossiers avec mémoires liés (données de démonstration).
-- Toutes les lignes sont marquées « [EXEMPLE] » (champs observation et
-- cree_par = 'exemple') afin de rester identifiables et supprimables :
--
--   DELETE FROM pgcc_reg_memoires WHERE cree_par = 'exemple';
--   DELETE FROM pgcc_reg_pourvoi_pen WHERE cree_par = 'exemple';
--   DELETE FROM pgcc_reg_pourvoi_civ WHERE cree_par = 'exemple';
--
-- Les mémoires couvrent chaque combinaison utile : ampliatif/défense,
-- origine pénale/civile, et les quatre états du cycle de vie.

USE pgcc_utf8mb4;

-- Garantit la lecture UTF-8 du présent fichier par le client mysql
-- (indépendamment du charset par défaut de la console).
SET NAMES utf8mb4;

-- Rejouable : retire d'abord un précédent jeu d'exemples éventuel.
DELETE FROM pgcc_reg_memoires     WHERE cree_par = 'exemple';
DELETE FROM pgcc_reg_pourvoi_pen  WHERE cree_par = 'exemple';
DELETE FROM pgcc_reg_pourvoi_civ  WHERE cree_par = 'exemple';

-- --- Deux dossiers pénaux d'exemple (année 2018, après le n° 386) --------
INSERT INTO pgcc_reg_pourvoi_pen
    (num_ordre, annee, date_insertion, date_EP, num_dossier_PGCS, detention,
     cassationnaire, inculpation, arret_attaque, date_pourvoi, juridiction,
     nature_decision, memoire_ampliatif, date_memoire_ampliatif,
     memoire_en_defense, date_memoire_en_defense, observation, archiver,
     date_creation, cree_par)
VALUES
    (387, 2018, '2018-11-05', '2018-11-12', '387/18 PEN', '1',
     'RAJAONARIVELO Jean [EXEMPLE] (Me ANDRIAMANANA Sophie) C/ Ministère Public',
     'Escroquerie et faux en écriture publique',
     'Arrêt n°214/2018 de la Cour d''Appel d''Antananarivo du 10 septembre 2018',
     '08 octobre 2018', 'Cour d''Appel d''Antananarivo',
     'Arrêt de condamnation', '1', '2018-11-20', '1', '2018-12-03',
     '[EXEMPLE] Dossier de démonstration — registre des mémoires', '0',
     NOW(), 'exemple'),
    (388, 2018, '2018-11-19', '2018-11-26', '388/18 PEN', '0',
     'RABEMANANJARA Fidy [EXEMPLE] (Me RAKOTONIRINA Jean Eric) C/ État malgache',
     'Détournement de deniers publics',
     'Jugement n°302/2018 du Tribunal de Première Instance de Toamasina du 05 octobre 2018',
     '29 octobre 2018', 'TPI Toamasina',
     'Jugement de condamnation', '0', NULL, '1', '2018-12-10',
     '[EXEMPLE] Dossier de démonstration — mémoire en défense seul', '0',
     NOW(), 'exemple');

-- --- Deux dossiers civils d'exemple (année 2018, après le n° 12) ----------
INSERT INTO pgcc_reg_pourvoi_civ
    (num_ordre, annee, date_insertion, num_dossier_PGCS,
     cassationnaire, arret_attaque_et_date, date_de_depot_requete,
     memoire_ampliatif, memoire_en_defense, observation, archiver,
     date_creation, cree_par)
VALUES
    (13, 2018, '2018-11-08', '13/18 CU',
     'Société TSARA VOLA S.A.R.L [EXEMPLE] c/ BAMBINY Haja et consorts',
     'l''arrêt n°167/2018 de la Cour d''Appel de Fianarantsoa du 20 août 2018',
     '2018-09-14', '1', '1',
     '[EXEMPLE] Dossier de démonstration — mémoires ampliatif et défense', '0',
     NOW(), 'exemple'),
    (14, 2018, '2018-11-22', '14/18 CU',
     'RANDRIAMAMPIONONA Niaina [EXEMPLE] c/ Société FANJAKANA IMMOBILIER',
     'l''ordonnance n°95/2018 du 12 septembre 2018 statuant en référé',
     '2018-10-19', '1', '0',
     '[EXEMPLE] Dossier de démonstration — mémoire ampliatif seul', '0',
     NOW(), 'exemple');

-- --- Six mémoires d'exemple liés aux dossiers ci-dessus -------------------
-- Pénal 387/18 : les deux mémoires, aux deux premiers états.
INSERT INTO pgcc_reg_memoires
    (num_ordre, annee, date_insertion, type_memoire, origine, num_dossier_PGCS,
     parties, avocat, juridiction, date_notification, date_depot,
     date_transmission_secretaire, rapporteur, observation, etat, archiver,
     date_creation, cree_par, id_reg_pourvoi_pen, id_reg_pourvoi_civ)
VALUES
    (1, 2018, '2018-11-21', 'ampliatif', 'penal', '387/18 PEN',
     'RAJAONARIVELO Jean [EXEMPLE] (Me ANDRIAMANANA Sophie) C/ Ministère Public',
     'Me ANDRIAMANANA Sophie', 'Cour d''Appel d''Antananarivo',
     '2018-11-18', '2018-11-20', NULL, NULL,
     '[EXEMPLE] Mémoire ampliatif déposé dans le délai', 'depose', '0',
     NOW(), 'exemple',
     (SELECT id_reg_pourvoi FROM pgcc_reg_pourvoi_pen WHERE num_dossier_PGCS = '387/18 PEN' AND cree_par = 'exemple'),
     NULL),
    (2, 2018, '2018-12-04', 'defense', 'penal', '387/18 PEN',
     'Ministère Public [EXEMPLE] C/ RAJAONARIVELO Jean',
     'Me RAKOTOARIMALALA Tianasoa', 'Cour d''Appel d''Antananarivo',
     '2018-11-28', '2018-12-03', '2018-12-10', 'AG JRK',
     '[EXEMPLE] Réplique transmise au rapporteur', 'transmis', '0',
     NOW(), 'exemple',
     (SELECT id_reg_pourvoi FROM pgcc_reg_pourvoi_pen WHERE num_dossier_PGCS = '387/18 PEN' AND cree_par = 'exemple'),
     NULL),
-- Pénal 388/18 : mémoire en défense, rapport établi.
    (3, 2018, '2018-12-11', 'defense', 'penal', '388/18 PEN',
     'RABEMANANJARA Fidy [EXEMPLE] (Me RAKOTONIRINA Jean Eric) C/ État malgache',
     'Me RAKOTONIRINA Jean Eric', 'TPI Toamasina',
     '2018-12-02', '2018-12-10', '2018-12-17', 'AG RAL',
     '[EXEMPLE] Rapport du rapporteur établi', 'rappore', '0',
     NOW(), 'exemple',
     (SELECT id_reg_pourvoi FROM pgcc_reg_pourvoi_pen WHERE num_dossier_PGCS = '388/18 PEN' AND cree_par = 'exemple'),
     NULL),
-- Civil 13/18 : les deux mémoires, dont un classé.
    (4, 2018, '2018-11-12', 'ampliatif', 'civil', '13/18 CU',
     'Société TSARA VOLA S.A.R.L [EXEMPLE] c/ BAMBINY Haja et consorts',
     'Me VELOMANANA Josiane', 'Cour d''Appel de Fianarantsoa',
     '2018-11-05', '2018-11-09', '2018-11-15', 'AG CHR',
     '[EXEMPLE] Mémoire ampliatif classé après retrait de pourvoi', 'classe', '0',
     NOW(), 'exemple',
     NULL,
     (SELECT id_reg_pourvoi FROM pgcc_reg_pourvoi_civ WHERE num_dossier_PGCS = '13/18 CU' AND cree_par = 'exemple')),
    (5, 2018, '2018-11-26', 'defense', 'civil', '13/18 CU',
     'BAMBINY Haja et consorts [EXEMPLE] c/ Société TSARA VOLA S.A.R.L',
     'Me ANDRIAMASOLO Hery', 'Cour d''Appel de Fianarantsoa',
     '2018-11-19', '2018-11-23', '2018-11-30', 'AG CHR',
     '[EXEMPLE] Mémoire en défense déposé', 'depose', '0',
     NOW(), 'exemple',
     NULL,
     (SELECT id_reg_pourvoi FROM pgcc_reg_pourvoi_civ WHERE num_dossier_PGCS = '13/18 CU' AND cree_par = 'exemple')),
-- Civil 14/18 : mémoire ampliatif transmis.
    (6, 2018, '2018-10-24', 'ampliatif', 'civil', '14/18 CU',
     'RANDRIAMAMPIONONA Niaina [EXEMPLE] c/ Société FANJAKANA IMMOBILIER',
     'Me RASOANAIVO Fanja', 'TPI Antananarivo — ordonnance de référé',
     '2018-10-15', '2018-10-22', '2018-10-29', 'AG JRA',
     '[EXEMPLE] Mémoire ampliatif transmis au rapporteur', 'transmis', '0',
     NOW(), 'exemple',
     NULL,
     (SELECT id_reg_pourvoi FROM pgcc_reg_pourvoi_civ WHERE num_dossier_PGCS = '14/18 CU' AND cree_par = 'exemple'));
