<?php

$sql = "
	select count(*) as nb,category
	from posts
	where is_draft = 0
		and blog_id={$blog['id']}
--		and is_dev <= {$user['is_dev']}
	group by category
	order by category
	";
$categories = $db->getall($sql);
if ($categories) {
	$smarty->assign('categories',$categories);
	$box = $smarty->fetch($conf['ffw']."mod_blog/views/widget_category.html");
	$page['column'] .= $box;
}
