-- ============================================================
-- PGCC v2 — patch T92 : champs du formulaire v1 manquants
-- À exécuter UNE FOIS dans phpMyAdmin (base pgcc_utf8mb4).
-- Idempotent : ADD COLUMN IF NOT EXISTS ne réinsère pas si la
-- colonne existe déjà (MariaDB 10.0+ — XAMPP en est équipé).
-- Sans danger pour les données existantes (colonne NULL, vide).
-- ============================================================

-- Registre des Déssaisissements : « Date de détention » du formulaire v1
ALTER TABLE pgcc_reg_des
    ADD COLUMN IF NOT EXISTS date_detention DATE NULL DEFAULT '0000-00-00'
    AFTER detention;

-- Vérification attendue (une ligne avec date_detention) :
-- SHOW COLUMNS FROM pgcc_reg_des LIKE 'date_detention';
