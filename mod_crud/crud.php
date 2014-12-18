<?php

// echo $module.'/'.$action.'<hr />';


function deal_with_fields(&$oo) {
	global $mod,$dbs;
	
	// we loop on all fields
	foreach ($oo as $field => $value) {

		// search for type of field
		$type = $mod['actions']['update']['fields'][$field]['type'];
		if (!$type)
			$type = $dbs[$mod['table']][$field]['type'];
		if (strpos($type,'|')) {
			list($type,$len) = explode('|',$type,2);
		}
		
		// deal with each field
		switch ($type) {
			case 'date':
				$oo[$field] = inverser_date($value);
			break;
			case 'datetime' :
				$oo[$field] = inverser_date($value).' '.$oo[$field.'_time'];
			break;
			case 'pwd':
				if ($value!='') {
					$oo[$field] = pwd_hash($value);
				} else {
					unset($oo[$field]);
				}
			break;
		}
		
		// deal with boolean checkboxes (unchecked boxes are NOT sent by browser, so we need to uncheck them manualy)
		if (is_array($mod['actions']['update']['fields'])) {
			// fields have been defined namely as bool
			foreach ($mod['actions']['update']['fields'] as $field => $arr)
				if ($arr['type']=='bool')
					$oo[$field] = intval($oo[$field]);
		} else {
			// we take ALL 'is_something' fields of table
			foreach ($dbs[$mod['table']] as $field_name => $afield)
				if (substr($field_name,0,3)=='is_')
					if (in_array($field_name,$_POST))	// only fields that are in the POST array
						$oo[$field_name] = intval($oo[$field_name]);
		}
	}
	
	$oo['updated_at'] = date("Y-m-d H:i:s");
}


// here we manage the action links
// the action links of an action overdrive the action links of a module
function manage_action_links($action) {
	global $conf,$mod,$page,$smarty;
	
	if ($mod['actions'][$action]['action_links']) {
		if ($mod['actions'][$action]['action_links'] == 'none') {
			unset($page['action_links']);
		} else {
			$page['action_links'] = $mod['actions'][$action]['action_links'];
		}
	} else if ($mod['action_links']) {
		$page['action_links'] = $mod['action_links'];
	} else {
		return;
	}
	
	$links = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_action_links.html");
	$page['content'] = $links . $page['content'] . $links;
}



if (!$mod) {
	$mod = load_mod($module,$action);
//	echo '<pre>';var_dump($mod['actions']['edit']);exit;
	$smarty->assign('mod',$mod);
}


// check if we have module functions
if (file_exists($conf['mods']."mod_$module/{$module}_functions.php"))
	include_once($conf['mods']."mod_$module/{$module}_functions.php");


// check access on module
if ($mod['access']) {
	foreach ($mod['access'] as $access_rule => $access)
		if (!eval($access['condition']))
			stop($access['message'],"Accès refusé");
}
// check access on action
if ($mod['actions'][$action]['access']) {
	foreach ($mod['actions'][$action]['access'] as $access_rule => $access)
		if (!eval($access['condition']))
			stop($access['message'],"Accès refusé");
}






// default action
if ($action == 'default') {
	$nb_obj = $db->getone("select count(*) from ".$mod['table']);
	if ($nb_obj>0) {
		header("Location: {$conf['url']}$module/list");
	} else {
		header("Location: {$conf['url']}$module/create");
	}
	exit;
}



if ($action == 'create') {
	if (!$page['title'])
		$page['title'] = "Ajouter ". $mod['object_det'].$mod['object'];
	
	// pseudo fields
	if (is_array($mod['actions']['create']['fields']))
		foreach ($mod['actions']['create']['fields'] as $key => $val)
			if ($val['filter'])
				$oo[$key] = eval($val['filter']);
			
	$form = my_array_merge(array('fields'=>$mod['fields']),$mod['actions']['create']);

	$smarty->assign('form',$form);

	if (file_exists($conf['mods']."mod_$module/views/{$module}_$action.html")) {
		$page['content'] = $smarty->fetch("file:{$conf['mods']}mod_$module/views/{$module}_$action.html");
	} else {
		$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_form.html");
	}

	manage_action_links('create');
}



if ($action == 'insert') {
	$oo = clean_magic_quotes($_POST);
	
	deal_with_fields($oo);
	
	$oo['created_at']	= date('Y-m-d H:i:s');

	// pseudo fields
	if (is_array($mod['actions']['insert']['fields']))
		foreach ($mod['actions']['insert']['fields'] as $key => $val)
			if ($val['filter']) {
				// echo "<hr />\$oo[$key] = eval({$val['filter']}) = ".eval($val['filter'])."<hr />";
				$oo[$key] = eval($val['filter']);
			}
	
	$db->AutoExecute($mod['table'],$oo,'INSERT');
	$oo['id'] = $db->Insert_ID();
	
	if ($mod['actions']['insert']['hook_after_query']) 
		eval($mod['actions']['insert']['hook_after_query']);

	$page['title'] = $mod['object']." créé".($mod['object_genre']=='f'?'e':'');
	if ($mod['actions']['insert']['redirect']) {
		header("Location: ".eval($mod['actions']['insert']['redirect']));
		exit;
	}
	$page['content'] = "
		<p>{$mod['object_det']}{$mod['object']} a été créé".($mod['object_genre']=='f'?'e':'').".</p>
		<p><a href='$module/view/{$oo['id']}'>Voir</a></p>
		<p><a href='$module/edit/{$oo['id']}'>Modifier</a></p>
	";
	manage_action_links('insert');
}




