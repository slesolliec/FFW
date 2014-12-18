<?php

// this function returns the HTML with the comment list and the comment form
// given : $module (ie: myblog) and $oo_id (ie: 142 = id of post in blog)
function get_comments($module,$oo_id) {
	global $conf,$db,$smarty,$userTable,$page;
	
	// comments
	$sql = "select comments.*,{$conf['author_name_column']} as author_name
		from comments,$userTable
		where comments.oo_id = $oo_id
			and comments.module = '$module'
			and comments.user_id = $userTable.id
		order by comments.created_at asc
	";
	// $db->debug = 1;
	$comments = $db->getall($sql);

	if ($comments)
		$smarty->assign('comments',$comments);
	
	$page['comments'] = $smarty->fetch($conf['ffw']."mod_comment/views/comment_view.html");
	$page['nb_comments'] = count($comments);
}

