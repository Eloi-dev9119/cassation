<?php
	
	require("cnx.php");
	
	require("libs/smarty.class.php");
	
	session_start();
	
	echo 'nom = '.(isset($_SESSION['nom']) ? $_SESSION['nom'] : '');

	echo 'prenom ='.(isset($_SESSION['prenom']) ? $_SESSION['prenom'] : '');
	
	$oSmarty = new Smarty();
	
	//Recuperatoin des variables
	
	if((isset($_REQUEST['txtEmail']))&&(isset($_REQUEST['txtPwd']))&&(isset($_REQUEST['txtPwd2']))&&(isset($_REQUEST['txtNom']))&&(isset($_REQUEST['txtPrenom']))&&(isset($_REQUEST['slSexe']))&&(isset($_REQUEST['txtMatricule']))&&(isset($_REQUEST['slPoste']))&&(isset($_REQUEST['txtSigne']))&&(isset($_REQUEST['slBureau'])))
	
	{
		$zEmail = $_REQUEST['txtEmail'];
	
		$Mdp = md5($_REQUEST['txtPwd']);
		
		$Mdpconfirm = $_REQUEST['txtPwd2'];
		
		$zNom = $_REQUEST['txtNom'];
		
		$zPrenom = $_REQUEST['txtPrenom'];
		
		$zSexe = $_REQUEST['slSexe'];
		
		$zMatricule = $_REQUEST['txtMatricule'];
		
		$zPoste = $_REQUEST['slPoste'];
		
		$zSigne = $_REQUEST['txtSigne'];
		
		$zBureau = $_REQUEST['slBureau'];
	
		$sql = "INSERT INTO pgcc_client (id_client,nom,prenom,sexe,login,mdp,poste,sigle_AG,IM,num_bureau,avatar,question,reponse,session_state,archiver) VALUES (NULL ,'".$zNom."','".$zPrenom."','".$zSexe."','".$zEmail."','".$Mdp."','".$zPoste."','".$zSigne."','".$zMatricule."','".$zBureau."',0,0,0,0,0)";
		
		$sqlInsert = $bdd->query($sql);
		
	}
	
	$oSmarty->display("contenuInscript.tpl");
	
?>