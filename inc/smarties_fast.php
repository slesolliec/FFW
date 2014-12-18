<?php

/* SMARTY helpers

C: character fields that should be shown in a <input type="text"> tag.
X: TeXt, large text fields that should be shown in a <textarea>
B: Blobs, or Binary Large Objects. Typically images.
D: Date field
T: Timestamp field
L: Logical field (boolean or bit-field)
I: Integer field
N: Numeric field. Includes autoincrement, numeric, floating point, real and integer.
R: Serial field. Includes serial, autoincrement integers. This works for selected databases.


*/

function strip_brackets($str) {
	$str = str_replace('[','_',$str);
	$str = str_replace(']','_',$str);
	return $str;
}


function fast_input($value,$name,$type_override = '',$fk_columns = '') {
	// $value			= value of field
	// $name			= 'login' or 'user.login'
	// $type_overrride	= 'C|99'
	// $fk_columns		= 'name' or 'name|surname' or 'regions.name' or 'regions.name|surname' or 'regions.'
	global $db,$dbs,$mod,$user;
	
	if (strpos($name,'.')) {
		list($table,$name) = explode('.',$name,2);
	}
	$column = $name;
	
	if (!$table) $table = $mod['table'];
	
	$type	= $dbs[$table][$column]['type'];
	$len	= $dbs[$table][$column]['length'];

	if ($dbs[$table][$column]['primary_key'] == 1)
		$type = 'PK';
	
	if (substr($column,0,3) == 'is_')	$type = "checkbox";
	if (substr($column,-3) == '_id')	$type = "FK";

	if ($type_override)
		list($type,$len) = explode('|',$type_override,2);
		// Rq : when you use $type_override, $len is used as a place to pass arguments
	
	switch ($type) {
		// Plain (we just display the content)
		case 'plain':
			switch ($len) {
				case 'tick':
					if ($value) $tag = "<img src='ffw/icons/tick.png' alt='-' width='16' height='16'/>";
					else $tag = "<img src='ffw/icons/cross.png' alt='X' width='16' height='16'/>";
				break;
			
				default:
					if (substr($column,0,3) == 'is_') {
						if ($value>0) $tag = 'Oui';
						else $tag = 'Non';
					} else {
						// we check if we have a Foreign Key
						if ((substr($column,-3) == '_id') and (intval($value) > 0)) {
							// $column
							// $fk_columns
							if ($fk_columns and strpos('.',$fk_columns)) {
								list($foreign_table,$fk_columns) = explode('.',$fk_columns,2);
							} else {
								$foreign_table = substr($column,0,-3).'s';
							}
							if (!$fk_columns) {
								// we take first char or varchar field < 100
								$fk_columns = '';
								foreach ($dbs[$foreign_table] as $foreign_field_name => $foreign_field) {
									if ($fk_columns=='') {
										if (($foreign_field['type'] == 'varchar') or ($foreign_field['type'] == 'char')) {
											if ($foreign_field['length'] < 100)
												$fk_columns = $foreign_field_name;
										}
									}
								}
							}
							$fk_cols = explode('|',$fk_columns);
		
							// we get all values from the table
							$sql = "select id,".implode(',',$fk_cols)."
								from $foreign_table
								where id=$value";
							$row = $db->getrow($sql);
							$tag = implode(' ',array_slice($row,1));
						} else {
							// we simply print the value
							$tag = $value;
						}
						
					}
				break;
			}
		break;
		// VarChars
		case 'enum':
			// selector
			// we get all values from $dbs
			$all_values = $dbs[$table][$column]['values'];
			$select_options = array();
		
			foreach ($all_values as $v) {
				$select_options[$v] = $v;
			}

			// we add the select
			$html_id = str_replace('[','_',str_replace(']','_',$options['control_name']));
			$tag = "<select name='$name' id='{$name}_select'>";
			foreach ($select_options as $so) {
				$tag .= "\n<option value=\"$so\"";
				if ($so == $value) $tag .= " selected='selected'";
				$tag .= ">$so</option>";
			}
			$tag .= "\n</select>";
		break;
		// VarChars
		case 'varchar':
		case 'char':
		if (($len == 99) or ($len == 199)){
			// happy selector
			global $defaults;

			// we get all values from the table
			$all_values = $db->getAll("select distinct $column from $table order by $column");
			if (is_array($defaults[$column])) {
				$select_options = $defaults[$column];					
			} else {
				// cas où on a aucune option par defaut
				if (!$all_values) {
					$tag = "<input name='$name' value=\"$value\" />";
					break;
				}
				
				$select_options = array();
			}
		
			if (is_array($all_values))
				foreach ($all_values as $v)
					$select_options[$v[$column]] = $v[$column];

			// we add the "Autre" option
			$select_options['Autre'] = "Autre ...";

			// we add the select
			$html_id = str_replace('[','_',str_replace(']','_',$options['control_name']));
			$tag = "<select name='$name' id='{$name}_select' onchange=\"happy_selector_onchange('$name');\">";
			foreach ($select_options as $so) {
				$tag .= "\n<option value=\"$so\"";
				if ($so == $value) $tag .= " selected='selected'";
				$tag .= ">$so</option>";
			}
			$tag .= "\n</select>";

			// we add the filter box
			if (count($select_options) > 50) {
				$tag = "Filtre : <input name='select_filter_$name' id='select_filter_{$name}_select' size='5' /> &nbsp; \n".$tag;
				$tag .= "<script type='text/javascript'>$('select_filter_{$name}_select').onblur = select_filter;</script>";
			}

			// we add the input
			$tag .= " <input name='{$name}' id='{$name}_input' disabled='true' value=\"$value\" style='display:none;' />";
		
			} elseif (($len == 98) or ($len == 198)) {
				// on cree un selecteur avec toutes les valeurs possibles, ET la première option vide
				// ce type de selecteur est généralement utilisé dans les formulaires de recherche
				global $defaults;

				// we get all values from the table
				$all_values = $db->getAll("select distinct $column from $table order by $column");
				if (is_array($defaults[$column])) {
					$select_options = $defaults[$column];					
				} else {
					$select_options = array();
				}
			
				foreach ($all_values as $v) {
					$select_options[$v[$column]] = $v[$column];
				}

				// we add the select
				$tag = "<select name='$name' id='$name'><option></option>";
				foreach ($select_options as $so) {
					if ($so) {	// pour ne pas avoir une double option vide en debut de menu
						$tag .= "\n<option value=\"$so\"";
						if ($so == $value) $tag .= " selected='selected'";
						$tag .= ">$so</option>";
					}
				}
				$tag .= "\n</select>";
			
			} elseif ($len) {
				if ($len<20) {
					$tag = "<input name='$name' value=\"$value\" maxlength='$len' size='$len' id='input_$name' />";
				} else {
					$tag = "<input name='$name' value=\"$value\" maxlength='$len' id='input_$name' />";
				}
			} else {
				$tag = "<input name='$name' value=\"$value\" id='input_$name' />";
			}
			
		break;
		// Foreign Key
		case 'FK':
		case 'FK_null':	// Foreign Key menu with first option == ''  (good for search forms)
		case 'FK_no_filter':	// Foreign Key menu with no filter input (sometimes you don't want it)
			// $column
			// $fk_columns
			
			if ($fk_columns and strpos($fk_columns,'.')) {
				list($foreign_table,$fk_columns) = explode('.',$fk_columns,2);
			} else {
				$foreign_table = substr($column,0,-3).'s';
			}
			if (!$fk_columns) {
				// we take first char or varchar field < 100
				$fk_columns = '';
				foreach ($dbs[$foreign_table] as $foreign_field_name => $foreign_field) {
					if ($fk_columns=='') {
						if (($foreign_field['type'] == 'varchar') or ($foreign_field['type'] == 'char')) {
							if ($foreign_field['lenght'] < 100)
								$fk_columns = $foreign_field_name;
						}
					}
				}
			}
			$fk_cols = explode('|',$fk_columns);
			
			// we get all values from the table
			$sql = "select id,".implode(',',$fk_cols);
			$sql .= " from $foreign_table";
			$sql .= " order by ".implode(',',$fk_cols);
			$all_values = $db->getAll($sql);
			foreach ($all_values as $v) {
				$select_options[$v['id']] = implode(' ',array_slice($v,1));
			}
			// we add the select
			$tag = "<select name='$name' id='$name'>";
			if ($type == 'FK_null') $tag .= "\n<option value=''></option>";
			foreach ($select_options as $key => $val) {
				$tag .= "\n<option value=\"$key\"";
				if ($key == $value) $tag .= " selected='selected'";
				$tag .= ">$val</option>";
			}
			$tag .= "\n</select>";
			// we add the filter box
			if ($type != 'FK_no_filter') {
				if (sizeof($all_values) > 30) {
					$tag = "Filtre : <input name='select_filter_$name' id='select_filter_$name' size='5' /> &nbsp; \n".$tag;
					$tag .= "<script type='text/javascript'>$('select_filter_$name').onblur = select_filter;</script>";
				}
			}

		break;
		// checkbox
		case 'checkbox':
			$tag = "<input type='checkbox' name='$name' value='1' id='$name'";
			if ($value) $tag .= " checked='checked'";
			$tag .= " />";
		break;
		// password
		case 'pwd':
			$tag = "<input type='password' name='$name' id='$name' />";
		break;
		// hidden or primary key
		case 'PK':
		case 'hidden':
			$tag = "<input type='hidden' name='$name' value='$value' id='$name' />";
		break;
		// Text
		case 'text':
			$tag = "<textarea name='$name' id='$name'";
			if ($len) $tag .= " maxlength='$len'";
			$tag .= ">$value</textarea>";
		break;
		// Rich Text
		case 'text_rich':
		case 'html' :
			$tag = "<script type='text/javascript' src='libjs/FCKeditor/fckeditor.js'></script>
			<script type='text/javascript'>
				var oFCKeditor = new FCKeditor('{$name}_fcked');
				oFCKeditor.BasePath = 'libjs/FCKeditor/';
				oFCKeditor.InstanceName = '$name';
				oFCKeditor.Width = '100%';
				oFCKeditor.Height = '500';
				oFCKeditor.Value = \"".preg_replace("/[\r\n]+/", '" + $0"', addslashes($value))."\"; 
				
				// CKFinder
				oFCKeditor.Config['ImageBrowserURL'] = '/ffw/js/ckfinder/ckfinder.html?type=Images';
				oFCKeditor.Config['ImageUploadURL'] = '/ffw/js/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
				";

			if ($len)
				$tag .= "	oFCKeditor.ToolbarSet = '$len';";
			$tag .= "	oFCKeditor.Create();
			</script>
			<div id='adveditor_$name' style='display:block;'></div>
			";
			
			/* JavaScript configs to add CKFinder to FCKEditor
			FCKConfig.LinkBrowserURL = '/ffw/js/ckfinder/ckfinder.html' ;
			FCKConfig.ImageBrowserURL = '/ffw/js/ckfinder/ckfinder.html?type=Images' ;
			FCKConfig.FlashBrowserURL = '/ffw/js/ckfinder/ckfinder.html?type=Flash' ;
			FCKConfig.LinkUploadURL = '/ffw/js/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files' ;
			FCKConfig.ImageUploadURL = '/ffw/js/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images' ;
			FCKConfig.FlashUploadURL = '/ffw/js/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash' ;
			*/

		break;
		// Int
		case 'int':
			if ($len) {
				$tag = "<input name='$name' value='$value' size='$len' maxlength='$len' id='$name' />";
			} else {
				$tag = "<input name='$name' value='$value' id='$name' />";
			}
		break;
		// Date
		case 'date':
			$value = substr($value,0,10);
			list($y,$m,$d) = explode('-',$value,3);
			$date_value = "$d/$m/$y";
			$date_value = str_replace('//','',$date_value);
			$tag = "<input name='$name' id='$name' value='$date_value' size='10' /><img id='caltrig_$name' src='ffw/icons/calendar.png' width='16' height='16' alt='choisir la date' />";
			$tag .= "<script type='text/javascript'>
			  Calendar.setup(
			    {
			      inputField  : '$name',		// ID of the input field
			      ifFormat    : '%d/%m/%Y',		// the date format
			      button      : 'caltrig_$name'	// ID of the button
			    }
			  );
			</script>";
		break;
		case 'datetime':
			list($date,$time) = explode(' ',$value);
			$date = substr($date,0,10);
			list($y,$m,$d) = explode('-',$date,3);
			$date_value = "$d/$m/$y";
			$date_value = str_replace('//','',$date_value);
			$tag = "<input name='$name' id='$name' value='$date_value' size='10' /><img id='caltrig_$name' src='ffw/icons/calendar.png' width='16' height='16' alt='choisir la date' />";
			$tag .= " à <input name='{$name}_time' id='{$name}_time' size='8' value='$time' />";
			$tag .= "<script type='text/javascript'>
			  Calendar.setup(
			    {
			      inputField  : '$name',		// ID of the input field
			      ifFormat    : '%d/%m/%Y',		// the date format
			      button      : 'caltrig_$name'	// ID of the button
			    }
			  );
			</script>";
		break;
		// File
		// pour ce type d'input :
		//	$len = chemin
		case 'file':
			if (!$len) $len = 'uploads/photo';
			$path = $len;
			$tag = "<input type='file' name='$name' id='$name' />\n";
			if ($value) {
				$ext = trim(strstr($value,'.'),'.');
				if (in_array($ext,array('jpg','gif','png'))) {
					$tag = "<br/><a href='$path/$value'><img src='$path/$value' width='45' height='45' alt='' align='left' style='margin-right:4px;border: 1px solid black;' /></a>\n".$tag;
				}
				$tag .= "<br/><a href='$path/$value'>$value</a>\n";
				$tag .= "<input type='checkbox' name='{$name}_del' value='1' id='{$name}_del' /> <label for='{$name}_del'>Supprimer</label>\n";
			}
			// ça ça doit être dans info: $tag .= "<span class='form_tip'>Choisir une photo Jpeg (100x130 pixels de préférence)</span>\n";
		break;
		// geomarker
		case 'geomarker':
			$tag = "<input type='password' name='$name' id='$name' />";
		break;
		default:
			$tag = "<input name='$name' value=\"$value\" id='$name' />";
		break;
	}
	
	return $tag;
}


