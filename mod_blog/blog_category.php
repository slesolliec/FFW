<?php

$category = urldecode($params);

// we show the posts in the category
$sql = "
	select posts.*,{$blog['author_name_column']} as author_name
	from posts,$userTable
	where posts.is_draft = 0
		and posts.blog_id = {$blog['id']}
		and posts.category = \"$category\"
		and posts.user_id = $userTable.id
	order by posts.post_day desc,posts.post_hour desc
	";

$posts = $db->getall($sql);

$smarty->assign('posts',$posts);

$page['content']	= $smarty->fetch($conf['ffw']."mod_blog/views/blog_default.html");