if ($action == 'list')
	include($conf['ffw'].'mod_crud/crud_list.php');



if ($action == 'edit') {
	
	if (!$oo)
		$oo = $db->getrow(" -- - crud/edit search {$mod['object_det']}{$mod['object']} with id ".intval($params)."
			select * from {$mod['table']} where id=".intval($params));
	
	if (!$oo) {
		$page['title'] = "$object_name non trouvé(e)";
		stop("Aucun(e) $object_name trouvé(e).");
	}

	$page['title'] = "Editer ".$mod['object_det'].$mod['object'].' : '.$oo[$mod['actions']['edit']['title']];

	// pseudo fields
	if (is_array($mod['actions']['edit']['fields']))
		foreach ($mod['actions']['edit']['fields'] as $key => $val) {
			if ($val['function'])
				$oo[$key] = $val['function']($oo);
			if ($val['filter'])
				$oo[$key] = eval($val['filter']);
		}

	$form = my_array_merge(array('fields'=>$mod['fields']),$mod['actions']['edit']);
		
	$smarty->assign('oo',$oo);
	$smarty->assign('form',$form);

	if ($mod['actions']['edit']['template']) {
		$page['content'] = $smarty->fetch("file:{$conf['mods']}{$mod['actions']['edit']['template']}");
	} else if (file_exists($conf['mods']."mod_$module/views/{$module}_$action.html")) {
		$page['content'] = $smarty->fetch("file:{$conf['mods']}mod_$module/views/{$module}_$action.html");
	} else {
		$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_form.html");
	}

	manage_action_links('edit');
}


if ($action == 'update') {
	$oo = clean_magic_quotes($_POST);

	$oo['updated_at']	= date('Y-m-d H:i:s');

	deal_with_fields($oo);

	// pseudo fields
	if (is_array($mod['actions']['update']['fields']))
		foreach ($mod['actions']['update']['fields'] as $key => $val)
			if ($val['filter'])
				$oo[$key] = eval($val['filter']);

	$db->AutoExecute($mod['table'],$oo,'UPDATE',"id=".$oo['id']);

	if ($mod['actions']['update']['hook_after_query'])
		eval($mod['actions']['update']['hook_after_query']);

	if ($mod['actions']['update']['redirect']) {
		header("Location: ".eval($mod['actions']['update']['redirect']));
	} else {
		header("Location: {$conf['url']}$module/view/{$oo['id']}?".date('U'));
	}
	exit;
}


if ($action == 'view') {
	// we look for the object we want to view
	// echo "<br />oo = db->GetRow('select * from {$mod['table']} where id='.intval($params))";
	if (!$oo)
		$oo = $db->GetRow(" -- - crud/view : we get the object ".intval($params)."
			select * from {$mod['table']} where id=".intval($params));

	if (!$oo) {
		$page['title'] = $mod['object']." non trouvé(e)";
		stop("Aucun".($mod['object_genre']=='f'?'e':'')." {$mod['object']} trouvé".($mod['object_genre']=='f'?'e':'').".");
	}

	if (file_exists($conf['mods']."mod_$module/{$module}_$action.php")) {
		include($conf['mods']."mod_$module/{$module}_$action.php");
	}

	// pseudo fields
	if (is_array($mod['actions']['view']['fields']))
		foreach ($mod['actions']['view']['fields'] as $key => $val)
			if ($val['filter'])
				$oo[$key] = eval($val['filter']);
	
	$form = my_array_merge(array('fields'=>$mod['fields']),$mod['actions']['view']);

	$page['title'] = $mod['object'].' '.$oo[$mod['actions']['view']['title']];
	$page['title'] = $oo[$mod['actions']['view']['title']];
	
	$smarty->assign('view',$form);
	$smarty->assign('oo',$oo);

	if (file_exists($conf['mods']."mod_$module/views/{$module}_$action.html")) {
		$page['content'] = $smarty->fetch("file:{$conf['mods']}mod_$module/views/{$module}_$action.html");
	} else {
		$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_view.html");
	}

	manage_action_links('view');
}


if ($action == 'delete') {
	// for this kind of action, we normaly should always use POST
	if ($_POST['id']) {
		$id	= intval($_POST['id']);
		$xss_armor = $_POST['xss_armor'];
	} else {
		list($id,$xss_armor) = explode("/",$params);
		$id = intval($id);
	}
	
	if (!$user['is_admin']) stop("Vous devez être administrateur pour supprimer cet élément.");
	
	if (xss_armor($id) != $xss_armor) {
		$page['title'] = "Mauvais code de confirmation";
		stop("XSS armor hit!");
	}
	
	$db->Execute("delete from {$mod['table']} where id=".$id);

	if ($mod['actions']['delete']['hook_after_query'])
		eval($mod['actions']['delete']['hook_after_query']);

	if ($mod['actions']['delete']['redirect']) {
		header("Location: ".eval($mod['actions']['delete']['redirect']));
	} else {
		header("Location: {$conf['url']}$module/list?".date('U'));
	}
	exit;
}


if (in_array($action,array('import','import_csv','import_csv_2')))
	include($conf['ffw'].'mod_crud/crud_import.php');


if (in_array($action,array('export','export_csv')))
	include($conf['ffw'].'mod_crud/crud_export.php');



	
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

