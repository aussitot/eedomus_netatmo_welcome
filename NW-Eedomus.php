<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

/* ------------------- Parametres :
action = live - Mettre à jour les snapshots live des cameras
action = event - Mise à jour de la vignette du dernier évènement
action = all (ou rien) - Mettre à jour les snapshots live des cameras et la vignette du dernier évènement

mode = 1 - Mise a jour depuis eedomus
mode = 2 - raz des données d'authentification stockées dans l'eedomus

option = images - Affichage des images lors de l'execution du script
*/

define('__ROOT__', dirname(dirname(__FILE__)));
require_once ('NW-Config.php');

//-------------- Parametres
$action = $_GET['action']; if (empty($action)) $action = "all";
$option = $_GET['option'];
$mode = $_GET['mode'];
$app_id = $client_id;
$app_secret = $client_secret;
$my_live_url = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
$scope = "read_camera access_camera read_presence access_presence";
$resulmaj = "erreur";

//-------------- Compatibilité avec ancienne version <1.2
if (empty($ftp_login0))
{
	$i = 0;
	foreach($cameraMAC as $camera)
	{
		$varname = "ftp_login".$i;
		${$varname} = $camera["ftp_login"];
		$varname = "ftp_password".$i;
		${$varname} = $camera["ftp_password"];
        $i++;
    }
}

//-------------- Recupération des parametres precedents
if ($mode == 1)
{
	//récupération des parametres sur les etats eedomus
	$eedomusrefresh_tokenurl = "https://api.eedomus.com/get?action=periph.caract&periph_id=$idrefresh_token1&api_user=$apiuser&api_secret=$apisecret";
	$contents = file_get_contents($eedomusrefresh_tokenurl);
	$params = json_decode($contents, true);
	$refresh_token1 = $params['body']['last_value'];

	$eedomusrefresh_tokenurl = "https://api.eedomus.com/get?action=periph.caract&periph_id=$idrefresh_token2&api_user=$apiuser&api_secret=$apisecret";
	$contents = file_get_contents($eedomusrefresh_tokenurl);
	$params = json_decode($contents, true);
	$refresh_token2 = $params['body']['last_value'];

	if ($refresh_token1 == 0 || $refresh_token2 == 0) {
		$refresh_token ='';
	} else {
		$refresh_token = $refresh_token1."|".$refresh_token2;
	}

	$eedomusaccess_tokenurl = "https://api.eedomus.com/get?action=periph.caract&periph_id=$idaccess_token1&api_user=$apiuser&api_secret=$apisecret";
	$contents = file_get_contents($eedomusaccess_tokenurl);
	$params = json_decode($contents, true);
	$access_token1 = $params['body']['last_value'];

	$eedomusaccess_tokenurl = "https://api.eedomus.com/get?action=periph.caract&periph_id=$idaccess_token2&api_user=$apiuser&api_secret=$apisecret";
	$contents = file_get_contents($eedomusaccess_tokenurl);
	$params = json_decode($contents, true);
	$paramstab = explode("-", $params['body']['last_value']);
	if (time() < $paramstab[1])
  	{
    	$access_token2 = $paramstab[0];
  	}

	if ($access_token1 == 0 || $access_token2 == 0) {
		$access_token ='';
	} else {
		$access_token = $access_token1."|".$access_token2;
	}

  if ($debug==true) { error_log(date("d-m-Y H:i:s").' time '.time().'**'.$paramstab[1]."\n", 3, "NW.log"); }
	if ($debug==true) { error_log(date("d-m-Y H:i:s").' eedomusaccess_token '.$access_token."\n", 3, "NW.log"); }
	if ($debug==true) { error_log(date("d-m-Y H:i:s").' eedomusrefresh_token '.$refresh_token."\n", 3, "NW.log"); }
} elseif ($mode == 2)
{
	$majaccess_token1 = "https://api.eedomus.com/set?action=periph.value&periph_id=$idaccess_token1&value=0&api_user=$apiuser&api_secret=$apisecret&format=xml";
	$contents = file_get_contents($majaccess_token1);
	$majaccess_token2 = "https://api.eedomus.com/set?action=periph.value&periph_id=$idaccess_token2&value=0&api_user=$apiuser&api_secret=$apisecret&format=xml";
	$contents = file_get_contents($majaccess_token2);
	$majrefresh_token1 = "https://api.eedomus.com/set?action=periph.value&periph_id=$idrefresh_token1&value=0&api_user=$apiuser&api_secret=$apisecret&format=xml";
	$contents = file_get_contents($majrefresh_token1);
	$majrefresh_token2 = "https://api.eedomus.com/set?action=periph.value&periph_id=$idrefresh_token2&value=0&api_user=$apiuser&api_secret=$apisecret&format=xml";
	$contents = file_get_contents($majrefresh_token2);
	die("raz des données d'authentification stockées dans l'eedomus effectuée");
} else
{
	$access_token = $_COOKIE["access_token"];
	$refresh_token = $_COOKIE["refresh_token"];
}

