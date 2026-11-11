@echo off
rem ===========================================================================
rem  PGCC v2 - Deploiement du site local (XAMPP)
rem
rem  Depuis le 2026-11-11, le dossier du projet est servi directement par
rem  Apache : C:\xampp\htdocs\pgcc2 est une JONCTION vers le depot de travail.
rem  Aucune copie de fichiers n'est necessaire pour publier le site : il suffit
rem  que la jonction soit en place et pointe vers le bon dossier.
rem
rem  Usage :
rem    tools\deployer-site.bat           rem diagnostic de la jonction
rem    tools\deployer-site.bat reparer   rem (re)cree la jonction si besoin
rem
rem  Ajustez PROJET et LIEN ci-dessous si les emplacements changent.
rem ===========================================================================
setlocal EnableExtensions

rem  Depot de travail (source servie par Apache).
set "PROJET=C:\Users\e\Desktop\PGCCS\LOGICIEL CASSATION\LogicielCassation"
rem  Lien attendu sous le dossier web de XAMPP.
set "LIEN=C:\xampp\htdocs\pgcc2"

echo Deploiement du site local PGCC v2
echo   Projet : %PROJET%
echo   Lien   : %LIEN%
echo.

if not exist "%PROJET%\index.php" (
    echo [ERREUR] Depot introuvable : %PROJET%
    echo          Verifiez la variable PROJET dans ce script.
    exit /b 1
)

rem --- Lecture de la cible actuelle de la jonction --------------------------
set "CIBLE="
if exist "%LIEN%" (
    for /f "tokens=2 delims=[]" %%T in ('dir /AL "%LIEN%\.." 2^>nul ^| findstr /i /c:"pgcc2 ["') do set "CIBLE=%%T"
)

if not defined CIBLE goto :absent

echo Cible actuelle : %CIBLE%
if /I "%CIBLE%"=="%PROJET%" (
    echo [OK] La jonction pointe vers le depot de travail : le site est a jour.
    exit /b 0
)
echo [ATTENTION] La jonction pointe vers un autre dossier que le depot de travail.
goto :reparer

:absent
if not exist "%LIEN%" (
    echo [ATTENTION] Aucune jonction %LIEN% detectee.
) else (
    echo [ATTENTION] %LIEN% existe mais n'est pas la jonction attendue.
)
goto :reparer

:reparer
if /I not "%~1"=="reparer" (
    echo.
    echo Pour corriger la jonction, executez :
    echo   tools\deployer-site.bat reparer
    exit /b 1
)

echo.
echo Correction de la jonction...
if exist "%LIEN%\" (
    rem rmdir supprime uniquement le lien de jonction, jamais son contenu cible.
    rmdir "%LIEN%" || goto :echec
)
mklink /J "%LIEN%" "%PROJET%" || goto :echec

echo [OK] Jonction en place : %LIEN%  -^>  %PROJET%
echo.
echo Si le site ne reflete pas le changement, redemarrez Apache :
echo   net stop Apache2.4 ^& net start Apache2.4   (ou via le panneau XAMPP)
exit /b 0

:echec
echo [ECHEC] Impossible de (re)creer la jonction.
echo         Verifiez que %LIEN% n'est pas ouvert par un autre programme,
echo         puis relancez : tools\deployer-site.bat reparer
exit /b 1
