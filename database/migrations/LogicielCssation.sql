-- PGCC v2 — migration SQL regroupée
--
-- Ce fichier rassemble les évolutions SQL nécessaires à PGCC v2 :
-- registre des mémoires, champs hérités, colonnes d'audit et relations.
-- Il s'applique sur une base PGCC existante (v1/v2), et ne crée pas les
-- anciennes tables métier qui doivent déjà être présentes.
--
-- Avant import : sauvegarder la base et vérifier que la cible est
-- pgcc_utf8mb4. Compatible MariaDB 10.4+ (XAMPP).
-- Les enregistrements historiques orphelins sont conservés. Les clés
-- étrangères correspondantes sont ajoutées seulement si les données sont
-- cohérentes ; les éventuels cas à corriger sont affichés en fin de script.

USE pgcc_utf8mb4;

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- 1. Registre des mémoires
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pgcc_reg_memoires (
    id_memoire                    INT NOT NULL AUTO_INCREMENT,
    num_ordre                     INT NOT NULL,
    annee                         INT NOT NULL,
    date_insertion                DATE NOT NULL,
    type_memoire                  ENUM('ampliatif', 'defense') NOT NULL,
    origine                       ENUM('penal', 'civil') NOT NULL,
    num_dossier_PGCS              VARCHAR(100) NOT NULL,
    parties                       VARCHAR(255) NULL,
    avocat                        VARCHAR(200) NULL,
    date_notification             DATE NULL,
    date_depot                    DATE NULL,
    date_transmission_secretaire  DATE NULL,
    rapporteur                    VARCHAR(200) NULL,
    observation                   TEXT NULL,
    etat                          VARCHAR(50) NOT NULL DEFAULT 'depose',
    archiver                      TINYINT(1) NOT NULL DEFAULT 0,
    id_reg_pourvoi_pen            BIGINT NULL,
    id_reg_pourvoi_civ            BIGINT NULL,
    PRIMARY KEY (id_memoire),
    INDEX idx_memoire_dossier (num_dossier_PGCS),
    INDEX idx_memoire_annee (annee),
    INDEX idx_memoire_etat (etat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Les deux colonnes sont ajoutées si la table des mémoires existait déjà.
ALTER TABLE pgcc_reg_memoires
    ADD COLUMN IF NOT EXISTS id_reg_pourvoi_pen BIGINT NULL,
    ADD COLUMN IF NOT EXISTS id_reg_pourvoi_civ BIGINT NULL;

-- Le registre du siège est historique ; seule sa colonne de liaison est ajoutée.
ALTER TABLE siege_cassation_civ
    ADD COLUMN IF NOT EXISTS id_reg_pourvoi_civ BIGINT NULL;

-- Répare le type des colonnes créées en INT lors d'une ancienne tentative.
SET @ddl = IF(
    (SELECT DATA_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pgcc_reg_memoires'
       AND COLUMN_NAME = 'id_reg_pourvoi_pen' LIMIT 1) = 'bigint',
    'SELECT 1',
    'ALTER TABLE pgcc_reg_memoires MODIFY COLUMN id_reg_pourvoi_pen BIGINT NULL'
);
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT DATA_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pgcc_reg_memoires'
       AND COLUMN_NAME = 'id_reg_pourvoi_civ' LIMIT 1) = 'bigint',
    'SELECT 1',
    'ALTER TABLE pgcc_reg_memoires MODIFY COLUMN id_reg_pourvoi_civ BIGINT NULL'
);
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT DATA_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'siege_cassation_civ'
       AND COLUMN_NAME = 'id_reg_pourvoi_civ' LIMIT 1) = 'bigint',
    'SELECT 1',
    'ALTER TABLE siege_cassation_civ MODIFY COLUMN id_reg_pourvoi_civ BIGINT NULL'
);
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

