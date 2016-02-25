<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

/* Parametres Formats video (ajoutez la variable quality = poor/low/medium/high dans l'url).
Par défaut la qualité est 'medium'

Si quality = poor : BANDWIDTH=64000,CODECS="avc1.42001f",NAME="640x360"
Si quality = low : BANDWIDTH=500000,CODECS="avc1.42001f",NAME="640x360"
Si quality = medium : BANDWIDTH=1000000,CODECS="avc1.42001f",NAME="1280x720"
Si quality = high : BANDWIDTH=3000000,CODECS="avc1.420028",NAME="1920x1080"
*/

define('__ROOT__', dirname(dirname(__FILE__)));
require_once ('NW-Config.php');

//-------------- Parametres
$quality = $_GET['quality']; $quality = ($quality == '')?'medium':$quality;
$app_id = $client_id;
$app_secret = $client_secret;
$my_live_url = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
$scope = "read_camera access_camera";

//-------------- Recupération des parametres precedents
$access_token = $_COOKIE["access_token"];
$refresh_token = $_COOKIE["refresh_token"];

if ($access_token == '')
{
  if (strlen($refresh_token) > 1)
  {
  	if ($debug==true) { error_log(date("d-m-Y H:i:s").' process refresh_token '.$refresh_token."\n", 3, "NW.log"); }
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

		//sauvegarde des parametres
		setcookie("access_token", $params['access_token'], time()+$params['expires_in']+1);
		setcookie("refresh_token", $params['refresh_token'], time()+60*60*24*30); //expire dans 30j
  }
  else
  {
  //--------------- Authentification complète
		session_start();
		$code = $_GET["code"];

		if(empty($code)) {
			$_SESSION['state'] = md5(uniqid(rand(), TRUE));
			$dialog_url="https://api.netatmo.net/oauth2/authorize?client_id="
			. $app_id . "&redirect_uri=" . urlencode($my_live_url)
			. "&scope=" . $scope
			. "&state=" . $_SESSION['state'];

			echo("<script> top.location.href='" . $dialog_url . "'</script>");
		}

		if($_SESSION['state'] && ($_SESSION['state']===$_GET['state'])) {
			$token_url = "https://api.netatmo.net/oauth2/token";

			$postdata = http_build_query(
				array(
					'grant_type' => "authorization_code",
					'client_id' => $app_id,
					'client_secret' => $app_secret,
					'code' => $code,
					'redirect_uri' => $my_live_url,
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

			//sauvegarde des parametres
			setcookie("access_token", $params['access_token'], time()+$params['expires_in']+1);
			setcookie("refresh_token", $params['refresh_token'], time()+60*60*24*30); //expire dans 30j
			//setcookie("expire_time", time()+$params['expires_in'], time()+$params['expires_in']);
		} else {
			die("The state does not match. You may be a victim of CSRF.");
		}
		if ($debug==true) { error_log(date("d-m-Y H:i:s").' process authentification '.$access_token.'/'.$refresh_token." ".$access_cookieOK." ".$refresh_cookieOK."\n", 3, "NW.log"); }
   }
}

if ($debug==true) { error_log(date("d-m-Y H:i:s").' process cookies '.$access_token.'/'.$refresh_token."\n", 3, "NW.log"); }

//------------------------Try to retrieve user's Welcome information
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
//echo '<pre>', HtmlSpecialChars(print_r($params)), '</pre>';
//echo '<pre>', HtmlSpecialChars(print_r($params['body']['homes'][0]['cameras'])), '</pre>';

$cameraList = $params['body']['homes'][0]['cameras'];

//------------------------affichage live

for ($i=0; $i < count($cameraList) ;$i++)
{
	$camera = $cameraList[$i];
	$statutcam = $camera['status'];
	if ($statutcam == 'on') {
		$VpnUrl = $camera['vpn_url'];
		$LiveVideo = $VpnUrl."/live/files/".$quality."/index.m3u8";
		if ($debug==true) { error_log(date("d-m-Y H:i:s").' process livevideo '."\n", 3, "NW.log"); }
		echo '<video controls="" autoplay="" name="media" style="max-width: 100%; max-height: 100%;"><source src="'.$LiveVideo.'" type="application/x-mpegurl"></video>';
	}
}

?>
