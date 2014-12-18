<?php

class db {
	
	// connexion to the mysql server
	private $cnx;
	
	// selected database
	private $database;

	// list of executed queries
	private $query_list = array();
	
	// counter of queries displayed via $this->displayQueries()
	private $nb_query_displayed = 0;
	
	// nb of queries in $query_list
	private $nb_query_logged = 0;
	
	// nb of selected or affected row of the last query
	public $nb_rows = 0;

	// here we open up the connexion to the database
	public function __construct($dsn,$load_structure = true) {
		// if $load_structure is true, we analyse the structure of the database once we've opened it
		
		global $conf;
		
		// $dsn = mysql://user:password@host/database
		// we parse the dsn
		$dsn = substr($dsn,8);
		list($up,$hd)				= explode('@',$dsn,2);
		list($user,$pwd)			= explode(':',$up,2);
		list($host,$database_name)	= explode('/',$hd,2);

		// we connect to mysql server
		$this->cnx = @mysql_connect($host, $user, $pwd);
		if (!$this->cnx)
			stop(mysql_error(),"Echec de la connexion MySQL",500);

		// we select the database
		$this->database = mysql_select_db($database_name, $this->cnx);
		if (!$this->database)
			stop(mysql_error(),"Echec de la selection de la base $database_name",500);
		
		// we load the structure of the database
		if ($load_structure) {
			if (file_exists($conf['mods'].'dbs_'.$database_name.'.php')) {
				// the structure exists in a cache file
				include($conf['mods'].'dbs_'.$database_name.'.php');
			} else {
				// the structure is not in cache -> we fetch it
				$this->dbs = array();
				
			//	$tables = $this->MetaTables('TABLES');
				$tables = $this->getall(" -- - list all tables of database
					SHOW tables FROM $database_name ");
				foreach ($tables as $t) {
					$table_name = array_pop($t);
					// $this->dbs[$table_name] = $db->MetaColumns($table_name);
					$this->dbs[$table_name] = $this->get_fields_of_table($table_name);
				}
				
				// we dump the database structure into a cache file to save SQL requests in the future
				$fh = fopen($conf['mods'].'dbs_'.$database_name.'.php','w');
				fwrite($fh,"<?php // cache file of the structure of the database $database_name \n \$this->dbs = ");
				fwrite($fh,var_export($this->dbs,true));
				fwrite($fh,';');
				fclose($fh);
				chmod($conf['mods'].'dbs_'.$database_name.'.php',0777);
			}
		}

	}

	// this function is for measuring query times
	function getamicrotime() {
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		return ($mtime);
	}

	// on va chercher les champs de la table de données
	// en passant par un show create table
	// cette methode a l'avantage de fonctionner même si la table de données est VIDE
	public function get_fields_of_table($table) {
		$create = $this->getrow(" -- - create query of table $table
			show create table $table");
		// we get the sql
		$create_sql = $create['Create Table'];
		// echo "<pre>$create_sql</pre>";exit;
		// mail('stephane@metacites.net',"create $table",$create_sql);
		// we put it into an array
		$create_array = explode("\n",$create_sql);
		// we trim boxing lines
		array_shift($create_array);
		array_pop($create_array);
	
		// we loop on lines = fields of the table
		foreach ($create_array as $create_line) {
			$create_line = trim($create_line);
			$create_line = trim($create_line,',');
//			echo "<pre>$create_line</pre>";
			list($field_name,$field_type,$other_rubish) = explode(' ',$create_line,3);
			$field_name = trim($field_name,'`');
			
			switch ($field_name) {
				case 'PRIMARY':		// on repere les primary keys
					if ($field_type == 'KEY') {
						$pk_name = trim(strtr($other_rubish,'()`','   '));
						if (array_key_exists($pk_name,$table_fields))
							$table_fields[$pk_name]['primary_key'] = 1;
					}
				break;

				case 'UNIQUE':		// on repere les clés uniques
					if ($field_type == 'KEY') {
						list($name_of_index,$column_name) = explode(' ',$other_rubish,2);
						$column_name = trim(strtr($column_name,'()`','   '));
						if (array_key_exists($column_name,$table_fields))
							$table_fields[$column_name]['unique'] = 1;
					}
				break;
				
				case 'KEY' :		// on se fiche des index
					
				break;
			
				default:
					list($field_type,$field_length) = explode('(',$field_type,2);
					$field_length = rtrim($field_length,')');
					if ($field_type)
						$table_fields[$field_name] = array(
							'type'		=> $field_type,
							'length'	=> $field_length,
						);
					
					// gestion des champs enum
					if ($field_type == 'enum') {
						// on redécoupe $create_line pour prendre ce qu'il y a entre les parentheses, même s'il y a des espaces dedans
						$values = substr($create_line,strpos($create_line,'(')+1);
						$values = substr($values,0,strpos($values,')'));
						// on explose le resultat
						$all_values = explode("','",trim($values,"'"));
						// on entre ça dans le tableau
						$table_fields[$field_name]['values'] = $all_values;
						$table_fields[$field_name]['length'] = count($all_values);
					}
				break;
			}
			
		}
		
		return $table_fields;
	}


	public function execute($sql,$arr = null) {
		global $conf;

		if ($arr != null) {
			// if we have an array in $arr, we replace all :field: by '$arr['field']'
			if (is_array($arr)) {
				foreach ($arr as $key => $val) {
					// to prevent SQL injections
					if (is_string($val))
						$val = str_replace("`","\`", mysql_real_escape_string($val));
					$sql = str_replace(":$key:", "'$val'", $sql);
				}
			} else {
				// if we have something else, we replace all ? by $arr
				// to prevent SQL injections
				if (is_string($val))
					$arr = str_replace("`","\`", mysql_real_escape_string($arr));
				$sql = str_replace("?", "$arr", $sql);
			}
		}
		
		$chrono = $this->getamicrotime();
		
		$result = mysql_query($sql,$this->cnx);

		$chrono = round(($this->getamicrotime()-$chrono), 4);
		
		if (!$result) {
			$error = mysql_error();

			$this->query_list[] = array(
				'query'	=> $sql,
				'time'	=> $chrono,
				'error'	=> mysql_error(),
			);

			echo "<table style='color:#900;font:11px tahoma;'>
					<tr style='background:#666;color:white;'>
						<th colspan='4' align='left'>&nbsp;ERREUR SQL</th>
					</tr>
					<tr style='background:#CCC;color:black;'>
						<td colspan='4' align='left'>".$this->pretty_sql($sql)."</td>
					</tr>
					<tr style='background:#666;color:white;'>
						<td colspan='4'>&nbsp;$error</td>
					</tr>
			
			
					<tr style='background:#BBB;'>
						<th colspan='4' align='left'>&nbsp;Backtrace :</th>
					</tr>
					<tr style='background:#BBB;'>
						<th>file</th>
						<th>line</th>
						<th>class</th>
						<th>function(<span style='color:#300;'>args</span>)</th>
					</tr>
					";
			$trace = debug_backtrace();
			while ( $t = array_pop($trace) ) {
				
//				if (($t['class'] != 'db') or ($t['function'] != 'execute')) {
					
					// we get the color
					if (stripos(' '.$t['file'],$conf['mods'])) {
						// mods file in local site
						$color = '#009';	// dark blue
						$t['file'] = str_replace($conf['mods'],'',$t['file']);
					} elseif (stripos(' '.$t['file'],$conf['ffw'])) {
						// file in FFW Framework
						$color = '#900';	// dark red
						$t['file'] = str_replace($conf['ffw'],'',$t['file']);
					} else {
						// out of both
						$color = '#000';
						$t['file'] = ltrim($t['file'],'/');
					}
					
					$line = "
					<tr valign='top' style='background:#DDD;color:$color;'>
						<td>/{$t['file']}</td>
						<td align='right'>{$t['line']}</td>
						<td align='right'><strong>{$t['class']}</strong></td>
						<td>{$t['type']}<strong>{$t['function']}</strong>(<span style='color:#300;'>".nl2br(implode('</span>,<span style="color:#300;">',$t['args']))."</span>)</td>
					</tr> ";
	
					echo $line;
//				}
			}
			echo "</table>";
			
			exit;
			return false;
		}

		// we look for the origin of the query
		$trace = debug_backtrace();
		// on ne s'interesse pas à $db->execute() SI elle a été appelée par getall(), getrow(), ...
		if ((in_array(strtolower($trace[1]['function']),array('getall','getrow','getone','autoexecute','getarray'))) and ($trace[1]['class'] == 'db')) {
			$origin = $trace[1];
		} else {
			$origin = $trace[0];
		}
		
		$this->nb_rows = intval(@mysql_num_rows($result));
		if ($this->nb_rows == 0)
			$this->nb_rows = intval(@mysql_affected_rows());
//		$this->nb_rows = mysql_info();

		// on peuple $this->_query_list
		if ($this->nb_query_logged < 200) {
			$this->query_list[] = array(
				'query'		=> $sql,
				'time'		=> $chrono,
				'nb_rows'	=> $this->nb_rows,
				'origin'	=> $origin,
			);
			$this->nb_query_logged++;
		}
		
		return $result;

	}


	/**
	 * Gets result from a recent query
	 *
	 * @param object $result reference of a recent query result
	 * @param bool $get_first_line true if you only want the first line (for ->getRow() and ->getOne())
	 * @return array result(s)
	 */

	public function getQueryResults($result, $get_first_line = false)
	{
		if ($get_first_line) {
			return mysql_fetch_assoc($result);
		} else {
			$results = array();

			while ($row = mysql_fetch_assoc($result))
				$results[] = $row;
		}
		
		return $results;
	}
	
	/**
	* Execute query and returns all data found as a 2D associative array
	*
	* @param string $sql query
	* @param array $arr array of values to integrage into $sql request (optional)
	* @return array 2D array that has all records found 
	*/
	public function getAll($sql,$arr = null)
	{
		$result = $this->execute($sql,$arr);
		$data = $this->getQueryResults($result);
		if (!$data) return false;
		return $data;
	}

	/**
	* Execution query and returns the first record found as an associative array
	*
	* @param string $sql query
	* @param array $arr array of values to integrage into $sql request (optional)
	* @return array associative one dim array of the first record found
	*/
	public function getRow($sql,$arr = null)
	{
		$result	= $this->execute($sql,$arr);
		$data	= $this->getQueryResults($result,true);
		if (!$data) return false;
		return $data;
	}

	/**
	* Execute query and returns the value of the first field of the first line
	*
	* @param string $sql query
	* @param array $arr array of values to integrage into $sql request (optional)
	* @return string first field of first record found
	*/
	public function getOne($sql,$arr = null)
	{
		$result = $this->execute($sql,$arr);
		$row = $this->getQueryResults($result,true);
		if (!$row) return false;
		foreach ($row as $field)	// we only need the first one
			return $field;
	}
	
	
	/**
	* Execute query and returns the values inside an associative array
	*
	* @param string $sql query
	* @param array $arr array of values to integrage into $sql request (optional)
	* @return string php associative array
	*/
	public function getArray($sql,$arr = null)
	{
		$result = $this->execute($sql,$arr);
		$data = $this->getQueryResults($result);
		if (!$data) return false;

		$myArray = Array();
		foreach ($data as $row)
			$myArray[array_shift($row)] = array_shift($row);
		return $myArray;
	}
	
	
	/**
	* Mimic of adodb's autoexecute() : http://phplens.com/adodb/reference.functions.getupdatesql.html#autoexecute 
	* This function builds the query herself from the table name and an array of data.
	*
	* @param string $table name of the table on which we will make the insert / update
	* @param array $arrFields associative array that has all the data in. the keys of the array should match the fields of the mysql table
	* @param string $mode "UPDATE" or "INSERT"
	* @param string $where where clause to put in the query (only for updates)
	**/
	public function AutoExecute($table, $arrFields, $mode, $where=false)
	{
		// we get the keys of the table
		$table_keys = array_keys($this->dbs[$table]);
		
		// we delete the keys of $arrFields that are not in $table_keys
		foreach ($arrFields as $key => $val)
			if (!in_array($key,$table_keys))
				unset($arrFields[$key]);

		$keys = array_keys($arrFields);

		if ($mode == 'INSERT') {
			$sql = " -- - AutoExecute $mode $table
				insert into $table
				(".implode(', ',$keys).")
				values
				(:".implode(':, :',$keys).":)";
			$this->execute($sql,$arrFields);
		}
		
		if ($mode == 'UPDATE') {
			$sql = " -- - AutoExecute $mode $table
				update $table
				set ";
			foreach ($keys as $key)
				$sql .= "\n $key = :$key:,";
			$sql = rtrim($sql,',');

			if (!$where)
				stop("You must specify a where clause when using db::AutoExecute() for update","Where clause needed");

			$sql .= "\n		where $where";
			$this->execute($sql,$arrFields);
		}
	}
	
	
	/**
	* Pretty formating of sql queries
	*
	* @param string $sql initial sql query
	* @return string html making the query prettier
	*/
	public function pretty_sql($sql) {
		$lines = explode("\n",$sql);
		foreach ($lines as &$line) {
			if (strpos($line,'-- -')) {
				// red line (starts with -- - for explanations of query
				$line2 = str_replace('-- -',"-<span style='color:#900;'>-",$line);
				if ($line2 != $line) $line = $line2.'</span>';
			} else {
				// grey line for simply commented out sql
				$line2 = str_replace('--', '-<span style="color:#666">-',$line);
				if ($line2 != $line) $line = $line2.'</span>';
			}
		}
		$query = implode("<br/>",str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',$lines));
		return $query;
	}
	
	
	/**
	 * Display a table of all queries
	 *
	 * @param void
	 * @return string html table listing all queries
	 */
	public function displayQueries()
	{
		global $conf;
		$html = "<table cellspacing='2' cellpadding='2' border='0' class='query_list'>
			<tr style='background:#BBB;'><th>#</th><th>Query</th><th>Rows</th><th>Time (sec)</th></tr>";
		
		// $i = 0;
		$total_chrono = 0;
		while ($q = array_shift($this->query_list)) {
			$this->nb_query_displayed++;
			if ($q['error']) {
				$html .= "
					<tr>
					<td colspan='4' style='background:#333;color:white;'>{$q['error']}</td>
					</tr>
				";
			}
			// we get the color
			if (stripos(' '.$q['origin']['file'],$conf['mods'])) {
				// file in local site
				$color = '#009';	// dark blue
				$q['origin']['file'] = str_replace($conf['mods'],'',$q['origin']['file']);
			} else {
				// file in FFW Framework
				$color = '#900';	// dark red
				$q['origin']['file'] = str_replace($conf['ffw'],'',$q['origin']['file']);
			}
			$html .= "
				<tr align='left' valign='top' style='background:#CCC;'>
					<td rowspan='2' align='right'>$this->nb_query_displayed</td>
					<td colspan='3' style='color:$color'>/{$q['origin']['file']} line {$q['origin']['line']}<br/>
					<strong>{$q['origin']['class']}{$q['origin']['type']}{$q['origin']['function']}</strong>(<span style='color:#300;'>".implode('</span>,<span style="color:#300;">',$q['origin']['args'])."</span>)</td>
				</tr>
				<tr align='right' style='background:#DDD;'>
					<td align='left'>".str_replace('#900',$color,$this->pretty_sql($q['query']))."</td>
					<td>{$q['nb_rows']}</td>
					<td>{$q['time']}</td>
				</tr>";
			$total_chrono += $q['time'];
		}
		
		$html .= "
			<tr style='background:#BBB;' class='last' align='right'>
				<th>&nbsp;</th>
				<th>total nb of queries: $this->nb_query_displayed</th>
				<th></th>
				<th style='text-align:right'>$total_chrono</th>
			</tr>";
		 
		$html .= '</table>';
		return $html;
	}

	// mimic of adodb
	public function Insert_ID() {
		return mysql_insert_id($this->cnx);
	}
	
	// 
	public function Affected_Rows() {
		return mysql_affected_rows($this->cnx);
	}
}

?>