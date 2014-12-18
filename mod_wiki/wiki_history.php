<?php

$params = clean_up_page_name($params);

// we get the page
$sql = "
	select p.name,p.title,p.updated_at,p.edit_desc,{$wiki['author_name_column']} as author_name
	from pages as p,$userTable
	where p.name = '$params'
		and p.user_id = $userTable.id
	";

$get_page = $db->getrow($sql);
if (!$get_page)
	stop('Cette page n\'existe pas',404);

foreach ($get_page as $k => $v)
	$page[$k] = $v;


// we get the archived versions
$sql = "
	select p.updated_at,p.edit_desc,p.title,{$wiki['author_name_column']} as author_name
	from pages_archives as p,$userTable
	where p.name = \"$params\"
		and p.user_id = $userTable.id
	order by p.updated_at desc";
$page_archived = $db->getall($sql);

$smarty->assign('page_archived',$page_archived);

$page['content'] = $smarty->fetch($conf['ffw'].'mod_wiki/views/wiki_history.html');
