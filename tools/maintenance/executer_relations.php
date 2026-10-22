<?php
/**
 * Exécution contrôlée des relations décrites dans
 * database/migrations/relations_cles_etrangeres.sql.
 * (nettoyage orphelins + 8 clés étrangères) avec rapport détaillé.
 * Usage : php tools/maintenance/executer_relations.php
 * Attention : le nettoyage supprime les historiques orphelins. Sauvegarder
 * la base et préférer LogicielCssation.sql si ces traces doivent être gardées.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';

$p = new PDO('mysql:host=' . DB_HOTE . ';dbname=' . DB_NOM . ';charset=utf8mb4', DB_USER, DB_MDP, [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "── Étape 0 : nettoyage des orphelins ──\n";
$nettoyages = [
    ['pgcc_historique_reg_arrivee_pen', 'pgcc_reg_arrivee_pen', 'id_reg_arrivee'],
    ['pgcc_historique_reg_pourvoi_pen', 'pgcc_reg_pourvoi_pen', 'id_reg_pourvoi'],
    ['pgcc_historique_reg_pourvoi_civ', 'pgcc_reg_pourvoi_civ', 'id_reg_pourvoi'],
    ['pgcc_historique_suivi_arrivee',   'pgcc_reg_arrivee_pen', 'id_reg_arrivee'],
];
$totalSupprimes = 0;
foreach ($nettoyages as [$hist, $parent, $pk]) {
    $n = $p->exec("DELETE h FROM `$hist` h LEFT JOIN `$parent` p ON p.`$pk` = h.id_dossier
                   WHERE h.id_dossier IS NOT NULL AND p.`$pk` IS NULL");
    $totalSupprimes += $n;
    printf("  %-36s → %d ligne(s) orpheline(s) supprimée(s)\n", $hist, $n);
}
echo "  TOTAL supprimé : $totalSupprimes\n";

echo "── Étape 1 : création des 8 clés étrangères ──\n";
$relations = [
    ['pgcc_historique_reg_arrivee_pen', 'pgcc_reg_arrivee_pen',  'id_reg_arrivee',  'arrivee_pen'],
    ['pgcc_historique_reg_pourvoi_pen', 'pgcc_reg_pourvoi_pen',  'id_reg_pourvoi',  'pourvoi_pen'],
    ['pgcc_historique_reg_pourvoi_civ', 'pgcc_reg_pourvoi_civ',  'id_reg_pourvoi',  'pourvoi_civ'],
    ['pgcc_historique_suivi_arrivee',   'pgcc_reg_arrivee_pen',  'id_reg_arrivee',  'suivi_arrivee'],
    ['pgcc_historique_suivi_pen',       'pgcc_reg_pourvoi_pen',  'id_reg_pourvoi',  'suivi_pen'],
    ['pgcc_historique_suivi_civ',       'pgcc_reg_pourvoi_civ',  'id_reg_pourvoi',  'suivi_civ'],
    ['pgcc_historique_pil',             'pgcc_reg_pil',          'id_pil',          'pil'],
    ['pgcc_historique_enrolement',      'pgcc_enrolement',       'id_enrolement',   'enrolement'],
];
$ok = 0;
foreach ($relations as [$table, $ref, $refCle, $nom]) {
    try {
        $p->exec("ALTER TABLE `$table`
                  ADD INDEX idx_hist_{$nom}_dossier (id_dossier),
                  ADD CONSTRAINT fk_hist_{$nom}_dossier
                      FOREIGN KEY (id_dossier) REFERENCES `$ref` (`$refCle`)
                      ON DELETE CASCADE ON UPDATE CASCADE");
        echo "  OK  $table.id_dossier → $ref.$refCle\n";
        $ok++;
    } catch (PDOException $e) {
        // index déjà existant (ré-exécution) → retenter sans l'index
        if (str_contains($e->getMessage(), 'Duplicate key name')) {
            try {
                $p->exec("ALTER TABLE `$table`
                          ADD CONSTRAINT fk_hist_{$nom}_dossier
                              FOREIGN KEY (id_dossier) REFERENCES `$ref` (`$refCle`)
                              ON DELETE CASCADE ON UPDATE CASCADE");
                echo "  OK  $table.id_dossier → $ref.$refCle (index existant réutilisé)\n";
                $ok++;
                continue;
            } catch (PDOException $e2) {
                echo "  ÉCHEC $table : " . $e2->getMessage() . "\n";
                continue;
            }
        }
        echo "  ÉCHEC $table : " . $e->getMessage() . "\n";
    }
}

echo "── Étape 2 : vérification ──\n";
$fks = $p->query("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                  FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
                  ORDER BY TABLE_NAME")->fetchAll();
printf("  Clés étrangères en base : %d / 8 attendues\n", count($fks));
foreach ($fks as $fk) {
    printf("    %-36s %-11s → %s.%s\n", $fk['TABLE_NAME'], $fk['COLUMN_NAME'],
        $fk['REFERENCED_TABLE_NAME'], $fk['REFERENCED_COLUMN_NAME']);
}
$orphelins = 0;
foreach ($relations as [$table, $ref, $refCle]) {
    $orphelins += (int) $p->query("SELECT COUNT(*) FROM `$table` h
        LEFT JOIN `$ref` r ON r.`$refCle` = h.id_dossier
        WHERE h.id_dossier IS NOT NULL AND r.`$refCle` IS NULL")->fetchColumn();
}
echo "  Orphelins restants : $orphelins\n";
exit(($ok === 8 && count($fks) === 8 && $orphelins === 0) ? 0 : 1);
