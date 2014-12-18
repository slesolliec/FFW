<?php

// this function could be used to create an account when someone logs in with openID
// and does not have an account yet on the platform
// cette fonction sera activee si on note dans config.yml :
// openid_login_fail: openid_login_failed_create_account()
function openid_login_failed_create_account() {
	
	global $user,$conf,$db,$page;
	
	// --> create a user with that openID
	$new = array("openid"=>$openid);
		
	// DEAL WITH LOGIN
	// check if name is valid
	if (@$sreg['nickname']) {
		$login = $sreg['nickname'];
		if (!eregi("^[a-zA-Z0-9]$", $login)) {
			$login2 = '';
			for($i=0;$i<strlen($login);$i++) {
				if (strpos("-abcdefghijklmnopqrstuvwxyzABCDEFJGHIJKLMNOPQRSTUVWXYZ1234567890",substr($login,$i,1)))
					$login2 .= substr($login,$i,1);
			}
			$login = $login2;
		}
	} else {
		$login = 'openid';
	}

	// check if name not already used
	$check_name = $db->getone('select id from users where lower(login) ="'.strtolower($login).'"');
	if ($check_name) {
		$i=2;
		while($check_name) {
			unset($check_name);
			$check_name = $db->getone('select id from users where lower(login) ="'.strtolower($login).'$i"');
			$i++;
		}
		$login .= $i;
	}
	
	$new['login'] = $login;

	// check if email is valid
	if (@$sreg['email']) {
		$email = trim($sreg['email']);
		// check email validity
		if (eregi("^[a-zA-Z0-9_\.]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$", $email)) {
			// check if email not already used
			$check_email = $db->getone('select id from users where lower(email)="'.strtolower($email).'"');
			if ($check_email)
				stop('Cette adresse email appartient déjà à un utilisateur, sans doute vous. Connectez-vous d\'abord de façon classique à cette plateforme, puis allez dans les informations de votre compte pour renseigner votre openID.',"Renseignez votre openID d'abord.");
					
			$new['email'] = $email;
		}
	}

	$new['creation_date']	= date('Y-m-d');
	$new['is_notified']		= 1;

	// add user to database
	$db->AutoExecute('users',$new,'INSERT');
	$new['id'] = $db->Insert_ID();


	// send email
	if ($new['email']) {
		// HTML body
		$page['title']		= "Bienvenue sur ".$conf['domain'];
		$page['content']	= "
				<p>Vous venez de vous inscrire sur <a href='http://www.{$conf['domain']}/'>www.{$conf['domain']}</a>.</p>

				<p>Les codes d'accés de votre compte sont :<br />";
		if ($new['login']) $page['content'] .= "pseudo : <strong>{$new['login']}</strong><br />";
		$page['content'] .= "email : <strong>{$new['email']}</strong><br />
			openID : <strong>{$new['openid']}</strong></p>

			<p>Si jamais votre serveur openID tombe en panne et vous empêche de vous connecter, suivez la procédure d'oubli de mot de passe. Elle vous générera un mot de passe et vous l'enverra sur votre email.</p>
		";
		$body = $smarty->fetch($conf['ffw']."mod_www/views/mail.html");

		// Plain text body (for mail clients that cannot read HTML)
		$body_txt = "Vous venez de vous inscrire sur www.{$conf['domain']}.

Les codes d'accés de votre compte sont :";
if ($new['login']) $body_txt .= "
pseudo       : {$new['login']}";
$body_txt .= "
email        : {$new['email']}
openID       : {$new['openid']}

Connectez-vous sur le site en allant sur http://www.{$conf['domain']}/

Si jamais votre serveur openID tombe en panne et vous empêche de vous connecter, suivez la procédure d'oubli de mot de passe. Elle vous générera un mot de passe et vous l'enverra sur votre email.
	";

		send_mail($new['email'],"[KarmaOS] Bienvenue",$body,$body_txt);
	}

	$user = $new + $user;
}

// FIN exemple de fonction créant un compte lorsqu'une nouvelle personne se logue avec openid



$consumer = getConsumer();

// Complete the authentication process using the server's response.
$return_to = getReturnTo();
$response = $consumer->complete($return_to);

// Check the response status.

// This means the authentication was cancelled.
if ($response->status == Auth_OpenID_CANCEL)
	stop('Vous avez annulé l\'authentification via openID.',"OpenID : vérification annulée.");

// Authentication failed; display the error message.
if ($response->status == Auth_OpenID_FAILURE)
	stop("Echec de l'authentification :<br/>" . $response->message,"OpenID echec");

if ($response->status == Auth_OpenID_SUCCESS) {
	// This means the authentication succeeded; extract the
	// identity URL and Simple Registration data (if it was
	// returned).
	$openid = $response->getDisplayIdentifier();
	$esc_identity = htmlspecialchars($openid, ENT_QUOTES);

	$success = sprintf('You have successfully verified ' .
	                   '<a href="%s">%s</a> as your identity.',
	                   $esc_identity, $esc_identity);

	if ($response->endpoint->canonicalID) {
	    $success .= '  (XRI CanonicalID: '.$response->endpoint->canonicalID.') ';
	}

	$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);

	$sreg = $sreg_resp->contents();

/*	if (@$sreg['email']) $success .= "  You also returned '".$sreg['email']."' as your email.";
	if (@$sreg['nickname']) $success .= "  Your nickname is '".$sreg['nickname']."'.";
	if (@$sreg['fullname']) $success .= "  Your fullname is '".$sreg['fullname']."'.";
*/

/*
	$pape_resp = Auth_OpenID_PAPE_Response::fromSuccessResponse($response);

	if ($pape_resp) {
		if ($pape_resp->auth_policies) {
			$success .= "<p>The following PAPE policies affected the authentication:</p><ul>";

			foreach ($pape_resp->auth_policies as $uri)
				$success .= "<li><tt>$uri</tt></li>";
			
			$success .= "</ul>";
		} else {
			$success .= "<p>No PAPE policies affected the authentication.</p>";
		}

		if ($pape_resp->auth_age) 
			$success .= "<p>The authentication age returned by the server is: <tt>".$pape_resp->auth_age."</tt></p>";

		if ($pape_resp->nist_auth_level)
			$success .= "<p>The NIST auth level returned by the server is: <tt>".$pape_resp->nist_auth_level."</tt></p>";

	} else {
		$success .= "<p>No PAPE response was sent by the provider.</p>";
	}
*/

	$page['success'] = $success;
	
	
	// log the user in
	
	// we look for the user in users
	$open_user = $db->getrow("select * from users where openid=\"$openid\"");
	
	if ($open_user) {
		// we've found the user
		$user = $open_user + $user;
		
	}  else {
		if ($conf['openid_login_fail']) {
			eval($conf['openid_login_fail']);
		} else {
			// user with that openID not in users table
			stop("Aucun compte ayant cette openID : <strong>$openid</strong>n'a été trouvé.<br/>Zutalors !!!","openID non trouvée");
		}
	}

	// we process with login

	// we set a connexion cookie
	$action_temp = $action;
	$action = 'none';
	include_once($conf['ffw']."mod_login/login.php");
	$action = $action_temp;
	set_cnx_cookie($user);

	if (!$_GET['redirect'])
		$_GET['redirect'] = '/?u='.date('U');

	header('Location: '.$_GET['redirect']);
	exit;
}


$page['content']	= $smarty->fetch($conf['ffw']."mod_login/views/openid.html");