-- Rattache automatiquement les mémoires et dossiers du siège lorsque le
-- numéro PGCS ne correspond qu'à un seul pourvoi. Les cas ambigus restent
-- à traiter depuis les formulaires.
UPDATE pgcc_reg_memoires m
JOIN (
    SELECT num_dossier_PGCS, MIN(id_reg_pourvoi) AS id_dossier
    FROM pgcc_reg_pourvoi_pen
    GROUP BY num_dossier_PGCS HAVING COUNT(*) = 1
) p ON p.num_dossier_PGCS = m.num_dossier_PGCS
SET m.id_reg_pourvoi_pen = p.id_dossier
WHERE m.origine = 'penal' AND m.id_reg_pourvoi_pen IS NULL;

UPDATE pgcc_reg_memoires m
JOIN (
    SELECT num_dossier_PGCS, MIN(id_reg_pourvoi) AS id_dossier
    FROM pgcc_reg_pourvoi_civ
    GROUP BY num_dossier_PGCS HAVING COUNT(*) = 1
) c ON c.num_dossier_PGCS = m.num_dossier_PGCS
SET m.id_reg_pourvoi_civ = c.id_dossier
WHERE m.origine = 'civil' AND m.id_reg_pourvoi_civ IS NULL;

UPDATE siege_cassation_civ s
JOIN (
    SELECT num_dossier_PGCS, MIN(id_reg_pourvoi) AS id_dossier
    FROM pgcc_reg_pourvoi_civ
    GROUP BY num_dossier_PGCS HAVING COUNT(*) = 1
) c ON c.num_dossier_PGCS = s.num_dossier
SET s.id_reg_pourvoi_civ = c.id_dossier
WHERE s.id_reg_pourvoi_civ IS NULL;

-- ---------------------------------------------------------------------------
-- 2. Champ manquant du formulaire de dessaisissement (compatibilité v1)
-- ---------------------------------------------------------------------------
ALTER TABLE pgcc_reg_des
    ADD COLUMN IF NOT EXISTS date_detention DATE NULL DEFAULT NULL AFTER detention;

-- ---------------------------------------------------------------------------
-- 3. Colonnes de traçabilité
-- ---------------------------------------------------------------------------
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

ALTER TABLE pgcc_reg_memoires
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

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

ALTER TABLE pgcc_distribution_pg
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

ALTER TABLE pgcc_client
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

ALTER TABLE siege_cassation_civ
    ADD COLUMN IF NOT EXISTS date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS cree_par VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS modifie_par VARCHAR(100) NULL;

-- ---------------------------------------------------------------------------
-- 4. Relations entre mémoires, pourvois civils et pénaux, et siège
-- ---------------------------------------------------------------------------
SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_memoire_pourvoi_pen') = 0
    AND (SELECT COUNT(*) FROM pgcc_reg_memoires m
         LEFT JOIN pgcc_reg_pourvoi_pen p ON p.id_reg_pourvoi = m.id_reg_pourvoi_pen
         WHERE m.id_reg_pourvoi_pen IS NOT NULL AND p.id_reg_pourvoi IS NULL) = 0,
    'ALTER TABLE pgcc_reg_memoires ADD CONSTRAINT fk_memoire_pourvoi_pen FOREIGN KEY (id_reg_pourvoi_pen) REFERENCES pgcc_reg_pourvoi_pen (id_reg_pourvoi) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_memoire_pourvoi_civ') = 0
    AND (SELECT COUNT(*) FROM pgcc_reg_memoires m
         LEFT JOIN pgcc_reg_pourvoi_civ p ON p.id_reg_pourvoi = m.id_reg_pourvoi_civ
         WHERE m.id_reg_pourvoi_civ IS NOT NULL AND p.id_reg_pourvoi IS NULL) = 0,
    'ALTER TABLE pgcc_reg_memoires ADD CONSTRAINT fk_memoire_pourvoi_civ FOREIGN KEY (id_reg_pourvoi_civ) REFERENCES pgcc_reg_pourvoi_civ (id_reg_pourvoi) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_siege_pourvoi_civ') = 0
    AND (SELECT COUNT(*) FROM siege_cassation_civ s
         LEFT JOIN pgcc_reg_pourvoi_civ p ON p.id_reg_pourvoi = s.id_reg_pourvoi_civ
         WHERE s.id_reg_pourvoi_civ IS NOT NULL AND p.id_reg_pourvoi IS NULL) = 0,
    'ALTER TABLE siege_cassation_civ ADD CONSTRAINT fk_siege_pourvoi_civ FOREIGN KEY (id_reg_pourvoi_civ) REFERENCES pgcc_reg_pourvoi_civ (id_reg_pourvoi) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

