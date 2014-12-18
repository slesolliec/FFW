<?php

// update tag_index
$db->execute('delete from tag_index where page_id='.$oo['id']);
$taga = tag_split($oo['tags']);
foreach ($taga as $t)
	$db->execute('insert into tag_index (tag_type,tag,page_id) values ("'.$t['type'].'","'.$t['tag'].'",'.$oo['id'].')');

// "ping" metawiki
// if ($conf['ping'])
//	@file('http://www.metawiki.com/wikipass/ping.php?wiki_url='.urlencode($conf['url']).'&page='.urlencode($p).'&title='.urlencode($_POST['title']).'&edit_desc='.urlencode($_POST['edit_desc']));

if ($wiki['has_email_notification']) {
	
	// email notification
	$get_subscribeds = $db->getall("
		select $userTable.id,$userTable.$emailUField as email from users,wiki_subscribers
		where wiki_subscribers.wiki_id={$wiki['id']}
			and $userTable.id = wiki_subscribers.user_id
		");
	if ($get_subscribeds) {
		$msg = "Le Wiki {$wiki['url']} a ete mis a jour.
	
	Page :
	{$wiki['url']}{$oo['name']}
	
	Description de la modification :
	{$oo['edit_desc']}
	
	User :
	code d'acces : {$user['login']}
	machine      : {$user['hostname']}
	adresse ip   : {$user['ip']}
	
	Pour ne plus recevoir ces messages, connectez-vous a ce wiki et rendez-vous sur la page mon profil.
	";
	
		foreach ($get_subscribeds as $a)
			if ($a['id']!=$user['id'])
				@mail($a['email'],'MaJ '.$wiki['url'],$msg);
	
	}

}

// we need the page name for redirection
$oo['name'] = $db->getone("select name from pages where id=".$oo['id']);