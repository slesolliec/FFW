<?php


// include("/Users/stephane/Web/lib/openid/OpenID.php");
// include("/Users/stephane/Web/lib/php-openid-2.0.1/examples/detect.php");

// include("/home/karma/lib/openid/examples/detect.php");


// $path_extra = dirname(dirname(dirname(__FILE__)));
$path = ini_get('include_path');
$path = $conf['lib_openid'] . PATH_SEPARATOR . $path;
// echo $path;exit;
ini_set('include_path', $path);


// Require the OpenID consumer code.
require_once $conf['lib_openid']."/Auth/OpenID/Consumer.php";
// Require the "file store" module, which we'll need to store OpenID information.
require_once $conf['lib_openid']."/Auth/OpenID/FileStore.php";
// Require the Simple Registration extension API.
require_once $conf['lib_openid']."/Auth/OpenID/SReg.php";
// Require the PAPE extension module.
require_once $conf['lib_openid']."/Auth/OpenID/PAPE.php";

global $pape_policy_uris;

$pape_policy_uris = array(
	PAPE_AUTH_MULTI_FACTOR_PHYSICAL,
	PAPE_AUTH_MULTI_FACTOR,
	PAPE_AUTH_PHISHING_RESISTANT
);
$smarty->assign('pape_uris',$pape_policy_uris);

function &getStore() {
	/**
	* This is where the example will store its OpenID information.
	* You should change this path if you want the example store to be
	* created elsewhere.  After you're done playing with the example
	* script, you'll have to remove this directory manually.
	*/
	$store_path = "/tmp/_php_consumer_test";

	if (!file_exists($store_path) && !mkdir($store_path))
		stop("Could not create the FileStore directory '$store_path'. Please check the effective permissions.","openID filestore error");

	return new Auth_OpenID_FileStore($store_path);
}

function &getConsumer() {
	/**
	* Create a consumer object using the store object created
	* earlier.
	*/
	$store = getStore();
	return new Auth_OpenID_Consumer($store);
}

function getScheme() {
	$scheme = 'http';
	if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on')
		$scheme .= 's';
	return $scheme;
}

function getReturnTo() {
	global $conf;
	if ($_GET['redirect']) {
		return $conf['url'].'login/openid_finish_auth?redirect='.urlencode($_GET['redirect']);
	} else {
		return $conf['url'].'login/openid_finish_auth';
	}
//	return sprintf("%s://%s:%s%s/finish_auth.php",getScheme(), $_SERVER['SERVER_NAME'],$_SERVER['SERVER_PORT'],dirname($_SERVER['PHP_SELF']));
}

function getTrustRoot() {
	global $conf;
	return $conf['url'];
//	return sprintf("%s://%s:%s%s/",getScheme(), $_SERVER['SERVER_NAME'],$_SERVER['SERVER_PORT'],dirname($_SERVER['PHP_SELF']));
}


global $pape_policy_uris;