// this function simple dresses-up an field input
function fast_field($field,$fieldname='',$type_override='',$fk_columns='',$info='') {
	// $field = nom du champ ecrit table.champ : exemple : personnes.nom
	// $fieldname = nom du champ affiché dans le navigateur
	// $type_override = pour forcer un type. Ex : 'C|99'
	// $fk_columns = name of columns that will appear in the options of the selector
	if (strpos('.',$field)) {
		list($table,$field) = explode('.',$field,2);
	}
	
	global $dbs,$mod,$oo,$action,$conf;
	
	if (!$table) $table = $mod['table'];

	if (!$fieldname) $fieldname = ucfirst(str_replace('_',' ',$field));

	// espace situé avant les :
	$space = ' ';
	if ($conf['lang'] == 'en')
		$space = '';
	
	if (($dbs[$table][$field]['primary_key'] and ($mod['actions'][$action]['fields'][$field]['type']!='plain'))
	 		or ($mod['actions'][$action]['fields'][$field]['type'] == 'hidden')) {
		$tag = fast_input($oo[$field],$field,$type_override,$fk_columns);
	} else {
		if ($mod['actions'][$action]['fields'][$field]['dressing']=='above') {
			// dressing above :     fieldname :
			//                      fieldinput
			$tag = "
			<div class='field'>
				<span class='names'>{$fieldname}{$space}:</span><br />
				".fast_input($oo[$field],$field,$type_override,$fk_columns)."&nbsp;";
			if ($info)
				$tag .= "<br />\n\t\t<small>$info</small>";
			$tag .="\n\t</div>\n";
		} else {
			// dressing by default :     fieldname :    fieldinput
			$tag = "
			<div class='field'>
				<div class='names'>{$fieldname}{$space}:</div>
				<div class='inputs'>".fast_input($oo[$field],$field,$type_override,$fk_columns)."&nbsp;";
			if ($info)
				$tag .= "<br />\n\t\t<small>$info</small>";
			$tag .="\n\t\t</div>\n\t</div>\n";
		}
	}

	return $tag;
}


