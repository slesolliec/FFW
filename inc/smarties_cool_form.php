<?php

// $table : this is the name of the mysql table we are going to use to generate the form (ie: $table = 'users')
// $form : array describing the form with lots of values that are strings or arrays
function form_maker($table_name,$form = array()) {
	global $dbs;
	
	$html = '';

	// we create the field inputs
	$fields = $form['fields'];
			
	if ($form['field_order']) {
		// we display fields as they are ordered in $field_order
		foreach ($form['field_order'] as $field) {
			// champ dans le formulaire qui n'existe pas dans la base
			$html .= fast_field($field,$fields[$field]['name'],$fields[$field]['type'],$fields[$field]['fk_field'],$fields[$field]['info']);
		}
	} else {
		// we display fields as they are ordered in the mysql table
//		global $mod;echo $table_name.'<pre>';var_dump($mod);exit;
		// var_dump($dbs[$table_name]);exit;
		foreach ($dbs[$table_name] as $field_name => $field)
			if (!$form['field_deny'] or !in_array($field_name,$form['field_deny']))
				$html .= fast_field($field_name,$fields[$field_name]['name'],$fields[$field_name]['type'],$fields[$field_name]['fk_field'],$fields[$field_name]['info']);
	}
	
	return $html;
}



// $table : this is the name of the mysql table we are going to use to generate the form (ie: $table = 'users')
// $form : array describing the form with lots of values that are strings or arrays
function filter_maker($table_name,$form = array()) {
	global $dbs,$mod;
	
	$html = '';
	$oo = rtrim($table_name,'s');

	// we create the field inputs
	$fields = $form['fields'];
	
	foreach ($fields as $key => $field) {
		// name of field
		if ($mod['filter']['fields'][$key]['name']) {
			$field_name = $mod['filter']['fields'][$key]['name'];
		} else {
			$field_name = ucfirst(str_replace('_',' ',$key));
		}
		
		// hack : detect FK --> FK_null
		if (substr($key,-3)=='_id')
			$field['type'] = 'FK_null';
		
		// we add the field
		$html .= "
			<div class='filter_field'>
				<div class='filter_name'>$field_name</div>
				<div class='filter_input'>".fast_input($_GET[$key],$key,$field['type'])."</div>
			</div>";
	}
	
	return $html;
}



// $table : this is the name of the mysql table we are going to use to generate the view (ie: $table = 'users')
// $view : array describing the view with lots of values that are strings or arrays
function view_maker($table_name,$view = array()) {
	global $dbs;
	
	$html = '';

	// we create the field views
	$fields = $view['fields'];

	if ($view['field_order']) {
		// we display fields as they are ordered in $field_order
		foreach ($view['field_order'] as $field_name) {
			$html .= view_field($field_name,$fields[$field_name]);
		}
	} else {
		// we display fields as they are ordered in the mysql table
		foreach ($dbs[$table_name] as $field_name => $field)
			if (!$view['field_deny'] or !in_array($field_name,$view['field_deny']))
				$html .= view_field($field_name,$fields[$field_name]);
	}
	
	return $html;
}
