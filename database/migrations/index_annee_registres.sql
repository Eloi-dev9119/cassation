-- Optimisation : index sur la colonne annee des grands registres.
-- Sert les filtres « par année » des listes et le GROUP BY annee du
-- graphique du tableau de bord, au-delà de la capacité du buffer pool.

USE pgcc_utf8mb4;
SET NAMES utf8mb4;

CREATE INDEX idx_annee ON pgcc_reg_arrivee_pen  (annee);
CREATE INDEX idx_annee ON pgcc_reg_pourvoi_pen  (annee);
CREATE INDEX idx_annee ON pgcc_reg_pourvoi_civ  (annee);
