<?php


function get_fields_of_table($table) {
	global $db;
	// on va chercher les vrais champs de la table de données
	// souvent, la table est vide avant l'import, donc on ne passe pas par $dbs
	$create = $db->getrow(" -- - create query of table $table
		show create table $table");
	$create_sql = $create['Create Table'];
	$create_array = explode("\n",$create_sql);
	array_shift($create_array);
	array_pop($create_array);
	
	foreach ($create_array as $create_line) {
		$create_line = trim($create_line);
		list($field_name,$field_type,$other_rubish) = explode(' ',$create_line,3);
		$field_name = trim($field_name,'`');
		
		$table_fields[$field_name] = $field_type;
	}
	
	return $table_fields;
}


// on rend les noms de type de champs plus simples pour les moldus
function mysql_types_for_moldus($type) {
	$types_de_champs = array(
		'int(11)'		=> 'entier (<2 147 483 648)',
		'int(10)'		=> 'entier (<2 147 483 648)',
		'mediumint(9)'	=> 'entier moyen (<8 388 608)',
		'mediumint(8)'	=> 'entier moyen (<8 388 608)',
		'smallint(7)'	=> 'entier petit (<32 768)',
		'smallint(6)'	=> 'entier petit (<32 768)',
		'tinyint(4)'	=> 'entier minus (<128)',
		'tinyint(3)'	=> 'entier minus (<128)',
		'datetime'		=> 'date heure',
	);
	
	if (array_key_exists($type,$types_de_champs))
		$type = $types_de_champs[$type];
	else
		$type = str_replace('varchar','chaîne ',$type);

	return $type;
}


if ($action == 'import') {
	$page['title'] = "Import de fichier CSV - 1/3";
	
	if ($mod['actions']['import']['title'])
		$page['title'] = $mod['actions']['import']['title'];

	$smarty->assign('form',$mod['actions']['import']);

	if (file_exists($conf['mods']."mod_$module/views/{$module}_$action.html")) {
		$page['content'] = $smarty->fetch("file:{$conf['mods']}mod_$module/views/{$module}_$action.html");
	} else {
		$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_import.html");
	}
	
	manage_action_links('import');
}


if ($action == 'import_csv') {
	$page['title'] = "Import de fichier CSV - 2/3";

	if (!$_FILES['csv_file']) stop("Aucun fichier n'a été envoyé.");
	
	$uploaded = $_FILES['csv_file'];
	$file_array = file($uploaded['tmp_name']);
	
	// on copie le fichier dans un endroit fixe
	rename($uploaded['tmp_name'],$conf['views_c'].'/my_upload.csv');

	//	echo "<pre>";//	print_r($uploaded);

	// on recupere la première ligne
	$first_line = $file_array[0];
	
	// on la casse en champs
	$raw_fields = explode(';',$first_line);
	$raw_fields = array_map('trim',$raw_fields);
	
	// on se debrouille pour que les noms de champs ressemblent un peu plus à des noms de champ mysql
	$fields = array();
	foreach ($raw_fields as $raw) {
		$cooked = str_replace("d'",'',$raw);
		$cooked = remove_accents($cooked);
		$cooked = str_replace(' ','_',$cooked);
		$cooked = str_replace('-','',$cooked);
		$cooked = strtolower($cooked);
		$fields[] = array('raw' => $raw, 'cooked' => $cooked);
	}
	$smarty->assign('fields',$fields);
	
	$table_fields = get_fields_of_table($mod['table']);
	
	// on supprime éventuellement certains champs de la table
	if (is_array($mod['actions']['import_csv']['field_deny']))
		foreach ($mod['actions']['import_csv']['field_deny'] as $denied)
			if (array_key_exists($denied,$table_fields))
				unset($table_fields[$denied]);
	
	// et là on affiche tous les champs avec la possible correspondance aux champs de la table courrante
	$smarty->assign('table_fields',$table_fields);
	$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_import2.html");
	
	manage_action_links('import');
/*	
	
	foreach ($file_array as $line) {
		$line = trim($line);
		echo "<br/>$line";
	}
	
//	$fh = fopen();
	*/
	
}


if ($action == 'import_csv_2') {
	$page['title'] = "Import de fichier CSV - 3/3";

	$data_array = file($conf['views_c'].'/my_upload.csv');
	
	// we get rid of the first line
	if ($_POST['import_first_line'] == 1)
		$first_line = $data_array[0];
	else
		$first_line = array_shift($data_array);
	$first_fields = explode(';',trim($first_line));
	$first_fields = array_map('trim',$first_fields);
	$nb_fields = count($first_fields);
	
	// we get back the translation  imported column --> table field
	$field_assoc = $_POST['field'];
	//	echo "<pre>";print_r($field_assoc);

	$table_fields = get_fields_of_table($mod['table']);
	
	// we crawl all the lines to import
	$j = 0;
	foreach ($data_array as $line) {
		$line = trim($line);
		if ($line != '') {
			$fields = explode(';',$line);
			$fields = array_map('trim',$fields);
			$oo = array();
			
			// we compose the $oo to import
			for ($i = 0; $i < $nb_fields; $i++) {
				if ($field_assoc[$i]) {
					// on fait un traitement éventuel en fonction du type
					switch ($table_fields[$field_assoc[$i]]) {
						case 'date' :
							if (strpos($fields[$i],'/'))
								$fields[$i] = inverser_date($fields[$i]);
						break;
					}
					$oo[$field_assoc[$i]] = $fields[$i];
				}
			}
			
			// traitement des pseudo-fields
			if (is_array($mod['actions']['import_csv_2']['fields']))
				foreach ($mod['actions']['import_csv_2']['fields'] as $key => $val)
					if ($val['filter'])
						$oo[$key] = eval($val['filter']);
			
			if (!isset($keys)) {
				$keys = array_keys($oo);
				
				$sql = " -- - Insert into {$mod['table']}
					insert into {$mod['table']}
					(".implode(', ',$keys).")
					values
					(:".implode(':, :',$keys).":)";
			}
			$db->execute($sql,$oo);
	
			unset($oo);
			// echo "<br/>$line";
			$j++;
			
		}
	}
	
	$page['content'] = "<p>Bravo, vous avez importé $j fiches</p>";
	
	unlink($conf['views_c'].'/my_upload.csv');

	manage_action_links('import');
}


