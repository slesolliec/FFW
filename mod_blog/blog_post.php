<?php

list($action,$params) = explode('/',$params,2);

if (!$action) {
	header("Location: {$blog['url']}post/list");
	exit;
}

// I don't like the /post/view/15  I prefer to have /post/15 as the viewing address
if ($action=='view') {
	header("Location: {$blog['url']}post/$params");
	exit;
}

// VIEW one post
if (intval($action)) {
	$params = $action;
	$action = 'view2';

	// we look for the object we want to view
	$oo = $db->GetRow("select * from posts where blog_id={$blog['id']} and id=".intval($params));

	if (!$oo) {
		$page['title'] = "Article non trouvé";
		stop("Aucun article trouvé ayant pour ID <strong>$params</strong>");
	}
	
	// we look for the name of author
	$oo['author_name'] = $db->getone("select {$blog['author_name_column']} as author_name from $userTable where $idUField='{$oo['user_id']}' ");
	
	$smarty->assign_by_ref('oo',$oo);

	$page['title'] = $oo['title'];

	// comments
	$page['url'] = $blog['url'].'post/'.$oo['id'];
	include_once($conf['ffw']."mod_comment/inc_comment.php");
	get_comments($module,$oo['id']);
	
	// check we have the good number of comments
	if ($oo['nb_comments']!=$page['nb_comments']) {
		$db->execute("update posts set nb_comments={$page['nb_comments']} where blog_id={$blog['id']} and id={$oo['id']}");
		$oo['nb_comments'] = $page['nb_comments'];
	}

	// for widgets we need to set $module back to 'blog':
	$module = 'blog';
	$page['module'] = 'blog';

	$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_blog/views/blog_post_view.html");
}


// we load the module array
$module = 'post';
$page['module'] = 'post';
$mod = Spyc::YAMLLoad($conf['ffw']."mod_crud/crud.yml");
$mod = recursive_replace('(module)',$module,$mod);
// we local configs (local to the module)
$mod2 = Spyc::YAMLLoad($conf['ffw']."mod_blog/post.yml");
$mod2 = recursive_replace('(module)',$module,$mod2);
// take care of special actions in CREATE and EDIT
$mod2['actions']['create']['action'] = $blog['url'].$mod2['actions']['create']['action'];
$mod2['actions']['edit']['action'] = $blog['url'].$mod2['actions']['edit']['action'];
$mod = my_array_merge($mod,$mod2);
unset($mod2);
$smarty->assign('mod',$mod);

// echo '<pre>';var_dump($mod['actions']);exit;


if (($action=='update') or ($action=='insert')) {
	$_POST['blog_id'] = $blog['id'];
	$_POST['user_id'] = $user['id'];
	$_POST['post_hour'] = input_to_time($_POST['post_day_time']);
	if (!intval($_POST['post_day'])) $_POST['post_day'] = date('d/m/Y');
}

// we pass vars in $_SESSION for CKFinder to work ok
if (($action == 'edit') or ($action == 'create')) {
	session_start();
	$_SESSION['CKFinder_baseUrl']	= $blog['img_baseUrl'];
	$_SESSION['CKFinder_baseDir']	= $blog['img_baseDir'];
	$_SESSION['CKFinder_UserRole']	= 'none';
	if ($user['is_writer'])	$_SESSION['CKFinder_UserRole'] = 'is_writer';
	if ($user['is_admin'])	$_SESSION['CKFinder_UserRole'] = 'is_admin';
}

include($conf['ffw']."mod_crud/crud.php");


if ($action == 'view2') {
	if ($oo['is_draft']) {
		if ($oo['user_id']==$user['id']) {
			$page['title']		.= " - Brouillon";
			$page['content'] = "<p style='background:red;color:white;font-weight:bold;padding:4px;margin:10px;text-align:center;'>Cet article est actuellement en cours de rédaction.</p>" . $page['content'];
		} else {
			$page['title']		= "Article en cours de rédaction";
			$page['content']	= "<p>Cet article est actuellement en cours de rédaction.</p>";
		}
	}
}

// for the widgets we set $module back to 'blog':
$module = 'blog';
$page['module'] = 'blog';
