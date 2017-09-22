<?php
//--------------------------------------------- Paramètres Netatmo
$netatmo_username = "netatmo login"; //votre login pour le site https://my.netatmo.com/app/camera
$netatmo_password = "netatmo password"; //votre password pour le site https://my.netatmo.com/app/camera
$client_id = "xxxxxxxxxxxxxxx"; //a recuperer sur https://dev.netatmo.com
$client_secret = "yyyyyyyyyyyyyy"; //a recuperer sur https://dev.netatmo.com

//--------------------------------------------- Paramètres eedomus
$apiuser = 'wwwwwwwwww'; //api_user eedomus
$apisecret = 'zzzzzzzzzzzzzzzz'; //api_secret eedomus

//------- Etats de sauvegarde de l'authentification
$idaccess_token = '12345'; //code api eedomus de l etat access_token 1
$idaccess_token = '25795'; //code api eedomus de l etat access_token 2

$idrefresh_token = '54321'; //code api eedomus de l etat refresh_token 1
$idrefresh_token = '79134'; //code api eedomus de l etat refresh_token 2

//------- Caméra Physiques
//Pour chaque caméra physique Netatmo Welcome créez 3 lignes  en remplacant 00:00:00:00:00:00 par l'adresse MAC de la caméra (que vous trouverez dans les paramètres de l'application netatmo welcome)
$cameraMAC['00:00:00:00:00:00']['id-API'] = '12345'; //code API de la macro qui va gerer la detection de mouvement de cette camera (laissez vide si vous ne voulez pas gérer cet aspect)
$cameraMAC['00:00:00:00:00:00']['ftp_login'] = 'camera12345'; //login ftp de la camera (crée par la box eedomus)
$cameraMAC['00:00:00:00:00:00']['ftp_password'] = 'xXxXxX'; //password ftp de la camera (crée par la box eedomus)

//------- Caméra Evènement
$ftp_server = "camera.eedomus.com"; //a priori ca on n'y touche pas
$ftp_login = "login camera evenement"; //login ftp de la camera évènement (crée par la box eedomus)
$ftp_password = "password camera evenement"; //password ftp de la camera évènement (crée par la box eedomus)

//-------- Personnes
//Pour chaque personne dont vous voulez gérer la présence, créer une ligne avec son nom (tel que définit dans l'application netatmo welcome) et le code API de l'etat (ou de la macro) qui va gerer la personne
//Ne mettez rien si vous ne voulez pas gérer cet aspect
$users['Marcel'] = '12345';
$users['Simone'] = '54321';

$usermode = 'etat'; //(etat ou macro) Mode de gestion de la présence. Par la simple mise à jour d'un etat à une valeur (etat) ou par le lancement d'une macro (macro)

//--------------------------------------------- Paramètres Script
$cache_duree = 30; //durée du cache d'identification en mn
$debug = false; //a priori ca on n'y touche pas, sauf si ca déconne, genere un fichier NW.log si true
?>
