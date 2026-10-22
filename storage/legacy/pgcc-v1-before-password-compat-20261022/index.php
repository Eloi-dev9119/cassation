<?php

	session_start();
	
	require("libs/smarty.class.php");

	require("modules/header/commun_header.php");

	require("cnx.php");
	
	$iEnreg = 0 ;

	$zError = "";
	
	$oSmarty = new Smarty();
	
	$iEnreg = isset($_REQUEST['iEnreg']) ;
	
	if($iEnreg == 1){
		
		if((isset($_REQUEST['txtEmail']))&&(isset($_REQUEST['txtPwd'])))
		
		{
			$zEmail = $_REQUEST['txtEmail'] ;
		
			$zMdp =  md5($_REQUEST['txtPwd']) ;
			
			//$sql = "SELECT login,nom,prenom FROM pgcc_client WHERE login = '".$zEmail."' AND valide = 1";
			
			$sql = "SELECT login,nom,prenom FROM pgcc_client WHERE login = '".$zEmail."'";
			
			$resultats=$bdd->query($sql);
			
			$resultats->setFetchMode(PDO::FETCH_OBJ);
			
			while( $ligne = $resultats->fetch() ) 
			
			{
				
				$userLogin = $ligne->login; 
				
				$userId = $ligne->nom.'&nbsp;&nbsp;'.$ligne->prenom; 
				
			}
			
			if(isset($userLogin)){
				
				$sql = "SELECT mdp,id_client,login,poste,valide FROM pgcc_client WHERE login = '".$zEmail."' AND mdp = '".$zMdp."'";
			
				$resultats=$bdd->query($sql);
				
				$resultats->setFetchMode(PDO::FETCH_OBJ);
				
				while( $ligne = $resultats->fetch() ) 
				
				{
					$_SESSION['userClientId'] = $ligne->id_client;
					
					$userPwd = $ligne->mdp; 
					
					$userLogin = $ligne->login;
					
					$userPoste = $ligne->poste;
					
					$userValide = $ligne->valide;
					
				}
				
				if(isset($userPwd)){
					
					if(!isset($_SESSION['session_user_id']))
					
					{
						$_SESSION['session_user_id'] = $userId;
						
						$_SESSION['session_user_login'] = $userLogin;
						
						$_SESSION['session_poste'] = $userPoste ;
					
						
					}
					
					if($userValide == 1)
					
					{
					
						echo "<script language=\"javascript\"> " ;
			
						echo " document.location.href = 'accueil.php'";
			
						echo "</script>" ;
					
					}
					
					else
					
					{
						$zError = 'Inscription en attente de VALIDATION !!!' ;
					}
					
				}
				else
				{
					
					$zError = 'Mot de passe incorrect' ;
					
				}
				
			}
			
			else
			{
					
					$zError  ='L\'utilisateur n\'existe pas<br> Veuillez créer un compte' ;
					
			}
		 
		}
	}
	
	else{
		
		//echo 'Not Ok';
	}
	
	$oSmarty->assign('zError',$zError);
	
	$oSmarty->display("index.tpl");

?>