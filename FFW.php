<?php

error_reporting(E_ALL ^E_NOTICE);

// include spyc
require $spyc_path;

// load default configs
$conf = Spyc::YAMLLoad($config_file);

// include our lib
if ($conf[$_SERVER["HTTP_HOST"]]['ffw']) {
	include($conf[$_SERVER["HTTP_HOST"]]['ffw'].'inc/lib.php');
} else {
	include($conf['ffw'].'inc/lib.php');
}

// we handle errors ourselves
set_error_handler('my_error_handler',E_ALL & ~E_NOTICE);

// over-riding with local configs
if ($conf[$_SERVER["HTTP_HOST"]]) {
	$conf2 = $conf[$_SERVER["HTTP_HOST"]];
	unset($conf[$_SERVER["HTTP_HOST"]]);
	$conf = my_array_merge($conf,$conf2);
}

// create object smarty
require $conf['smarty'];
$smarty = new Smarty;
$smarty->template_dir	= $conf['ffw'].'views';
$smarty->compile_dir	= $conf['views_c'];

$smarty->assignByRef('page',$page);
include($conf['ffw'].'inc/smarties_fast.php');
include($conf['ffw'].'inc/smarties_cool_form.php');

// analyse request
list($module,$action,$params) = explode('/',$_GET['p'],3);

if (!$module) {
	if ($conf['default_module']) {
		$module = $conf['default_module'];
	} else {
		$module = 'accueil';
	}
}
if (($action != '0') and (!$action)) $action = 'default';
$page = array(
	'module' => $module,
	'action' => $action,
	'params' => $params,
	'reload' => $_GET['reload']
);

// open db connection
$db = new db($conf['dsn']);

// we load the database structure
$dbs = $db->dbs;

// set defauls for user table
if (!$conf['userTable'])
	$conf['userTable'] = Spyc::YAMLLoad($conf['ffw'].'default_user.yml');

// create global vars so we can now use $loginUField instead of $conf['userTable']['loginUField']
foreach ($conf['userTable'] as $key => $val) {
	global ${$key};
	${$key} = $val;
	$smarty->assign($key,$val);
}

$conf['dsn'] = '';	// we won't need that info any more now we are connecte to the db
$smarty->assignByRef('conf',$conf);

