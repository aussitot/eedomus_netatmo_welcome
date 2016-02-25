<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

/* ------------------- Parametres :
action = add - subscribe to Webhook
action = drop - unsubscribe to Webhook

url = url de la page webhook a enregistrer
*/

define('__ROOT__', dirname(dirname(__FILE__)));
require_once ('NW-Config.php');

//-------------- Parametres
$action = $_GET['action'];
$webhookurl = $_GET['url'];
if ($action != 'add' && $action != 'drop') { die('parametre incorrect'); }
$app_id = $client_id;
$app_secret = $client_secret;
$my_live_url = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
if ($webhookurl == "") { $webhookurl = str_replace('NW-WebhookRegistration.php?action=add','NW-Webhook.php',$my_live_url); }

//-------------- Recuperation des parametres precedents
$access_token = $_COOKIE["access_token"];
$refresh_token = $_COOKIE["refresh_token"];
if (!empty($code)) { $refresh_token = $code; }

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

		//sauvegarde des parametres
		$access_cookieOK = setcookie("access_token", $params['access_token'], time()+$params['expires_in']+1);
		$refresh_cookieOK = setcookie("refresh_token", $params['refresh_token'], time()+60*60*24*30); //expire dans 30j
		if ($debug==true) { error_log(date("d-m-Y H:i:s").' process refresh_token '.$access_token.'/'.$refresh_token." ".$access_cookieOK." ".$refresh_cookieOK."\n", 3, "NW.log"); }
  }
  else
  {
  //--------------- Authentification complete
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

//------------------------Recuperation des informations Netatmo Welcome
if ($debug==true) { error_log(date("d-m-Y H:i:s").' process '.$action.'webhook '.$access_token.'/'.$refresh_token.'/'.$webhookurl."\n", 3, "NW.log"); }

if ($action=='add')
{
	 $api_url = "https://api.netatmo.com/api/addwebhook?access_token=".$access_token."&url=".$webhookurl."&app_type=app_camera";
} elseif ($action=='drop') {
	 $api_url = "https://api.netatmo.com/api/dropwebhook?access_token=".$access_token."&app_type=app_camera";
} else {
	echo "Aucune action";
	exit();
}

$result = json_decode(file_get_contents($api_url));
echo '<pre>', HtmlSpecialChars(print_r($result)), '</pre>';
?>
