<?php

	require("libs/smarty.class.php");
	
	require("cnx.php");
	
	session_start();
	
	$oSmarty = new Smarty();
	
	//Récuperation des variables 
	
	if((isset($_REQUEST['txtEmail']))&&(isset($_REQUEST['txtPwd'])))
	
	{
		$zEmail = $_REQUEST['txtEmail'] ;
	
		$zMdp =  md5($_REQUEST['txtPwd']) ;
		
		$sql = "SELECT nom,prenom,mdp FROM pgcc_client WHERE login = '".$zEmail."'";
		
		$resultats=$bdd->query($sql);
		
		$resultats->setFetchMode(PDO::FETCH_OBJ);
		 
		while( $ligne = $resultats->fetch() ) 
		
		{
			$_SESSION['nom'] = $ligne->nom; 
				
			$_SESSION['prenom'] = $ligne->prenom; 
				
			$cMdp = $ligne->mdp; 
				
			if($zMdp == $cMdp){
				
				echo 'Ok';
				
			}
			else
				
			{
				echo 'l\'identifiant ou mot de passe sont incorrectes';
			}
				
		}
		
		$resultats->closeCursor(); 
	}
	
	else
	
	{
		echo 'Parametre vide ';
	}
	
	/*$oSmarty->assign('cNom',$cNom);
		
	$oSmarty->assign('cPrenom',$cPrenom);*/
	
	$oSmarty->display("connexion.tpl");
	
?>