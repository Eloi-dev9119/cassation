-- ---------------------------------------------------------------------------
-- Registre des Mémoires — nouveau module PGCC v2
--
-- Table créée dans la base commune « pgcc » : les applications PGCC v1
-- (historique) et PGCC v2 partagent les mêmes données.
--
-- Un mémoire est une pièce de procédure déposée dans le cadre d'un
-- pourvoi (ampliatif : demandeur / défense : défendeur).
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS pgcc_reg_memoires (
    id_memoire                    INT          NOT NULL AUTO_INCREMENT,
    num_ordre                     INT          NOT NULL,
    annee                         INT          NOT NULL,
    date_insertion                DATE         NOT NULL,
    type_memoire                  ENUM('ampliatif','defense') NOT NULL,
    origine                       ENUM('penal','civil')       NOT NULL,
    num_dossier_PGCS              VARCHAR(100) NOT NULL,
    parties                       VARCHAR(255) NULL,
    avocat                        VARCHAR(200) NULL,
    date_notification             DATE         NULL,
    date_depot                    DATE         NULL,
    date_transmission_secretaire  DATE         NULL,
    rapporteur                    VARCHAR(200) NULL,
    observation                   TEXT         NULL,
    etat                          VARCHAR(50)  NOT NULL DEFAULT 'depose',
    archiver                      TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id_memoire),
    INDEX idx_memoire_dossier (num_dossier_PGCS),
    INDEX idx_memoire_annee (annee),
    INDEX idx_memoire_etat (etat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
