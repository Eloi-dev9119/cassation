<?php
declare(strict_types=1);

final class Mailer
{
    private static string $derniereErreur = '';

    public static function estConfigure(): bool
    {
        return defined('MAIL_ACTIF')
            && MAIL_ACTIF
            && trim((string) MAIL_HOTE) !== ''
            && filter_var(MAIL_EXPEDITEUR, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function derniereErreur(): string
    {
        return self::$derniereErreur;
    }

    public static function notifierValidationCompte(?array $membre): string
    {
        if (getenv('PGCC_E2E_MODE') === 'true') {
            return 'E-mail non envoyé : mode test.';
        }
        if ($membre === null || trim((string) ($membre['login'] ?? '')) === '') {
            return 'E-mail non envoyé : adresse introuvable.';
        }
        if (!self::estConfigure()) {
            return 'E-mail non envoyé : SMTP non configuré.';
        }
        try {
            if (self::envoyerValidationCompte($membre)) {
                return 'Un e-mail de confirmation a été envoyé à ' . $membre['login'] . '.';
            }
        } catch (Throwable $e) {
            self::$derniereErreur = $e->getMessage();
        }

        return 'L’e-mail de confirmation n’a pas pu être envoyé.';
    }

    public static function envoyerValidationCompte(array $membre): bool
    {
        $email = trim((string) ($membre['login'] ?? ''));
        $prenom = trim((string) ($membre['prenom'] ?? ''));
        $nom = trim((string) ($membre['nom'] ?? ''));
        $nomComplet = trim($prenom . ' ' . $nom);
        $bonjour = $nomComplet !== '' ? 'Bonjour ' . $nomComplet . ',' : 'Bonjour,';

        $corps = implode("\n", [
            $bonjour,
            '',
            'Votre compte PGCC a été validé par l’administration du Parquet Général.',
            '',
            'Vous pouvez désormais vous connecter :',
            'Adresse : ' . APP_URL,
            'Identifiant : ' . $email,
            '',
            'Utilisez le mot de passe défini lors de votre inscription. En cas d’oubli, utilisez la procédure « Mot de passe oublié » sur la page de connexion.',
            '',
            'Cordialement,',
            'Le Parquet Général près la Cour Suprême',
        ]);

        return self::envoyer($email, 'Votre compte PGCC a été activé', $corps);
    }

    public static function envoyer(string $destinataire, string $sujet, string $corps): bool
    {
        self::$derniereErreur = '';
        $destinataire = trim($destinataire);
        if (filter_var($destinataire, FILTER_VALIDATE_EMAIL) === false) {
            self::$derniereErreur = 'Adresse e-mail du destinataire invalide.';
            return false;
        }
        if (!self::estConfigure()) {
            self::$derniereErreur = 'SMTP non configuré.';
            return false;
        }

        $hote = trim((string) MAIL_HOTE);
        $securite = strtolower(trim((string) MAIL_SECURITE));
        if (!in_array($securite, ['ssl', 'tls', 'none'], true)) {
            $securite = 'tls';
        }
        $port = (int) MAIL_PORT;
        if ($port < 1) {
            $port = $securite === 'ssl' ? 465 : ($securite === 'tls' ? 587 : 25);
        }
        $prefixe = $securite === 'ssl' ? 'ssl://' : '';
        $contexte = stream_context_create([
            'ssl' => [
                'peer_name' => $hote,
                'SNI_enabled' => true,
                'verify_peer' => (bool) MAIL_VERIFIER_CERT,
                'verify_peer_name' => (bool) MAIL_VERIFIER_CERT,
                'allow_self_signed' => !(bool) MAIL_VERIFIER_CERT,
            ],
        ]);
        $socket = @stream_socket_client(
            $prefixe . $hote . ':' . $port,
            $numeroErreur,
            $messageErreur,
            (int) MAIL_TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $contexte
        );
        if ($socket === false) {
            self::$derniereErreur = 'Connexion SMTP impossible : '
                . ($messageErreur !== '' ? $messageErreur : 'erreur ' . $numeroErreur);
            error_log('[PGCC2][MAIL] ' . self::$derniereErreur);
            return false;
        }

        stream_set_timeout($socket, (int) MAIL_TIMEOUT);

        try {
            self::attendre($socket, [220]);
            $nomHote = self::nomHote();
            self::commande($socket, 'EHLO ' . $nomHote, [250]);

            if ($securite === 'tls') {
                self::commande($socket, 'STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Négociation TLS impossible.');
                }
                self::commande($socket, 'EHLO ' . $nomHote, [250]);
            }

            $utilisateur = trim((string) MAIL_UTILISATEUR);
            if ($utilisateur !== '') {
                self::commande($socket, 'AUTH LOGIN', [334]);
                self::commande($socket, base64_encode($utilisateur), [334]);
                self::commande($socket, base64_encode((string) MAIL_MOT_DE_PASSE), [235]);
            }

            self::commande($socket, 'MAIL FROM:<' . self::adresseExpediteur() . '>', [250]);
            self::commande($socket, 'RCPT TO:<' . $destinataire . '>', [250, 251]);
            self::commande($socket, 'DATA', [354]);
            self::ecrire($socket, self::message($destinataire, $sujet, $corps) . "\r\n.\r\n");
            self::attendre($socket, [250]);
            self::commande($socket, 'QUIT', [221]);
            fclose($socket);

            return true;
        } catch (Throwable $e) {
            self::$derniereErreur = $e->getMessage();
            error_log('[PGCC2][MAIL] ' . self::$derniereErreur);
            if (is_resource($socket)) {
                @fclose($socket);
            }
            return false;
        }
    }

    private static function message(string $destinataire, string $sujet, string $corps): string
    {
        $sujet = str_replace(["\r", "\n"], ' ', $sujet);
        $expediteur = self::adresseExpediteur();
        $nomExpediteur = str_replace(["\r", "\n"], ' ', trim((string) MAIL_NOM_EXPEDITEUR));
        $from = $nomExpediteur !== ''
            ? self::encoderEntete($nomExpediteur) . ' <' . $expediteur . '>'
            : '<' . $expediteur . '>';
        $domaine = substr(strrchr($expediteur, '@') ?: '@localhost', 1);
        $corps = str_replace(["\r\n", "\r"], "\n", $corps);
        $corpsEncode = chunk_split(base64_encode($corps), 76, "\r\n");

        $entetes = [
            'Date: ' . date('r'),
            'From: ' . $from,
            'To: <' . $destinataire . '>',
            'Subject: ' . self::encoderEntete($sujet),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domaine . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'X-Mailer: PGCC v2',
        ];

        return implode("\r\n", $entetes) . "\r\n\r\n" . $corpsEncode;
    }

    private static function encoderEntete(string $texte): string
    {
        return '=?UTF-8?B?' . base64_encode($texte) . '?=';
    }

    private static function adresseExpediteur(): string
    {
        return str_replace(["\r", "\n", '<', '>'], '', trim((string) MAIL_EXPEDITEUR));
    }

    private static function nomHote(): string
    {
        $nom = preg_replace('/[^A-Za-z0-9.-]/', '', (string) (gethostname() ?: 'localhost'));
        return $nom !== '' ? $nom : 'localhost';
    }

    private static function commande($socket, string $commande, array $codes): void
    {
        self::ecrire($socket, $commande . "\r\n");
        self::attendre($socket, $codes);
    }

    private static function attendre($socket, array $codes): void
    {
        $lignes = [];
        while (($ligne = fgets($socket, 515)) !== false) {
            $lignes[] = rtrim($ligne, "\r\n");
            if (strlen($ligne) < 4 || $ligne[3] !== '-') {
                break;
            }
        }
        if ($lignes === []) {
            throw new RuntimeException('Réponse SMTP vide.');
        }
        $derniere = end($lignes);
        $code = (int) substr($derniere, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException('SMTP ' . $code . ' : ' . implode(' | ', $lignes));
        }
    }

    private static function ecrire($socket, string $donnees): void
    {
        $longueur = strlen($donnees);
        $ecrit = 0;
        while ($ecrit < $longueur) {
            $nombre = fwrite($socket, substr($donnees, $ecrit));
            if ($nombre === false || $nombre === 0) {
                throw new RuntimeException('Écriture SMTP impossible.');
            }
            $ecrit += $nombre;
        }
    }
}
