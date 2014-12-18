<?php

list($action,$params) = explode('/',$params,2);

if (!$action) {
	header("Location: {$blog['url']}comment/list");
	exit;
}

if (!$user['is_admin']) {
	if (!in_array($action,array('insert','delete'))) {
		$page['title'] = "Interdit";
		stop("Vous n'avez pas le droit d'effectuer cette action.");
	}
}

$module = 'comment';
$page['module'] = 'comment';
$mod = Spyc::YAMLLoad($conf['ffw']."mod_crud/crud.yml");
$mod = recursive_replace('(module)',$module,$mod);
// we local configs (local to the module)
$mod2 = Spyc::YAMLLoad($conf['mods']."mod_blog/comment.yml");
$mod2 = recursive_replace('(module)',$module,$mod2);
$mod = my_array_merge($mod,$mod2);
unset($mod2);
$smarty->assign('mod',$mod);

include($conf['ffw']."mod_crud/crud.php");


if ($action=='insert') {
	// we update the number of comments
	$post_id	= intval($_POST['post_id']);
	$nb_coms	= intval($db->getone("select count(*) from comments where blog_id={$blog['id']} and post_id=$post_id"));
	$db->execute("update posts set nb_comments=$nb_coms where blog_id={$blog['id']} and id=$post_id");

	// notify comment by mail
	$post = $db->getrow("select * from posts where blog_id={$blog['id']} and id=$post_id");
	
	if ($post['user_id']!= $user['id']) {
	    // HTML body
		$oo['author_name']	= $db->getone("select {$blog['author_name_column']} as author_name from $userTable where id=".$post['user_id']);
		$email = $db->getone("select email from $userTable where id=".$post['user_id']);

		$smarty->assign('post',$post);
		$smarty->assign('c',$oo);
		$page['title']		= "Nouveau commentaire";
		$page['content']	= $smarty->fetch("file: {$conf['mods']}mod_blog/views/mail_comment.html");
		$body = $smarty->fetch("file: {$conf['ffw']}mod_www/views/mail.html");

	    // Plain text body (for mail clients that cannot read HTML)
		$body_txt = "Un commentaire vient d'être ajouté à votre article :\n"
			."  {$post['title']}\n"
			."  {$blog['url']}post/$post_id\n\n"
			.$oo['comment']."\n"
			."par {$user['author_name']} le {$oo['created_at']}\n"
			."{$conf['url']}post/$post_id#comment_$comment_id\n";

		send_mail($email,"[{$conf['flag']}] nouveau commentaire par {$oo['author_name']}",$body,$body_txt);
	}
	
	// redirect to post
	header("Location: {$blog['url']}post/{$_POST['post_id']}?".date('U'));
	exit;
}

if ($action == 'delete') {
	list($post_id,$comment_id) = explode('/',$params,2);
	// check if the post is writable
	if (!$user['is_admin']) {
		$user_id = $db->getone("select user_id from posts where id=$post_id");
		if ($user_id != $user['id']) {
			$page['title'] = "Interdit";
			stop("Vous n'avez pas le droit de supprimer des commentaires sur cet article.");
		}
	}
	// we delete the comment
	$db->execute("delete from comments where blog_id={$blog['id']} and id=$comment_id");
	// we update the numer of comments
	$nb_coms	= intval($db->getone("select count(*) from comments where blog_id={$blog['id']} and post_id=$post_id"));
	$db->execute("update posts set nb_comments=$nb_coms where blog_id={$blog['id']} and id=$post_id");
	// redirect to post
	header("Location: {$blog['url']}post/$post_id?".date('U'));
	exit;
}


// for widgets we need to set $module back to 'blog':
$module = 'blog';
$page['module'] = 'blog';
