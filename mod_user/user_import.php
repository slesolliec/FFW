<?php

exit;

$users = file($conf['root'].'cnir.txt');

foreach ($users as $line) {
//	echo "<hr />$line";
	$line = trim($line);
	$user = array();
	list($user['nom'],$user['prenom'],$user['region'],$user['fonction'],$user['email'])
		= explode(' ',$line,5);

	$user['nom']	= str_replace('_',' ',$user['nom']);
	$user['prenom']	= str_replace('_',' ',$user['prenom']);
	
	$user['is_active'] = 1;
	$user['is_admin'] = 0;

	switch ($user['fonction']) {
		case 'Cnir_régional':
			$user['is_cnir_titulaire'] = 1;
			$user['is_cnir_suppleant'] = 0;
			$user['is_cnir_national'] = 0;
			$user['is_cnir_regional'] = 1;
		break;
		case 'Cnir_régional,_suppléant(e)':
			$user['is_cnir_titulaire'] = 0;
			$user['is_cnir_suppleant'] = 1;
			$user['is_cnir_national'] = 0;
			$user['is_cnir_regional'] = 1;
		break;
		case 'Cnir_national':
			$user['is_cnir_titulaire'] = 1;
			$user['is_cnir_suppleant'] = 0;
			$user['is_cnir_national'] = 1;
			$user['is_cnir_regional'] = 0;
		break;
		case 'Cnir_nationalional,_suppléant(e)':
			$user['is_cnir_titulaire'] = 0;
			$user['is_cnir_suppleant'] = 1;
			$user['is_cnir_national'] = 1;
			$user['is_cnir_regional'] = 0;
		break;
	}
	
	echo "<hr />nom: {$user['nom']}<br />prenom: {$user['prenom']}<br />region: {$user['region']}<br />
		fonction: {$user['fonction']}<br />
		email: {$user['email']}<br />";

	$db->debug = 1;
	$db->autoexecute("users",$user,'INSERT');

	unset($user);
}