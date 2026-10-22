<?php

session_start();
	
	if(!isset($_SESSION['session_user_id']))
	
	{
		echo "<script language=\"javascript\"> " ;
			
		echo " document.location.href = 'index.php'";
			
		echo "</script>" ;
	}
	
	//include("cookiesFile.php");
	
	require("libs/smarty.class.php");

	require("modules/header/commun_header.php");

	require("cnx.php");
	
	$iEnreg = 0;
	
	//Déconnexion PAGE
	
	$iExit = isset($_REQUEST['iExit']);
	
	/*if($iExit == 1){
		
		session_destroy();
		
		echo "<script language=\"javascript\"> " ;
			
		echo " document.location.href = 'index.php'";
			
		echo "</script>" ;
		
	}*/
	
	$iEnreg = isset($_REQUEST['iEnreg']);
	
	if($iEnreg ==1)
	
	{	
	
		$zSigle = $_REQUEST['txtSigle'];
	
		$zPwdNouveau= md5($_REQUEST['txtPwdNouveau']);
		
		$sql = "UPDATE `pgcc_client` SET `mdp`='".$zPwdNouveau."'";
		
		$sql .=" WHERE `sigle_AG`='".$zSigle."'";
		
		$resultats=$bdd->query($sql);
		
	}
	
	$oSmarty = new Smarty();
	
	$oSmarty->assign('sessionId',$_SESSION['session_user_id']);
	
	$oSmarty->assign('zPoste',$_SESSION['session_poste']);
	
	$oSmarty->display("pwdAG.tpl");
	
?>