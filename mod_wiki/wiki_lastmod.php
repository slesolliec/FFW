<?php

$page['title'] = 'Dernières modifications du wiki';

$a_week_ago = date('Y-m-d H:i:s',intval(date('U') - 7 * 24 * 3600));
$sql = "
	select p.name,p.id,p.title,p.updated_at,p.edit_desc,{$wiki['author_name_column']} as author_name
	from pages as p,$userTable
	where updated_at>\"$a_week_ago\"
		and p.user_id = $userTable.id
	order by p.updated_at desc limit 30
	";
$mod_pages = $db->getall($sql);

if ($mod_pages) {
	for($i=0;$i<sizeof($mod_pages);$i++) {
		// make sure edit_desc is not empty
		if (!$mod_pages[$i]['edit_desc']) $mod_pages[$i]['edit_desc'] = '[no description]';
		// we compute the age of the page
		$t = $mod_pages[$i]['updated_at'];
		$delta = mktime(23,59,59,date('m'),date('d'),date('Y')) - mktime(substr($t,11,2),substr($t,14,2),substr($t,17,2),substr($t,5,2),substr($t,8,2),substr($t,0,4));
		$mod_pages[$i]['days_ago'] = floor($delta / (24*3600));
				
		$sql = "
			select p.title,p.updated_at,p.edit_desc,{$wiki['author_name_column']} as author_name
			from pages_archives as p,$userTable
			where p.id=".intval($mod_pages[$i]['id'])."
				and p.updated_at>\"$a_week_ago\"
				and p.user_id = $userTable.id
			order by p.updated_at desc limit 30
			";
		$mod2 = $db->getall($sql);
		if ($mod2) {
			for ($j=0;$j<sizeof($mod2);$j++) {
				$t = $mod2[$j]['updated_at'];
				$delta = mktime(23,59,59,date('m'),date('d'),date('Y')) - mktime(substr($t,11,2),substr($t,14,2),substr($t,17,2),substr($t,5,2),substr($t,8,2),substr($t,0,4));
				$mod2[$j]['days_ago'] = floor($delta / (24*3600));
				if (!$mod2[$j]['edit_desc']) $mod2[$j]['edit_desc'] = '[no description]';
			}
			$mod_pages[$i]['archives'] = $mod2;
			$mod_pages[$i]['archives_nb'] = sizeof($mod2);
			unset($mod2);
		}
	}
}

$smarty->assign('pages',$mod_pages);
$smarty->assign('today',date('m-d'));

$page['content'] = $smarty->fetch($conf['ffw'].'mod_wiki/views/wiki_lastmod.html');
