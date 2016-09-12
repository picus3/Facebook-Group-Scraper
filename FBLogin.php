<?php
require_once('variables.php');
define('FACEBOOK_SDK_V4_SRC_DIR', 'facebook/src/Facebook/');
require __DIR__ . '/facebook/autoload.php';
session_start();

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\FacebookRequestException;
use Facebook\FacebookRedirectLoginHelper;

FacebookSession::setDefaultApplication($app_id,$app_secrete);

$session = new FacebookSession($access_token);

if (isset($session)) {
	echo 'Logined to Facebook<br>';
	$request = new FacebookRequest($session, 'GET', '/me');
	$response = $request->execute();
	$graphObject = $response->getGraphObject();
	echo 'Welcome ';
	echo $graphObject->getProperty('name');
	echo '!<hr>';
}


?>