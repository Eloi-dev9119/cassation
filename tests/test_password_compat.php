<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/core/Password.php';

$motDePasse = 'Mot-de-passe-test-2026';
$empreinteHistorique = md5($motDePasse);
$empreinteModerne = Password::creer($motDePasse);

if (!Password::verifier($motDePasse, $empreinteHistorique)) {
    throw new RuntimeException('Un mot de passe MD5 historique doit rester vérifiable.');
}
if (!Password::verifier($motDePasse, $empreinteModerne)) {
    throw new RuntimeException('Un mot de passe moderne doit être vérifiable.');
}
if (Password::verifier('mauvais mot de passe', $empreinteHistorique)
    || Password::verifier('mauvais mot de passe', $empreinteModerne)) {
    throw new RuntimeException('Un mot de passe incorrect ne doit jamais être accepté.');
}

echo "Compatibilité MD5/bcrypt : OK\n";
