#!/bin/bash
# =============================================================================
# E2E PGCC v2 — exécution automatisée du plan Desktop\partage\PLAN_DE_TESTS_PGCC2.md
# Cible : instance de test PGCC + base jetable pgcc_e2e_* (jamais la base partagee).
# Compte temporaire cree dans la base de test uniquement.
# Les donnees de test restent dans cette base jetable, a supprimer apres inspection.
# NB : toutes les URLs GET sont construites directement (jamais via
# --data-urlencode « r=/… » : Git Bash transformerait « /… » en chemin Windows).
E2E_DB="${PGCC_E2E_DB:-}"
if [[ ! "$E2E_DB" =~ ^pgcc_e2e_[A-Za-z0-9_]+$ ]]; then
  echo "Refus : PGCC_E2E_DB doit designer une base isolee nommee pgcc_e2e_*." >&2
  exit 2
fi
if [ -z "${PGCC_E2E_BASE_URL:-}" ]; then
  echo "Refus : PGCC_E2E_BASE_URL doit pointer vers une instance PGCC configuree sur cette base isolee." >&2
  exit 2
fi
B="$PGCC_E2E_BASE_URL"
export PGCC_E2E_DB="$E2E_DB"
export PGCC_E2E_DB_HOST="${PGCC_E2E_DB_HOST:-localhost}"
export PGCC_E2E_DB_USER="${PGCC_E2E_DB_USER:-root}"
export PGCC_E2E_DB_PASSWORD="${PGCC_E2E_DB_PASSWORD:-}"

APP_DB=$(curl -fsS "$B?e2e_probe=1") || {
  echo "Refus : l'instance HTTP n'est pas en mode E2E isole." >&2
  exit 2
}
if [ "$APP_DB" != "$E2E_DB" ]; then
  echo "Refus : la base HTTP ($APP_DB) ne correspond pas a la base de test ($E2E_DB)." >&2
  exit 2
fi
if [ -n "${PGCC_E2E_V1_BASE_URL:-}" ]; then
  V1_DB=$(curl -fsS "${PGCC_E2E_V1_BASE_URL}?e2e_probe=1") || {
    echo "Refus : la copie v1 n'est pas en mode E2E isole." >&2
    exit 2
  }
  if [ "$V1_DB" != "$E2E_DB" ]; then
    echo "Refus : la copie v1 utilise une autre base que $E2E_DB." >&2
    exit 2
  fi
fi

SUF=$(date +%H%M%S)-$$
SIG_SUFFIX=$(printf '%s' "$SUF" | cksum | awk '{printf "%06d", $1 % 1000000}')
TEST_EMAIL="TEST.admin.$SUF@pgcc.com"
TEST_PASSWORD="E2eTest2026-$SUF"
REG_EMAIL="TEST.inscription.$SUF@pgcc.com"
TEST_AG1_EMAIL="TEST.ag1.$SUF@pgcc.com"
TEST_AG2_EMAIL="TEST.ag2.$SUF@pgcc.com"
TEST_AG1_SIGLE="T1$SIG_SUFFIX"
TEST_AG2_SIGLE="T2$SIG_SUFFIX"
export PGCC_E2E_TEST_EMAIL="$TEST_EMAIL" PGCC_E2E_TEST_PASSWORD="$TEST_PASSWORD"
export PGCC_E2E_AG1_EMAIL="$TEST_AG1_EMAIL" PGCC_E2E_AG2_EMAIL="$TEST_AG2_EMAIL"
export PGCC_E2E_AG1_SIGLE="$TEST_AG1_SIGLE" PGCC_E2E_AG2_SIGLE="$TEST_AG2_SIGLE"
J1=/tmp/e2e_j1.txt; J2=/tmp/e2e_j2.txt; J3=/tmp/e2e_j3.txt
W=/tmp/e2e_work; mkdir -p $W; rm -f $J1 $J2 $J3
PASS=0; FAIL=0; ECHECS=""

check() { if [ "$2" -eq 0 ]; then PASS=$((PASS+1)); printf '  OK  %-7s %s\n' "$1" "$3"
  else FAIL=$((FAIL+1)); ECHECS="$ECHECS $1"; printf '  KO  %-7s %s\n' "$1" "$3"; fi; }
