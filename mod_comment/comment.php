<?php

// access
if (!$action) {
	header("Location: {$conf['url']}comment/list");
	exit;
}

if ($action == 'create') {
	$page['title'] = "Action non activée pour ce module.";
	stop("Cette action n'est pas active pour ce module.");
}



if (!$user['is_admin']) {
	if (!in_array($action,array('insert'))) {
		$page['title'] = "Interdit";
		stop("Vous n'avez pas le droit d'effectuer cette action.");
	}
}

function comment_get_view_link($oo) {
	global $conf;
//	echo $conf['comment']['view_url'][$oo['module']];exit;
	return eval($conf['comment']['view_url'][$oo['module']]);
}



if ($action == 'delete') $action = 'bypass_crud_delete';

include($conf['ffw']."mod_crud/crud.php");

if ($action == 'bypass_crud_delete') $action = 'delete';

if ($action=='insert') {
	
	// notify comment by mail
	if ($oo['oo_user_id']!= $user['id']) {
	    // HTML body
		$oo['author_name']	= $db->getone("select {$conf['author_name_column']} as author_name from $userTable where id=".$oo['oo_user_id']);
		$email = $db->getone("select email from $userTable where id=".$oo['oo_user_id']);

		$smarty->assign('c',$oo);
		$page['title']		= "Nouveau commentaire";
		$page['content']	= $smarty->fetch("file: {$conf['ffw']}mod_comment/views/mail_comment.html");
		$body = $smarty->fetch("file: {$conf['ffw']}mod_www/views/mail.html");

	    // Plain text body (for mail clients that cannot read HTML)
		$body_txt = "Un commentaire vient d'être ajouté à :\n"
			."  {$oo['oo_title']}\n"
			."  {$oo['oo_url']}\n\n"
			.$oo['comment']."\n"
			."par {$oo['author_name']} le {$oo['created_at']}\n"
			."{$oo['oo_url']}#comment_$comment_id\n";

		send_mail($email,"[{$conf['flag']}] nouveau commentaire par {$oo['author_name']}",$body,$body_txt);
	}
	
	// redirect to post
	header("Location: {$oo['oo_url']}?".date('U'));
	exit;
}

if ($action == 'delete') {

	list($oo_module,$oo_id,$id,$xss_armor) = explode("/",$params,4);
	
	if (xss_armor($id) != $xss_armor) {
		$page['title'] = "Mauvais code de confirmation";
		stop("XSS armor hit!");
	}

	// check if the post is writable  -- WE CANNOT DO IT ANYMORE  :-(
	/*
	if (!$user['is_admin']) {
		$user_id = $db->getone("select user_id from posts where id=$post_id");
		if ($user_id != $user['id']) {
			$page['title'] = "Interdit";
			stop("Vous n'avez pas le droit de supprimer des commentaires sur cet article.");
		}
	}
	*/
	
	// we delete the comment
	$db->execute("
		delete from comments
		where module='{$oo_module}'
			and oo_id={$oo_id}
			and id=$id");
	// redirect to post
	header("Location: {$_GET['oo_url']}?".date('U'));
	exit;
}

