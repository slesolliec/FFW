<?php

if (!$user['is_admin'])
	stop('Il faut être administrateur pour accèder à cette page. :-|',401);

$_GET['id'] = intval($_GET['id']);
if (!$_GET['id'])
	stop("L'identifiant de la page à ré-ordonner n'est pas spécifié.","Page non spécifiée.");
	
if ($_GET['do'] == 'up')
	$db->execute("-- on monte la page {$_GET['id']} du wiki {$wiki['id']}
		update pages
		set menu_rank = menu_rank+1
		where id={$_GET['id']}
			and wiki_id={$wiki['id']}
	");
	

if ($_GET['do'] == 'down')
	$db->execute("-- on monte la page {$_GET['id']} du wiki {$wiki['id']}
		update pages
		set menu_rank = menu_rank-1
		where id={$_GET['id']}
			and wiki_id={$wiki['id']}
	");


$page['title'] = 'Gestion des menus de votre wiki';

$get_menus = $db->getall('
	select menu_title,menu_rank,dir,name,id
	from pages
	where menu_title != ""
	order by dir,menu_rank DESC,menu_title');
$smarty->assign('menus',$get_menus);

$page['content'] = $smarty->fetch($conf['ffw'].'mod_wiki/views/wiki_menu.html');

