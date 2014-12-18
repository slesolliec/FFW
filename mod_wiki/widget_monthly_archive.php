<?php

function get_month($num) {
	$mois = array("","Janvier","Février",'Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');
	return $mois[intval($num)];
}

$sql = "
	select count(*) as nb,substring(page_date,1,7) as month
	from pages
		where page_date != '0000-00-00'
	group by month
	order by month desc
";
$months = $db->getall($sql);

if ($months) {
	$smarty->assign('months',$months);
	$box = $smarty->fetch($conf['ffw']."mod_wiki/views/widget_months.html");

	if ($mod['widgets_position'] == 'before')
		$page['column'] = $box . $page['column'];
	else
		$page['column'] .= $box;
}
