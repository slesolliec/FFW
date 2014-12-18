<?php

$html = '';

if ($mod['actions']['list']['title']) {
	$page['title'] = $mod['actions']['list']['title'];
} else {
	$page['title'] = "Liste des ".$mod['object_plural'];
}

function field_header($field_name) {
	global $mod;
	$alist = $mod['actions']['list'];

	if ($alist['fields'][$field_name]['name']) {
		if ($alist['fields'][$field_name]['info']) {
			return "<acronym title=\"{$alist['fields'][$field_name]['info']}\">{$alist['fields'][$field_name]['name']}</acronym>";
		} else {
			return $alist['fields'][$field_name]['name'];
		}
	} else {
		return ucfirst(str_replace('_',' ',$field_name));
	}
}


$mod['actions']['list'] = my_array_merge(array('fields'=>$mod['fields']),$mod['actions']['list']);

// where clause
if (!$where) {
	// si la clause where n'a pas été composée dans le module, on la compose maintenant
	$where = '';
	foreach ($dbs[$mod['table']] as $field_name => $field) {
		if ($_GET[$field_name] or ($_GET[$field_name] === '0') or ($_GET[$field_name] === 0)) {
			if (substr($field_name,-3) == '_id') {
				// foreign key
				$where .= "\n and $field_name = '".intval($_GET[$field_name])."' ";
			} else if (substr($field_name,0,3) == 'is_') {
				$where .= "\n and $field_name = \"{$_GET[$field_name]}\" ";
			} else {
				if ($field['type'] == 'enum') {
					$where .= "\n and $field_name = \"{$_GET[$field_name]}\" ";
				} else if ($field['type'] == 'varchar') {
					$where .= "\n and $field_name like \"%{$_GET[$field_name]}%\" ";
				} else {
					$where .= "\n and $field_name = \"{$_GET[$field_name]}\" ";
				}
			}
		}
	}
	if ($where) $where = "where ".substr($where,6);
}

// we eventuelly add where clauses
if ($more_where) $where .= $more_where;
// on s'assure qu'on ait pas une clause where qui commence par "and "
// ça peut arriver avec des $more_where
if (substr(ltrim($where),0,4) == 'and ')
	$where = "where ".substr(ltrim($where),4);

// we make a first query just to know the number of corresponding lines in the table
if (!$sql_countlines)
	$sql_countlines = " -- - crud/list get number of lines
		select count(*)
		from {$mod['table']}
		$where ";
$nb_lines = intval($db->getone($sql_countlines));


/* query string	// merde, cette méthode ne prend pas en compte les changements du tableau $_GET forcés dans PHP
if ($_SERVER['REQUEST_URI']) {
	list($rubish,$query_string) = explode('?',$_SERVER['REQUEST_URI'],2);
	if ($query_string) $page['query_string'] = $query_string;
} */
// autre méthode
if ($_GET) {
	$page['query_string'] = '';
	foreach ($_GET as $key => $val) {
		// if (is_array($val)) // cas à prendre en compte ??
		if ($key != 'p')	// on ne veut pas prendre en compte ce cas particulier
			$page['query_string'] .= "&amp;$key=".urlencode($val);
	}
	$page['query_string'] = substr($page['query_string'],5);
}



