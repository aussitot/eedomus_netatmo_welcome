[![GitHub release](https://img.shields.io/github/release/aussitot/eedomus_netatmo_welcome.svg?style=flat-square)](https://github.com/aussitot/eedomus_netatmo_welcome/releases)
![GitHub license](https://img.shields.io/github/license/aussitot/eedomus_netatmo_welcome.svg?style=flat-square)
![Status](https://img.shields.io/badge/Status-Complete-brightgreen.svg?style=flat-square)
[![Twitter](https://img.shields.io/badge/twitter-@havok-blue.svg?style=flat-square)](http://twitter.com/havok)
# eedomus_netatmo_welcome
Scripts d'intégration des caméras Netatmo Welcome et Presence pour eedomus
script cree par twitter:@Havok pour la eedomus

NB : Script à installer sur un serveur web/php autre que l'eedomus elle-même

# INSTALLATION
Bonjour,

Voici un  script pour intégrer dans l'interface eedomus les caméras Netatmo Welcome et/ou Presence.

**Prérequis** : Il faut disposer d'un serveur web/php autre que l'eedomus elle-même.  

**Ce que ca fait** : Ca va vous permettre de
- créer des caméras "virtuelles" qui afficheront de manière régulière les images prise par vos caméras Netatmo.
- utiliser les caméras comme détecteur de mouvement (pour les caméras Welcome)
- d'accéder au live mais pas depuis l'interface eedomus
- d'accéder à la reconnaissance faciale des caméras pour gérer la présence de riri, fifi, loulou (pour les caméras Welcome)

**Ce qu'on va faire**
- On va créer une (ou plusieurs si vous en avez plusieurs...) caméras qui vont afficher les photos du "live" et une caméra qui affichera la vignette du dernier évènement détecté par vos caméras (présence de X, présence inconnue, mouvement, etc...)
- On va créer 2 états qui vont sauvegarder les informations d'identification netatmo (access_token et refresh_token)
- On va créer un état (ou plusieurs si vous en avez plusieurs...) pour gérer la detection de mouvement
- On va créer plusieurs état pour gérer la reconnaissance faciale et donc la présence des membres du foyer

Certaines fonction sont optionnelles. Si vous ne voulez pas gérer la detection de mouvement ou la reconnaissance faciale et bien vous n'aurez pas à le faire et le script fonctionnera très bien pour la mise à jour des snapshot des caméra.
De même les options d'intimité définies dans l'application Netatmo Welcome sont bien prises en compte. Si vous avez défini que lors de la présence de madame il ne faut pas enregistrer de video alors vous n'aurez pas de vignette lorsque la caméra détecte le visage de madame (mais vous aurez quand meme les vignettes définies à interval régulier)

## Etape 1
créez une application sur https://dev.netatmo.com (ne remplissez que les champs obligatoires) pour récupérer le "Client id" et "Client secret"
Modifiez le fichier NW-Config.php pour y reporter ces valeurs ainsi que vos login et password Netatmo

```php
//--------------------------------------------- Paramètres Netatmo
$netatmo_username = "netatmo login"; //votre login pour le site https://my.netatmo.com/app/camera
$netatmo_password = "netatmo password"; //votre password pour le site https://my.netatmo.com/app/camera
$client_id = "xxxxxxxxxxxxxxx"; //a recuperer sur https://dev.netatmo.com
$client_secret = "yyyyyyyyyyyyyy"; //a recuperer sur https://dev.netatmo.com
```

## Etape 2
- Copiez les fichiers du projet dans le répertoire "netatmo" sur votre serveur.
- Renommez le fichier NW-Config-Blank.php en NW-Config.php

## Etape 3
Pour chacune de vos caméras créez dans eedomus une caméra (Configuration/Ajouter ou supprimer un périphérique/Ajouter un autre type de caméra/Caméra - Autre).
Mettez n'importe quelle adresse IP dans la zone IP locale (on ne pourra pas voir le live pour l'instant) et cachez les caneaux liés (en cliquant sur l'oeil a coté).
Récupérez les valeurs du login et password FTP générés automatiquement par eedomus.
Récupérez dans l'application Netatmo Welcome sur smartphone ou sur le site Netatmo l'adresse MAC de votre ou vos caméra(s) (c'est de la forme 00:00:00:00:00:00)
Modifiez le fichier NW-Config.php pour y reporter ces valeurs.
Laissez vide le code API associé à la ligne $cameraMAC['00:00:00:00:00:00']['id-API'] pour l'instant
```php
//------- Caméra Physiques
//Pour chaque caméra physique Netatmo Welcome créez 3 lignes  en remplacant 00:00:00:00:00:00 par l'adresse MAC de la caméra (que vous trouverez dans les paramètres de l'application netatmo welcome)
$cameraMAC['00:00:00:00:00:00']['id-API'] = ''; //code API de la macro qui va gerer la detection de mouvement de cette camera (laissez vide si vous ne voulez pas gérer cet aspect)
$cameraMAC['00:00:00:00:00:00']['ftp_login'] = 'camera12345'; //login ftp de la camera (crée par la box eedomus)
$cameraMAC['00:00:00:00:00:00']['ftp_password'] = 'xXxXxX'; //password ftp de la camera (crée par la box eedomus)
```

**Créer une caméra supplémentaire** (que nous appelerons caméra évènement) et récupérez également les valeurs du login et password FTP générés automatiquement par eedomus.
Modifiez le fichier NW-Config.php pour y reporter ces valeurs.
```php
//------- Caméra Evènement
$ftp_server = "camera.eedomus.com"; //a priori ca on n'y touche pas
$ftp_login = "login camera evenement"; //login ftp de la camera évènement (crée par la box eedomus)
$ftp_password = "password camera evenement"; //password ftp de la camera évènement (crée par la box eedomus)
```

## Etape 4
Créez deux périphérique "état" dans l'eedomus Configuration/Ajouter ou supprimer un périphérique/Ajouter un autre type de périphérique/Etat
C'est eux qui vont stocker les données d'authentification

- Etat 1 : Nom : Netatmo access_token, Usage : Autre indicateur, Type de données : Texte
- Etat 2 : Nom : Netatmo refresh_token, Usage : Autre indicateur, Type de données : Texte

Vous pouvez mettre ces 2 états en invisible, il ne servent que de stockage des données d'authentification

Récupérez les valeurs du code API des deux états générés automatiquement par eedomus.
Modifiez le fichier NW-Config.php pour y reporter ces valeurs (en ne confondant pas l'access_token et le refresh_token).
```PHP
//------- Etats de sauvegarde de l'authentification
$idaccess_token = '12345'; //code api eedomus de l etat access_token
$idrefresh_token = '54321'; //code api eedomus de l etat refresh_token
```

## Etape 5
Modifiez le fichier NW-Config.php
Ajoutez dans le fichier le fichier NW-Config.php l'api_user et l'api_secret récupéré dans les paramètres de votre compte eedomus
```php
//--------------------------------------------- Paramètres eedomus
$apiuser = 'wwwwwwwwww'; //api_user eedomus
$apisecret = 'zzzzzzzzzzzzzzzz'; //api_secret eedomus
```

## Etape 6
Il est l'heure de tester le zinzin...
Dans un navigateur tapez l'url : ```http://www.votreserveur.com/netatmo/NW-Eedomus.php?option=images``` (cette dernière options vous permet de voir les images qui vont être téléchargées).
Si rien ne s'affiche et bien c'est que ca ne marche pas ... Dans ce cas recommencer les différents étapes vous avez du rater quelque chose (spéciale dédicace pour anne-marie ;)
Si vous voyez une image par caméra alors passons à la suite.

## Etape 7
On va maintenant automatiser tout ca.
Dans eedomus créez un Capteur http :
- Nom : Date Maj Live Caméras
- Type de données : Texte
- URL de la requete : ```http://www.votreserveur.com/netatmo/NW-Eedomus.php?mode=1``` (ATTENTION a bien mettre le mode=1)
- Chemin XPath : cameras/maj
- Fréquence de la requête : en mn le délai que vous souhaitez pour rafraichir les screenshot.

Et voila, ce capteur à 2 roles, mettre à jour régulièrement les screenshot et vous informez de l'heure de dernière mise à jour.

Normalement, au bout de quelques secondes ou minutes les sreenshot des caméras s'affichent dans l'interface eedomus.
La ou les caméras "live" affichent une vignette du live
La caméra "Evènement" affiche une vignette du dernier évènement détecté par vos caméras (présence de X, présence inconnue, mouvement, etc...)

## Etape 8
Comme je ne sais pas commun intégrer le lien vers le live dans l'interface eedomus il faudra y accéder en dehors... Avec l'url suivante : ```http://www.votreserveur.com/netatmo/NW-Live.php```
Le paramètre quality permet de choisir la qualité de la vidéo (poor/low/medium/high dans l'url).
Par défaut la qualité est 'medium'.
Par exemple ```http://www.votreserveur.com/netatmo/NW-Live.php?quality=poor```

Ca fonctionne sous Safari. Pas sur que ca fonctionne sous les autres navigateurs. Achetez vous un mac :D

Normalement vous pouvez supprimer du fichier NW-Config.php vos identifiants et password netatmo.
Vous n'en aurez plus besoin.
En cas de soucis vous pouvez réinitialisez l'authentification stockée dans la eedomus en
- vérifiant que vos login/password netatmo sont bien présent dans le fichier NW-Config.php
- lançant l'url : ```http://www.votreserveur.com/netatmo/NW-Eedomus.php?mode=2```

Et bonus pour ceux qui auraient pas tout compris voici ce que ca donne :
J'ai 2 caméra "physiques" Netatmo, une dans l'entrée et une dans le salon.
Sur l'interface j'ai donc 3 caméras (ba oui^^faut suivre...)
La caméra "Entrée" me donne l'image prise par celle-ci il y a 5mn (car c'est le délai que j'ai choisi à l'étape 7)
La caméra "Salon" me donne l'image prise par celle-ci il y a 5mn
La caméra "Maison" me donne l'image du dernier évènement détecté par les 2 caméras (présence de X, présence inconnue, mouvement, etc...). Quand il n'y a personne à la maison cette image ne bougera pas, alors que celle des 2 autres sera bien mise à jour (avec une maison vide...)

**Les étapes suivantes sont optionnelles, elles ne fonctionnent pour l'instant que pour les caméras Netatmo Welcome**

# Gestion de la détection de mouvement

Si vous souhaitez utiliser vos caméra comme détecteur de mouvement (ca fonctionne plutot pas mal) voici la procédure :

## Etape 9
retournez sur https://dev.netatmo.com, dans les paramètres de votre application remplissez la zone "Webhook URL" par l'adresse ```http://www.votreserveur.com/netatmo/NW-Webhook.php```
A chaque fois qu'un évènement va se produire sur vos caméras, Netatmo va envoyer un message a cette adresse.

## Etape 10
créez, pour chaque caméra un périphérique "état" dans l'eedomus (Configuration/Ajouter ou supprimer un périphérique/Ajouter un autre type de périphérique/Etat)
- Ajoutez deux valeurs à cet état (avec les icones qui vont bien):
  - 0 - Aucun mouvement
  - 100 - Mouvement
- Créez une macro
  - Attendre 0 secondes puis Mouvement
  - Attendre 2 minutes puis Aucun mouvement
- Récupérez la valeurs du code API de la macro et modifiez le fichier NW-Config.php pour y reporter cette valeur (et ceci pour chaque caméra), dans la zone qu'on a laissé à vide lors des étapes précédente ($cameraMAC['00:00:00:00:00:00']['id-API'])
```
//------- Caméra Physiques
//Pour chaque caméra physique Netatmo Welcome créez 3 lignes  en remplacant 00:00:00:00:00:00 par l'adresse MAC de la caméra (que vous trouverez dans les paramètres de l'application netatmo welcome)
$cameraMAC['00:00:00:00:00:00']['id-API'] = ''; //code API de la macro qui va gerer la detection de mouvement de cette camera (laissez vide si vous ne voulez pas gérer cet aspect)
$cameraMAC['00:00:00:00:00:00']['ftp_login'] = 'camera12345'; //login ftp de la camera (crée par la box eedomus)
$cameraMAC['00:00:00:00:00:00']['ftp_password'] = 'xXxXxX'; //password ftp de la camera (crée par la box eedomus)
```

A chaque fois que la caméra detecte un mouvement ou un visage elle appelle la macro qui va positionner l'etat à 100-Mouvement, au bout de 2 mn si rien n'a été détecté l'etat repasse à 0-Aucun mouvement
En parralèle le script envoie un snapshot de la caméra qui a détecté le mouvement (seulement en cas de reconnaissance d'un visage connu ou pas). Cela met donc à jour la vignette de la caméra physique mais aussi celle de la caméra "évènement"

**SI CELA NE FONCTIONNE PAS** : Il est possible que netatmo n'ai pas bien enregistré l'url de votre webhook.
Faites alors la manipulation suivante (qui est un peu chiante à cause d'un bug que je n'arrive pas à résoudre mais qui fonctionne) :

- Vérifiez que vos login/password netatmo sont bien présent dans le fichier NW-Config.php
- Lancez l'url : ```http://www.votreserveur.com/netatmo/NW-WebhookRegistration.php?action=drop```
- lancez l'url : ```http://www.votreserveur.com/netatmo/NW-Eedomus.php?mode=2```
- attendre que dans l'eedomus les deux états access_token et refresh_token soit de nouveau remplis
- Lancez l'url : ```http://www.votreserveur.com/netatmo/NW-WebhookRegistration.php?action=add```
- lancez de nouveau l'url : ```http://www.votreserveur.com/netatmo/NW-Eedomus.php?mode=2```
- Vous pouvez éventuellement supprimer de votre fichier NW-Config.php vos identifiants et password netatmo. Vous n'en aurez plus besoin.
- attendre éventuellement 24h car si l'url de votre webhook était éronée netatmo à blacklisté celle-ci pendant 24h

# Gestion de la reconnaissance faciale

Si vous souhaitez utiliser vos caméra comme dispositif de reconnaissance faciale voici la procédure :

**Si vous n'avez pas activé la gestion de la détection de mouvement (donc les etapes 9 et 10)**
## Etape 11
retournez sur https://dev.netatmo.com, dans les paramètres de votre application remplissez la zone "Webhook URL" par l'adresse ```http://www.votreserveur.com/netatmo/NW-Webhook.php```
A chaque fois qu'un évènement va se produire sur vos caméras, Netatmo va envoyer un message a cette adresse.

## Etape 12
créez, pour chaque personne dont vous voulez gérer la présence un périphérique "état" dans l'eedomus (Configuration/Ajouter ou supprimer un périphérique/Ajouter un autre type de périphérique/Etat)
- Ajoutez deux valeurs à cet état (avec les icones qui vont bien):
  - 0 - Absent
  - 1 - Présent

Récupérez la valeurs du code API de chaque état

## Etape 13
Modifiez le fichier NW-Config.php
Pour chaque personne dont vous voulez gérer la présence, créez une ligne avec son nom (tel que définit dans l'application netatmo welcome) et le code API de l'etat (ou de la macro) qui va gerer la personne.

```php
//-------- Personnes
//Pour chaque personne dont vous voulez gérer la présence, créer une ligne avec son nom (tel que définit dans l'application netatmo welcome) et le code API de l'etat (ou de la macro) qui va gerer la personne
//Ne mettez rien si vous ne voulez pas gérer cet aspect
$users['Marcel'] = '12345';
$users['Simone'] = '54321';
```

A chaque fois que la caméra reconnait un visage et qu'elle l'identifie elle va positionner l'etat associé à la personne à 1-Présent

Par contre elle n'informe pas quand une personne est partie donc soit vous gérez ca vous meme avec les regles de l'eedomus (dans ce cas le parametre $usermode = 'etat' dans le fichier NW-Config.php) soit vous gérez cela avec une macro du genre "SI je n'ai pas revu simone au bout de 2h alors c'est qu'elle est absente". Pour ce faire :
- modifiez le fichier NW-Config.php et mettez le paramètre $usermode = 'macro'
- Créez (pour chaque etat de personne) une macro
- Attendre 0 secondes puis Présent
- Attendre 2 heures puis Absent
- Récupérez la valeurs du code API de la macro et modifiez le fichier NW-Config.php pour y reporter cette valeur (à la place du code API de l'etat mis en place dans l'étape 13)
```php
//-------- Personnes
//Pour chaque personne dont vous voulez gérer la présence, créer une ligne avec son nom (tel que définit dans l'application netatmo welcome) et le code API de l'etat (ou de la macro) qui va gerer la personne
//Ne mettez rien si vous ne voulez pas gérer cet aspect
$users['Marcel'] = '12345';
$users['Simone'] = '54321';
```

Afin d'éviter de trop solliciter la eedomus il est possible de mettre un cache sur l'identification. A priori on considère que lorsqu'une personne a été reconnue ce n'est pas la peine d'avertir la eedomus trop souvent. Par défaut on considère que si une personne est reconnue par la Welcome elle n'informera plus la eedomus de la même identification pendant 30mn.
Ce paramètre peut être modifier dans le fichier NW-Config.php
```php
//--------------------------------------------- Paramètres Script
$cache_duree = 30; //durée du cache d'identification en mn
```

**SI CELA NE FONCTIONNE PAS** : Il est possible que netatmo n'ai pas bien enregistré l'url de votre webhook.
Faites alors la manipulation suivante (qui est un peu chiante à cause d'un bug que je n'arrive pas à résoudre mais qui fonctionne) :

- Vérifiez que vos login/password netatmo sont bien présent dans le fichier NW-Config.php
- Lancez l'url : ```http://www.votreserveur.com/netatmo/NW-WebhookRegistration.php?action=drop```
- lancez l'url : ```http://www.votreserveur.com/netatmo/NW-Eedomus.php?mode=2 ```
- attendre que dans l'eedomus les deux états access_token et refresh_token soit de nouveau remplis
- Lancez l'url : ```http://www.votreserveur.com/netatmo/NW-WebhookRegistration.php?action=add```
- lancez de nouveau l'url : ```http://www.votreserveur.com/netatmo/NW-Eedomus.php?mode=2```
- Vous pouvez éventuellement supprimer de votre fichier NW-Config.php vos identifiants et password netatmo. Vous n'en aurez plus besoin.
- attendre éventuellement 24h car si l'url de votre webhook était éronée netatmo à blacklisté celle-ci pendant 24h

Voila c'est tout mais si vous êtes arrivés jusqu'à la c'est que vous le valez bien ! ;)
Amusez-vous bien !

# Parametres
## Pour NW-Eedomus.php
- action = live (Mettre à jour les snapshots live des cameras)
- action = event (Mise à jour de la vignette du dernier évènement)
- action = all ou vide (Mettre à jour les snapshots live des cameras et la vignette du dernier évènement)

- mode = 1 (Mise a jour depuis eedomus)
- mode = 2 (raz des données d'authentification stockées dans l'eedomus)

- option = images (Affichage des images lors de l'execution du script)

## Pour NW-Live.php
Parametres Formats video (ajoutez la variable quality = poor/low/medium/high dans l'url).
Par défaut la qualité est 'medium'

- Si quality = poor : BANDWIDTH=64000,CODECS="avc1.42001f",NAME="640x360"
- Si quality = low : BANDWIDTH=500000,CODECS="avc1.42001f",NAME="640x360"
- Si quality = medium : BANDWIDTH=1000000,CODECS="avc1.42001f",NAME="1280x720"
- Si quality = high : BANDWIDTH=3000000,CODECS="avc1.420028",NAME="1920x1080"

## Pour NW-WebhookRegistration.php
- action = add (subscribe to Webhook)
- action = drop (unsubscribe to Webhook)

- url = url de la page webhook a enregistrer
