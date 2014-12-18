<?php

if (!$user['is_admin'])
	stop('Il faut être administrateur pour accéder à cette page. :-|',401);

$page['title'] = 'Supprimer des versions archivées des pages de votre wiki';

if ($_POST['do_it']==xss_armor(date('YmdH'))) {
	
	foreach($_POST as $key => $val) {
		if (substr($key,0,7)=='delete_') {
			$key = substr($key,7);
			$key = substr($key,0,strlen($key)-15);
			$sql = 'delete from pages_archives where id="'.$key.'" and updated_at="'.$val.'" limit 1';
//			echo $sql;exit;
			$db->execute($sql);
		}
	}
	header('location: '.$wiki['url'].'arch_delete?'.date('U'));
	exit;


} else {
	$get_pages = $db->getall("
		select p.id,p.name,p.title,p.updated_at,p.edit_desc,p.user_id,{$wiki['author_name_column']} as author_name
		from pages_archives as p,$userTable
		where p.user_id=$userTable.id
		order by p.updated_at desc
		limit 100");
	if ($get_pages)	$smarty->assign('pages',$get_pages);
	$page['content'] = $smarty->fetch($conf['ffw'].'mod_wiki/views/wiki_arch_delete.html');
}

