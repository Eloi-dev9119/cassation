
<?php

	require("libs/smarty.class.php");

	require("modules/header/commun_header.php");

	require("cnx.php");
	
	$oSmarty = new Smarty();
	
	$iExit = 0 ;
	
	$eMail = "" ;
	
	$zSigle = "";
	
	$iVerif2 = 0;
	
	$iEnreg = 0;
	
	$iVerif = 0;
	
	$regClientId = 0 ;
	
	$zError = "";
	
	$zError2 = "";
	
	$iErreur = 0;
	
	$zSigne = 0;
	
	$iInscript = 0;
	
	$zInscript = "";
	
	//Verification adresse e-mail
	
	$iVerif  = isset($_REQUEST['iVerif']);
	
	if($iVerif == 1)
	
	{
	
		$eMail = $_REQUEST['eMail'];
		
		if(isset($eMail))
		
		{
		
			$sql = "SELECT id_client FROM pgcc_client WHERE login='".$eMail."'";
			
			$resultats=$bdd->query($sql);
				
			$resultats->setFetchMode(PDO::FETCH_OBJ);
				
			while( $ligne = $resultats->fetch() ) 
				
			{
				$regClientId = $ligne->id_client;
			}
		
			if($regClientId == 0)
			
			{
				$zError = 'L\'adresse e-mail valide';
				
				$iErreur = 0 ;
			}
			
			else
			
			{
				$zError = 'L\'adresse e-mail appartient &agrave; quelq\'un ';
				
				$iErreur = 1 ;
			}
		
		}
	}
	
	$iVerif2 = isset($_REQUEST['iVerif2']);
	
	if($iVerif2 == 1)
	
	{
		$zSigle = $_REQUEST['eSigle'];
		
			$sql = "SELECT id_client FROM pgcc_client WHERE sigle_AG='".$zSigle."'";
			
			$resultats=$bdd->query($sql);
				
			$resultats->setFetchMode(PDO::FETCH_OBJ);
				
			while( $ligne = $resultats->fetch() ) 
				
			{
				$regClientId = $ligne->id_client;
			}
			
			if($regClientId == 0)
			
			{
				$zError2 = 'Sigle AG valide';
				
				$iErreur2 = 0 ;
			}
			
			else
			
			{
				$zError2 = 'Le sigle AG appartient &agrave; quelq\'un ';
				
				$iErreur2 = 1 ;
			}
		
		}
		
	//Enregistrement Nouveau Inscription
		
	$iEnreg = isset($_REQUEST['iEnreg']);
	
	if($iEnreg == 1)
	
	{
		if((isset($_REQUEST['txtEmail']))&&(isset($_REQUEST['txtPwd']))&&(isset($_REQUEST['txtPwd2'])))
			
			{
				$zEmail = $_REQUEST['txtEmail'];
				
				$sqlMail = "SELECT id_client FROM pgcc_client WHERE login='".$zEmail."'";
				
				$resultats=$bdd->query($sqlMail);
					
				$resultats->setFetchMode(PDO::FETCH_OBJ);
					
				while( $ligne = $resultats->fetch() ) 
					
				{
					$regClientId = $ligne->id_client;
				}
			
				if($regClientId == 0)
				
				{
					
					$zSigne = $_REQUEST['txtSigne'];
					
					if($zSigne != "")
					
					{
						$sqlSigle = "SELECT id_client FROM pgcc_client WHERE sigle_AG='".$zSigne."'";
					
						$resultats=$bdd->query($sqlSigle);
							
						$resultats->setFetchMode(PDO::FETCH_OBJ);
							
						while( $ligne = $resultats->fetch() ) 
							
						{
							$regClientId = $ligne->id_client;
						}
						
						if($regClientId == 0)
						
						{
							$zEmail = $_REQUEST['txtEmail'];
				
							$Mdp = md5($_REQUEST['txtPwd']);
							
							$Mdpconfirm = $_REQUEST['txtPwd2'];
							
							$zNom = strtoupper($_REQUEST['txtNom']);
							
							$zPrenom = ucfirst($_REQUEST['txtPrenom']);
							
							$zSexe = $_REQUEST['slSexe'];
							
							$zMatricule = $_REQUEST['txtMatricule'];
							
							$zPoste = $_REQUEST['slPoste'];
							
							$zBureau = $_REQUEST['slBureau'];
							
							$sql = "INSERT INTO pgcc_client (nom,prenom,sexe,login,mdp,poste,sigle_AG,IM,num_bureau,archiver,valide,status) VALUES ('".$zNom."','".$zPrenom."','".$zSexe."','".$zEmail."','".$Mdp."','".$zPoste."','".$zSigne."','".$zMatricule."','".$zBureau."',0,0,'Actifs')";
							
							$sqlInsert = $bdd->query($sql);
					
							header ('Location: index.php');
				
						}
						
						else
						
						{
							$zError2 = 'Le sigle AG appartient &agrave; quelq\'un ';
							
						}
					}
					
					else
					
					{
							$zEmail = $_REQUEST['txtEmail'];
				
							$Mdp = md5($_REQUEST['txtPwd']);
							
							$Mdpconfirm = $_REQUEST['txtPwd2'];
							
							$zNom = strtoupper($_REQUEST['txtNom']);
							
							$zPrenom = ucfirst($_REQUEST['txtPrenom']);
							
							$zSexe = $_REQUEST['slSexe'];
							
							$zMatricule = $_REQUEST['txtMatricule'];
							
							$zPoste = $_REQUEST['slPoste'];
							
							$zBureau = $_REQUEST['slBureau'];
							
							$zStatus = 'Actifs';
							
							$sql = "INSERT INTO pgcc_client (id_client,nom,prenom,sexe,login,mdp,poste,IM,num_bureau,avatar,question,reponse,session_state,archiver,status) VALUES (NULL ,'".$zNom."','".$zPrenom."','".$zSexe."','".$zEmail."','".$Mdp."','".$zPoste."','".$zMatricule."','".$zBureau."',0,0,0,0,0,'".$zStatus."')";
							
							$sqlInsert = $bdd->query($sql);
					
							header ('Location: index.php');
					}
					
				}
				
				else
			
				{
					$zError = 'L\'adresse e-mail appartient &agrave; quelq\'un ';
					
					
				}
			}
	}
	
	//Affichage Pages
	
	$iInscript = isset($_REQUEST['iInscript']);
	
	if($iInscript == 1)
	
	{
		$zInscript = $_REQUEST['zInscript'];
	}
	
	
	//Recuperatoin des variables
	
	$oSmarty->assign('zError',$zError);
	
	$oSmarty->assign('zError2',$zError2);
	
	$oSmarty->assign('zInscript',$zInscript);
	
	$oSmarty->display("inscription2.tpl");
	
?>
