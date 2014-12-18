<?php

list($y,$m,$d) = explode('/',$params,3);
$date = $y;

if ($m) $date .= '-'.$m;
if ($d) $date .= '-'.$d;

// we show the posts
$sql = "
	select posts.*,{$blog['author_name_column']} as author_name
	from posts,$userTable
	where posts.is_draft = 0
		and posts.blog_id = {$blog['id']}
		and posts.post_day like '$date%'
		and posts.user_id = $userTable.id
	order by posts.post_day desc,posts.post_hour desc
	";

$posts = $db->getall($sql);

$smarty->assign('posts',$posts);

$page['content']	= $smarty->fetch($conf['ffw']."mod_blog/views/blog_default.html");

list($page['cal_year'],$page['cal_month'],$page['cal_day']) = explode('-',$date,3);
