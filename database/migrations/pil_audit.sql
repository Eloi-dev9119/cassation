-- ---------------------------------------------------------------------------
-- PGCC v2 — Migration : registre PIL (Pourvoi dans l'intérêt de la loi)
--
-- La table pgcc_reg_pil existait en base (héritage des versions précédentes)
-- sans les colonnes d'audit ni l'index d'année utilisés par le moteur
-- déclaratif de PGCC v2. Cette migration les ajoute pour exposer le registre
-- « Registres PIL (Intérêt de la Loi) » à l'identique des autres registres.
--
-- Script ré-exécutable (ADD COLUMN IF NOT EXISTS / ADD INDEX IF NOT EXISTS —
-- MariaDB 10.4+). Aucune colonne ni donnée existante n'est modifiée.
-- ---------------------------------------------------------------------------

USE pgcc_utf8mb4;

ALTER TABLE pgcc_reg_pil
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

ALTER TABLE pgcc_reg_pil
    ADD INDEX IF NOT EXISTS idx_pil_annee (annee);
