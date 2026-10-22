<?php
	/* 
		Créer le 24/02/2014 par Hery RABEHARIVELO
	*/
	
	// Connexion Database

	try {
		
		
		/*$PARAM_hote='localhost'; 

		$PARAM_port='8888';
	
		$PARAM_nom_bd='pgcc'; 
	
		$PARAM_utilisateur='root';
	
		$PARAM_mot_passe='root';
	
		$bdd  = new PDO('mysql:host='.$PARAM_hote.';port='.$PARAM_port.';dbname='.$PARAM_nom_bd, $PARAM_utilisateur, $PARAM_mot_passe);*/
		
		$bdd = new PDO("mysql:host=localhost;dbname=pgcc_utf8mb4;charset=utf8mb4", "root", "");

		$bdd->query("SET NAMES utf8mb4");

		// Compatibilite PHP 7+/8 : mysql_real_escape_string supprimee depuis PHP 7, encore appelee par les formulaires (registres, conclusions)
		if (!function_exists('mysql_real_escape_string')) {

			function mysql_real_escape_string($chaine) {

				return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $chaine);

			}

		}

	} catch (Exception $e) {

	    echo "Problème de connexion à la base de donnée pgcc_utf8mb4...";

	    die();

	}

?>