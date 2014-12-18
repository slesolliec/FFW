<?php

list($y,$m,$d) = explode('/',$params,3);
$date = $y;

if ($m) $date .= '-'.$m;
if ($d) $date .= '-'.$d;

// we show the posts
$sql = "
	select pages.*,{$conf['author_name_column']} as author_name
	from pages,$userTable
	where pages.page_date like '$date%'
		and pages.user_id = $userTable.id
	order by pages.page_date desc,pages.page_time desc
	";

$pages = $db->getall($sql);

// go straigth to the page if there is only one
// we don't do it for month or years's pages, because of next/prev links in the calendar widget
if (($d>0) and (count($pages)==1)) {
	header("Location: {$wiki['url']}{$pages[0]['name']}");
	exit;
}

$smarty->assign('pages',$pages);

if ($d) {
	$page['title']	= "Archives du $d/$m/$y";
} elseif ($m) {
	$page['title']	= "Archives du mois de $m/$y";
} else {
	$page['title']	= "Archives de l'année $y";
}


$page['content']	= $smarty->fetch($conf['ffw']."mod_wiki/views/wiki_archive.html");

list($page['cal_year'],$page['cal_month'],$page['cal_day']) = explode('-',$date,3);
