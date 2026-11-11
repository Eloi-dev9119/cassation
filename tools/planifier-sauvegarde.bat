@echo off
rem ===========================================================================
rem  PGCC v2 - Planification de la sauvegarde automatique de la base
rem
rem  Usage :
rem    planifier-sauvegarde.bat creer [HH:MM]   installe la tache quotidienne
rem    planifier-sauvegarde.bat supprimer        retire la tache
rem    planifier-sauvegarde.bat statut           affiche l'etat de la tache
rem    planifier-sauvegarde.bat executer         lance la tache tout de suite
rem
rem  Sans argument : "creer 18:00".
rem  Exemple : planifier-sauvegarde.bat creer 20:30
rem
rem  La tache execute tools\sauvegarde-bdd.bat avec les reglages par
rem  defaut (base pgcc_utf8mb4, rotation 30 sauvegardes). Pour changer ces
rem  reglages, modifiez les variables en tete de sauvegarde-bdd.bat.
rem
rem  Remarque : la creation/suppression peut exiger une console ouverte
rem  en tant qu'administrateur, selon la configuration de Windows.
rem ===========================================================================
setlocal EnableExtensions

set "NOM=PGCC - Sauvegarde base de donnees"
set "SCRIPT=%~dp0sauvegarde-bdd.bat"
set "HEURE=%~2"
if not defined HEURE set "HEURE=18:00"

set "ACTION=%~1"
if not defined ACTION set "ACTION=creer"

if /i "%ACTION%"=="creer"     goto :creer
if /i "%ACTION%"=="supprimer" goto :supprimer
if /i "%ACTION%"=="statut"    goto :statut
if /i "%ACTION%"=="executer"  goto :executer
goto :usage

:creer
if not exist "%SCRIPT%" (
    echo [ERREUR] Script de sauvegarde introuvable : %SCRIPT%
    exit /b 1
)
echo %HEURE%| findstr /r "^[0-2][0-9]:[0-5][0-9]$" >nul
if errorlevel 1 (
    echo [ERREUR] Heure invalide : %HEURE% ^(format attendu HH:MM^)
    exit /b 1
)

echo Creation de la tache quotidienne "%NOM%" a %HEURE% ...
schtasks /Create /TN "%NOM%" /TR "\"%SCRIPT%\"" /SC DAILY /ST %HEURE% /F
if errorlevel 1 (
    echo [ECHEC] Creation impossible. Ouvrez une console en administrateur puis relancez.
    exit /b 1
)
echo [OK] Tache planifiee chaque jour a %HEURE%.
echo Les sauvegardes sont ecrites dans database\backups\ ; journal : sauvegarde.log
endlocal & exit /b 0

:supprimer
echo Suppression de la tache "%NOM%" ...
schtasks /Delete /TN "%NOM%" /F
if errorlevel 1 (
    echo [ECHEC] Tache introuvable ou suppression refusee ^(droits administrateur^).
    exit /b 1
)
echo [OK] Tache supprimee.
endlocal & exit /b 0

:statut
schtasks /Query /TN "%NOM%" /V /FO LIST >"%TEMP%\pgcc_tache.txt" 2>&1
if errorlevel 1 (
    del "%TEMP%\pgcc_tache.txt" >nul 2>&1
    echo [INFO] Aucune tache "%NOM%" installee.
    echo        Installez-la avec : planifier-sauvegarde.bat creer 18:00
    endlocal & exit /b 1
)
type "%TEMP%\pgcc_tache.txt"
del "%TEMP%\pgcc_tache.txt" >nul 2>&1
endlocal & exit /b 0

:executer
schtasks /Run /TN "%NOM%"
if errorlevel 1 (
    echo [ECHEC] Impossible de lancer la tache. Verifiez qu'elle est installee.
    exit /b 1
)
echo [OK] Tache lancee. Suivez la progression dans database\backups\sauvegarde.log
endlocal & exit /b 0

:usage
echo Usage :
echo   planifier-sauvegarde.bat creer [HH:MM]
echo   planifier-sauvegarde.bat supprimer
echo   planifier-sauvegarde.bat statut
echo   planifier-sauvegarde.bat executer
endlocal & exit /b 1