if ($access_token == '')
{
  if (strlen($refresh_token) > 1)
  {
    //------------------ on peut juste rafraichir le token
    $token_url = "https://api.netatmo.net/oauth2/token";

		$postdata = http_build_query(
			array(
				'grant_type' => "refresh_token",
				'client_id' => $app_id,
				'refresh_token' => $refresh_token,
				'client_secret' => $app_secret
			)
		);

		$opts = array('http' =>
		array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => $postdata
		)
		);

		$context  = stream_context_create($opts);

		$response = file_get_contents($token_url, false, $context);
		$params = null;
		$params = json_decode($response, true);
		$access_token = $params['access_token'];
		$refresh_token = $params['refresh_token'];
		$expires_in = $params['expires_in'];
		$time_expire = (string) time()+$expires_in;

		//sauvegarde des parametres
		$access_cookieOK = setcookie("access_token", $params['access_token'], time()+$params['expires_in']+1);
		$refresh_cookieOK = setcookie("refresh_token", $params['refresh_token'], time()+60*60*24*30); //expire dans 30j

		if ($mode == 1)
		{
			//sauvegarde des parametres sur les etats eedomus
			$paramsaccess_token = explode("|", $access_token);
			$access_token1 = $paramsaccess_token[0];
			$access_token2 = $paramsaccess_token[1];
			$majaccess_token = "https://api.eedomus.com/set?action=periph.value&periph_id=$idaccess_token1&value=$access_token1&api_user=$apiuser&api_secret=$apisecret&format=xml";
			$contents = file_get_contents($majaccess_token);
			$majaccess_token = "https://api.eedomus.com/set?action=periph.value&periph_id=$idaccess_token2&value=$access_token2-$time_expire&api_user=$apiuser&api_secret=$apisecret&format=xml";
			$contents = file_get_contents($majaccess_token);

			$paramsrefresh_token = explode("|", $refresh_token);
			$refresh_token1 = $paramsrefresh_token[0];
			$refresh_token2 = $paramsrefresh_token[1];
			$majrefresh_token = "https://api.eedomus.com/set?action=periph.value&periph_id=$idrefresh_token1&value=$refresh_token1&api_user=$apiuser&api_secret=$apisecret&format=xml";
			$contents = file_get_contents($majrefresh_token);
			$majrefresh_token = "https://api.eedomus.com/set?action=periph.value&periph_id=$idrefresh_token2&value=$refresh_token2&api_user=$apiuser&api_secret=$apisecret&format=xml";
			$contents = file_get_contents($majrefresh_token);
		}

		if ($debug==true) { error_log(date("d-m-Y H:i:s").' process refresh_token '.$access_token.'/'.$refresh_token." ".$access_cookieOK." ".$refresh_cookieOK."\n", 3, "NW.log"); }
  }
  else
  {
  //--------------- Authentification complète
		session_start();

		$token_url = "https://api.netatmo.net/oauth2/token";

		$postdata = http_build_query(
			array(
				'grant_type' => "password",
				'client_id' => $app_id,
				'client_secret' => $app_secret,
				'username' => $netatmo_username,
				'password' => $netatmo_password,
				'scope' => $scope
			)
		);

		$opts = array('http' =>
		array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => $postdata
		)
		);

		$context  = stream_context_create($opts);

		$response = file_get_contents($token_url, false, $context);
		$params = null;
		$params = json_decode($response, true);
		$access_token = $params['access_token'];
		$refresh_token = $params['refresh_token'];
		$expires_in = $params['expires_in'];
		$time_expire = (string) time()+$expires_in;

		//sauvegarde des parametres
		$access_cookieOK = setcookie("access_token", $params['access_token'], time()+$params['expires_in']+1);
		$refresh_cookieOK = setcookie("refresh_token", $params['refresh_token'], time()+60*60*24*30); //expire dans 30j

		if ($mode == 1)
		{
			//sauvegarde des parametres sur les etats eedomus
			$paramsaccess_token = explode("|", $access_token);
			$access_token1 = $paramsaccess_token[0];
			$access_token2 = $paramsaccess_token[1];
			$majaccess_token = "https://api.eedomus.com/set?action=periph.value&periph_id=$idaccess_token1&value=$access_token1&api_user=$apiuser&api_secret=$apisecret&format=xml";
			$contents = file_get_contents($majaccess_token);
			$majaccess_token = "https://api.eedomus.com/set?action=periph.value&periph_id=$idaccess_token2&value=$access_token2-$time_expire&api_user=$apiuser&api_secret=$apisecret&format=xml";
			$contents = file_get_contents($majaccess_token);

			$paramsrefresh_token = explode("|", $refresh_token);
			$refresh_token1 = $paramsrefresh_token[0];
			$refresh_token2 = $paramsrefresh_token[1];
			$majrefresh_token = "https://api.eedomus.com/set?action=periph.value&periph_id=$idrefresh_token1&value=$refresh_token1&api_user=$apiuser&api_secret=$apisecret&format=xml";
			$contents = file_get_contents($majrefresh_token);
			$majrefresh_token = "https://api.eedomus.com/set?action=periph.value&periph_id=$idrefresh_token2&value=$refresh_token2&api_user=$apiuser&api_secret=$apisecret&format=xml";
			$contents = file_get_contents($majrefresh_token);
		}

		if ($debug==true) { error_log(date("d-m-Y H:i:s").' process authentification '.$access_token.'/'.$refresh_token." ".$access_cookieOK." ".$refresh_cookieOK."\n", 3, "NW.log"); }

   }
}

