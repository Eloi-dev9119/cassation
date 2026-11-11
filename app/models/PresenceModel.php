<?php
/**
 * Modèle de présence : horodatage de la dernière activité HTTP de chaque
 * utilisateur connecté (table pgcc_presence). Un utilisateur est considéré
 * « en ligne » tant que son activité date de moins de PRESENCE_DUREE
 * secondes (config/app.php).
 */

declare(strict_types=1);

final class PresenceModel
{
    /** Enregistre (ou rafraîchit) l'horodatage d'activité d'un utilisateur. */
    public function enregistrer(int $idClient): void
    {
        $req = Database::connexion()->prepare(
            'INSERT INTO pgcc_presence (id_client, derniere_activite)
             VALUES (:id, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE derniere_activite = CURRENT_TIMESTAMP'
        );
        $req->execute(['id' => $idClient]);
    }

    /**
     * Carte des dernières activités connues.
     *
     * @return array<int,string> id_client => « AAAA-MM-JJ HH:MM:SS »
     */
    public function carte(): array
    {
        $req = Database::connexion()->query('SELECT id_client, derniere_activite FROM pgcc_presence');
        $carte = [];
        foreach ($req->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $carte[(int) $ligne['id_client']] = (string) $ligne['derniere_activite'];
        }
        return $carte;
    }
}
