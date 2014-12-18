<?php

$oo = clean_magic_quotes($_POST);

// pseudo fields
if (is_array($mod['actions']['insert']['fields']))
	foreach ($mod['actions']['insert']['fields'] as $key => $val)
		if ($val['filter'])
			$oo[$key] = eval($val['filter']);


// check if login is valid
if ($conf['userTable']['loginUField']!='none') {
	$oo[$loginUField] = trim($oo[$loginUField]);
	if (!$oo[$loginUField])
		stop('Vous devez donner un code d\'accès (utilisez un pseudo si vous voulez).',"Code d'accès manquant");
	if (!preg_match("/^[a-zA-Z0-9][ a-zA-Z0-9_\-]+$/i", $oo[$loginUField]))
		stop('Ce nom ou pseudo n\'est pas valide. N\'utilisez que des chiffres, des lettres sans accents et - ou _. Commencez par une lettre.',"Code d'accès invalide");
}

// check if email is valid
$oo[$emailUField] = trim($oo[$emailUField]);
if (!$oo[$emailUField])
	stop('Vous devez obligatoirement donner une adresse email pour vous inscrire, puisque votre nouveau mot de passe doit vous être envoyé par email.',"Adresse email manquante");
if (!preg_match("/^[-%&._a-zA-Z0-9]+@[-a-z0-9]+(\.[-a-z0-9]+)*\.[-a-z0-9]{2,6}$/i", $oo[$emailUField]))
	stop('L\'email donnée n\'est pas valide.',"Email invalide");

// check if email not already used
$check_email = $db->getone("select $idUField from $userTable where lower($emailUField)='".strtolower($oo[$emailUField])."'");
if ($check_email)
	stop("<p>Cette adresse email appartient déjà à un utilisateur.</p>
			<p>Si c'est vous, alors c'est que vous avez déjà un compte sur ce site. Vous avez peut-être <a href='login/forgot'>oublié votre mot de passe</a>.</p>","Email déjà utilisée");

// check if login not already used
if ($loginUField != 'none') {
	$check_name = $db->getone("select $idUField from $userTable where lower($loginUField) ='".strtolower($oo[$loginUField])."'");
	if ($check_name)
		stop("<p>Ce code d'accès est déjà utilisé. Il vous faut en choisir un autre.</p>","Code d'accès déjà pris");
}

// add user to database

// we create the password if it has not been created by filters
if (!$oo[$pwdUField]) {
	$oo['clear_pwd']	= substr(md5(microtime()),0,6);
	$oo[$pwdUField]		= pwd_hash($new_pwd);
}

$oo['created_at']	= date('Y-m-d H:i:s');
$db->AutoExecute($userTable,$oo,'INSERT');
$oo['id']			= $db->Insert_ID();

// send email

// HTML body
$page['title']	= "Bienvenue sur ".$conf['flag'];
$oo['login']	= $oo[$loginUField];
$oo['email']	= $oo[$emailUField];
$oo['pwd']		= $oo['clear_pwd'];
$smarty->assign('oo',$oo);
$page['content']	= $smarty->fetch("file:{$conf['ffw']}mod_user/views/user_insert.html");

if (file_exists($conf['root'].'mod_www/views/mail.html')) {
	$body = $smarty->fetch("file:{$conf['root']}mod_www/views/mail.html");
} else {
	$body = $smarty->fetch("file:{$conf['ffw']}mod_www/views/mail.html");
}

// Plain text body (for mail clients that cannot read HTML)
$body_txt = "Vous venez de vous inscrire sur {$conf['url']}

Les codes d'accés de votre compte sont :
email        : {$oo['email']}";
if ($oo['login'])
	$body_txt .= "code d'accès : {$oo['login']}";
$body_txt .= "
mot de passe : $new_pwd

Connectez-vous sur le site en allant sur {$conf['url']}
";

send_mail($oo['email'],"[{$conf['flag']}] Bienvenue - votre mot de passe",$body,$body_txt);

$page['title'] = "Compte créé";
$page['content'] = "
	<p>Merci de vous être inscrit, un email contenant votre mot de passe vient de vous être envoyé.</p>
	<p>Si vous n'avez rien reçu dans les quelques minutes, pensez à regarder dans votre boite à SPAM.</p>
";
