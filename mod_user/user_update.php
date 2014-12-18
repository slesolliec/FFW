<?php



/*

foreach ($_POST as $key => $val)
	if (strpos(' '.$val,'<'))
		stop('Le caractère &lt; est interdit !!!');

// you cannot change the login
// you cannot change the email

// change pwd
if ($_POST['pwd_new']) $db->execute('update users set pwd="'.md5($_POST['pwd_new']).'" where id='.$user['id']);
// change url
if ($_POST['url'] != $user['url']) $db->execute('update users set url="'.$_POST['url'].'" where id='.$user['id']);
// change notified
if ($_POST['is_notified'] != $user['is_notified']) $db->execute('update users set is_notified="'.$_POST['is_notified'].'" where id='.$user['id']);
// change mood
$smileys = array_flip($smileys);
if ($_POST['mood'] != $user['mood']) {
	$_POST['mood'] = intval($smileys[$_POST['mood']]);
	$db->execute('update users set mood='.$_POST['mood'].' where id='.$user['id']);
}

// handle photo upload
include($conf['ffw'].'inc/icon_upload.php');
icon_upload();
*/

header("Location: {$conf['url']}user/edit/?".date('U'));
exit;

// $action = 'edit';
