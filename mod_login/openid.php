<?php

// echo $_GET['p'];echo '<hr />';

unset($_GET['p']);
unset($_REQUEST["p"]);
$len = strlen('p=openid/try_auth&');
$_SERVER["QUERY_STRING"] = substr($_SERVER["QUERY_STRING"],$len);
$_ENV["QUERY_STRING"] = substr($_ENV["QUERY_STRING"],$len);

// phpinfo();exit;
// echo $_GET['p'];

include($conf['ffw'].'mod_login/openid_common.php');

$page['title']		= "PHP OpenID Authentication Example";

if ($action == 'openid_default')
	$page['content']	= $smarty->fetch("openid.html");

if ($action == 'openid_try_auth')
	include($conf['ffw'].'mod_login/openid_try_auth.php');

if ($action == 'openid_finish_auth')
	include($conf['ffw'].'mod_login/openid_finish_auth.php');