if ($debug==true) { error_log(date("d-m-Y H:i:s").' process cookies '.$access_token.'/'.$refresh_token."\n", 3, "NW.log"); }

//------------------------Recuperation des informations Netatmo Welcome
if ($debug==true) { error_log(date("d-m-Y H:i:s").' process gethomedata '.$access_token.'/'.$refresh_token."\n", 3, "NW.log"); }
$homedata_url = "https://api.netatmo.com/api/gethomedata";

$postdata = http_build_query(
	array(
		'access_token' => $access_token,
		'size' => 10
	)
);

$opts = array('http' =>
array(
	'method'  => 'POST',
	'header'  => 'Content-type: application/x-www-form-urlencoded',
	'content' => $postdata
)
);

$context  = stream_context_create($opts);

$response = file_get_contents($homedata_url, false, $context);
$params = null;
$params = json_decode($response, true);

$cameraList = $params['body']['homes'][0]['cameras'];
$eventList = $params['body']['homes'][0]['events'];

//echo '<pre>', HtmlSpecialChars(print_r($params)), '</pre>';
//echo '<pre>', HtmlSpecialChars(print_r($params['body']['homes'][0]['cameras'])), '</pre>';
//echo '<pre>', HtmlSpecialChars(print_r($params['body']['homes'][0]['events'])), '</pre>';

//------------------------mise à jour live
if ($action == 'live' || $action == 'all')
{
	for ($i=0; $i < count($cameraList) ;$i++)
	{
		$camera = $cameraList[$i];
		$statutcam = $camera['status'];
		if ($statutcam == 'on') {
			$VpnUrl = $camera['vpn_url'];
			$LiveSnapshot = $VpnUrl."/live/snapshot_720.jpg";
			if ($option == 'images') echo "<img src='".$LiveSnapshot."'>";
				//mise à jour FTP
			$ftp_current_login = "ftp_login".$i;
			$ftp_current_password = "ftp_password".$i;
			$ftp = ftp_connect($ftp_server) or die("Impossible de se connecter au serveur FTP");
			ftp_login($ftp, $$ftp_current_login, $$ftp_current_password);
			# switch to passive mode (mandatory on Ovh shared hosting)
			ftp_pasv( $ftp, true );
			file_put_contents("Snapshot$i.jpg", fopen("$LiveSnapshot", 'r'));
			$ftpliveOK[$i] = ftp_put($ftp,"Snapshot$i.jpg" , "Snapshot$i.jpg", FTP_BINARY);
			ftp_close($ftp);
			if ($debug==true) { error_log(date("d-m-Y H:i:s").' process ftp live'.$i.' '.$ftpliveOK[$i]."\n", 3, "NW.log"); }
		}
	}
	$resulmaj = date("Y-m-d H:i:s");
}

//------------------------mise à jour vignette dernier évènement
if ($action == 'event' || $action == 'all')
{
	//Connexion ftp
	$ftp = ftp_connect($ftp_server) or die("Impossible de se connecter au serveur FTP");
	ftp_login($ftp, $ftp_login, $ftp_password);
	# switch to passive mode (mandatory on Ovh shared hosting)
	ftp_pasv( $ftp, true );

	for ($i=0; $i < count($eventList) ;$i++)
	{
		//---------------- Acces aux donnes
		//echo '<pre>', HtmlSpecialChars(print_r($eventList[$i])), '</pre>';
		if (is_array($eventList[$i]['snapshot']))
		{
			$imageId = $eventList[$i]['snapshot']['id'];
			$imageKey = $eventList[$i]['snapshot']['key'];
			$image_url = "https://api.netatmo.com/api/getcamerapicture?image_id=".$imageId."&key=".$imageKey;
			if ($option == 'images') echo "<img src='".$image_url."'>";
			file_put_contents("Snapshot.jpg", fopen("$image_url", 'r'));
			$ftpeventOK = ftp_put($ftp,"Snapshot.jpg" , "Snapshot.jpg", FTP_BINARY);
			ftp_close($ftp);
			if ($debug==true) { error_log(date("d-m-Y H:i:s").' process ftp event '.$ftpeventOK."\n", 3, "NW.log"); }
			break;
		}
	}
	$resulmaj = date("Y-m-d H:i:s");
}

if (empty($option) && !empty($action))
{
	//----------- Génération du XML
	echo '<?xml version="1.0"?>';
	echo '<racine>';
	echo '<cameras>';
	echo '<maj>'.$resulmaj.'</maj>';
	echo '</cameras>';
	echo '</racine>';
}

?>