// we build the pagination
$limit = intval($mod['actions']['list']['limit']);
$pag = intval($params);
if ($pag == 0) $pag = 1;
if ($nb_lines > $limit) {
	$nb_pages = ceil($nb_lines / $limit);
	
	if ($pag == $nb_pages) {
		$nb_items = $nb_lines - ($nb_pages - 1) * $limit;
		$pagination = "affiche $nb_items lignes sur <strong>$nb_lines</strong> résultats trouvés.<div class='links'>";
	} else {
		$pagination = "affiche $limit lignes sur <strong>$nb_lines</strong> résultats trouvés.<div class='links'>";
	}
	
	// goto first
	if ($pag > 1) {
		$pagination .= "<a href='{$page['module']}/list";
		if ($page['query_string']) $pagination .= "?".$page['query_string'];
		$pagination .= "' title='Début'><img src='ffw/icons/resultset_first.png' width='16' height='16' alt='Début' align='absmiddle' /></a>&nbsp;";
	} else {
		$pagination .= "<img src='ffw/icons/resultset_first_gray.png' width='16' height='16' alt='Début' title='Début' align='absmiddle' />&nbsp;";
	}

	// goto previous
	if ($pag > 1) {
		$pagination .= "<a href='{$page['module']}/list";
		if ($pag > 2) $pagination .= '/'.($pag -1);
		if ($page['query_string']) $pagination .= "?".$page['query_string'];
		$pagination .= "' title='Précédent'><img src='ffw/icons/resultset_previous.png' width='16' height='16' alt='Précédent' align='absmiddle' /></a> &nbsp; ";
	} else {
		$pagination .= "<img src='ffw/icons/resultset_previous_gray.png' width='16' height='16' alt='Précédent' title='Précédent' align='absmiddle' /> &nbsp; ";
	}

	if ($nb_pages < 11) {
		for ($i = 1; $i <= $nb_pages; $i++) {
			if ($pag == $i) {
				$pagination .= "$i &nbsp; ";
			} else {
				$pagination .= "<a href='{$page['module']}/list";
				if ($i>1) $pagination .= "/$i";
				if ($page['query_string']) $pagination .= "?".$page['query_string'];
				$pagination .= "'>$i</a> &nbsp; ";
			}
		}
	} else {
		// il y a plus de 10 pages
		$min = max(intval($pag - 3),1);
		$max = min(intval($pag + 3),$nb_pages);
		if ($min > 1) $pagination .= "... &nbsp; ";
		for ($i = $min; $i <= $max; $i++) {
			if ($pag == $i) {
				$pagination .= "$i &nbsp; ";
			} else {
				$pagination .= "<a href='{$page['module']}/list";
				if ($i>1) $pagination .= "/$i";
				if ($page['query_string']) $pagination .= "?".$page['query_string'];
				$pagination .= "'>$i</a> &nbsp; ";
			}
		}
		if ($max < $nb_pages) $pagination .= "... &nbsp; ";
	}
	
	

	// goto next
	if ($pag < $nb_pages) {
		$pagination .= "<a href='{$page['module']}/list/".($pag +1);
		if ($page['query_string']) $pagination .= "?".$page['query_string'];
		$pagination .= "' title='Suivant'><img src='ffw/icons/resultset_next.png' width='16' height='16' alt='Suivant' align='absmiddle' /></a>&nbsp;";
	} else {
		$pagination .= "<img src='ffw/icons/resultset_next_gray.png' width='16' height='16' alt='Suivant' title='Suivant' align='absmiddle' />&nbsp;";
	}

	// goto last
	if ($pag < $nb_pages) {
		$pagination .= "<a href='{$page['module']}/list/$nb_pages";
		if ($page['query_string']) $pagination .= "?".$page['query_string'];
		$pagination .= "' title='Fin'><img src='ffw/icons/resultset_last.png' width='16' height='16' alt='Fin' align='absmiddle' /></a>";
	} else {
		$pagination .= "<img src='ffw/icons/resultset_last_gray.png' width='16' height='16' alt='Fin' title='Fin' align='absmiddle' />";
	}


	$pagination .= "</div>";
} else {
	$pagination = "<strong>$nb_lines</strong> résultats trouvés.<br/>";
}
$pagination = "<div class='pagination'>$pagination</div>";


// we now make the true query

// we manage the order by
$order_by = $mod['actions']['list']['order_by'];
$order = $mod['actions']['list']['order'];
if ($_GET['order_by']) {
	$order_by = str_replace(' ','',$_GET['order_by']);	// we don't like spaces, do we?
	list($order_by,$order) = explode('|',$order_by,2);
}
if ($order != 'desc') $order = 'asc';

// we build the query
if (!$sql_getlines) {
	$sql_getlines = " -- - crud/list get $limit lines of table {$mod['table']}
		select *";
	if ($mod['actions']['list']['subquery']) $sql_getlines .= ",\n	".$mod['actions']['list']['subquery'];
	$sql_getlines .= "
		from {$mod['table']}
		$where ";
}

$sql_getlines .= "
	order by $order_by $order
	limit ".intval($pag*$limit - $limit).",$limit";
// we get the objects
$oos = $db->getall($sql_getlines);


// we add view and edit links
if ($oos) {
	foreach ($oos as &$oo) {
		$oo['view_link'] = "<a href='$module/view/{$oo['id']}'><img src='ffw/icons/doc_view.png' alt='voir' title='voir' width='16' height='16' /></a>";
		$oo['edit_link'] = "<a href='$module/edit/{$oo['id']}'><img src='ffw/icons/doc_edit.png' alt='modifier' title='modifier' width='16' height='16' /></a>";
	}
}

// pseudo fields
if ($oos)
	if (is_array($mod['actions']['list']['fields']))
		foreach ($mod['actions']['list']['fields'] as $key => $val) {
			if ($val['function'])
				foreach ($oos as &$oo)
					$oo[$key] = $val['function']($oo);
			if ($val['filter'])
				foreach ($oos as &$oo) {
//						echo "<pre>{$val['filter']}</pre>";exit;
					$oo[$key] = eval($val['filter']);
					
				}
					
		}

