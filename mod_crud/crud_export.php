<?php



// pour cette action, on passe le query_string et on ajout les champs à exporter
if ($action == 'export') {
	$page['title'] = "Export CSV 1/2 : choix des champs";
	
	if ($_GET)		$smarty->assign('get',$_GET);

	// liste de tous les champs
	$fields = $dbs[$mod['table']];
	
	// on supprime des champs de la liste s'ils sont dans fields_deny
	if ($mod['actions']['export']['field_deny'])
		foreach ($mod['actions']['export']['field_deny'] as $denied_field)
			unset($fields[$denied_field]);
	$smarty->assign('fields',$fields);
	
	if (file_exists($conf['mods']."mod_$module/views/{$module}_$action.html")) {
		$page['content'] = $smarty->fetch("file:{$conf['mods']}mod_$module/views/{$module}_$action.html");
	} else {
		$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_export.html");
	}

	manage_action_links('export');
}

// on envoie les infos au format CSV	
if ($action == 'export_csv') {

	if (!$where) {
		$where = '';
		foreach ($dbs[$mod['table']] as $field_name => $field) {
			if ($_GET[$field_name] or ($_GET[$field_name] === '0')) {
				if (substr($field_name,-3) == '_id') {
					// foreign key
					$where .= "\n and $field_name = '".intval($_GET[$field_name])."' ";
				} else if (substr($field_name,0,3) == 'is_') {
					$where .= "\n and $field_name = \"{$_GET[$field_name]}\" ";
				} else {
					if ($field['type'] == 'enum') {
						$where .= "\n and $field_name = \"{$_GET[$field_name]}\" ";
					} else {
						$where .= "\n and $field_name like \"%{$_GET[$field_name]}%\" ";
					}
				}
			}
		}
		if ($where) $where = "where ".substr($where,6);
	}
	
	if (!is_array($_GET['field_to_export']))
		stop("Vous n'avez pas sélectionné de champs à exporter !!!","Erreur: aucun champ à exporter !!!");

	// on va chercher toutes les lignes nécessaires
	$sql = " -- - crud/export_csv get lines of table {$mod['table']}
		select ".implode(',',$_GET['field_to_export']);
//	if ($mod['actions']['list']['subquery']) $sql .= ",\n	".$mod['actions']['list']['subquery'];
	$sql .= "
		from {$mod['table']}
		$where
--		order by {$mod['actions']['list']['order_by']}";
	// we get the objects
	$oos = $db->getall($sql);
	
	// echo $db->displayQueries();exit;
	
	// we apply filters
	if (is_array($mod['actions']['export_csv']['fields']))
		foreach ($mod['actions']['export_csv']['fields'] as $key => $val)
			if ($val['filter'])
				foreach ($oos as &$oo)
					$oo[$key] = eval($val['filter']); 
	
	if (!$oos) stop("Aucun objet trouvé à exporter.");
	
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"export_{$mod['table']}_".date('Ymd_Hi').".csv\";");
	
	echo implode(';',array_keys($oos[0]));
	
	foreach ($oos as $oo) {
		// on remplace les ; par des ,
		foreach ($oo as $key => $val)
			$oo[$key] = str_replace(";",",",$val);
	
		echo "\n". implode(';',$oo);
	}
	
	exit;
}

