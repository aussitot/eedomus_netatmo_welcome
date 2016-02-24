<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

define('__ROOT__', dirname(dirname(__FILE__)));
require_once 'NW-Config.php';

//------ Variables
$cache_file = 'NW-Cache.txt'; //nom du fichier cache

/**
* Webhooks Endpoint.
* This script has to be hosted on a webserver in order to make it work.
* This endpoint should first be registered as webhook URI in your app settings on Netatmo Developer Platform (or registered using the API).
* If you don't known how to register a webhook, or simply need ore information please refer to documentation: https://dev.netatmo.com/doc/webhooks)
*/

//Get the post JSON sent by Netatmo servers
$jsonData = file_get_contents("php://input");

//Each time a webhook notification is sent
if(!is_null($jsonData) && !empty($jsonData))
{
    //first check the data sent using its signature (contained in X-Netatmo-secret HTTP header). $client_secret corresponds to your application secret in NW-Config.php
    if(hash_hmac("sha256", $jsonData, $client_secret) !== $_SERVER['HTTP_X_NETATMO_SECRET'])
    {
        //be careful on the way you handle issues, if you send back too many error codes, webhooks to your app would be suspended for a day
        trigger_error('An error occured while checking webhooks data signature', E_USER_WARNING);
        if ($debug==true) { error_log(date("d-m-Y H:i:s").' webhook An error occured while checking webhooks data signature'."\n", 3, "NW.log"); }
    }
    else
    {
        //webhooks notifications are json encoded, you need to first decode them in order to access it as PHP arrays
        $notif = json_decode($jsonData, TRUE);

        //Printing the notification message in a file. If you want to access other available webhooks fields please see https://dev.netatmo.com/doc/webhooks/webhooks_camera
        if(isset($notif['message']) && isset($notif['camera_id']) && ($notif['event_type'] == 'person' || $notif['event_type'] == 'movement'))
        {
        	if ($debug==true) { error_log(date("d-m-Y H:i:s").' webhook '.$notif['message']."*".$notif['event_type']."*".$notif['camera_id']."\n", 3, "NW.log"); }
           
           if (!empty($cameraMAC[$notif['camera_id']]['id-API'])) {
           		//lancement de la macro présence associée à la caméra
           		$majpresence = "https://api.eedomus.com/set?action=periph.macro&macro=".$cameraMAC[$notif['camera_id']]['id-API']."&api_user=$apiuser&api_secret=$apisecret";
		   		$contents = file_get_contents($majpresence);
		   }
           
           //enregistrement de l'image si présente
           if (isset($notif['snapshot_id']) && isset($notif['snapshot_key']))
           {
           		//Mise a jour de l'image de la camera
				$ftp = ftp_connect($ftp_server) or die("Impossible de se connecter au serveur FTP");
				ftp_login($ftp, $cameraMAC[$notif['camera_id']]['ftp_login'], $cameraMAC[$notif['camera_id']]['ftp_password']);
				# switch to passive mode (mandatory on Ovh shared hosting)
				ftp_pasv( $ftp, true );
				
           		$image_url = "https://api.netatmo.com/api/getcamerapicture?image_id=".$notif['snapshot_id']."&key=".$notif['snapshot_key'];
		   		file_put_contents("Snapshot.jpg", fopen("$image_url", 'r'));
		   		$ftpeventOK = ftp_put($ftp,"Snapshot.jpg" , "Snapshot.jpg", FTP_BINARY);
				ftp_close($ftp);
				if ($debug==true) { error_log(date("d-m-Y H:i:s").' webhook ftp camera '.$ftpeventOK."\n", 3, "NW.log"); }
				
				//Mise à jour de la camera "event"
				//Connexion ftp
				$ftp = ftp_connect($ftp_server) or die("Impossible de se connecter au serveur FTP");
				ftp_login($ftp, $ftp_login, $ftp_password);
				# switch to passive mode (mandatory on Ovh shared hosting)
				ftp_pasv( $ftp, true );
		   		$ftpeventOK = ftp_put($ftp,"Snapshot.jpg" , "Snapshot.jpg", FTP_BINARY);
				ftp_close($ftp);
				if ($debug==true) { error_log(date("d-m-Y H:i:s").' webhook ftp event '.$ftpeventOK."\n", 3, "NW.log"); }			
           }
           
           //enregistrement de l'identification
           if ($notif['event_type'] == 'person' && isset($notif['message']))
           {
				if (!isset($userstate)) { $userstate = 1; }
				
				//lecture du fichier cache
    			$lecture_fichier_cache = file_get_contents($cache_file);
     		    // récupère la structure du tableau cache
    			$tab_cache = unserialize($lecture_fichier_cache);
         			
				foreach($users as $cle => $element)
				{
				  if (strpos($notif['message'], $cle) !== false)
				  {
				  	if(time() > $tab_cache[$cle])
				  	{
						if ($usermode == 'etat') {
							$majidentification = "https://api.eedomus.com/set?action=periph.value&periph_id=$element&value=$userstate&api_user=$apiuser&api_secret=$apisecret&format=xml";
							$contents = file_get_contents($majidentification);
						} elseif ($usermode == 'macro') {
							$majidentification = "https://api.eedomus.com/set?action=periph.macro&macro=$element&api_user=$apiuser&api_secret=$apisecret";
							$contents = file_get_contents($majidentification);
						}
						//Sauvegarde du cache
						$tab_cache[$cle] = time() + $cache_duree*60;
						unlink($cache_file);
						file_put_contents($cache_file, serialize($tab_cache));
												
						if ($debug==true) { error_log(date("d-m-Y H:i:s").' webhook authentification '.$cle." pas de cache\n", 3, "NW.log"); }
					} else {
						if ($debug==true) { error_log(date("d-m-Y H:i:s").' webhook authentification '.$cle." CACHE\n", 3, "NW.log"); }
					}
				  }				  
				}				
           }
        }
    }
}
?>
