<?php

$smarty->assign('blog',$blog);

// if the column we use to get the name of the author (of a post or a comment) is not specified
// we use the first varchar column of the user table
if (!$blog['author_name_column'])
	foreach ($dbs[$userTable] as $fieldname => $field)
		if ($field->type=='varchar')
			if (!$blog['author_name_column'])
				$blog['author_name_column'] = $userTable.'.'.$fieldname;


if (file_exists($conf['mods']."mod_blog/blog_$action.php")) {
	include($conf['mods']."mod_blog/blog_$action.php");
} else if (file_exists($conf['ffw']."mod_blog/blog_$action.php")) {
	include($conf['ffw']."mod_blog/blog_$action.php");
} else {
	$page['title'] = 'Action non trouvée.';
	stop("Action non trouvée : $action");
}

// echo $module.'/'.$action.'/'.$params;

$module = 'blog';

if ($user['is_author'])
	$conf['menu_user']['Nouvel Article'] = $blog['url']."post/create";

// couper une chaîne
function truncate_content() {
	global $oo;
	$str = $oo['content'];
	$str = substr(trim(strip_tags($str)),0,150);
	if (strlen($str)<150) {
		return $str;
	}
	// echo $str;exit;
	$last = strrchr($str,' ');
	$str = substr($str,0,-strlen($last));
	$str .= '...';
	return $str;
}

// widget -> calendrier
include($conf['ffw']."mod_blog/widget_calendar.php");

// widget -> monthly archive links
include($conf['ffw']."mod_blog/widget_monthly_archive.php");

// widget -> categories
include($conf['ffw']."mod_blog/widget_category.php");

