-- ---------------------------------------------------------------------------
-- PGCC v2 — Migration : colonnes de traçabilité (audit)
--
-- Ajoute à toutes les tables métier quatre colonnes d'audit :
--   date_creation     : horodatage automatique par MySQL à l'insertion
--   date_modification : horodatage automatique par MySQL à chaque UPDATE
--                       qui change réellement la ligne (ON UPDATE)
--   cree_par          : login de l'utilisateur créateur (rempli par PGCC v2)
--   modifie_par       : login du dernier modificateur (rempli par PGCC v2)
--
-- Les horodatages sont tenus par le SGBD : ils sont donc renseignés même
-- pour les écritures effectuées par l'application historique PGCC v1.
-- Les colonnes « ..._par » restent NULL pour les écritures de la v1.
--
-- Script ré-exécutable (ADD COLUMN IF NOT EXISTS — MariaDB 10.4+).
-- Aucune colonne ni donnée existante n'est modifiée : la v1 reste
-- entièrement compatible.
-- ---------------------------------------------------------------------------

USE pgcc;

-- Registres -----------------------------------------------------------------
ALTER TABLE pgcc_reg_arrivee_pen
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

ALTER TABLE pgcc_reg_pourvoi_pen
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

ALTER TABLE pgcc_reg_pourvoi_civ
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

ALTER TABLE pgcc_reg_des
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

-- Registre des Mémoires (nouveau module) ------------------------------------
ALTER TABLE pgcc_reg_memoires
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

-- Suivis --------------------------------------------------------------------
ALTER TABLE pgcc_suivi_arrivee
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

ALTER TABLE pgcc_suivi_pen
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

-- Distributions --------------------------------------------------------------
ALTER TABLE pgcc_distribution_pg
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

-- Comptes utilisateurs (trace de validation, retraite, mot de passe…) --------
ALTER TABLE pgcc_client
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

-- Registre du Siège (table créée à part, même convention d'audit) ------------
ALTER TABLE siege_cassation_civ
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;