// $field = 'prenom'
// $view_field = array('name'=>'Prénom', type='blahblah')
function view_field($field_name,$view_field = array(),$oo = NULL) {
	if (!$view_field['name']) $view_field['name'] = ucfirst(str_replace('_',' ',$field_name));

	global $db,$action,$mod;
	if (!is_array($oo)) global $oo;
	
/*
	if ($action == 'list') {
		echo "<pre>";
		print_r($view_field);
		echo "<hr/>";
		print_r($oo);
	}
	*/
	if (!$view_field['type']) {
		if (substr($field_name,0,3) == 'is_')	$view_field['type'] = "checkbox";
		if (substr($field_name,-3) == '_id')	$view_field['type'] = "FK";
	}
	
	// we compute the value
	switch ($view_field['type']) {
		case 'bool':
		case 'checkbox':
			if (intval($oo[$field_name])>0) $tag = '<img src="ffw/icons/tick.png" width="16" height="16" alt="Oui"/>';
			else $tag = '';
		break;
		case 'FK':
			// $column
			// $fk_columns
			if ($oo[$field_name]) {
				$fk_columns = $view_field['fk_columns'];
				if (strpos($fk_columns,'.')) {
					list($foreign_table,$fk_columns) = explode('.',$fk_columns,2);
				} else {
					$foreign_table = substr($field_name,0,-3).'s';
				}
				if (!$fk_columns) {
					// we take first char or varchar field < 100
					global $db,$dbs;
					$fk_columns = '';
					if (!is_array($dbs[$foreign_table]))
						stop("Foreign table <strong>$foreign_table</strong> not found for field <strong>$field_name</strong>.",'Foreign key not found.',500);
					
					foreach ($dbs[$foreign_table] as $foreign_field_name => $foreign_field) {
						if ($fk_columns=='') {
							if (($foreign_field['type'] == 'varchar') or ($foreign_field['type'] == 'char')) {
								if ($foreign_field['length'] < 100) {
									$fk_columns = $foreign_field_name;
									break;	// pour sortir du foreach
								}
							}
						}
					}
				}
				$fk_cols = explode('|',$fk_columns);

				// we get the value from the table
				$sql = "-- - get foreign key values for FK $field_name
					select ".implode(',',$fk_cols)."
					from $foreign_table
					where id=".$oo[$field_name];
				$ze_values = $db->getrow($sql);
				if (!$ze_values) {	// cas où on ne trouve pas la foreign key correspondante
					$tag = $oo[$field_name];
				} else {
					$tag = implode(' ',$ze_values);
				}
			} else {
				$tag = '';
			}
		break;
		default:
			$tag = $oo[$field_name];
		break;
	}
	
	// on n'habille pas les champs dans le listing
	if ($action == 'list') return $tag;
	
	// espace situé avant les :
	$space = ' ';
	if ($conf['lang'] == 'en')
		$space = '';
	
	// we dress the value
	if ($mod['actions'][$action]['fields'][$field_name]['dressing']=='above') {
		// dressing above :     fieldname :
		//                      fieldinput
		$tag = "
		<div class='field'>
			<span class='names'>{$view_field['name']}{$space}:</span><br />
			$tag&nbsp;
		</div>\n";
	} else {

		$tag = "
		 <div class='field'>
			<div class='names'>{$view_field['name']}{$space}:</div>
			<div class='inputs'>$tag&nbsp;</div>
		</div>
		";
	}

	return $tag;
}

