-- ---------------------------------------------------------------------------
-- Registre des Mémoires : champ « Juridiction » (demande Parquet).
--
-- Le formulaire du registre des mémoires est recentré sur : année, numéro
-- d'ordre, type, origine, n° dossier PGCS, pièces, déposant, juridiction,
-- date de dépôt, état et observation. Les champs Rapporteur, Date de
-- notification et Transmission au secrétariat sont retirés du formulaire
-- et de l'affichage ; leurs colonnes restent en base pour l'historique.
--
-- Libellés renommés sans changement de colonne : avocat → « Déposant »,
-- parties → « Pièces ». Nouvelle colonne : juridiction.
-- ---------------------------------------------------------------------------

ALTER TABLE pgcc_reg_memoires
    ADD COLUMN IF NOT EXISTS juridiction VARCHAR(200) NULL AFTER avocat;
