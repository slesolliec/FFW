<?php

function get_month($num) {
	$mois = array("","Janvier","Février",'Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');
	return $mois[intval($num)];
}
$sql = "
	select count(*) as nb,substring(post_day,1,7) as month
	from posts
	where is_draft = 0
		and blog_id={$blog['id']}
--		and is_dev <= {$user['is_dev']}
	group by month
	order by month desc
	";
$months = $db->getall($sql);
if ($months) {
	$smarty->assign('months',$months);
	$box = $smarty->fetch($conf['ffw']."mod_blog/views/widget_months.html");
	$page['column'] .= $box;
}
