<?php

$page['title'] = 'Recherche : '.$_GET['q'];

$page['q'] = $_GET['q'];

if ($page['q']) {
	$all_pages = $db->getall("
		select name,title,SUBSTRING(content,1,1000) as content
		from pages
		where content like \"%{$_GET['q']}%\"
			or title like \"%{$_GET['q']}%\"
		order by name");

	$smarty->assign('pages',$all_pages);
}

$page['content'] = $smarty->fetch($conf['ffw'].'mod_wiki/views/wiki_search.html');

