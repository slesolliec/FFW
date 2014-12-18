<?php

$category = urldecode($params);

// we show the posts in the category
$sql = "
	select pages.*,{$conf['author_name_column']} as author_name
	from pages,$userTable
	where pages.category = \"$category\"
		and pages.user_id = $userTable.id
	order by pages.page_date desc,pages.page_time desc
	";

$pages = $db->getall($sql);

$smarty->assign('pages',$pages);

$page['title']		= "Rubrique : $category";
$page['content']	= $smarty->fetch($conf['ffw']."mod_wiki/views/wiki_category.html");
