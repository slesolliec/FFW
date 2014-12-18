<?php

$sql = "
	select count(*) as nb,category
	from pages
	where category != ''
	group by category
	order by category
	";
$categories = $db->getall($sql);
if ($categories) {
	$smarty->assign('categories',$categories);
	$box = $smarty->fetch($conf['ffw']."mod_wiki/views/widget_category.html");

	if ($mod['widgets_position'] == 'before')
		$page['column'] = $box . $page['column'];
	else
		$page['column'] .= $box;
}
