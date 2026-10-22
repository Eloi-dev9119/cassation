<?php
/**
 * Vérification des mots de passe actuels et migration des empreintes MD5.
 *
 * La branche MD5 de verifier() est une couche de compatibilité, pas du
 * chiffrement actif : chaque connexion valide déclenche la réécriture en
 * bcrypt (verifierEtMigrer), et tout nouveau mot de passe — inscription,
 * réinitialisation, changement — est déjà stocké avec password_hash().
 * Elle ne peut pas être retirée aujourd'hui : PGCC v1, qui partage la
 * table pgcc_client, écrit toujours du MD5 et ne migre jamais. Retirer
 * la vérification MD5 exclurait donc les comptes qui ne se connectent
 * que via v1. Condition de retrait : compter_mdp_legacy.php
 * (tools/maintenance) annonce zéro empreinte MD5 ET v1 est hors service.
 */

declare(strict_types=1);

final class Password
{
    public static function creer(string $motDePasse): string
    {
        return password_hash($motDePasse, PASSWORD_DEFAULT);
    }

    public static function verifier(string $motDePasse, string $empreinte): bool
    {
        if (password_verify($motDePasse, $empreinte)) {
            return true;
        }

        // Les comptes historiques utilisent MD5. La comparaison en temps
        // constant limite les différences de comportement observables.
        return preg_match('/^[a-f0-9]{32}$/i', $empreinte) === 1
            && hash_equals(strtolower($empreinte), md5($motDePasse));
    }

    /** Met à niveau un compte après une connexion valide, sans écraser un changement concurrent. */
    public static function verifierEtMigrer(PDO $bdd, int $id, string $empreinte, string $motDePasse): bool
    {
        if (!self::verifier($motDePasse, $empreinte)) {
            return false;
        }

        $legacyMd5 = preg_match('/^[a-f0-9]{32}$/i', $empreinte) === 1;
        if ($legacyMd5 || password_needs_rehash($empreinte, PASSWORD_DEFAULT)) {
            $req = $bdd->prepare(
                'UPDATE pgcc_client SET mdp = :nouveau WHERE id_client = :id AND mdp = :ancien'
            );
            $req->execute([
                'nouveau' => self::creer($motDePasse),
                'id' => $id,
                'ancien' => $empreinte,
            ]);
        }
        return true;
    }
}
