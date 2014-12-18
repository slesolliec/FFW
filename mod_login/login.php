<?php

/* this module does :

	- login
	- logout
	- forgot (pwd)
	- send_new (pwd)

	It could be more intelligent in terme of forgotten pwd:
	could send a "I can change my pwd once" token (only once every 15min)
	instead of erasing the former PWD
	
	It could deal with applications that DON'T use logins (that use only email to identify their users)
	
	It could work with OpenID, cellphones, twitter, ...

*/


// this function sets the connexion cookie of a user
function set_cnx_cookie($user) {
	global $db,$conf,$userTable,$cookieUField,$idUField;
	$token = substr(md5(microtime()),0,12);
	$user[$cookieUField]	= $token;
	$user['lastlog_ip']		= $user['ip'];
	$user['lastlog_utime']	= date('U');
	$db->AutoExecute($userTable,$user,'UPDATE',"$idUField=".$user[$idUField]);
	setcookie($cookieUField,$user['id'].'-'.$token,time()+3600*24*365,'/','.'.$conf['domain']);
}


// we show the login form
if ($action == 'default') {
	$page['title'] = "Login";
	
	if (file_exists($conf['mods'].'mod_login/views/login_default.html'))
		$page['content'] = $smarty->fetch($conf['mods'].'mod_login/views/login_default.html');
	else
		$page['content'] = $smarty->fetch($conf['ffw'].'mod_login/views/login_default.html');
	
}



if ($action == 'login') {
	// we get the user
	if (strpos($_POST['login'],'@')) {
		$get_user = $db->GetRow("select * from $userTable where $emailUField='{$_POST['login']}' order by $idUField asc");
		if (!$get_user)
			stop('Cet email est inconnu : <strong>'.$_POST['login'].'</strong>','Email inconnu');
	} else {
		$get_user = $db->GetRow("select * from $userTable where $loginUField='{$_POST['login']}' order by $idUField asc");
		if (!$get_user)
			stop('Ce code d\'accès est inconnu : <strong>'.$_POST['login'].'</strong>','Code d\'accès inconnu');
	}

	if (!$get_user['is_active'])
		stop("<p>Votre compte n'est pas actif. Vous ne pouvez pas vous connecter à ce site.</p>
			<p>Contactez un administrateur du site si vous voulez changer le statut de votre compte.</p>
			","Compte inactif");

	if ($get_user[$pwdUField] == '')
		stop("<p>Votre mot de passe n'a jamais été généré. Vous ne pourrez pas vous connecter au site tant que
			vous n'aurez pas <a href='login/forgot'>demandé la génération de votre mot de passe</a>.</p>","Mot de passe à générer");

	if ($get_user[$pwdUField] != pwd_hash($_POST['pwd']))
		stop("<p>Le mot de passe que vous avez saisi n'est pas le bon.</p>
			<p>Avez-vous <a href='login/forgot'>oublié votre mot de passe</a> ?</p>","Mauvais mot de passe");

	$user = $get_user + $user;
	// we set a connexion cookie
	set_cnx_cookie($user);
	
	// possibility of a hook
	if ($conf['hooks']['login']['login']) {
		include($conf['mods'].$conf['hooks']['login']['login']);
	} else {
		if (!$_POST['redirect']) {
	//		if ($_SERVER["HTTP_REFERER"]) {
	//			$_POST['redirect'] = $_SERVER["HTTP_REFERER"].'?u='.date('U');
	//		} else {
				$_POST['redirect'] = $conf['url'].'?u='.date('U');
	//		}
		}
		header('Location: '.$_POST['redirect']);
		exit;
	}

}


// action = log off
if ($action == 'logout') {
	session_start();
	session_destroy();
	setcookie($cookieUField,'',time()-3600,'/','.'.$conf['domain']);
	$db->execute("update $userTable set $cookieUField='' where $idUField=".$user[$idUField]);
	unset($user);

	if ($_GET['redirect']) {
		$redirect = $_GET['redirect'];
	} else {
//		if ($_SERVER["HTTP_REFERER"]) {
//			$_POST['redirect'] = $_SERVER["HTTP_REFERER"].'?'.date('U');
//		} else {
			$redirect = $conf['url'].'?'.date('U');
//		}
	}
	header("Location: $redirect");
	exit;
}


// forgotten password
if ($action == 'forgot') {
	$page['title'] = "Mot de passe oublié";
	$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_login/login_forgot.html");
}


if ($action == 'send_new') {
	if (!$_POST['login'])
		stop('Vous devez donner votre code d\'accès ou votre email.',"Code vide");

	if (strpos(' '.$_POST['login'],'@')) {
		$get_lost = $db->GetRow("select * from $userTable where $emailUField='{$_POST['login']}' order by id asc");
		if (!$get_lost)
			stop("Aucun utilisateur n'ayant cet email : <strong>{$_POST['login']}</strong> n'existe dans notre base.","Email inconnu");
	} else {
		$get_lost = $db->GetRow("select * from $userTable where $loginUField='{$_POST['login']}' order by id asc");
		if (!$get_lost)
			stop("Aucun utilisateur n'ayant ce code d'accès : <strong>{$_POST['login']}</strong> n'existe dans notre base.","Code d'accès inconnu");
	}
	
	// change pwd and send new one
	$pwd = substr(md5(microtime()),0,6);
	$db->execute("update $userTable set $pwdUField='".pwd_hash($pwd)."' where $idUField=".$get_lost[$idUField]);

	// send email

	// HTML body
	$smarty->assign('get_lost',$get_lost);
	$smarty->assign('pwd',$pwd);
	$body = $smarty->fetch($conf['ffw'].'mod_login/login_forgot_mailbody.html');
	$page['title'] = "Nouveau mot de passe";
	$page['content'] = $body;
	if (file_exists($conf['root'].'mod_www/views/mail.html')) {
		$body = $smarty->fetch("file:{$conf['root']}mod_www/views/mail.html");
	} else {
		$body = $smarty->fetch("file:{$conf['ffw']}mod_www/views/mail.html");
	}
	
	// Plain text body (for mail clients that cannot read HTML)
	$body_txt = $smarty->fetch($conf['ffw'].'mod_login/login_forgot_mailbody.txt');

	send_mail($get_lost[$emailUField],"[{$conf['flag']}] nouveau mot de passe",$body,$body_txt);

	$page['title'] = "Nouveau mot de passe envoyé";
	$page['content'] = "
		<p>Un message avec votre nouveau mot de passe vient de vous être envoyé.</p>
		<p>Si vous n'avez rien reçu dans les quelques minutes, pensez à regarder dans votre boite à SPAM.</p>
	";
}


if (substr($action,0,6) == 'openid')
	include($conf['FFW'].'mod_login/openid.php');