jeton() { grep -oE 'name="jeton" value="[a-f0-9]+"' "$1" | head -1 | sed 's/.*value="//;s/"$//'; }
dbq() { E2E_SQL="$1" php -r '
$p = new PDO("mysql:host=".getenv("PGCC_E2E_DB_HOST").";dbname=".getenv("PGCC_E2E_DB").";charset=utf8mb4", getenv("PGCC_E2E_DB_USER"), getenv("PGCC_E2E_DB_PASSWORD"), [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
echo json_encode($p->query(getenv("E2E_SQL"))->fetchAll(), JSON_UNESCAPED_UNICODE);'; }
db1() { dbq "$1" | php -r '$o=json_decode(stream_get_contents(STDIN)); echo $o ? array_values((array)$o[0])[0] : "";'; }
# prochain n° d'ordre pour une table/année (simule le pré-remplissage JS du v1)
prochain_ordre() { db1 "SELECT COALESCE(MAX(num_ordre),0)+1 FROM $1 WHERE annee=$2"; }
# remplisseur générique : tous les input non-checkbox du fichier avec leur value
fill_form() { php -r '
$html = file_get_contents($argv[1]); $pairs = [];
$add = function ($name, $value) use (&$pairs) { $pairs[] = rawurlencode($name) . "=" . rawurlencode($value); };
preg_match_all("/<input\\b[^>]*>/i", $html, $m);
foreach ($m[0] as $tag) {
  if (!preg_match("/name=\"([^\"]+)\"/", $tag, $n) || $n[1] === "jeton") continue;
  $type = preg_match("/type=\"([^\"]+)\"/i", $tag, $t) ? strtolower($t[1]) : "text";
  if (in_array($type, ["submit","button","reset","password"], true)) continue;
  if (in_array($type, ["checkbox","radio"], true) && !preg_match("/\\bchecked(?:\\s|=|>)/i", $tag)) continue;
  preg_match("/value=\"([^\"]*)\"/i", $tag, $v);
  $add($n[1], html_entity_decode($v[1] ?? "", ENT_QUOTES | ENT_HTML5, "UTF-8"));
}
preg_match_all("/<textarea\\b([^>]*)>(.*?)<\\/textarea>/is", $html, $areas, PREG_SET_ORDER);
foreach ($areas as $area) {
  if (!preg_match("/name=\"([^\"]+)\"/i", $area[1], $n) || $n[1] === "jeton") continue;
  $add($n[1], html_entity_decode($area[2], ENT_QUOTES | ENT_HTML5, "UTF-8"));
}
preg_match_all("/<select\\b([^>]*)>(.*?)<\\/select>/is", $html, $selects, PREG_SET_ORDER);
foreach ($selects as $select) {
  if (!preg_match("/name=\"([^\"]+)\"/i", $select[1], $n) || $n[1] === "jeton") continue;
  preg_match_all("/<option\\b([^>]*)>(.*?)<\\/option>/is", $select[2], $options, PREG_SET_ORDER);
  $choix = [];
  foreach ($options as $option) {
    if (preg_match("/\\bdisabled(?:\\s|=|>|$)/i", $option[1])) continue;
    if (preg_match("/value=\"([^\"]*)\"/i", $option[1], $v)) $value = $v[1]; else $value = strip_tags($option[2]);
    if (preg_match("/\\bselected(?:\\s|=|>|$)/i", $option[1])) $choix[] = $value;
  }
  if ($choix === []) foreach ($options as $option) {
    if (preg_match("/\\bdisabled(?:\\s|=|>|$)/i", $option[1])) continue;
    if (preg_match("/value=\"([^\"]*)\"/i", $option[1], $v)) $choix[] = $v[1]; else $choix[] = strip_tags($option[2]);
    break;
  }
  foreach ($choix as $value) $add($n[1], html_entity_decode($value, ENT_QUOTES | ENT_HTML5, "UTF-8"));
}
echo implode("&", $pairs);' "$1"; }
# valeur de la N-ième option non vide d'un <select> nommé
option() { php -r '
$html = file_get_contents($argv[1]);
if (!preg_match("/<select[^>]*name=\"".$argv[2]."\"[^>]*>(.*?)<\/select>/is", $html, $m)) exit(1);
preg_match_all("/value=\"([^\"]*)\"/", $m[1], $o);
$vals = array_values(array_filter($o[1], fn($v) => trim($v) !== ""));
$i = (int)$argv[3] - 1; echo $vals[$i] ?? "";' "$1" "$2" "$3"; }
table_has() { php -r '
$html = file_get_contents($argv[1]);
if (preg_match("/<tbody\\b[^>]*>(.*?)<\\/tbody>/is", $html, $m) && strpos($m[1], $argv[2]) !== false) exit(0);
exit(1);' "$1" "$2"; }
csv_download() {
  local code
  code=$(curl -s -b "$J1" -D "$W/$1.headers" -o "$W/$1.csv" -w '%{http_code}' "$B$2")
  [ "$code" = "200" ] && grep -qi "Content-Type: text/csv" "$W/$1.headers" && grep -Fq -- "$3" "$W/$1.csv"
}

echo "Preparation des donnees de test dans $E2E_DB"
php -r '
$p = new PDO("mysql:host=".getenv("PGCC_E2E_DB_HOST").";dbname=".getenv("PGCC_E2E_DB").";charset=utf8mb4", getenv("PGCC_E2E_DB_USER"), getenv("PGCC_E2E_DB_PASSWORD"), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$q = $p->prepare("INSERT INTO pgcc_client (nom,prenom,sexe,login,mdp,poste,sigle_AG,IM,valide,status) VALUES (?,?,?,?,?,?,?,?,1,?)");
$hash = password_hash(getenv("PGCC_E2E_TEST_PASSWORD"), PASSWORD_DEFAULT);
$comptes = [
    ["TEST", "Admin", "M", getenv("PGCC_E2E_TEST_EMAIL"), $hash, "PG", "", random_int(900000, 999999), "Actifs"],
    ["TEST", "Avocat A", "M", getenv("PGCC_E2E_AG1_EMAIL"), $hash, "AG", getenv("PGCC_E2E_AG1_SIGLE"), random_int(900000, 999999), "Actifs"],
    ["TEST", "Avocat B", "M", getenv("PGCC_E2E_AG2_EMAIL"), $hash, "AG", getenv("PGCC_E2E_AG2_SIGLE"), random_int(900000, 999999), "Actifs"],
];
foreach ($comptes as $compte) $q->execute($compte);
$distribution = $p->prepare("INSERT INTO pgcc_distribution_pg (annee,nom_liste,num_dossier_PGCS,type_dossier,parties,sigle,date_remise_secr,date_remise_AG,cree_par) VALUES (2013,?,?,?,?,?,?,?,?)");
$distribution->execute(["Juin 2013 E2E ".getenv("PGCC_E2E_AG1_SIGLE"), "TEST-E2E-BASE-".getenv("PGCC_E2E_AG1_SIGLE"), "pen", "Dossier de test", getenv("PGCC_E2E_AG1_SIGLE"), "2013-06-01", "2013-06-02", getenv("PGCC_E2E_TEST_EMAIL")]);
echo "  comptes de test isoles prets\n";'
TEST_ID=$(db1 "SELECT id_client FROM pgcc_client WHERE login='$TEST_EMAIL'")

P=$(curl -s -L -c $J1 -o $W/p2.html -w '%{http_code}' "$B?r=/"); T=$(grep -oE '<title>[^<]*' $W/p2.html | sed 's/<title>//')
[ "$P" = "200" ] && echo "$T" | grep -q "Connexion"; check "P-2" $? "GET / → $P « $T »"
if [ -n "${PGCC_E2E_V1_BASE_URL:-}" ]; then
  C=$(curl -s -o /dev/null -w '%{http_code}' "$PGCC_E2E_V1_BASE_URL")
  [ "$C" = "200" ]; check "P-3" $? "copie v1 isolee repond (HTTP $C)"
else
  echo "  SKIP P-3 : aucune URL v1 de test fournie."
fi
echo "══════════ 1. Authentification ══════════"
JT=$(jeton $W/p2.html)
curl -s -b $J1 -c $J1 -o $W/t01.html --data-urlencode "email=$TEST_EMAIL" --data-urlencode "motDePasse=MAUVAIS" --data-urlencode "jeton=$JT" "$B?r=/connexion"
grep -qi "identifiants incorrects" $W/t01.html; check "T-01" $? "mauvais mot de passe → « Identifiants incorrects »"
rm -f $J1; curl -s -c $J1 "$B?r=/connexion" -o $W/conn.html; JT=$(jeton $W/conn.html)
C=$(curl -s -b $J1 -c $J1 -L -o $W/t02.html -w '%{http_code}' --data-urlencode "email=$TEST_EMAIL" --data-urlencode "motDePasse=$TEST_PASSWORD" --data-urlencode "jeton=$JT" "$B?r=/connexion")
grep -q "TEST Admin" $W/t02.html && grep -qi "tableau de bord" $W/t02.html
check "T-02" $? "compte E2E → tableau de bord (HTTP $C, nom affiché)"
R=$(curl -s -L -o $W/t03.html -w '%{url_effective}' "$B?r=/tableau-de-bord")
echo "$R" | grep -q "r=/connexion"; check "T-03" $? "page protégée sans session → retour Connexion"
R=$(curl -s -L -o $W/t112.html -w '%{url_effective}' "$B?r=/memoires")
echo "$R" | grep -q "r=/connexion"; check "T-112" $? "/memoires sans session → Connexion"
C=$(curl -s -o $W/t113.html -w '%{http_code}' "$B?r=/page-inconnue")
grep -qiE "404|introuvable" $W/t113.html; check "T-113" $? "?r=page-inconnue → page 404 propre (HTTP $C)"

echo "══════════ 2. Tableau de bord ══════════"
curl -s -b $J1 -o $W/dash.html "$B?r=/tableau-de-bord"
grep -qE "Arriv" $W/dash.html && grep -qi "dessaisissements" $W/dash.html
check "T-10" $? "compteurs Arrivées/Pénaux/Civils/Déssaisissements affichés"
grep -q "registres/penaux/creer" $W/dash.html; check "T-12" $? "raccourci « + Dossier pénal » présent"

echo "══════════ 3. Registre Pénaux ══════════"
curl -s -b $J1 -o $W/pen.html "$B?r=/registres/penaux"
grep -qi "nouveau dossier" $W/pen.html; check "T-20" $? "liste pénaux + bouton « Nouveau dossier »"

creer_penal() { # $1=num_dossier $2=cassationnaire → id créé (ou "")
  curl -s -b $J1 -o $W/fp.html "$B?r=/registres/penaux/creer"
  JT=$(jeton $W/fp.html); NO=$(prochain_ordre pgcc_reg_pourvoi_pen 2026)
  curl -s -b $J1 -L -o $W/rp.html --data-urlencode "num_ordre=$NO" --data-urlencode "annee=2026" \
    --data-urlencode "date_insertion=2026-10-20" --data-urlencode "num_dossier_PGCS=$1" \
    --data-urlencode "cassationnaire=$2" --data-urlencode "jeton=$JT" "$B?r=/registres/penaux/creer" >/dev/null
  db1 "SELECT id_reg_pourvoi FROM pgcc_reg_pourvoi_pen WHERE num_dossier_PGCS='$1'"
}
IDPEN=$(creer_penal "TEST-PEN-$SUF" "TEST Utilisateur")
[ -n "$IDPEN" ]; check "T-23" $? "création TEST-PEN-$SUF (n° ordre auto) → id=$IDPEN en base"

NO=$(prochain_ordre pgcc_reg_pourvoi_pen 2026)
N0=$(db1 "SELECT COUNT(*) FROM pgcc_reg_pourvoi_pen WHERE num_dossier_PGCS LIKE 'TEST-%'")
curl -s -b $J1 -L -o $W/t24.html --data-urlencode "num_ordre=$NO" --data-urlencode "annee=2026" \
  --data-urlencode "date_insertion=2026-10-20" --data-urlencode "num_dossier_PGCS=" \
  --data-urlencode "jeton=$(jeton $W/fp.html)" "$B?r=/registres/penaux/creer" >/dev/null
N1=$(db1 "SELECT COUNT(*) FROM pgcc_reg_pourvoi_pen WHERE num_dossier_PGCS LIKE 'TEST-%'")
grep -qi "obligatoire" $W/t24.html && [ "$N0" = "$N1" ]
check "T-24" $? "validation : n° dossier vide refusé, aucune ligne créée ($N0 → $N1)"

curl -s -b $J1 -o $W/t25.html "$B?r=/registres/penaux/fiche&id=$IDPEN"
grep -qi "traçabilité" $W/t25.html && grep -q "$TEST_EMAIL" $W/t25.html
check "T-25" $? "fiche : bloc Traçabilité + « créé par hermann »"

curl -s -b $J1 -o $W/penmod.html "$B?r=/registres/penaux/modifier&id=$IDPEN"
curl -s -b $J1 -L -o /dev/null --data "$(fill_form $W/penmod.html)" \
  --data-urlencode "cassationnaire=TEST Modifie" --data-urlencode "jeton=$(jeton $W/penmod.html)" \
  "$B?r=/registres/penaux/modifier&id=$IDPEN" >/dev/null
MP=$(db1 "SELECT modifie_par FROM pgcc_reg_pourvoi_pen WHERE id_reg_pourvoi=$IDPEN")
[ "$MP" = "$TEST_EMAIL" ]; check "T-26" $? "modification → modifie_par=$MP en base"
YEAR_AFTER_EDIT=$(db1 "SELECT annee FROM pgcc_reg_pourvoi_pen WHERE id_reg_pourvoi=$IDPEN")
[ "$YEAR_AFTER_EDIT" = "2026" ]; check "T-26b" $? "la modification préserve l'année sélectionnée (2026)"

curl -s -b $J1 -o $W/concl.html "$B?r=/registres/penaux/conclusion&id=$IDPEN"
curl -s -b $J1 -L -o /dev/null --data-urlencode "conclusion=L'arret attaque est annule (test d'apostrophe)" \
  --data-urlencode "jeton=$(jeton $W/concl.html)" "$B?r=/registres/penaux/conclusion&id=$IDPEN" >/dev/null
CONC=$(dbq "SELECT conclusion FROM pgcc_reg_pourvoi_pen WHERE id_reg_pourvoi=$IDPEN" | php -r 'echo json_decode(stream_get_contents(STDIN))[0]->conclusion;')
echo "$CONC" | grep -q "L'arret attaque est annule"; check "T-27" $? "conclusion avec apostrophe intacte en base"

curl -s -b $J1 -o $W/t21.html "$B?r=/registres/penaux&q=TEST-PEN-$SUF"
table_has $W/t21.html "TEST-PEN-$SUF"; check "T-21" $? "recherche retrouve le dossier dans les lignes du tableau"
curl -s -b $J1 -o $W/t22.html "$B?r=/registres/penaux&annee=2026"
table_has $W/t22.html "TEST-PEN-$SUF"; check "T-22" $? "filtre année 2026 retrouve le dossier dans le tableau"

curl -s -b $J1 -L -o /dev/null --data-urlencode "jeton=$(jeton $W/pen.html)" "$B?r=/registres/penaux/archiver&id=$IDPEN" >/dev/null
curl -s -b $J1 -o $W/penl.html "$B?r=/registres/penaux"
AR=$(db1 "SELECT archiver FROM pgcc_reg_pourvoi_pen WHERE id_reg_pourvoi=$IDPEN" | php -r 'echo bin2hex(stream_get_contents(STDIN));')
[ "$AR" = "31" ]; check "T-28" $? "archivage → archiver=0x$AR (badge « Oui » en liste)"
csv_download export-registres "?r=/registres/penaux&export=csv&q=TEST-PEN-$SUF" "TEST-PEN-$SUF"
check "T-CSV-1" $? "export CSV du registre pénal (type, en-tête et ligne du dossier)"
grep -q 'window.print()' $W/penl.html && grep -q '@media print' public/assets/style.css
check "T-PRINT" $? "action Imprimer disponible avec règles de mise en page"

echo "══════════ 3bis. Autres registres (T-29) ══════════"
for REG in arrivees:ARR:pgcc_reg_arrivee_pen civils:CIV:pgcc_reg_pourvoi_civ dessaisissements:DES:pgcc_reg_des; do
  R="${REG%%:*}"; TAG=$(echo "${REG#*:}" | cut -d: -f1); TBL="${REG##*:}"
  curl -s -b $J1 -o $W/f_$R.html "$B?r=/registres/$R/creer"
  JT=$(jeton $W/f_$R.html); NO=$(prochain_ordre "$TBL" 2026)
  curl -s -b $J1 -L -o /dev/null --data-urlencode "num_ordre=$NO" --data-urlencode "annee=2026" \
    --data-urlencode "date_insertion=2026-10-20" --data-urlencode "num_dossier_PGCS=TEST-$TAG-$SUF" \
    --data-urlencode "jeton=$JT" "$B?r=/registres/$R/creer" >/dev/null
  NR=$(db1 "SELECT COUNT(*) FROM $TBL WHERE num_dossier_PGCS='TEST-$TAG-$SUF'")
  [ "${NR:-0}" -ge 1 ]; check "T-29" $? "registre $R : création TEST-$TAG-$SUF → $NR ligne"
  if [ "$R" = "civils" ]; then
    CIVIL_ID=$(db1 "SELECT id_reg_pourvoi FROM pgcc_reg_pourvoi_civ WHERE num_dossier_PGCS='TEST-CIV-$SUF'")
  fi
done
NO=$(prochain_ordre pgcc_reg_pourvoi_civ 2026)
curl -s -b $J1 -L -o /dev/null --data-urlencode "num_ordre=$NO" --data-urlencode "annee=2026" \
  --data-urlencode "date_insertion=2026-10-20" --data-urlencode "num_dossier_PGCS=TEST-CIV2-$SUF" \
  --data-urlencode "jeton=$(jeton $W/f_civils.html)" "$B?r=/registres/civils/creer" >/dev/null
CIVIL2=$(db1 "SELECT id_reg_pourvoi FROM pgcc_reg_pourvoi_civ WHERE num_dossier_PGCS='TEST-CIV2-$SUF'")
[ -n "$CIVIL2" ]; check "T-29c" $? "second dossier civil de test disponible pour tester les distributions"
curl -s -b $J1 -o $W/f_siege.html "$B?r=/registres/siege/creer&dossier_civil=$CIVIL_ID"
curl -s -b $J1 -L -o /dev/null --data "$(fill_form $W/f_siege.html)" \
  --data-urlencode "annee=2026" --data-urlencode "num_dossier=TEST-SIE-$SUF" \
  --data-urlencode "id_reg_pourvoi_civ=$CIVIL_ID" \
  --data-urlencode "jeton=$(jeton $W/f_siege.html)" "$B?r=/registres/siege/creer" >/dev/null
NS=$(db1 "SELECT COUNT(*) FROM siege_cassation_civ WHERE num_dossier='TEST-SIE-$SUF'")
[ "${NS:-0}" -ge 1 ]; check "T-29" $? "registre siège : création TEST-SIE-$SUF → $NS ligne"
SIEGE_CIVIL=$(db1 "SELECT id_reg_pourvoi_civ FROM siege_cassation_civ WHERE num_dossier='TEST-SIE-$SUF'")
[ "$SIEGE_CIVIL" = "$CIVIL_ID" ]; check "T-29b" $? "lien siège conservé vers le dossier civil $CIVIL_ID"
curl -s -b $J1 -o $W/dash-after.html "$B?r=/tableau-de-bord"
grep -q "<svg" $W/dash-after.html; check "T-11" $? "graphique SVG du tableau de bord présent avec des dossiers"

echo "══════════ 3ter. Sécurité ══════════"
IDX=$(creer_penal "TEST-XSS-$SUF" "<script>alert(1)</script>")
curl -s -b $J1 -o $W/xssf.html "$B?r=/registres/penaux/fiche&id=$IDX"
grep -q "&lt;script&gt;" $W/xssf.html && ! grep -q "<script>alert" $W/xssf.html
check "T-111" $? "XSS : <script> affiché échappé, jamais exécutable"
C=$(curl -s -b $J1 -o $W/inj.html -w '%{http_code}' "$B?r=/registres/penaux&q='+OR+'1'%3D'1")
[ "$C" = "200" ] && ! grep -qiE "exception|fatal|PDO" $W/inj.html
check "T-110" $? "injection SQL traitée comme texte (HTTP $C, aucune erreur)"

echo "══════════ 4. Registre des Mémoires ══════════"
curl -s -b $J1 -o $W/mform.html "$B?r=/memoires/creer"
TY=$(option $W/mform.html type_memoire 1); OR=$(option $W/mform.html origine 1)
TY2=$(option $W/mform.html type_memoire 2); TY2=${TY2:-$TY}
curl -s -b $J1 -L -o $W/t30.html --data "$(fill_form $W/mform.html)" \
  --data-urlencode "num_ordre=" --data-urlencode "annee=2026" \
  --data-urlencode "type_memoire=$TY" --data-urlencode "origine=$OR" \
  --data-urlencode "num_dossier_PGCS=TEST-PEN-$SUF" --data-urlencode "parties=TEST Parties" \
  --data-urlencode "avocat=Me Rakoto" --data-urlencode "date_depot=2026-10-20" \
  --data-urlencode "jeton=$(jeton $W/mform.html)" "$B?r=/memoires/creer" >/dev/null
NM=$(db1 "SELECT CONCAT(num_ordre,'/',annee) FROM pgcc_reg_memoires WHERE num_dossier_PGCS='TEST-PEN-$SUF'")
[ -n "$NM" ]; check "T-30" $? "mémoire créé (type=$TY, origine=$OR) → n° ordre auto $NM"
csv_download export-memoires "?r=/memoires&export=csv&q=TEST-PEN-$SUF" "TEST-PEN-$SUF"
check "T-CSV-2" $? "export CSV des mémoires"
IDM=$(db1 "SELECT id_memoire FROM pgcc_reg_memoires WHERE num_dossier_PGCS='TEST-PEN-$SUF'")
curl -s -b $J1 -o $W/mlist.html "$B?r=/memoires&q=TEST-PEN-$SUF"
ET=$(php -r '
$h = file_get_contents($argv[1]);
if (preg_match("/etat&amp;id=" . $argv[2] . "\".*?<\/form>/is", $h, $f)
    && preg_match_all("/<option value=\"([^\"]+)\"/", $f[0], $o)) {
  $vals = array_values(array_filter($o[1]));
  echo $vals[1] ?? $vals[0];
}' "$W/mlist.html" "$IDM")
curl -s -b $J1 -L -o /dev/null --data-urlencode "etat=$ET" --data-urlencode "jeton=$(jeton $W/mlist.html)" "$B?r=/memoires/etat&id=$IDM" >/dev/null
EM=$(db1 "SELECT etat FROM pgcc_reg_memoires WHERE id_memoire=$IDM")
[ "$EM" = "$ET" ] && [ -n "$ET" ]; check "T-31" $? "cycle d'état : changement → « $EM » en base"
curl -s -b $J1 -o $W/mmod.html "$B?r=/memoires/modifier&id=$IDM"
grep -qi "traçabilité" $W/mmod.html; check "T-32a" $? "formulaire modification : bandeau Traçabilité visible"
curl -s -b $J1 -L -o /dev/null --data "$(fill_form $W/mmod.html)" \
  --data-urlencode "type_memoire=$TY2" --data-urlencode "jeton=$(jeton $W/mmod.html)" \
  "$B?r=/memoires/modifier&id=$IDM" >/dev/null
TYDB=$(db1 "SELECT type_memoire FROM pgcc_reg_memoires WHERE id_memoire=$IDM")
[ "$TYDB" = "$TY2" ] && [ -n "$TY2" ]; check "T-32b" $? "modification → type_memoire=$TYDB en base"
curl -s -b $J1 -o $W/t34.html "$B?r=/memoires&q=TEST-PEN-$SUF"
grep -q "TEST-PEN-$SUF" $W/t34.html; check "T-34" $? "recherche mémoire par n° de dossier"
curl -s -b $J1 -o $W/t36.html "$B?r=/registres/penaux/fiche&id=$IDPEN"
grep -qi "mémoires rattachés" $W/t36.html && grep -q "Me Rakoto" $W/t36.html
check "T-36" $? "fiche dossier : bloc « Mémoires rattachés » avec le mémoire"
curl -s -b $J1 -L -o /dev/null --data-urlencode "jeton=$(jeton $W/mlist.html)" "$B?r=/memoires/supprimer&id=$IDM" >/dev/null
NM2=$(db1 "SELECT COUNT(*) FROM pgcc_reg_memoires WHERE id_memoire=$IDM")
[ "$NM2" = "0" ]; check "T-35" $? "suppression du mémoire → $NM2 ligne restante"

echo "══════════ 5. Suivis 321 / 107 ══════════"
curl -s -b $J1 -o $W/s321.html "$B?r=/suivis/321"
grep -qi "nouveau suivi" $W/s321.html; check "T-40" $? "consultation suivis 321 OK"
curl -s -b $J1 -o $W/sf321.html "$B?r=/suivis/321/creer"
JT=$(jeton $W/sf321.html); NO=$(prochain_ordre pgcc_suivi_arrivee 2026)
curl -s -b $J1 -L -o $W/t41.html --data-urlencode "num_ordre=$NO" --data-urlencode "annee=2026" \
  --data-urlencode "num_dossier_PGCS=TEST-SUIVI321-$SUF" --data-urlencode "sigle_AG=TST" \
  --data-urlencode "jeton=$JT" "$B?r=/suivis/321/creer" >/dev/null
N3=$(db1 "SELECT COUNT(*) FROM pgcc_suivi_arrivee WHERE num_dossier_PGCS='TEST-SUIVI321-$SUF'")
grep -qi "enregistré" $W/t41.html && [ "${N3:-0}" -ge 1 ]
check "T-41" $? "suivi 321 créé (numérotation auto) → $N3 ligne + message succès"
csv_download export-suivis "?r=/suivis/321&export=csv" "TEST-SUIVI321-$SUF"
check "T-CSV-3" $? "export CSV des suivis"
ID3=$(db1 "SELECT id_suivi_arrivee FROM pgcc_suivi_arrivee WHERE num_dossier_PGCS='TEST-SUIVI321-$SUF'")
curl -s -b $J1 -L -o /dev/null --data-urlencode "id=$ID3" --data-urlencode "jeton=$JT" "$B?r=/suivis/321/archiver" >/dev/null
A3=$(db1 "SELECT archiver FROM pgcc_suivi_arrivee WHERE id_suivi_arrivee=$ID3" | php -r 'echo bin2hex(stream_get_contents(STDIN));')
[ "$A3" = "31" ]; check "T-42" $? "archivage suivi 321 → archiver=0x$A3"
curl -s -b $J1 -o $W/sf107.html "$B?r=/suivis/107/creer"
JT=$(jeton $W/sf107.html); NO=$(prochain_ordre pgcc_suivi_pen 2026)
curl -s -b $J1 -L -o /dev/null --data-urlencode "num_ordre=$NO" --data-urlencode "annee=2026" \
  --data-urlencode "num_dossier_PGCS=TEST-SUIVI107-$SUF" --data-urlencode "sigle_AG=TST" \
  --data-urlencode "jeton=$JT" "$B?r=/suivis/107/creer" >/dev/null
N7=$(db1 "SELECT COUNT(*) FROM pgcc_suivi_pen WHERE num_dossier_PGCS='TEST-SUIVI107-$SUF'")
[ "${N7:-0}" -ge 1 ]; check "T-43" $? "suivi 107 TEST-SUIVI107-$SUF créé → $N7 ligne"
curl -s -b $J1 -o $W/t44.html "$B?r=/suivis/321&q=TEST-SUIVI321-$SUF"
grep -q "TEST-SUIVI321-$SUF" $W/t44.html; check "T-44" $? "recherche du suivi dans l'onglet 321"

echo "══════════ 6. Suivis Dossiers (par Avocat Général) ══════════"
curl -s -b $J1 -o $W/sd.html "$B?r=/suivis-dossiers"
grep -qE "<select" $W/sd.html && grep -qi "pénaux" $W/sd.html
check "T-50" $? "sélecteur AG + cartes pénaux/civils présents"
curl -s -b $J1 -o $W/sd.html "$B?r=/suivis-dossiers&ag=$TEST_AG1_SIGLE"
grep -qE "Rédigée|Non rédigée" $W/sd.html; check "T-53" $? "badges de rédaction affichés pour un AG avec des dossiers distribués"
csv_download export-ag "?r=/suivis-dossiers&export=csv&ag=$TEST_AG1_SIGLE" "TEST-E2E-BASE-$TEST_AG1_SIGLE"
check "T-CSV-4" $? "export CSV de la synthèse d'un Avocat Général"
VAL="$TEST_AG2_SIGLE"
curl -s -b $J1 -o $W/sd2.html "$B?r=/suivis-dossiers&ag=$VAL"
[ -n "$VAL" ] && ! cmp -s $W/sd.html $W/sd2.html; check "T-52" $? "changement d'AG (ag=$VAL) → compteurs recalculés"

echo "══════════ 7. Répartition des dossiers ══════════"
curl -s -b $J1 -o $W/rep18.html "$B?r=/repartition&mois=Juin&annee=2013"
grep -q "<svg" $W/rep18.html && grep -qi "total" $W/rep18.html
check "T-60" $? "répartition Juin 2013 avec une distribution de test : graphique SVG + totaux"
csv_download export-repartition "?r=/repartition&export=csv&mois=Juin&annee=2013" "Sigle AG"
check "T-CSV-5" $? "export CSV de la répartition mensuelle"
curl -s -b $J1 -o $W/rep26.html "$B?r=/repartition&mois=Mars&annee=2031"
CV=$(db1 "SELECT COUNT(*) FROM pgcc_distribution_pg WHERE nom_liste LIKE '%Mars%' AND annee=2031")
grep -q "Mars 2031" $W/rep26.html \&\& [ "$CV" = "0" ]
check "T-62" $? "mois sans données (Mars 2031) : page OK, 0 distribution en base"

echo "══════════ 8. Distributions Pénaux / Civiles ══════════"
curl -s -b $J1 -o $W/dp.html "$B?r=/distributions&type=pen"
grep -qi "nouvelle liste" $W/dp.html && grep -qi "civiles" $W/dp.html
check "T-70" $? "liste distributions pénales + bascule Civiles"
creer_liste() { # $1=type $2=nom → 0 si créée
  curl -s -b $J1 -o $W/dn_$1.html "$B?r=/distributions&a=nouvelle&type=$1"
  D1=$(grep -oE 'name="dossiers\[\]" value="[0-9]+"' $W/dn_$1.html | head -1 | grep -oE '[0-9]+')
  D2=$(grep -oE 'name="dossiers\[\]" value="[0-9]+"' $W/dn_$1.html | sed -n 2p | grep -oE '[0-9]+')
  [ -z "$D1" ] && return 1
  curl -s -b $J1 -L -o $W/dl_$1.html --data "$(fill_form $W/dn_$1.html)" \
    --data-urlencode "nom_liste=$2" --data-urlencode "sigle=$TEST_AG1_SIGLE" \
    --data-urlencode "dossiers[]=$D1" --data-urlencode "dossiers[]=$D2" \
    --data-urlencode "jeton=$(jeton $W/dn_$1.html)" "$B?r=/distributions&a=nouvelle&type=$1" >/dev/null
  grep -qi "créée" $W/dl_$1.html
}
creer_liste pen "TEST LISTE PEN $SUF"; check "T-71" $? "liste pénale TEST créée avec 2 dossiers non distribués"
csv_download export-distributions "?r=/distributions&export=csv&type=pen" "TEST LISTE PEN $SUF"
check "T-CSV-6" $? "export CSV des distributions pénales"
D1P=$(grep -oE 'name="dossiers\[\]" value="[0-9]+"' $W/dn_pen.html | head -1 | grep -oE '[0-9]+')
curl -s -b $J1 -o $W/dn2.html "$B?r=/distributions&a=nouvelle&type=pen"
[ -n "$D1P" ] && ! grep -q "value=\"$D1P\"" $W/dn2.html; check "T-73" $? "dossier $D1P exclu des candidats (déjà distribué)"
curl -s -b $J1 -L -o $W/t74.html --data "$(fill_form $W/dn2.html)" \
  --data-urlencode "nom_liste=TEST VIDE $SUF" --data-urlencode "jeton=$(jeton $W/dn2.html)" \
  "$B?r=/distributions&a=nouvelle&type=pen" >/dev/null
grep -qi "au moins un dossier" $W/t74.html; check "T-74" $? "création sans dossier → « Sélectionnez au moins un dossier »"
creer_liste civ "TEST LISTE CIV $SUF"; check "T-72" $? "liste civile TEST créée avec 2 dossiers civils"

IMLIBRE=$(db1 "SELECT COALESCE(MAX(IM),999000)+1 FROM pgcc_client")
echo "══════════ 9. Membres ══════════"
curl -s -c $J3 "$B?r=/inscription" -o $W/ins.html
curl -s -b $J3 -c $J3 -L -o $W/t05.html --data "$(fill_form $W/ins.html)" \
  --data-urlencode "nom=TEST" --data-urlencode "prenom=Inscription$SUF" --data-urlencode "sexe=M" \
  --data-urlencode "email=$REG_EMAIL" --data-urlencode "matricule=$IMLIBRE" \
  --data-urlencode "motDePasse=Test2026!" --data-urlencode "confirmation=Test2026!" \
  --data-urlencode "jeton=$(jeton $W/ins.html)" "$B?r=/inscription" >/dev/null
grep -qiE "enregistrée|demande" $W/t05.html; check "T-05" $? "inscription $REG_EMAIL enregistrée"
curl -s -b $J1 -o $W/att.html "$B?r=/membres/attente"
grep -q "$REG_EMAIL" $W/att.html; check "T-80" $? "compte TEST listé dans « En attente »"
IDN=$(db1 "SELECT id_client FROM pgcc_new_client WHERE login='$REG_EMAIL'")
[ -n "$IDN" ]; check "T-80b" $? "demande en base pgcc_new_client (id=$IDN)"
SRC=$(awk 'BEGIN{RS="<form"} /$REG_EMAIL/' $W/att.html | grep -oE 'name="source" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')

curl -s -c $J2 "$B?r=/connexion" -o $W/connA.html; JTA=$(jeton $W/connA.html)
C=$(curl -s -b $J2 -c $J2 -L -o $W/adm.html -w '%{http_code}' --data-urlencode "email=$TEST_EMAIL" \
  --data-urlencode "motDePasse=$TEST_PASSWORD" --data-urlencode "jeton=$JTA" "$B?r=/connexion")
grep -q "TEST Admin" $W/adm.html; check "T-99" $? "connexion TEST.admin (poste PG → administration) OK"
curl -s -b $J2 -L -o /dev/null --data-urlencode "action=valider" --data-urlencode "id=$IDN" \
  --data-urlencode "source=${SRC:-new}" --data-urlencode "jeton=$JTA" "$B?r=/membres/attente" >/dev/null
VA=$(db1 "SELECT CONCAT(status,'/valide=',valide) FROM pgcc_client WHERE login='$REG_EMAIL'")
echo "$VA" | grep -q "Actifs"; check "T-81" $? "validation → compte copié dans pgcc_client ($VA)"

rm -f $J3; curl -s -c $J3 "$B?r=/connexion" -o $W/c3.html; JTI=$(jeton $W/c3.html)
curl -s -b $J3 -c $J3 -L -o $W/t82.html --data-urlencode "email=$REG_EMAIL" \
  --data-urlencode "motDePasse=Test2026!" --data-urlencode "jeton=$JTI" "$B?r=/connexion" >/dev/null
grep -q "TEST Inscription" $W/t82.html; check "T-82" $? "connexion du nouveau membre validé"
IDC=$(db1 "SELECT id_client FROM pgcc_client WHERE login='$REG_EMAIL'")
curl -s -b $J2 -o $W/act.html "$B?r=/membres/actifs"
curl -s -b $J2 -L -o /dev/null --data-urlencode "action=motdepasse" --data-urlencode "id=$IDC" \
  --data-urlencode "source=client" --data-urlencode "jeton=$JTA" "$B?r=/membres/actifs" >/dev/null
rm -f $J3; curl -s -c $J3 "$B?r=/connexion" -o $W/c3b.html; JTI=$(jeton $W/c3b.html)
curl -s -b $J3 -c $J3 -L -o $W/t83b.html --data-urlencode "email=$REG_EMAIL" \
  --data-urlencode "motDePasse=pgcc2026" --data-urlencode "jeton=$JTI" "$B?r=/connexion" >/dev/null
grep -q "TEST Inscription" $W/t83b.html; check "T-83" $? "réinitialisation mdp → connexion avec « pgcc2026 » généré"
curl -s -b $J2 -L -o /dev/null --data-urlencode "action=retraite" --data-urlencode "id=$IDC" \
  --data-urlencode "source=client" --data-urlencode "jeton=$JTA" "$B?r=/membres/actifs" >/dev/null
SR=$(db1 "SELECT status FROM pgcc_client WHERE login='$REG_EMAIL'")
[ "$SR" = "Retraite" ]; check "T-84a" $? "mise à la retraite → status=$SR"
rm -f $J3; curl -s -c $J3 "$B?r=/connexion" -o $W/c3c.html; JTI=$(jeton $W/c3c.html)
curl -s -b $J3 -o $W/t84x.html --data-urlencode "email=$REG_EMAIL" \
  --data-urlencode "motDePasse=pgcc2026" --data-urlencode "jeton=$JTI" "$B?r=/connexion" >/dev/null
grep -qi "incorrect" $W/t84x.html; check "T-84b" $? "membre retraité refusé à la connexion"
curl -s -b $J2 -L -o /dev/null --data-urlencode "action=reactiver" --data-urlencode "id=$IDC" \
  --data-urlencode "source=client" --data-urlencode "jeton=$JTA" "$B?r=/membres/retraites" >/dev/null
SR=$(db1 "SELECT status FROM pgcc_client WHERE login='$REG_EMAIL'")
[ "$SR" = "Actifs" ]; check "T-84c" $? "réactivation → status=$SR"
MPA=$(db1 "SELECT modifie_par FROM pgcc_client WHERE login='$REG_EMAIL'")
[ "$MPA" = "$TEST_EMAIL" ]; check "T-85" $? "traçabilité : modifie_par=$MPA"

echo "══════════ 10. Mes Informations (profil) ══════════"
curl -s -b $J1 -o $W/prof.html "$B?r=/profil"
curl -s -b $J1 -L -o $W/t90.html --data "$(fill_form $W/prof.html)" \
  --data-urlencode "action=infos" --data-urlencode "bureau=123" \
  --data-urlencode "jeton=$(jeton $W/prof.html)" "$B?r=/profil" >/dev/null
BDB=$(db1 "SELECT num_bureau FROM pgcc_client WHERE id_client=$TEST_ID")
[ "$BDB" = "123" ]; check "T-90" $? "modification n° de bureau → 123 en base"
MDPAV=$(db1 "SELECT mdp FROM pgcc_client WHERE id_client=$TEST_ID")
curl -s -b $J1 -L -o $W/t92.html --data "$(fill_form $W/prof.html)" \
  --data-urlencode "action=motdepasse" --data-urlencode "motDePasseActuel=MAUVAIS" \
  --data-urlencode "motDePasseNouveau=Xtest2026" --data-urlencode "motDePasseConfirm=Xtest2026" \
  --data-urlencode "jeton=$(jeton $W/prof.html)" "$B?r=/profil" >/dev/null
MDPAP=$(db1 "SELECT mdp FROM pgcc_client WHERE id_client=$TEST_ID")
grep -qiE "incorrect" $W/t92.html && [ -n "$MDPAV" ] && [ "$MDPAV" = "$MDPAP" ]
check "T-92" $? "ancien mot de passe invalide refusé, mdp inchangé en base"

echo "══════════ 10bis. Mot de passe oublié ══════════"
curl -s -c $J3 "$B?r=/mot-de-passe-oublie" -o $W/mpd1.html
curl -s -b $J3 -L -o $W/t06.html --data-urlencode "email=$TEST_EMAIL" \
  --data-urlencode "jeton=$(jeton $W/mpd1.html)" "$B?r=/mot-de-passe-oublie" >/dev/null
grep -qi "sans question secrète" $W/t06.html; check "T-06" $? "compte sans question secrète → message dédié"
php -r '$p=new PDO("mysql:host=".getenv("PGCC_E2E_DB_HOST").";dbname=".getenv("PGCC_E2E_DB").";charset=utf8mb4",getenv("PGCC_E2E_DB_USER"),getenv("PGCC_E2E_DB_PASSWORD")); $q=$p->prepare("UPDATE pgcc_client SET question=?, reponse=? WHERE login=?"); $q->execute(["Nom de votre animal ?","felix",getenv("PGCC_E2E_TEST_EMAIL")]);'
curl -s -b $J3 -L -o $W/mpd2.html --data-urlencode "email=$TEST_EMAIL" \
  --data-urlencode "jeton=$(jeton $W/mpd1.html)" "$B?r=/mot-de-passe-oublie" >/dev/null
grep -qi "Nom de votre animal" $W/mpd2.html; check "T-07a" $? "question secrète affichée après saisie de l'e-mail"
MDPAV=$(db1 "SELECT mdp FROM pgcc_client WHERE id_client=$TEST_ID")
curl -s -b $J3 -L -o $W/t07b.html --data "$(fill_form $W/mpd2.html)" \
  --data-urlencode "etape=reponse" --data-urlencode "email=$TEST_EMAIL" \
  --data-urlencode "reponse=mauvaise" --data-urlencode "motDePasse=Nouveau2026!" --data-urlencode "confirmation=Nouveau2026!" \
  --data-urlencode "jeton=$(jeton $W/mpd2.html)" "$B?r=/mot-de-passe-oublie" >/dev/null
MDPAP=$(db1 "SELECT mdp FROM pgcc_client WHERE id_client=$TEST_ID")
grep -qiE "incorrect|erreur" $W/t07b.html && [ -n "$MDPAV" ] && [ "$MDPAV" = "$MDPAP" ]
check "T-07b" $? "mauvaise réponse → refus, mdp inchangé"
curl -s -b $J3 -L -o $W/t07c.html --data "$(fill_form $W/mpd2.html)" \
  --data-urlencode "etape=reponse" --data-urlencode "email=$TEST_EMAIL" \
  --data-urlencode "reponse=felix" --data-urlencode "motDePasse=Recup2026!" --data-urlencode "confirmation=Recup2026!" \
  --data-urlencode "jeton=$(jeton $W/mpd2.html)" "$B?r=/mot-de-passe-oublie" >/dev/null
rm -f $J3; curl -s -c $J3 "$B?r=/connexion" -o $W/c3d.html; JTI=$(jeton $W/c3d.html)
curl -s -b $J3 -L -o $W/t07d.html --data-urlencode "email=$TEST_EMAIL" \
  --data-urlencode "motDePasse=Recup2026!" --data-urlencode "jeton=$JTI" "$B?r=/connexion" >/dev/null
grep -q "TEST Admin" $W/t07d.html; check "T-07c" $? "bonne réponse → connexion avec le nouveau mot de passe"

echo "Synchronisation v1 (facultative, copie de test uniquement)"
IDSYNC=$(creer_penal "TEST-SYNC-$SUF" "TEST Sync")
[ -n "$IDSYNC" ]; check "T-100a" $? "dossier TEST-SYNC-$SUF cree dans la base isolee"
if [ -n "${PGCC_E2E_V1_BASE_URL:-}" ]; then
  V1_DB=$(curl -fsS "${PGCC_E2E_V1_BASE_URL}?e2e_probe=1") || {
    echo "Refus : la copie v1 n'est pas en mode E2E isole." >&2
    exit 2
  }
  if [ "$V1_DB" != "$E2E_DB" ]; then
    echo "Refus : la copie v1 utilise une autre base que $E2E_DB." >&2
    exit 2
  fi
  JV1=/tmp/e2e_v1.txt; rm -f "$JV1"
  curl -s -c "$JV1" -b "$JV1" -L -o "$W/v1c.html" --data-urlencode "txtEmail=$TEST_EMAIL" \
    --data-urlencode "txtPwd=Recup2026!" "$PGCC_E2E_V1_BASE_URL" >/dev/null
  curl -s -b "$JV1" -L -o "$W/v1pen.html" "${PGCC_E2E_V1_BASE_URL%/}/registrePenaux.php?annee=2026"
  grep -q "TEST-SYNC-$SUF" "$W/v1pen.html"; check "T-100b" $? "dossier visible dans la copie v1 isolee"
else
  echo "  SKIP T-100b : aucune URL v1 de test fournie."
fi

echo "Base isolee conservee pour inspection : $E2E_DB (supprimer la base entiere apres validation)."
rm -f $J1 $J2 $J3 /tmp/e2e_v1.txt

echo "══════════════════════════════════════════════"
echo "RÉSULTAT E2E : $PASS OK, $FAIL KO"
[ -n "$ECHECS" ] && echo "Cas en échec :$ECHECS"
exit $FAIL
