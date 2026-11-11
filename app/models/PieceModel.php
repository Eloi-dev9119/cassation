<?php
/**
 * Modèle des pièces jointes : documents scannés rattachés à un mémoire ou
 * à un dossier de registre (mémoire signé, arrêt attaqué, conclusion…).
 *
 * Les fichiers sont conservés hors base, dans storage/pieces/, sous un nom
 * aléatoire ; seul le nom de fichier est enregistré en base. Le
 * téléchargement passe toujours par PieceController (le dossier storage/
 * n'est jamais servi directement par le .htaccess).
 */

declare(strict_types=1);

final class PieceModel
{
    /** Extensions acceptées (dépôt de documents administratifs). */
    public const EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'doc', 'docx', 'xls', 'xlsx'];

    /** Taille maximale d'un fichier, en octets (10 Mo). */
    public const TAILLE_MAX = 10 * 1024 * 1024;

    /** Types MIME associés aux extensions (pour l'envoi au navigateur). */
    private const MIMES = [
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * Pièces rattachées à une entité ('memoire', 'dossier_penaux', …),
     * la plus récente d'abord.
     */
    public function pourEntite(string $entite, int $entiteId): array
    {
        $req = Database::connexion()->prepare(
            'SELECT * FROM pgcc_pieces_jointes
             WHERE entite = :entite AND entite_id = :id
             ORDER BY date_ajout DESC, id_piece DESC'
        );
        $req->execute(['entite' => $entite, 'id' => $entiteId]);
        return $req->fetchAll();
    }

    /**
     * Enregistre un fichier reçu par POST. Retourne l'identifiant de la
     * pièce créée ; lève une RuntimeException avec un message présentable
     * si le dépôt est refusé.
     */
    public function ajouter(string $entite, int $entiteId, array $fichier): int
    {
        $erreur = (int) ($fichier['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($erreur !== UPLOAD_ERR_OK) {
            throw new RuntimeException(match ($erreur) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille autorisée par le serveur.',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n’a été choisi.',
                default => 'Le téléversement du fichier a échoué (code ' . $erreur . ').',
            });
        }
        if ((int) ($fichier['size'] ?? 0) > self::TAILLE_MAX) {
            throw new RuntimeException('Le fichier dépasse la taille maximale de 10 Mo.');
        }

        $nomOriginal = (string) ($fichier['name'] ?? '');
        $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONS, true)) {
            throw new RuntimeException('Type de fichier non autorisé. Formats acceptés : '
                . implode(', ', self::EXTENSIONS) . '.');
        }
        if (!is_uploaded_file((string) ($fichier['tmp_name'] ?? ''))) {
            throw new RuntimeException('Le fichier reçu est invalide.');
        }

        $dossier = self::dossierStockage();
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new RuntimeException('Le dossier de stockage des pièces est inaccessible.');
        }

        // Nom aléatoire : jamais de données fournies par l'utilisateur dans
        // le chemin sur le disque ; l'extension est issue de la whitelist.
        $nomStocke = 'piece_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = $dossier . DIRECTORY_SEPARATOR . $nomStocke;
        if (!move_uploaded_file((string) $fichier['tmp_name'], $destination)) {
            throw new RuntimeException('L’enregistrement du fichier sur le disque a échoué.');
        }

        $req = Database::connexion()->prepare(
            'INSERT INTO pgcc_pieces_jointes
                (entite, entite_id, nom_original, fichier, taille, mime, ajoute_par)
             VALUES (:entite, :id, :nom, :fichier, :taille, :mime, :auteur)'
        );
        $req->execute([
            'entite'  => $entite,
            'id'      => $entiteId,
            'nom'     => mb_substr($nomOriginal, 0, 255),
            'fichier' => $nomStocke,
            'taille'  => (int) ($fichier['size'] ?? 0),
            'mime'    => self::MIMES[$extension] ?? 'application/octet-stream',
            'auteur'  => utilisateur_login(),
        ]);
        return (int) Database::connexion()->lastInsertId();
    }

    /** Une pièce par son identifiant (null si inconnue). */
    public function trouver(int $id): ?array
    {
        $req = Database::connexion()->prepare(
            'SELECT * FROM pgcc_pieces_jointes WHERE id_piece = :id'
        );
        $req->execute(['id' => $id]);
        $piece = $req->fetch();
        return $piece === false ? null : $piece;
    }

    /** Supprime la pièce et son fichier ; retourne false si elle n'existait pas. */
    public function supprimer(int $id): bool
    {
        $piece = $this->trouver($id);
        if ($piece === null) {
            return false;
        }
        $chemin = self::dossierStockage() . DIRECTORY_SEPARATOR . basename((string) $piece['fichier']);
        if (is_file($chemin)) {
            @unlink($chemin);
        }
        $req = Database::connexion()->prepare('DELETE FROM pgcc_pieces_jointes WHERE id_piece = :id');
        $req->execute(['id' => $id]);
        return true;
    }

    /** Chemin complet du fichier stocké (basename : jamais de traversal). */
    public function cheminFichier(array $piece): string
    {
        return self::dossierStockage() . DIRECTORY_SEPARATOR . basename((string) $piece['fichier']);
    }

    /** Dossier de stockage des pièces, sous storage/. */
    public static function dossierStockage(): string
    {
        return APP_CHEMIN . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'pieces';
    }

    /** Taille présentable (ko/Mo). */
    public static function tailleLisible(int $octets): string
    {
        return $octets >= 1024 * 1024
            ? number_format($octets / (1024 * 1024), 1, ',', ' ') . ' Mo'
            : max(1, (int) round($octets / 1024)) . ' ko';
    }
}
