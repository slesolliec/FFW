<?php

if (!$user['is_admin'])
	stop('Il faut être administrateur pour accèder à cette page. :-|',401);

$nb_pages = $db->getone('select count(*) from pages_archives');

// do the actual purge
if ($_POST['purge_it']==xss_armor($nb_pages)) {
	$db->execute("delete from pages_archives");
	$nb_pages = $db->getone('select count(*) from pages_archives');	
}

$page['nb_archives'] = intval($nb_pages);

$page['title']		= 'Maintenance de votre wiki';
$page['content']	= $smarty->fetch($conf['ffw'].'mod_wiki/views/wiki_maintenance.html');
