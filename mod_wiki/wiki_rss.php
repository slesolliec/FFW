<?php

// this page generates the rss feed of the wiki

$mod_pages = $db->getall("
	select p.name,p.title,p.updated_at,p.edit_desc,substring(p.content,1,2048) as content,p.tags,{$wiki['author_name_column']} as author_name
	from pages as p,$userTable
	where p.user_id = $userTable.id
	order by p.updated_at desc
	limit 10
");

if ($mod_pages) {
	for($i=0;$i<sizeof($mod_pages);$i++) {
		$t = $mod_pages[$i]['updated_at'];
		$tt = mktime(substr($t,11,2),substr($t,14,2),substr($t,17,2),substr($t,5,2),substr($t,8,2),substr($t,0,4));
//		$mod_pages[$i]['updated_at'] = date_iso8601($tt);
		$mod_pages[$i]['updated_at'] = date('N',$tt);
		}
	}

$smarty->assign('pages',$mod_pages);

header('Content-type: text/xml; charset=utf-8');
$page['content'] = $smarty->display($conf['ffw'].'mod_wiki/views/wiki_rss.xml');
exit;

