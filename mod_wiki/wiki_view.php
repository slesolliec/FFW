<?php

$page_name = clean_up_page_name($page_name);

// normal page
$get_page = $db->getrow("select * from pages where name = \"$page_name\"");

if (!$get_page) {
	// page does not exist
	$page['title'] = '404 : cette page n\'existe pas.';
	$page['content'] = '<p>La page <strong>'.$page_name.'</strong> n\'existe pas.</p> <p>Voulez-vous <a href="'.$wiki['url'].'create/'.$page_name.'">la créer</a> ?</p>
		<style type="text/css">#text {border:1px solid #c66;background: #fcc;margin:50px 20px;width:490px;padding:10px;}</style>';
	$page['http_code'] = 404;
	$page['name'] = $page_name;

} else {
	foreach ($get_page as $key=>$val)
		$page[$key] = $val;

	// we manage redirects
	if (substr(strtolower($page['title']),0,4) == 'http') {
		header('location: '.$page['title']);
		exit;
	}

	// we get archive
	if ($_GET['updated_at']) {
		$get_archive = $db->getrow("-- - we get archive
			select *
			from pages_archives
			where name = \"$page_name\"
				and updated_at='".$_GET['updated_at']."'" );
		if ($get_archive) {
			foreach ($get_archive as $key=>$val)
				$page[$key] = $val;
			$page['archive'] = 1;
		}
		unset($get_archive);
	}

	unset($get_page);
	$page['tag_array'] = tagsplit($page['tags']);
	
	// we look for the name of author
	$page['author_name'] = $db->getone("-- - get name of author
		select {$wiki['author_name_column']} as author_name
		from $userTable
		where $idUField='{$page['user_id']}' ");

	// comments
	if ($wiki['has_comment']) {
		$page['url'] = $wiki['url'].$page['name'];
		$oo = $page;
		$smarty->assign('oo',$oo);
		include_once($conf['ffw']."mod_comment/inc_comment.php");
		get_comments($module,$page['id']);
		
		// check we have the good number of comments
		if ($oo['nb_comments']!=$page['nb_comments']) {
			$db->execute("-- - store the right number of comments
				update pages
				set nb_comments={$page['nb_comments']}
				where wiki_id={$wiki['id']}
					and id={$oo['id']}");
			$oo['nb_comments'] = $page['nb_comments'];
		}
	}

	$page['content'] = $smarty->fetch($conf['ffw'].'mod_wiki/views/wiki_view.html');
}

