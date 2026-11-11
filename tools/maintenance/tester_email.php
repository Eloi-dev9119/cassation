<?php
/**
 * Test d'envoi d'e-mail (SMTP) — outil en ligne de commande.
 *
 * Usage :
 *   php tools/maintenance/tester_email.php destinataire@example.com
 *
 * Lit la configuration depuis config/mail.local.php (chargée par config.php)
 * puis tente une véritable connexion SMTP et l'envoi d'un message de test.
 */

declare(strict_types=1);

require dirname(__DIR__, 2) . '/config.php';
require APP_CHEMIN . '/app/core/Mailer.php';

$destinataire = trim((string) ($argv[1] ?? ''));

echo "== Configuration SMTP ==\n";
echo 'Actif        : ' . (MAIL_ACTIF ? 'oui' : 'non') . "\n";
echo 'Hôte         : ' . (MAIL_HOTE !== '' ? MAIL_HOTE : '(non défini)') . "\n";
echo 'Port         : ' . MAIL_PORT . "\n";
echo 'Sécurité     : ' . MAIL_SECURITE . "\n";
echo 'Utilisateur  : ' . (MAIL_UTILISATEUR !== '' ? MAIL_UTILISATEUR : '(non défini)') . "\n";
echo 'Expéditeur   : ' . (MAIL_EXPEDITEUR !== '' ? MAIL_EXPEDITEUR : '(non défini)') . "\n";
echo 'Mot de passe : ' . (MAIL_MOT_DE_PASSE !== '' ? 'défini (' . strlen(MAIL_MOT_DE_PASSE) . ' caractères)' : '(vide)') . "\n";
echo 'Configuré    : ' . (Mailer::estConfigure() ? 'oui' : 'NON') . "\n\n";

if ($destinataire === '') {
    fwrite(STDERR, "Destinataire manquant.\nUsage : php tools/maintenance/tester_email.php destinataire@example.com\n");
    exit(2);
}

if (filter_var($destinataire, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Adresse de destinataire invalide : {$destinataire}\n");
    exit(2);
}

echo "== Envoi du message de test vers {$destinataire} ==\n";
$debut = microtime(true);
$ok = Mailer::envoyer(
    $destinataire,
    'Test PGCC — envoi de mail',
    "Ceci est un message de test envoyé par l'application PGCC v2.\n\n"
    . "Date : " . date('c') . "\n"
);
$duree = round((microtime(true) - $debut) * 1000);

if ($ok) {
    echo "RÉSULTAT : SUCCÈS — e-mail accepté par le serveur SMTP ({$duree} ms).\n";
    exit(0);
}

echo "RÉSULTAT : ÉCHEC ({$duree} ms).\n";
echo 'Erreur   : ' . Mailer::derniereErreur() . "\n";
exit(1);
