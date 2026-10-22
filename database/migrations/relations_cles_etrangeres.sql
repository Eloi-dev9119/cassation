-- ============================================================
-- PGCC — Clés étrangères des tables d'historique (audit)
-- Base : pgcc_utf8mb4 (InnoDB, partagée v1/v2)
-- Généré le 2026-10-21 — ré-exécutable après vérification des orphelins
--
-- RÈGLES APPLIQUÉES
--   1. Colonne `xxx_id` / `id_xxx` → référence la table métier correspondante.
--      N.B. : dans ce schéma les clés primaires NE s'appellent PAS `id`
--      (elles s'appellent id_reg_pourvoi, id_client, …) : la référence pointe
--      donc la clé primaire RÉELLE de la table cible.
--      Toutes les autres colonnes id_* détectées sont les clés primaires de
--      leur PROPRE table (id_client, id_distribution, id_memoire, …) :
--      elles ne référencent rien et sont exclues.
--   2. Moteur : les 30 tables sont déjà en InnoDB (vérifié information_schema).
--   3. ON DELETE CASCADE / ON UPDATE CASCADE :
--      les tables pgcc_historique_* sont des lignes d'audit FILLES d'un
--      dossier (Insertion / Modification / Archivage). Les orphelins
--      existants (166 + 21 + 3 + 6) prouvent que des dossiers sont supprimés
--      sans toucher à leur historique : avec RESTRICT, ces suppressions
--      échoueraient et casseraient la v1, qui ne connaît pas ces clés.
--      → CASCADE est le seul choix compatible v1 : l'historique suit son
--      dossier. La variante RESTRICT est fournie en commentaire.
--   4. La variante « Concepteur phpMyAdmin » (relations internes pma__relation)
--      est fournie en fin de script, exécutable séparément.
--
-- TABLES CONCERNÉES (8 relations détectées)
--   Confirmées par les données (jointures vérifiées) :
--     pgcc_historique_reg_arrivee_pen.id_dossier  → pgcc_reg_arrivee_pen.id_reg_arrivee   (12 192/12 195 correspondent)
--     pgcc_historique_reg_pourvoi_pen.id_dossier  → pgcc_reg_pourvoi_pen.id_reg_pourvoi (8 998/9 164)
--     pgcc_historique_reg_pourvoi_civ.id_dossier  → pgcc_reg_pourvoi_civ.id_reg_pourvoi (2 471/2 492)
--     pgcc_historique_suivi_arrivee.id_dossier    → pgcc_reg_arrivee_pen.id_reg_arrivee   (12 255/12 261 — le suivi
--                                                                                       « 321 » suit un dossier du
--                                                                                       registre Arrivés, PAS pgcc_suivi_arrivee)
--   Déduites par analogie (tables vides, aucune donnée à vérifier) :
--     pgcc_historique_suivi_pen.id_dossier        → pgcc_reg_pourvoi_pen.id_reg_pourvoi  (suivi « 107 » → registre Pénaux)
--     pgcc_historique_suivi_civ.id_dossier        → pgcc_reg_pourvoi_civ.id_reg_pourvoi
--     pgcc_historique_pil.id_dossier              → pgcc_reg_pil.id_pil
--     pgcc_historique_enrolement.id_dossier       → pgcc_enrolement.id_enrolement
-- ============================================================

-- ------------------------------------------------------------
-- ÉTAPE 0 — Nettoyage des orphelins (OBLIGATOIRE avant ALTER :
-- sinon ADD FOREIGN KEY échoue avec l'erreur 1452). Seules les
-- lignes d'historique dont le dossier n'existe plus sont visées.
-- ------------------------------------------------------------
DELETE h FROM pgcc_historique_reg_arrivee_pen h
LEFT JOIN pgcc_reg_arrivee_pen p ON p.id_reg_arrivee = h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_arrivee IS NULL;

DELETE h FROM pgcc_historique_reg_pourvoi_pen h
LEFT JOIN pgcc_reg_pourvoi_pen p ON p.id_reg_pourvoi = h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL;

DELETE h FROM pgcc_historique_reg_pourvoi_civ h
LEFT JOIN pgcc_reg_pourvoi_civ p ON p.id_reg_pourvoi = h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL;

DELETE h FROM pgcc_historique_suivi_arrivee h
LEFT JOIN pgcc_reg_arrivee_pen p ON p.id_reg_arrivee = h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_arrivee IS NULL;

-- ------------------------------------------------------------
-- ÉTAPE 1 — Clés étrangères (index explicites puis contraintes)
-- ------------------------------------------------------------
ALTER TABLE pgcc_historique_reg_arrivee_pen
    ADD INDEX idx_hist_arrivee_pen_dossier (id_dossier),
    ADD CONSTRAINT fk_hist_arrivee_pen_dossier
        FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_arrivee_pen (id_reg_arrivee)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE pgcc_historique_reg_pourvoi_pen
    ADD INDEX idx_hist_pourvoi_pen_dossier (id_dossier),
    ADD CONSTRAINT fk_hist_pourvoi_pen_dossier
        FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pourvoi_pen (id_reg_pourvoi)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE pgcc_historique_reg_pourvoi_civ
    ADD INDEX idx_hist_pourvoi_civ_dossier (id_dossier),
    ADD CONSTRAINT fk_hist_pourvoi_civ_dossier
        FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pourvoi_civ (id_reg_pourvoi)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE pgcc_historique_suivi_arrivee
    ADD INDEX idx_hist_suivi_arrivee_dossier (id_dossier),
    ADD CONSTRAINT fk_hist_suivi_arrivee_dossier
        FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_arrivee_pen (id_reg_arrivee)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE pgcc_historique_suivi_pen
    ADD INDEX idx_hist_suivi_pen_dossier (id_dossier),
    ADD CONSTRAINT fk_hist_suivi_pen_dossier
        FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pourvoi_pen (id_reg_pourvoi)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE pgcc_historique_suivi_civ
    ADD INDEX idx_hist_suivi_civ_dossier (id_dossier),
    ADD CONSTRAINT fk_hist_suivi_civ_dossier
        FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pourvoi_civ (id_reg_pourvoi)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE pgcc_historique_pil
    ADD INDEX idx_hist_pil_dossier (id_dossier),
    ADD CONSTRAINT fk_hist_pil_dossier
        FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pil (id_pil)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE pgcc_historique_enrolement
    ADD INDEX idx_hist_enrolement_dossier (id_dossier),
    ADD CONSTRAINT fk_hist_enrolement_dossier
        FOREIGN KEY (id_dossier) REFERENCES pgcc_enrolement (id_enrolement)
        ON DELETE CASCADE ON UPDATE CASCADE;

-- ------------------------------------------------------------
-- VARIANTE SÉCURITAIRE — ON DELETE RESTRICT
-- (interdit de supprimer un dossier possédant un historique ;
--  ⚠ retourne en erreur les DELETE de dossiers faits par la v1 :
--  à n'utiliser que si la suppression de dossiers est interdite)
-- ------------------------------------------------------------
-- ALTER TABLE pgcc_historique_reg_pourvoi_pen
--     ADD CONSTRAINT fk_hist_pourvoi_pen_dossier
--     FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pourvoi_pen (id_reg_pourvoi)
--     ON DELETE RESTRICT ON UPDATE RESTRICT;
-- (idem pour les 7 autres)

-- ------------------------------------------------------------
-- VÉRIFICATION après exécution
-- ------------------------------------------------------------
-- Les 8 contraintes doivent apparaître :
-- SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME,
--        REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
-- FROM information_schema.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = 'pgcc_utf8mb4' AND REFERENCED_TABLE_NAME IS NOT NULL;
--
-- Plus aucun orphelin ne doit exister :
-- SELECT COUNT(*) FROM pgcc_historique_reg_pourvoi_pen h
-- LEFT JOIN pgcc_reg_pourvoi_pen p ON p.id_reg_pourvoi = h.id_dossier
-- WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL;

-- ============================================================
-- CONCEPTEUR phpMyAdmin (optionnel, exécutable seul)
-- Les clés InnoDB ci-dessus apparaissent AUTOMATIQUEMENT dans le
-- Concepteur. Les inserts ci-dessous déclarent en plus des
-- « relations internes » (infrastructure de configuration
-- phpMyAdmin, base `phpmyadmin`) pour que les liaisons soient
-- visibles dans le Concepteur et l'export relationnel même sans
-- exécuter les ALTER ci-dessus. XAMPP livré avec pma__relation actif.
-- ============================================================
-- INSERT INTO `phpmyadmin`.`pma__relation`
--     (master_db, master_table, master_field, foreign_db, foreign_table, foreign_field) VALUES
-- ('pgcc_utf8mb4', 'pgcc_historique_reg_arrivee_pen', 'id_dossier', 'pgcc_utf8mb4', 'pgcc_reg_arrivee_pen',  'id_reg_arrivee'),
-- ('pgcc_utf8mb4', 'pgcc_historique_reg_pourvoi_pen', 'id_dossier', 'pgcc_utf8mb4', 'pgcc_reg_pourvoi_pen', 'id_reg_pourvoi'),
-- ('pgcc_utf8mb4', 'pgcc_historique_reg_pourvoi_civ', 'id_dossier', 'pgcc_utf8mb4', 'pgcc_reg_pourvoi_civ', 'id_reg_pourvoi'),
-- ('pgcc_utf8mb4', 'pgcc_historique_suivi_arrivee',   'id_dossier', 'pgcc_utf8mb4', 'pgcc_reg_arrivee_pen',  'id_reg_arrivee'),
-- ('pgcc_utf8mb4', 'pgcc_historique_suivi_pen',       'id_dossier', 'pgcc_utf8mb4', 'pgcc_reg_pourvoi_pen', 'id_reg_pourvoi'),
-- ('pgcc_utf8mb4', 'pgcc_historique_suivi_civ',       'id_dossier', 'pgcc_utf8mb4', 'pgcc_reg_pourvoi_civ', 'id_reg_pourvoi'),
-- ('pgcc_utf8mb4', 'pgcc_historique_pil',             'id_dossier', 'pgcc_utf8mb4', 'pgcc_reg_pil',          'id_pil'),
-- ('pgcc_utf8mb4', 'pgcc_historique_enrolement',      'id_dossier', 'pgcc_utf8mb4', 'pgcc_enrolement',       'id_enrolement');
