<?php

include($conf['ffw'].'mod_crud/crud.php');

// post-traitement

// we delete all possible connexion cookies if the user has been unactivated
if ($action == 'update')
	if (!$oo['is_active'])
		$db->execute("update {$conf['userTable']['userTable']} set {$conf['userTable']['cookieUField']}='' where id=".$oo['id']);