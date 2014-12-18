<?php

if ((!$user['id']) and (!in_array($action,array('create','default','list','view','none','insert'))))
	stop("Il faut être connecté au site pour accéder à cette page.","Accès refusé");

$mod = load_mod($module,$action);
// echo '<pre>';var_dump($mod);exit;
$smarty->assign('mod',$mod);

// check if we have module functions
if (file_exists($conf['mods']."mod_$module/{$module}_functions.php"))
	include_once($conf['mods']."mod_$module/{$module}_functions.php");

if ($action == 'default') {
	header("Location: {$conf['url']}$module/list");
	exit;
}


if (in_array($action, array('insert')))
	include($conf['ffw']."mod_user/user_$action.php");

if ($action == 'create') {
	
	$form = &$mod['actions']['create'];

	if ($form['title'])
		$page['title'] = $form['title'];
	else
		$page['title'] = "Créer un compte";
		
	// on recopie les valeurs de $form['fields']['loginUField'] -> $form['fields'][ $conf['userTable']['loginUField'] ]
	foreach ($conf['userTable'] as $key => $val)
		$form['fields'][$val] = $form['fields'][$key];
	// on traduit les valeurs dans $form['field_order'] :   loginUField --> login
	if ($form['field_order']) {
		foreach ($form['field_order'] as $key => $val) {
			if (array_key_exists($val,$conf['userTable'])) {
				$form['field_order'][$key] = $conf['userTable'][$val];
			}
		}
	}
	// supprime login s'il le faut
	if ($conf['userTable']['loginUField']=='none') {
		$temp = $mod['actions']['create']['field_order'];
		$temp2 = array();
		if (is_array($temp))
			foreach ($temp as $val)
				if ($val != 'none')
					$temp2[] = $val;
		$mod['actions']['create']['field_order'] = $temp2;
	}
	// pseudo fields
	if (is_array($form['fields']))
		foreach ($form['fields'] as $key => $val)
			if ($val['filter'])
				$oo[$key] = eval($val['filter']);

	$smarty->assign('form',$form);	
	$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_form.html");
}


if ($action == 'list') {
	$page['title'] = "Liste des utilisateurs";
	include($conf['ffw'].'mod_crud/crud.php');

/*	$oos = $db->getall("select * from {$conf['userTable']['userTable']} order by {$mod['actions']['list']['order_by']}");

	// we add view and edit links
	foreach ($oos as &$oo) {
		$oo['view_link'] = "<a href='$module/view/{$oo['id']}'><img src='ffw/icons/doc_view.png' alt='voir' width='16' height='16' /></a>";
		$oo['edit_link'] = "<a href='$module/edit/{$oo['id']}'><img src='ffw/icons/doc_edit.png' alt='modifier' width='16' height='16' /></a>";
	}
	
	// pseudo fields
	if (is_array($mod['actions']['list']['fields']))
		foreach ($mod['actions']['list']['fields'] as $key => $val)
			if ($val['function'])
				foreach ($oos as &$oo)
					$oo[$key] = $val['function']($oo);

	$smarty->assign('oos',$oos);
	$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_list.html");
*/

}


if ($action == 'edit') {
	$page['title']   = 'Editer vos infos';
	
	if (!$user[$idUField])	stop("Il faut être connecté pour accéder à cette page.","Déconnecté");
	
	$form = $mod['actions']['edit'];
	$oo = $user;
	$oo[$pwdUField] = '';

	// on recopie les valeurs de $form['fields']['loginUField'] -> $form['fields'][ $conf['userTable']['loginUField'] ]
	foreach ($conf['userTable'] as $key => $val)
		$form['fields'][$val] = $form['fields'][$key];
	// on traduit les valeurs dans $form['field_order'] :   loginUField --> login
	if ($form['field_order']) {
		foreach ($form['field_order'] as $key => $val) {
			if (array_key_exists($val,$conf['userTable'])) {
				$form['field_order'][$key] = $conf['userTable'][$val];
			}
		}
	}

	$smarty->assign('form',$form);	
	$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_form.html");
}


if ($action == 'update') {
	$oo = clean_magic_quotes($_POST);
	$oo[$idUField]	= $user[$idUField];

	// we loop on all fields
	foreach ($oo as $field => $value) {
		$type = $dbs['users'][$field]->type;
		switch ($type) {
			case 'date':
				$oo[$field] = inverser_date($value);
			break;
		}
	}
	
	if ($oo[$pwdUField]!='') $oo[$pwdUField] = pwd_hash($oo[$pwdUField]);
	else unset($oo[$pwdUField]);
	
	// filters
	if (is_array($mod['actions']['update']['fields']))
		foreach ($mod['actions']['update']['fields'] as $key => $val)
			if ($val['filter'])
				$oo[$key] = eval($val['filter']);

	// $personne['is_validated'] 	= intval($personne['is_validated']);
	$oo['updated_at'] 		= date("Y-m-d H:i:s");

	$db->AutoExecute('users',$oo,'UPDATE',"$idUField=".$user[$idUField]);

	
	
	header("Location: {$conf['url']}user/edit/?".date('U'));
	exit;
}



if ($action == 'view') {
	// we look for the user we want to view
	// $oo = $db->GetRow("select * from {$conf['userTable']['userTable']} where id=".$id);

	include($conf['ffw'].'mod_crud/crud.php');
}


// filter
// we read the yaml that shoud describe the filter
if (!$mod['filter']) {
	if (file_exists($conf['mods']."mod_$module/{$module}_filter.yml"))
		$mod['filter'] = Spyc::YAMLLoad($conf['mods']."mod_$module/{$module}_filter.yml");
}

if ($mod['filter']) {
	// we insert in the page
	//	$page['filter_form'] = $smarty->fetch($conf['mods']."mod_$module/views/{$module}_filter.html");
	$page['filter_form'] = $smarty->fetch($conf['ffw']."mod_crud/views/crud_filter.html");
}


