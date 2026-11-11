@echo off
rem ===========================================================================
rem  PGCC v2 - Sauvegarde de la base pgcc_utf8mb4 (mysqldump)
rem
rem  Usage :
rem    - double-cliquer, ou lancer depuis une console ;
rem    - planification automatique : tools\planifier-sauvegarde.bat
rem
rem  Variables surchargeables avant l'appel :
rem    MYSQLBIN   dossier de mysqldump/mysql (defaut C:\xampp\mysql\bin)
rem    DB         base a sauvegarder          (defaut pgcc_utf8mb4)
rem    USERMYSQL  utilisateur MySQL           (defaut root)
rem    MYSQLPWD   mot de passe MySQL          (defaut : aucun)
rem    KEEP       sauvegardes conservees      (defaut 30)
rem    COMPRESSER 1 = archive .zip du dump    (defaut 1)
rem
rem  - Dump complet (routines + evenements), charset utf8mb4
rem  - Fichier horodate a la seconde dans database\backups\
rem  - Compression .zip par defaut (environ 10 fois plus petit)
rem  - Journal database\backups\sauvegarde.log (rotation au-dela de 1 Mo)
rem  - Retention des KEEP sauvegardes les plus recentes (.sql et .zip)
rem ===========================================================================
setlocal EnableExtensions

if not defined MYSQLBIN set "MYSQLBIN=C:\xampp\mysql\bin"
if not defined DB set "DB=pgcc_utf8mb4"
if not defined USERMYSQL set "USERMYSQL=root"
if not defined KEEP set "KEEP=30"
if not defined COMPRESSER set "COMPRESSER=1"

set "DEST=%~dp0..\database\backups"
set "LOG=%DEST%\sauvegarde.log"

rem KEEP doit etre un entier superieur ou egal a 1.
set "KEEP_NUM=30"
set /a KEEP_NUM=%KEEP% >nul 2>&1
if %KEEP_NUM% LSS 1 set "KEEP_NUM=30"

if not exist "%MYSQLBIN%\mysqldump.exe" (
    call :journal "[ERREUR] mysqldump introuvable : %MYSQLBIN%\mysqldump.exe"
    endlocal & exit /b 1
)
if not exist "%MYSQLBIN%\mysql.exe" (
    call :journal "[ERREUR] mysql introuvable : %MYSQLBIN%\mysql.exe"
    endlocal & exit /b 1
)
if not exist "%DEST%" mkdir "%DEST%" 2>nul

rem Rotation du journal : au-dela de 1 Mo, on conserve la generation .old.
if exist "%LOG%" for %%A in ("%LOG%") do if %%~zA GTR 1048576 move /y "%LOG%" "%LOG%.old" >nul 2>&1

set "PWDARG="
if defined MYSQLPWD set "PWDARG=--password=%MYSQLPWD%"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%i"
if not defined STAMP set "STAMP=inconnu"
set "FICHIER=%DEST%\%DB%_%STAMP%.sql"

call :journal "Debut de la sauvegarde de %DB% vers %FICHIER%"

rem --- Verification de l'existence de la base avant le dump ---------------
"%MYSQLBIN%\mysql.exe" -u %USERMYSQL% %PWDARG% -N -B -e "SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='%DB%'" >"%TEMP%\pgcc_db_check.txt" 2>>"%LOG%"
set "DBCHECK="
for /f "usebackq delims=" %%i in ("%TEMP%\pgcc_db_check.txt") do set "DBCHECK=%%i"
del "%TEMP%\pgcc_db_check.txt" >nul 2>&1
if not "%DBCHECK%"=="1" (
    call :journal "[ECHEC] Base introuvable ou MySQL inaccessible : %DB%"
    endlocal & exit /b 1
)

rem --- Dump ----------------------------------------------------------------
"%MYSQLBIN%\mysqldump.exe" -u %USERMYSQL% %PWDARG% --single-transaction --routines --events --default-character-set=utf8mb4 --result-file="%FICHIER%" %DB% >>"%LOG%" 2>&1
if errorlevel 1 goto :echec

for %%A in ("%FICHIER%") do set "TAILLE=%%~zA"
if not defined TAILLE set "TAILLE=0"
if "%TAILLE%"=="0" goto :echec

call :journal "[OK] Sauvegarde terminee : %FICHIER% (%TAILLE% octets)"

rem --- Compression .zip (optionnelle, activee par defaut) ----------------
if "%COMPRESSER%"=="1" call :compresser

rem --- Retention : conserve les KEEP_NUM fichiers les plus recents ---------
set "SUPPRIMES=0"
for /f "skip=%KEEP_NUM% delims=" %%F in ('dir /b /o-d /a-d "%DEST%" ^| findstr /b /c:"%DB%_" ^| findstr /e /c:".sql" /c:".zip"') do (
    del "%DEST%\%%F" >nul 2>&1
    set /a SUPPRIMES+=1
)
if not "%SUPPRIMES%"=="0" call :journal "Retention : %SUPPRIMES% ancienne(s) sauvegarde(s) supprimee(s), %KEEP_NUM% conservee(s)."

endlocal & exit /b 0

:compresser
call :journal "Compression de la sauvegarde (environ 10 fois plus petite)..."
powershell -NoProfile -Command "Compress-Archive -LiteralPath '%FICHIER%' -DestinationPath '%FICHIER%.zip' -CompressionLevel Optimal -Force" >>"%LOG%" 2>&1
if errorlevel 1 (
    call :journal "[AVERTISSEMENT] Compression impossible : le fichier .sql est conserve."
    exit /b 0
)
del "%FICHIER%" >nul 2>&1
set "FICHIER=%FICHIER%.zip"
for %%A in ("%FICHIER%") do set "TAILLE=%%~zA"
call :journal "[OK] Archive compressee : %FICHIER% (%TAILLE% octets)"
exit /b 0

:echec
call :journal "[ECHEC] La sauvegarde a echoue. Verifiez que MySQL est demarre et que les identifiants sont corrects."
if exist "%FICHIER%" del "%FICHIER%" >nul 2>&1
endlocal & exit /b 1

:journal
rem Affiche le message et l'ajoute au journal horodate.
set "MSG=%~1"
set "HORODATAGE=%DATE% %TIME%"
echo %MSG%
>>"%LOG%" echo [%HORODATAGE%] %MSG%
exit /b 0
