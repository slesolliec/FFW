<?php

$sql = "
	select page_date,title,name
	from pages
		where page_date >= '".date('Y-m-d')."'
	order by page_date,page_time
	limit 5
";
$pages = $db->getall($sql);

if ($pages) {
	$smarty->assign('pages',$pages);
	$box = $smarty->fetch($conf['ffw']."mod_wiki/views/widget_upcoming.html");

	if ($mod['widgets_position'] == 'before')
		$page['column'] = $box . $page['column'];
	else
		$page['column'] .= $box;

}
