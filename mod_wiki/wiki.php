<?php

if ($action == 'default') $action = 'accueil';
if ($params == 'default') $params = '';

/*
$wiki = array(
	'id'		=> 1,
	'url'		=> $conf['url'].'wiki/',
	'images'	=> $conf['mods'].'../wiki_image/',
);
*/

// over-riding with local configs
if ($wiki[$_SERVER["HTTP_HOST"]]) {
	$wiki2 = $wiki[$_SERVER["HTTP_HOST"]];
	unset($wiki[$_SERVER["HTTP_HOST"]]);
	$wiki = my_array_merge($wiki,$wiki2);
}


// access control
if (in_array($action,array('create','edit','insert','update','delete'))) {
	if (!$user[$idUField]) {
		$page['title'] = "Connexion nécessaire";
		stop("Il faut être connecté au site pour pouvoir faire ceci.");
	}
	if (!$user['is_writer']) {
		$page['title'] = "Il faut être auteur";
		stop("Vous devez être auteur pour faire ceci.");
	}
}




$smarty->assign('wiki',$wiki);

$page['rss'] = $wiki['url'].'rss';

// function to clean up page name
function clean_up_page_name($page_name) {
	list($page_name, $anchor) = explode("#",$page_name,2);
	$page_name = ltrim($page_name,'/');
	$page_name = stripslashes($page_name);
	$page_name = str_replace(' ','_',$page_name);
	if (!$page_name) $page_name='accueil';
	return $page_name;
}

// function to turn tagsoup into clean XHTML
function turn_into_valid_xhtml($tagsoup) {
	return str_replace('<br>','<br />',$tagsoup);	// LOL
}

// we compute the dir from the pagename
function get_dir_from_page_name($p) {
	if (strpos($p,'/'))
		return substr($p,0,(strlen($p) - strlen(strrchr($p,'/')))) . '/';

	return '';
}


// if the column we use to get the name of the author (of a post or a comment) is not specified
// we use the first varchar column of the user table

if (!$wiki['author_name_column'])
	foreach ($dbs[$userTable] as $fieldname => $field)
		if ($field['type']=='varchar')
			if (!$wiki['author_name_column'])
				$wiki['author_name_column'] = $userTable.'.'.$fieldname;

if (in_array($action, array('create','insert','edit','update','list','delete'))) {
	// actions where we can rely on mod_crud

	if ($action == 'edit') {
		$params = clean_up_page_name($params);

		// we get actual page or an archived version
		if ($_GET['updated_at']) {
			$sql = "select * from pages_archives where name = \"$params\" and updated_at='{$_GET['updated_at']}'";
		} else {
			$sql = "select * from pages where name = \"$params\"";
		}
		$oo = $db->getrow($sql);
	}
	
	
	if (($action == 'update') or ($action == 'insert')) {
		
		// on gère la date
		if ($_POST['page_date_time']) {
			$_POST['page_time'] = input_to_time($_POST['page_date_time']);
		} else {
			$_POST['page_time'] = '';
		}
		
	}
	
	
	// we pass vars in $_SESSION for CKFinder to work ok
	if (($action == 'edit') or ($action == 'create')) {
		session_start();
		$_SESSION['CKFinder_baseUrl']	= $wiki['img_baseUrl'];
		$_SESSION['CKFinder_baseDir']	= $wiki['img_baseDir'];
		$_SESSION['CKFinder_UserRole']	= 'none';
		if ($user['is_writer'])	$_SESSION['CKFinder_UserRole'] = 'is_writer';
		if ($user['is_admin'])	$_SESSION['CKFinder_UserRole'] = 'is_admin';
	}
	

	include $conf['ffw']."mod_crud/crud.php";

	// we append the delete button
	if ($action == 'edit') {
		$page['content'] .= "
			<form action=\"{$wiki['url']}delete/{$oo['id']}/".xss_armor($oo['id'])."\">
				<input id='btn_delete' type='submit' value='Supprimer cette page' onclick=\"return confirm('Etes-vous certain de vouloir supprimer la page {$oo['name']} ? C\'est irréversible');\" />
			</form>
		";
	}

} elseif (file_exists($conf['ffw']."mod_wiki/wiki_$action.php")) {
	// actions we need to handle within the module
	include($conf['ffw']."mod_wiki/wiki_$action.php");

} else {
	// view  page
	$page_name = $action;
	if ($params) $page_name .= '/'.$params;

	include($conf['ffw']."mod_wiki/wiki_view.php");
}


// we look for tagged pages
$page['tagged'] = $db->getall('select pages.name,pages.title
	from pages,tag_index
	where tag_index.tag="'.str_replace('_',' ',$page['name']).'"
		and tag_index.page_id=pages.id');


// we build the page menu by overriding $conf['menu']
$page['menu'] = $db->getall('select name,menu_title
	from pages
	where menu_title != ""
		and dir = "'.$page['dir'].'"
	order by menu_rank DESC,menu_title');
if ($page['menu']) {
	unset($conf['menu']);
	$conf['menu'] = array();
	foreach ($page['menu'] as $menu)
		$conf['menu'][$menu['menu_title']] = $wiki['url'].$page['dir'].$menu['name'];
} 



// SHOULD BE A WIDGET: we look for the top 20 tags of the wiki
$page['toptags'] = $db->getall('select tag,count(*) as nb from tag_index group by tag order by nb desc limit 20');

// we get the right template
$template_temp = $page['name'];
while ($template_temp) {
	$test_file = $conf['mods']."mod_$module/views/".str_replace('/','_',$template_temp).".html";
	if (file_exists($test_file)) {
		$page['template'] = str_replace('/','_',$template_temp).'.html';
		break;
	}
	$template_temp = substr($template_temp,0,strrpos($template_temp,'/'));
}


// add "create page" to user widget
if ($user['is_author'])
	$conf['menu_user']['Nouvelle Page'] = $wiki['url']."page/create";



// $temp_column = $page['column'];
// $page['column'] = '';
/* add widgets
if ($wiki['widgets'])
	foreach ($wiki['widgets'] as $widget)
		include($conf['ffw']."mod_wiki/widget_$widget.php");
*/
// $page['column'] .= $temp_column;

$mod = $wiki;