-- ---------------------------------------------------------------------------
-- 5. Relations des tables d'historique
--
-- Une contrainte n'est créée que si aucun historique orphelin ne subsiste.
-- Cela évite de supprimer silencieusement des traces métier.
-- ---------------------------------------------------------------------------
SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_hist_arrivee_pen_dossier') = 0
    AND (SELECT COUNT(*) FROM pgcc_historique_reg_arrivee_pen h LEFT JOIN pgcc_reg_arrivee_pen p ON p.id_reg_arrivee=h.id_dossier WHERE h.id_dossier IS NOT NULL AND p.id_reg_arrivee IS NULL) = 0,
    'ALTER TABLE pgcc_historique_reg_arrivee_pen ADD CONSTRAINT fk_hist_arrivee_pen_dossier FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_arrivee_pen (id_reg_arrivee) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_hist_pourvoi_pen_dossier') = 0
    AND (SELECT COUNT(*) FROM pgcc_historique_reg_pourvoi_pen h LEFT JOIN pgcc_reg_pourvoi_pen p ON p.id_reg_pourvoi=h.id_dossier WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL) = 0,
    'ALTER TABLE pgcc_historique_reg_pourvoi_pen ADD CONSTRAINT fk_hist_pourvoi_pen_dossier FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pourvoi_pen (id_reg_pourvoi) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_hist_pourvoi_civ_dossier') = 0
    AND (SELECT COUNT(*) FROM pgcc_historique_reg_pourvoi_civ h LEFT JOIN pgcc_reg_pourvoi_civ p ON p.id_reg_pourvoi=h.id_dossier WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL) = 0,
    'ALTER TABLE pgcc_historique_reg_pourvoi_civ ADD CONSTRAINT fk_hist_pourvoi_civ_dossier FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pourvoi_civ (id_reg_pourvoi) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_hist_suivi_arrivee_dossier') = 0
    AND (SELECT COUNT(*) FROM pgcc_historique_suivi_arrivee h LEFT JOIN pgcc_reg_arrivee_pen p ON p.id_reg_arrivee=h.id_dossier WHERE h.id_dossier IS NOT NULL AND p.id_reg_arrivee IS NULL) = 0,
    'ALTER TABLE pgcc_historique_suivi_arrivee ADD CONSTRAINT fk_hist_suivi_arrivee_dossier FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_arrivee_pen (id_reg_arrivee) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_hist_suivi_pen_dossier') = 0
    AND (SELECT COUNT(*) FROM pgcc_historique_suivi_pen h LEFT JOIN pgcc_reg_pourvoi_pen p ON p.id_reg_pourvoi=h.id_dossier WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL) = 0,
    'ALTER TABLE pgcc_historique_suivi_pen ADD CONSTRAINT fk_hist_suivi_pen_dossier FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pourvoi_pen (id_reg_pourvoi) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_hist_suivi_civ_dossier') = 0
    AND (SELECT COUNT(*) FROM pgcc_historique_suivi_civ h LEFT JOIN pgcc_reg_pourvoi_civ p ON p.id_reg_pourvoi=h.id_dossier WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL) = 0,
    'ALTER TABLE pgcc_historique_suivi_civ ADD CONSTRAINT fk_hist_suivi_civ_dossier FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pourvoi_civ (id_reg_pourvoi) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_hist_pil_dossier') = 0
    AND (SELECT COUNT(*) FROM pgcc_historique_pil h LEFT JOIN pgcc_reg_pil p ON p.id_pil=h.id_dossier WHERE h.id_dossier IS NOT NULL AND p.id_pil IS NULL) = 0,
    'ALTER TABLE pgcc_historique_pil ADD CONSTRAINT fk_hist_pil_dossier FOREIGN KEY (id_dossier) REFERENCES pgcc_reg_pil (id_pil) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

