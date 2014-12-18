<?php

if (!$user['is_admin'])
	stop('Il faut être administrateur pour accèder à cette page. :-|',401);

$page['title'] = 'Retourner en arrière';

if ($_POST['do_it']==xss_armor(date('Hmyd'))) {
	
	foreach($_POST as $key => $val) {
		if (substr($key,0,8)=='reverse_') {
			$key = substr($key,8);
			// echo $key . ':'. $val.'<br />';
			$sql = 'delete from pages where id="'.intval($key).'" and updated_at="'.$val.'" limit 1';
			$delete_page = $db->execute($sql);
			// echo $delete_page;
			if ($delete_page) {
				// get the most recent archive back into the table pages
				$db->execute('insert into pages select * from pages_archives where id="'.intval($key).'" order by updated_at desc limit 1');
				// we do a check (a bit because you can't user order by in delete clauses, so we need to know the date_edit
				$check_added = $db->getone('select updated_at from pages where id="'.intval($key).'"');
				if ($check_added) {
					// delete the most recent archive from table_archives
					$db->execute('delete from pages_archives where id="'.intval($key).'" and updated_at="'.$check_added['updated_at'].'" limit 1');
				}
			}
		}
	}
	header('location: '.$wiki['url'].'reverse?'.date('U'));
	exit;

} else {
	$get_pages = $db->getall("
		select p.id,p.name,p.title,p.updated_at,p.user_id,p.edit_desc,{$wiki['author_name_column']} as author_name
		from pages as p,$userTable,pages_archives as arch
		where p.user_id=$userTable.id
			and p.id=arch.id
		order by p.updated_at desc
		limit 100
	");
	$smarty->assign('pages',$get_pages);
	$page['content'] = $smarty->fetch($conf['ffw'].'mod_wiki/views/wiki_reverse.html');
}