// we now check if a template exists
if (file_exists($conf['mods']."mod_$module/views/{$module}_$action.html")) {
	if ($oos)
		$smarty->assign('oos',$oos);
	$page['content'] = $smarty->fetch("file:{$conf['mods']}mod_$module/views/{$module}_$action.html");
} else {
	// if no template exists, we take care of the rendering :

	// table
	$html .= "\n<table class='list' cellspacing='2'>";
	// colgroups
	
	$alist = $mod['actions']['list'];
	/* $alist = array(
			'field_order'	=>	array(	'firstname','lastname','born_on' )
			'field_deny'	=>	array(	'pwd','id' )
			'fields'		=>	array(
									'name'	=>
									'info'	=>
								)
			''
		)
	*/

	// THEAD
	$html .= "\n\t<thead>\n\t\t<tr>";

	// case where we have field_order defined
	if ($alist['field_order']) {
		foreach ($alist['field_order'] as $field_name) {
			$html .= "\n\t\t\t<th><a href='{$page['module']}/list/?{$page['query_string']}&order_by=$field_name";
			if (($field_name == $order_by) and ($order == 'asc')) $html .= '|desc';
			$html .= "'>".field_header($field_name)."</a>";
			if ($field_name == $order_by) {
				if ($order != 'desc') {
					$html .= "<img src='ffw/icons/bullet_arrow_up.png' width='16' height='16' alt='trié par order ascendant' align='absmiddle' />";
				} else {
					$html .= "<img src='ffw/icons/bullet_arrow_down.png' width='16' height='16' alt='trié par order descendant' align='absmiddle' />";
				}
			}
			$html .= "</th>";
		}

	} else {
	// default case: we loop on all fields of table
		if ($oos)
			foreach ($oos[0] as $key => $value)
				if (!is_array($alist['field_deny']) or !in_array($key,$alist['field_deny'])) {
					$html .= "\n\t\t\t<th><a href='{$page['module']}/list/?{$page['query_string']}&order_by=$key";
					if (($key == $order_by) and ($order == 'asc')) $html .= '|desc';
					$html .= "'>".field_header($key)."</a></th>";
				}
	}
	$html .= "\n\t\t</tr>\n\t</thead>";

	// TBODY
	$html .= "\n\t<tbody>";

	if ($oos) {
		
		// we fill the types of fields
		if (is_array($mod['actions']['list']['fields']))
			$fields = $mod['actions']['list']['fields'];
		else
			$fields = array();

		$oo1 = $oos[0];
		foreach ($oo1 as $key => $value)
			if (!$fields[$key]['type'])
				$fields[$key]['type'] = $dbs[$mod['table']][strtoupper($key)]->type;
		
		foreach ($oos as $obj) {
			if ($odd) {$odd = 0;} else {$odd = 1;}
			$html .= "\n\t\t<tr class='".($odd?'odd':'even')."' valign='top'>";
			
			// cas field_order
			if ($alist['field_order']) {
				foreach ($alist['field_order'] as $field_name)
					$html .= "\n\t\t\t<td class='{$fields[$field_name]['type']}'>".view_field($field_name,$fields[$field_name],$obj)."</td>";

			} else {
				// cas field_deny
				if (is_array($alist['field_deny'])) {
					foreach ($obj as $key => $value)
						if (!in_array($key,$alist['field_deny']))
							$html .= "\n\t\t\t<td class='{$fields[$key]['type']}'>".view_field($key,$fields[$key],$obj)."</td>";
				
				// cas tous les fields
				} else {
					foreach ($obj as $key => $value)
						$html .= "\n\t\t\t<td class='{$fields[$key]['type']}'>".view_field($key,$fields[$key],$obj)."</td>";
				}
			}
			$html .= "\n\t\t</tr>";
		}

	} else {
		if ($mod['object_genre']=='f') {
			$html .= "\n\t\t<tr><td>Aucune {$mod['object']} trouvée.</td></tr>";
		} else {
			$html .= "\n\t\t<tr><td>Aucun {$mod['object']} trouvé.</td></tr>";
		}
	}

	$html .= "\n\t</tbody>";
	
	// /TABLE
	$html .= "\n</table>";
	
	if ($pagination)
		$html = $pagination .$html. $pagination;
	
	
	$page['table'] = $html;
	
//	$smarty->assign('oos',$oos);
	$page['introduction'] = $mod['actions']['list']['introduction'];
	$page['content'] = $smarty->fetch("file:{$conf['ffw']}mod_crud/views/crud_list.html");

	manage_action_links('list');
}