SET @ddl = IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_hist_enrolement_dossier') = 0
    AND (SELECT COUNT(*) FROM pgcc_historique_enrolement h LEFT JOIN pgcc_enrolement e ON e.id_enrolement=h.id_dossier WHERE h.id_dossier IS NOT NULL AND e.id_enrolement IS NULL) = 0,
    'ALTER TABLE pgcc_historique_enrolement ADD CONSTRAINT fk_hist_enrolement_dossier FOREIGN KEY (id_dossier) REFERENCES pgcc_enrolement (id_enrolement) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_stmt FROM @ddl; EXECUTE pgcc_stmt; DEALLOCATE PREPARE pgcc_stmt;

-- ---------------------------------------------------------------------------
-- 6. Contrôles après migration
-- ---------------------------------------------------------------------------
SELECT 'Mémoires pénales sans lien' AS controle, COUNT(*) AS lignes
FROM pgcc_reg_memoires WHERE origine='penal' AND id_reg_pourvoi_pen IS NULL;

SELECT 'Mémoires civiles sans lien' AS controle, COUNT(*) AS lignes
FROM pgcc_reg_memoires WHERE origine='civil' AND id_reg_pourvoi_civ IS NULL;

SELECT 'Dossiers du siège sans lien civil' AS controle, COUNT(*) AS lignes
FROM siege_cassation_civ WHERE id_reg_pourvoi_civ IS NULL;

SELECT 'Historiques arrivée pénale orphelins' AS controle, COUNT(*) AS lignes
FROM pgcc_historique_reg_arrivee_pen h LEFT JOIN pgcc_reg_arrivee_pen p ON p.id_reg_arrivee=h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_arrivee IS NULL
UNION ALL
SELECT 'Historiques pourvoi pénal orphelins', COUNT(*)
FROM pgcc_historique_reg_pourvoi_pen h LEFT JOIN pgcc_reg_pourvoi_pen p ON p.id_reg_pourvoi=h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL
UNION ALL
SELECT 'Historiques pourvoi civil orphelins', COUNT(*)
FROM pgcc_historique_reg_pourvoi_civ h LEFT JOIN pgcc_reg_pourvoi_civ p ON p.id_reg_pourvoi=h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL
UNION ALL
SELECT 'Historiques suivi arrivée orphelins', COUNT(*)
FROM pgcc_historique_suivi_arrivee h LEFT JOIN pgcc_reg_arrivee_pen p ON p.id_reg_arrivee=h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_arrivee IS NULL
UNION ALL
SELECT 'Historiques suivi pénal orphelins', COUNT(*)
FROM pgcc_historique_suivi_pen h LEFT JOIN pgcc_reg_pourvoi_pen p ON p.id_reg_pourvoi=h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL
UNION ALL
SELECT 'Historiques suivi civil orphelins', COUNT(*)
FROM pgcc_historique_suivi_civ h LEFT JOIN pgcc_reg_pourvoi_civ p ON p.id_reg_pourvoi=h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_reg_pourvoi IS NULL
UNION ALL
SELECT 'Historiques PIL orphelins', COUNT(*)
FROM pgcc_historique_pil h LEFT JOIN pgcc_reg_pil p ON p.id_pil=h.id_dossier
WHERE h.id_dossier IS NOT NULL AND p.id_pil IS NULL
UNION ALL
SELECT 'Historiques enrôlement orphelins', COUNT(*)
FROM pgcc_historique_enrolement h LEFT JOIN pgcc_enrolement e ON e.id_enrolement=h.id_dossier
WHERE h.id_dossier IS NOT NULL AND e.id_enrolement IS NULL;

SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME,
       REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;
