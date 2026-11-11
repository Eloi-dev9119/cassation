-- ---------------------------------------------------------------------------
-- Exemples de mémoires NON reliés (liaison différée) — données de démonstration.
--
-- But : illustrer l'état « non relié » (aucun dossier au registre ne porte
-- encore le numéro) et alimenter l'indicateur « Mémoires » de la liste des
-- registres. Les numéros 999/99 et 888/99 n'existent volontairement dans
-- aucun registre : ces mémoires restent donc non reliés.
--
-- Idempotent : les lignes d'exemple sont d'abord supprimées, puis recréées.
-- Suppression manuelle : DELETE FROM pgcc_reg_memoires WHERE observation LIKE 'EXEMPLE%';
-- ---------------------------------------------------------------------------

USE pgcc_utf8mb4;

DELETE FROM pgcc_reg_memoires WHERE observation LIKE 'EXEMPLE%';

INSERT INTO pgcc_reg_memoires
    (num_ordre, annee, date_insertion, type_memoire, origine, num_dossier_PGCS,
     parties, avocat, juridiction, date_depot, observation, etat, cree_par)
VALUES
    (1, 2026, CURDATE(), 'ampliatif', 'penal', '999/99 PEN',
     'Rakoto Jean c/ Ministère public', 'Me Andrianina', 'Cour de cassation',
     CURDATE(), 'EXEMPLE — non relié (aucun dossier 999/99 PEN au registre)', 'depose', 'demo'),
    (2, 2026, CURDATE(), 'defense', 'civil', '999/99 CIV',
     'Rasoanaivo c/ Société XYZ', 'Me Rabe', 'Cour de cassation',
     CURDATE(), 'EXEMPLE — non relié (aucun dossier 999/99 CIV au registre)', 'transmis', 'demo'),
    (3, 2026, CURDATE(), 'ampliatif', 'civil', '888/99 CIV',
     'Ramanana c/ État malgache', 'Me Soa', 'Cour de cassation',
     CURDATE(), 'EXEMPLE — non relié (aucun dossier 888/99 CIV au registre)', 'depose', 'demo');
