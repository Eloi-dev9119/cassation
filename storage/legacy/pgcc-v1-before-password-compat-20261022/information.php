<?php

	session_start();
	
	require("libs/smarty.class.php");

	require("modules/header/commun_header.php");

	require("cnx.php");
	
	$oSmarty = new Smarty();
	
	if(!isset($_SESSION['session_user_id']))
	
	{
		echo "<script language=\"javascript\"> " ;
			
		echo " document.location.href = 'index.php'";
			
		echo "</script>" ;
	}
	
	$zClientId =  $_SESSION['userClientId'];
	
	$iExit = 0;
	
	$iEnreg = 0;
	
	$iEnreg2 = 0;
	
	$zMdp = 0;
	
	$zError = "";
	
	//Déconnexion PAGE
		
	$iExit = isset($_REQUEST['iExit']);
	
	if($iExit == 1){
		
		session_destroy();
		
		echo "<script language=\"javascript\"> " ;
			
		echo " document.location.href = 'index.php'";
			
		echo "</script>" ;
		
	}
	
	$iEnreg = isset($_REQUEST['iEnreg']);
	
	if($iEnreg == 1)
	
	{
		if(isset($_REQUEST['txtNom']))
			
			{
			
				$zNom = $_REQUEST['txtNom'];
				
				$zPrenom = $_REQUEST['txtPrenom'];
				
				$zSexe = $_REQUEST['slSexe'];
				
				$zPoste = $_REQUEST['slPoste'];
				
				$zNumMatricule = $_REQUEST['txtNumMatricule'];
				
				$zNumBureau = $_REQUEST['txtNumBureau'];
				
				$sql = "UPDATE `pgcc_client` SET  `nom`='".$zNom."',`prenom`='".$zPrenom."',`sexe`='".$zSexe."',`num_bureau`='".$zNumBureau."',`poste`='".$zPoste."',`IM`='".$zNumMatricule."'";
				
				$sql .=" WHERE id_client=".$zClientId;
				
			    $sqlInsert = $bdd->query($sql);
				
			}
	}
	
	$sql = "SELECT * FROM pgcc_client WHERE id_client='".$zClientId."'";
			
	$resultats=$bdd->query($sql);
				
	$resultats->setFetchMode(PDO::FETCH_OBJ);
				
	while( $ligne = $resultats->fetch() ) 
				
	{
		
		$regClientId = $ligne->id_client;
		
		$regClientNom  = $ligne->nom;
		
		$regClientPrenom = $ligne->prenom ;
		
		$regSexe = $ligne->sexe;
		
		$regPoste = $ligne->poste;
		
		$regMatricule = $ligne->IM;
		
		$regBureau = $ligne->num_bureau;
		
	}
			
	$iEnreg2 = isset($_REQUEST['iEnreg2']);
	
	if($iEnreg2 == 1)
	
	{
		if(isset($_REQUEST['txtPwdAncien']))
			
			{
				$zOldPwd = md5($_REQUEST['txtPwdAncien']);
				
				$sqlSelect = "SELECT id_client from pgcc_client WHERE mdp ='".$zOldPwd."'";
				
				$sqlSelect .=" AND id_client=".$zClientId;
				
				$resultats=$bdd->query($sqlSelect);
				
				$resultats->setFetchMode(PDO::FETCH_OBJ);
							
				while( $ligne = $resultats->fetch() ) 
							
				{
					$zMdp = $ligne->id_client ;
				}
				
				 if($zMdp != 0)
				 
				 {
				 		$zNewPwd = md5($_REQUEST['txtPwdNouveau']);
				
						$sql = "UPDATE `pgcc_client` SET  `mdp`='".$zNewPwd."'";
						
						$sql .=" WHERE id_client=".$zClientId;
						
						$sqlInsert = $bdd->query($sql);
						
						session_destroy();
		
						echo "<script language=\"javascript\"> " ;
							
						echo " document.location.href = 'index.php'";
							
						echo "</script>" ;
				 }
				 
				 else
				 
				 {
				 	$zError = "Ancien mot de passe  incorrecte !!! ";
					
				 }
				
			}
	}		
	
	$oSmarty->assign('sessionId',$_SESSION['session_user_id']);
	
	$oSmarty->assign('zPoste',$_SESSION['session_poste']);
	
	$oSmarty->assign('regClientNom',$regClientNom);
	
	$oSmarty->assign('regClientPrenom',$regClientPrenom);
	
	$oSmarty->assign('regSexe',$regSexe);
	
	$oSmarty->assign('regPoste',$regPoste);
	
	$oSmarty->assign('regMatricule',$regMatricule);
	
	$oSmarty->assign('regBureau',$regBureau);
	
	$oSmarty->assign('zError',$zError);
	

	$oSmarty->display("information.tpl");
	
?>
