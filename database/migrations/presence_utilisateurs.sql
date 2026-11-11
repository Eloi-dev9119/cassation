-- Migration : suivi de présence des utilisateurs connectés.
-- Une ligne par utilisateur ; derniere_activite est rafraîchie à chaque
-- requête HTTP authentifiée (voir index.php). Un utilisateur est considéré
-- « en ligne » si son activité date de moins de PRESENCE_DUREE secondes
-- (config/app.php).

USE pgcc_utf8mb4;

CREATE TABLE IF NOT EXISTS pgcc_presence (
    id_client         BIGINT NOT NULL,
    derniere_activite DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_client),
    CONSTRAINT fk_presence_client
        FOREIGN KEY (id_client) REFERENCES pgcc_client (id_client)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Présence : dernière activité HTTP par utilisateur';
