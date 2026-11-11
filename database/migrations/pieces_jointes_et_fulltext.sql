-- ---------------------------------------------------------------------------
-- Pièces jointes + recherche plein texte (PGCC v2).
--
-- 1) Table pgcc_pieces_jointes : documents scannés (mémoires signés, arrêts
--    attaqués, conclusions) rattachés à un mémoire ou à un dossier de
--    registre. Les fichiers sont stockés hors base dans storage/pieces/ ;
--    le téléchargement passe obligatoirement par le contrôleur (le dossier
--    storage/ est inaccessible directement via le .htaccess).
--
-- 2) Index FULLTEXT sur les colonnes de recherche de chaque registre et du
--    registre des mémoires : la recherche plein texte remplace le
--    LIKE '%…%' (qui ne peut utiliser aucun index) sur les gros volumes.
--    L'application replie automatiquement sur LIKE si un index manque.
--
-- Migration rejouable (IF NOT EXISTS). Syntaxe « ADD FULLTEXT INDEX IF NOT
-- EXISTS » : MariaDB 10.1+ (XAMPP). Sous MySQL, retirer « IF NOT EXISTS »
-- et ignorer l'erreur 1061 si l'index existe déjà.
-- ---------------------------------------------------------------------------

USE pgcc_utf8mb4;
SET NAMES utf8mb4;

-- 1) Pièces jointes ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS pgcc_pieces_jointes (
    id_piece      INT           NOT NULL AUTO_INCREMENT,
    entite        VARCHAR(30)   NOT NULL,  -- 'memoire', 'dossier_penaux', …
    entite_id     INT           NOT NULL,  -- clé de la ligne rattachée
    nom_original  VARCHAR(255)  NOT NULL,  -- nom du fichier au dépôt
    fichier       VARCHAR(255)  NOT NULL,  -- nom stocké (aléatoire) sur le disque
    taille        INT           NOT NULL,  -- octets
    mime          VARCHAR(100)  NOT NULL,
    date_ajout    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ajoute_par    VARCHAR(100)  NULL,
    PRIMARY KEY (id_piece),
    INDEX idx_piece_entite (entite, entite_id),
    INDEX idx_piece_fichier (fichier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Recherche plein texte ---------------------------------------------------

ALTER TABLE pgcc_reg_memoires
    ADD FULLTEXT INDEX IF NOT EXISTS ft_memoires_recherche
        (num_dossier_PGCS, parties, avocat, juridiction, observation);

ALTER TABLE pgcc_reg_pourvoi_pen
    ADD FULLTEXT INDEX IF NOT EXISTS ft_pen_recherche
        (num_dossier_PGCS, cassationnaire, juridiction, inculpation, arret_attaque);

ALTER TABLE pgcc_reg_pourvoi_civ
    ADD FULLTEXT INDEX IF NOT EXISTS ft_civ_recherche
        (num_dossier_PGCS, cassationnaire, arret_attaque_et_date);

ALTER TABLE pgcc_reg_arrivee_pen
    ADD FULLTEXT INDEX IF NOT EXISTS ft_arrivee_recherche
        (num_dossier_PGCS, juridiction, bordereaux_arrivee, num_dossier_et_pers_concernee);

ALTER TABLE pgcc_reg_des
    ADD FULLTEXT INDEX IF NOT EXISTS ft_des_recherche
        (num_dossier_PGCS, cassationnaire, inculpation, arret_attaque);

ALTER TABLE siege_cassation_civ
    ADD FULLTEXT INDEX IF NOT EXISTS ft_siege_recherche
        (num_dossier, nom_demandeur, nom_defendeur, rapporteur);

ALTER TABLE pgcc_reg_pil
    ADD FULLTEXT INDEX IF NOT EXISTS ft_pil_recherche
        (num_pil, requete, magistrat_instructeur, lettre_GSMJ);
