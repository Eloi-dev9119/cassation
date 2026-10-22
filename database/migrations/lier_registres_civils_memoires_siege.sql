-- Liens explicites entre memoires, pourvois et registre du Siege.
-- A executer une fois sur pgcc_utf8mb4 apres sauvegarde de la base.
-- Aucun dossier n'est supprime; seuls les numeros de dossier uniques sont repris automatiquement.
-- Les cles des registres source sont des BIGINT; les colonnes liees doivent avoir le meme type.
USE pgcc_utf8mb4;
ALTER TABLE pgcc_reg_memoires
    ADD COLUMN IF NOT EXISTS id_reg_pourvoi_pen BIGINT(255) NULL,
    ADD COLUMN IF NOT EXISTS id_reg_pourvoi_civ BIGINT(255) NULL;
ALTER TABLE siege_cassation_civ
    ADD COLUMN IF NOT EXISTS id_reg_pourvoi_civ BIGINT(255) NULL;

-- Corrige aussi les colonnes INT laissees par une tentative precedente.
ALTER TABLE pgcc_reg_memoires
    MODIFY COLUMN id_reg_pourvoi_pen BIGINT(255) NULL,
    MODIFY COLUMN id_reg_pourvoi_civ BIGINT(255) NULL;
ALTER TABLE siege_cassation_civ
    MODIFY COLUMN id_reg_pourvoi_civ BIGINT(255) NULL;

UPDATE pgcc_reg_memoires m
JOIN (SELECT num_dossier_PGCS, MIN(id_reg_pourvoi) AS id_dossier FROM pgcc_reg_pourvoi_pen GROUP BY num_dossier_PGCS HAVING COUNT(*) = 1) p
  ON p.num_dossier_PGCS = m.num_dossier_PGCS
SET m.id_reg_pourvoi_pen = p.id_dossier
WHERE m.origine = 'penal' AND m.id_reg_pourvoi_pen IS NULL;
UPDATE pgcc_reg_memoires m
JOIN (SELECT num_dossier_PGCS, MIN(id_reg_pourvoi) AS id_dossier FROM pgcc_reg_pourvoi_civ GROUP BY num_dossier_PGCS HAVING COUNT(*) = 1) c
  ON c.num_dossier_PGCS = m.num_dossier_PGCS
SET m.id_reg_pourvoi_civ = c.id_dossier
WHERE m.origine = 'civil' AND m.id_reg_pourvoi_civ IS NULL;
UPDATE siege_cassation_civ s
JOIN (SELECT num_dossier_PGCS, MIN(id_reg_pourvoi) AS id_dossier FROM pgcc_reg_pourvoi_civ GROUP BY num_dossier_PGCS HAVING COUNT(*) = 1) c
  ON c.num_dossier_PGCS = s.num_dossier
SET s.id_reg_pourvoi_civ = c.id_dossier
WHERE s.id_reg_pourvoi_civ IS NULL;

ALTER TABLE pgcc_reg_memoires
    ADD INDEX IF NOT EXISTS idx_memoire_pourvoi_pen (id_reg_pourvoi_pen),
    ADD INDEX IF NOT EXISTS idx_memoire_pourvoi_civ (id_reg_pourvoi_civ);
ALTER TABLE siege_cassation_civ
    ADD INDEX IF NOT EXISTS idx_siege_pourvoi_civ (id_reg_pourvoi_civ);

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_memoire_pourvoi_pen') = 0,
 'ALTER TABLE pgcc_reg_memoires ADD CONSTRAINT fk_memoire_pourvoi_pen FOREIGN KEY (id_reg_pourvoi_pen) REFERENCES pgcc_reg_pourvoi_pen (id_reg_pourvoi) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_lien_stmt FROM @ddl; EXECUTE pgcc_lien_stmt; DEALLOCATE PREPARE pgcc_lien_stmt;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_memoire_pourvoi_civ') = 0,
 'ALTER TABLE pgcc_reg_memoires ADD CONSTRAINT fk_memoire_pourvoi_civ FOREIGN KEY (id_reg_pourvoi_civ) REFERENCES pgcc_reg_pourvoi_civ (id_reg_pourvoi) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_lien_stmt FROM @ddl; EXECUTE pgcc_lien_stmt; DEALLOCATE PREPARE pgcc_lien_stmt;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_siege_pourvoi_civ') = 0,
 'ALTER TABLE siege_cassation_civ ADD CONSTRAINT fk_siege_pourvoi_civ FOREIGN KEY (id_reg_pourvoi_civ) REFERENCES pgcc_reg_pourvoi_civ (id_reg_pourvoi) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT 1');
PREPARE pgcc_lien_stmt FROM @ddl; EXECUTE pgcc_lien_stmt; DEALLOCATE PREPARE pgcc_lien_stmt;

-- Ces compteurs signalent les lignes anciennes a associer manuellement si elles ne sont pas nulles.
SELECT origine, COUNT(*) AS memoires_a_associer FROM pgcc_reg_memoires
WHERE (origine = 'penal' AND id_reg_pourvoi_pen IS NULL) OR (origine = 'civil' AND id_reg_pourvoi_civ IS NULL)
GROUP BY origine;
SELECT COUNT(*) AS sieges_a_associer FROM siege_cassation_civ WHERE id_reg_pourvoi_civ IS NULL;