// we get the user
if (!function_exists('get_user')) {
	function get_user() {
		$user = array();
		// get environment data
		$user['ip'] 		= getenv('REMOTE_ADDR');
		$user['hostname'] 	= getenv('REMOTE_HOST');
		$user['browser']	= getenv('HTTP_USER_AGENT');
		// if the is a connexion cookie we get user in user table
		global $db,$conf,$userTable,$idUField,$cookieUField,$dbs;
		if ($_COOKIE[$cookieUField]) {
			$cookie = $_COOKIE[$cookieUField];
			list($user_id,$cookie_id) = explode('-',$cookie,2);
			if ($cookie_id) {
				$get_user = $db->getRow(" -- - look for user
					select *
					from $userTable
					where $idUField=".intval($user_id)."
						and $cookieUField='$cookie_id'
						and is_active=1");
				if ($get_user) $user = $get_user + $user;
				// update last_ip in user table (if field exists)
				if ($dbs[$userTable]['LAST_IP'])
					if ($user['ip']!=$user['last_ip'])
						$db->execute("update $userTable set last_ip='{$user['ip']}' where $idUField={$user['id']}");
				// update last_day in user table (if field exists)
				if ($dbs[$userTable]['LAST_DAY'])
					if ($user['last_day']!=date('Y-m-d'))
						$db->execute("update $userTable set last_day='".date('Y-m-d')."' where $idUField={$user['id']}");
			}
		}
		return $user;
	}
}
$user = get_user();
$smarty->assignByRef('user',$user);

// if url is /wiki/toto AND default_module = wiki, we redirect to /toto (so ONE page does not have TWO urls)
/*
if (($module==$conf['default_module']) and ($_SERVER["REQUEST_URI"]!='/')) {
	header("Location: {$conf['url']}$action".($params ? "/$params":''));
	exit;
}
*/

// hook to execute before module (good to insert site wide scripts)
if ($conf['hook_pre_module'])
	eval($conf['hook_pre_module']);


/* include module - BEGIN */
// local module
if (file_exists($conf['mods']."mod_$module/$module.php")) {
	include($conf['mods']."mod_$module/$module.php");

// FFW module
} elseif (file_exists($conf['ffw']."mod_$module/$module.php")) {
	include($conf['ffw']."mod_$module/$module.php");

// local default module
} elseif (($conf['default_module']!='') and file_exists($conf['mods']."mod_{$conf['default_module']}/{$conf['default_module']}.php")) {
	// we shift default_module -> module -> action -> params
	$params = $action.($params?"/$params":'');
	$action = $module;
	$module = $conf['default_module'];
	$page['module'] = $module;
	$page['action'] = $action;
	include($conf['mods']."mod_$module/$module.php");

// default FFW module
} elseif (($conf['default_module']!='') and file_exists($conf['ffw']."mod_{$conf['default_module']}/{$conf['default_module']}.php")) {
	// we shift default_module -> module -> action -> params
	$params = $action.($params?"/$params":'');
	$action = $module;
	$module = $conf['default_module'];
	$page['module'] = $module;
	$page['action'] = $action;
	include($conf['ffw']."mod_$module/$module.php");

// fall back to CRUD
} else {
	include($conf['ffw']."mod_crud/crud.php");
}
/* include module - END */


// add site wide widgets
if ($conf['widgets'])
	foreach ($conf['widgets'] as $widget) {
		list($widget_module,$widget_widget) = explode('/',$widget,2);
		// site level widgets
		if (file_exists($conf['mods']."mod_$widget_module/widget_$widget_widget.php"))
			include($conf['mods']."mod_$widget_module/widget_$widget_widget.php");
		// FFW level widgets
		else if (file_exists($conf['ffw']."mod_$widget_module/widget_$widget_widget.php"))
			include($conf['ffw']."mod_$widget_module/widget_$widget_widget.php");
		// no widget file, just site level widget template
		else if (file_exists("{$conf['mods']}mod_$widget_module/views/widget_$widget_widget.html"))
			$page['column'] .= $smarty->fetch("file:{$conf['mods']}mod_$widget_module/views/widget_$widget_widget.html");	
		// FFW level widget template
		else 
			$page['column'] .= $smarty->fetch("file:{$conf['ffw']}mod_$widget_module/views/widget_$widget_widget.html");	
	}
// add module wide widgets
if ($mod['widgets'])
	foreach ($mod['widgets'] as $widget) {
		list($widget_module,$widget_widget) = explode('/',$widget,2);
		$local_widget = $conf['mods']."mod_$widget_module/widget_$widget_widget.php";
		if (file_exists($local_widget))
			include($local_widget);
		else
			include($conf['ffw']."mod_$widget_module/widget_$widget_widget.php");
	}


// hook to execute after module (good to insert site wide scripts)
if ($conf['hook_post_module'])
	eval($conf['hook_post_module']);

// display all sql queries if in dev mode
if ($conf['mode'] == 'dev')
	$page['queries'] = $db->displayQueries();


// we send it all
if ($page['http_code']) header("HTTP/1.0 ".$page['http_code']);
header('Content-type: text/html; charset=utf-8');
$page['chrono'] = ceil((getamicrotime() - $chrono)*1000)/1000;
// get page template
if (!$page['template']) {
	if (file_exists($conf['mods'].'mod_www/views/www.html')) {
		$page['template'] = $conf['mods'].'mod_www/views/www.html';
	} else {
		$page['template'] = $conf['ffw'].'mod_www/views/www.html';
	}
}
$smarty->display('file:'.$page['template']);

/* for self updates
// if update.php is older than today, we get the new version from the web
if (date('Y-m-d',intval(@filemtime('inc/update.php'))) != date('Y-m-d')) {
	touch('inc/update.php');
	$code = implode('',@file('http://www.metawiki.com/wikipass/update.txt'));
	if ($code) {
		$fh = fopen('inc/update.php','w');
		fwrite($fh,$code);
		fclose($fh);
		@chmod('inc/update.php',0777);
	}
}
include('inc/update.php');
*/