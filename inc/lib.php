<?php

// the first thing is to start the chrono
function getamicrotime() {
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	return ($mtime);
	}
$chrono = getamicrotime();

// my updated php screams if I don't set this  :-/
date_default_timezone_set('Europe/Paris');

function isEmail($string) {
	return eregi('^[-a-z0-9._]+\@[-a-z0-9.]+\.[a-z]{2,6}$',$string);
}


// classes autoload function
function my_autoload($class) {
	global $conf;
	if (file_exists($conf['ffw']."classes/$class.class.php"))
		include_once($conf['ffw']."classes/$class.class.php");
}
spl_autoload_register("my_autoload");

// function to display a pretty trace
function trace($title='',$color='#666') {
	global $conf;
	
	$txt = "<table style='color:#900;font:11px tahoma;'>";
	if ($title)
		$txt .= "	<tr><th colspan='4' style='background:$color;color:black;'>$title</th></tr>";
	$txt .= "
			<tr style='background:#BBB;'>
				<th colspan='4' style='text-align:left;'>&nbsp;Backtrace :</th>
			</tr>
			<tr style='background:#BBB;'>
				<th>file</th>
				<th>line</th>
				<th>class</th>
				<th>function(<span style='color:#300;'>args</span>)</th>
			</tr>
			";
	$trace = debug_backtrace();
	while ( $d = array_pop($trace) ) {
		
		// we cannot display my_error_handler() in the trace because displaying its arguments means displaying the whole context and cause infinite loops
		if ($d['function'] == 'my_error_handler')
			break;

		if (is_array($d['args'])) {
			foreach ($d['args'] as &$arg) {
				if (is_array($arg)) {
					$arg = '<pre>'.trim(var_export($arg,true)).'</pre>';
				//	$arg = '<pre>['.implode(',',$arg).']</pre>';
				//	$arg = nl2br($arg);
				} else if (!is_object($arg)) {
					$arg = nl2br($arg);
				} else {
					$arg = "object of type ".gettype($arg);
				}
			}
		} else {
			$d['args'] = array();
		}
		
		// we colorize : red=FFW / blue=application / black=other
		if (stripos(' '.$d['file'],$conf['mods'])) {
			// mods file in local site
			$color = '#009';	// dark blue
			$d['file'] = str_replace($conf['mods'],'',$d['file']);
		} elseif (stripos(' '.$d['file'],$conf['ffw'])) {
			// file in FFW Framework
			$color = '#900';	// dark red
			$d['file'] = str_replace($conf['ffw'],'',$d['file']);
		} else {
			// out of both
			$color = '#000';
			$d['file'] = ltrim($d['file'],'/');
		}
		
		$txt .= "
		<tr align='left' valign='top' style='background:#DDD;color:$color;'>
			<td>{$d['file']}</td>
			<td align='right'>{$d['line']}</td>
			<td align='right'><strong>{$d['class']}</strong></td>
			<td>{$d['type']}<strong>{$d['function']}</strong>(<span style='color:#300;'>".implode('</span>,<span style="color:#300;">',$d['args'])."</span>)</td>
		</tr> ";
	}
	$txt .= "</table>";
	unset($trace);
	return $txt;
}


// we handle errors ourselves
function my_error_handler($errno, $errstr, $errfile, $errline) {
	
	// if function called with @
	if (error_reporting() == 0)
		return;

	$title = "$errstr <span style='font-weight:normal'>in</span> $errfile <span style='font-weight:normal'>on line</span> $errline";
	
	switch ($errno) {
		case E_ERROR:
			echo trace($title,'red');
			exit(1);
		break;
		case E_WARNING:
			echo trace($title,'orange');
			exit(1);
		break;
	}
	echo trace($errstr,'violet');
	return true;
}


function mois($nb) {
	$mois = array('01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05' => 'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre');
	return $mois[$nb];
}

function xss_armor($txt) {
	global $user,$conf;
	if ($conf['salt'])	$salt = $conf['salt'];
	else				$salt = 'salt887';
	return substr(md5($salt.$txt.$user['pwd'].date('Ym')),6,8);
}


$debug = 1;
setlocale(LC_TIME, "fr_FR");

// this function removes accents
function remove_accents($string) {
	$string = utf8_decode($string);
 	$string = strtr($string, utf8_decode("ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ"), "SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
	$string = utf8_encode($string);
	$string = str_replace(':','',$string);
	$string = str_replace('\'','_',$string);
	$string = str_replace('"','',$string);
	$string = str_replace('.','-',$string);
	$string = str_replace(',','',$string);
	$string = str_replace(';','',$string);
	$string = str_replace('(','',$string);
	$string = str_replace(')','',$string);
	$string = str_replace('!','',$string);
	$string = str_replace('?','',$string);
	return $string;
}

// this function gives an error msg and exits
function stop($txt,$title='',$http_code=0) {
	global $page,$smarty,$chrono,$conf,$mod,$db,$user;
	
	$http_codes = array(
		100	=>	"100 Continue",
		101 =>	"101 Switching Protocols",
		200 =>	"200 OK",
		201 =>	"201 Created",
		202 =>	"202 Accepted",
		203 =>	"203 Non-Authoritative Information",
		204 =>	"204 No Content",
		205 =>	"205 Reset Content",
		206 =>	"206 Partial Content",
		300 =>	"300 Multiple Choices",
		301 =>	"301 Moved Permanently",
		302 =>	"302 Moved Temporarily",
		303 =>	"303 See Other",
		304 =>	"304 Not Modified",
		305 =>	"305 Use Proxy",
		400 =>	"400 Bad Request",
		401 =>	"401 Unauthorized",
		402 =>	"402 Payment Required",
		403 =>	"403 Forbidden",
		404 =>	"404 Not Found",
		405 =>	"405 Method Not Allowed",
		406 =>	"406 Not Acceptable",
		407 =>	"407 Proxy Authentication Required",
		408 =>	"408 Request Time-out",
		409 =>	"409 Conflict",
		410 =>	"410 Gone",
		411 =>	"411 Length Required",
		412 =>	"412 Precondition Failed",
		413 =>	"413 Request Entity Too Large",
		414 =>	"414 Request-URI Too Long",
		415 =>	"415 Unsupported Media Type",
		416 =>	"416 Requested range unsatifiable",
		417 =>	"417 Expectation failed",
		500 =>	"500 Internal Server Error",
		501 =>	"501 Not Implemented",
		502 =>	"502 Bad Gateway",
		503 =>	"503 Service Unavailable",
		504 =>	"504 Gateway Time-out",
		505 =>	"505 HTTP Version not supported"
	);

	if ($title) $page['title'] = $title;

	$page['content'] = $txt;
	if ($conf['mode'] == 'dev') {
		$page['debug'] = trace($page['module'].'/'.$page['action'].'/'.$page['params'],'#01C9ED');
		if (isset($db))
			$page['queries'] = $db->displayQueries();
	}
	
	/*
	switch($http_code) {
		case 500:
		
	}
	*/

	if ($http_code>0) header("HTTP/1.1 ".$http_codes[$http_code]);
	header('Content-type: text/html; charset=utf-8');
	$page['chrono'] = ceil((getamicrotime() - $chrono)*1000)/1000;
	if (file_exists($conf['mods']."mod_www/views/error.html")) {
		$smarty->display("file:{$conf['mods']}mod_www/views/error.html");
	} else {
		$smarty->display("file:{$conf['ffw']}mod_www/views/error.html");
	}
	exit;
}


// we need that for our cool_input
// this function gets the col types of tables
function get_col_types($table) {
	global $db;
	$cols = array();
	$recordSet = $db->execute("select * from $table limit 1");
	for ($i=0; $i < $recordSet->FieldCount(); $i++) {
		$field = $recordSet->FetchField($i);
		$cols[$field->name] = $recordSet->MetaType($field->type).'|'.$field->max_length;
	}
	return $cols;
}


// this function create a directory in a safe way (even if the parent dir does not exist)
function kmkdir($dir) {
	if (file_exists($dir)) return(true);
	$parent_dir = substr($dir,0,strrpos($dir,'/'));
	if ($dir == $parent_dir) stop('kmkdir() error !!! zut.');
	if (!file_exists($parent_dir)) kmkdir($parent_dir);
	mkdir($dir);
	@chmod($dir,0777);
	return;
}


// this function checks if GD2 is here
function chkgd2() {
	$testGD = get_extension_funcs("gd"); // Grab function list
	if (!$testGD){ echo "GD not even installed."; exit; }
	if (in_array ("imagegd2",$testGD)) $gd_version = "<2"; // Check
	if ($gd_version == "<2") return false; else return true;
}

// "Content-Type: text/plain; charset=\"UTF-8\"\r\nContent-Transfer-Encoding: 8bit"
function send_mail($to,$subject,$body,$body_txt,$from_email='',$from_name='') {
	global $conf;

	include_once($conf['PHPMailer']);
	$mail = new PHPMailer();

	if ($from_email) {
		$mail->From     = $from_email;
		$mail->FromName = $from_name;
	} else {
		$mail->From     = $conf['email_from'];
		$mail->FromName = $conf['flag'];
	}
	$mail->Subject	= $subject;
	$mail->CharSet	= 'UTF-8';

	$mail->Body    = $body;
	$mail->AltBody = $body_txt;
	// $mail->AddAddress(, $post['login']);
	$mail->AddAddress($to);

	// do we send via SMTP ?
	if ($conf['smtp']) {
		$mail->IsSMTP();
		$mail->Host = $conf['smtp'];
	}
	
	$mail->Send();
}


// fonction qui passe de 24/04/1970 à 1970-04-24 et inversement
function inverser_date($date) {
	// just in case we have a 1970-04-24 21:20:00, we split the second part
	list($date,$devnul) = explode(' ',$date);

	if (strpos($date,'/')) {
		list($d,$m,$y) = explode('/',$date,3);
		return "$y-$m-$d";	
	} else {
		list($y,$m,$d) = explode('-',$date,3);
		$r = "$d/$m/$y";
		$r = str_replace('//','',$r);
		return $r;
	}
}

function mktime_from_dayhour($day,$hour) {
	return mktime(substr($hour,0,2),substr($hour,3,2),substr($hour,6,2),substr($day,5,2),substr($day,8,2),substr($day,0,4));
}

// we take some string and try to turn it into some time 
function input_to_time($str) {
	// format 1234
	if (ereg("^[0-9]{1,4}$",$str)) {
		if (strlen($str)<3) {
			$hour = $str;
			$minutes = '00';
		} else {
			$hour = substr($str,0,-2);
			$minutes = substr($str,-2);
		}
	} else {
		// format 12h34
		if (strpos($str,'h'))
			list($hour,$minutes) = explode('h',$str,2);
		// format 12:34
		if (strpos($str,':'))
			list($hour,$minutes) = explode(':',$str,2);
		if (isset($hour)) {
			$hour = intval($hour);
			$minutes = intval($minutes);
		} else {
			// we set the date as now
			return date('H:i:s');
		}
	}
	return "$hour:$minutes";
}


// split les tags d'un post into an array   $taga[] = array('type'=>'person','tag'=>'Mickael Jackson')
// coma separated tags
function tag_split($tags) {
	$tagarray = explode(',',$tags);
	if ($tagarray) {
		$taga = array();
		foreach ($tagarray as $t) {
			if (strpos($t,':')) {
				list($type,$tag) = explode(':',$t,2);
			} else {
				$type = '';
				$tag = $t;
			}
			$type = trim($type);
			$tag = trim($tag);
			if ($tag)	// un tag ne doit pas être vide
				$taga[] = array('type'=>$type,'tag'=>$tag);
		}
		return $taga;
	} else {
		return false;
	}
}

// space seperated tags
function tagsplit ($string) {
	$tag_array = explode(' ',trim($string));
	$j = 0;
	$final_array = array();
	$inside_tag = false;
	for($i=0;$i<sizeof($tag_array);$i++) {
		$tag_array[$i] = stripslashes(trim($tag_array[$i]));
		if ($tag_array[$i]) {
			if ($inside_tag) {
				if (substr($tag_array[$i],-1,1)=='"') {
					$final_array[$j] .= ' '.substr($tag_array[$i],0,strlen($tag_array[$i])-1);
					$inside_tag = false;
					$j++;
					} else {
					$final_array[$j] .= ' '.$tag_array[$i];
					}
				} else {
				if (substr($tag_array[$i],0,1)=='"') {
					$final_array[$j] = substr($tag_array[$i],1);
					$inside_tag = true;
					} else {
					$final_array[$j] = $tag_array[$i];
					$j++;
					}
				}
			}
		}
	$typed_tags = array();
	foreach ($final_array as $t) {
		$type = '';
		if (strpos($t,':')) list($type,$t) = explode(':',$t,2);
		// just for security :
		$type = str_replace('"','',$type);
		$t = str_replace('"','',$t);
		$typed_tags[] = array('type'=>$type,'tag'=>$t);
		}
	return $typed_tags;
	}



// turns a string into a list of links to tag pages
function tag_link($tag_string,$base_url) {
	$taga = tag_split($tag_string);
	if (!is_array($taga))
		return '';
	$html = '';
	foreach ($taga as $tag) {
		if ($html) $html .= ', ';
		$html .= "<a href='{$base_url}".($tag['type']!=''?$tag['type'].':':'').$tag['tag']."'>{$tag['tag']}</a>";
	}
	return $html;
}





// this function link the links in comments
function link_links($match) {
	$link = $match[0];
//	return $link;
	// the last char of a link cannot be a . or a , or a ;
	$last_char = substr($link,-1);
	if (in_array($last_char,array(',','.',';',')'))) {
		$link = substr($link,0,strlen($link)-1);
	} else {
		$last_char = '';
	}
	if (strlen($link)>50) {
		$inner_link = substr($link,0,30).'...'.substr($link,-10);
	} else {
		$inner_link = $link;
	}
	return "<a href=\"$link\">$inner_link</a>".$last_char;
}

function comment_mix($str) {
	$patern = '/([a-z]*):\/\/[^ \n\r\t]*/i';
	$return = preg_replace_callback($patern,"link_links",$str);
	$return = nl2br($return);
	return $return;
}


// this function cleans the slashes in a string or array
function clean_magic_quotes($object) {
	if (get_magic_quotes_gpc()) {
		if (is_array($object)) {
			foreach ($object as $key => $val)
				$object[$key] = clean_magic_quotes($val);
		}
		if (is_string($object)) {
			$object = stripslashes($object);
		}
	}
	return $object;
}

function recursive_replace($from,$to,$obj) {
	if (is_array($obj))
		foreach ($obj as $key => $val)
			$obj[$key] = recursive_replace($from,$to,$val);

	if (is_string($obj))
		$obj = str_replace($from,$to,$obj);

	return $obj;
}


// merging of two arrays
function my_array_merge($a,$b) {
	// $a and $b are both array
	// $b is supposed to override the values of $a
	if (is_array($b)) {
		foreach ($b as $key => $val) {
			if (is_array($val)) {
				if (!is_array($a[$key]))
					$a[$key] = array();
				$a[$key] = my_array_merge($a[$key],$b[$key]);
			} else {
				if (is_numeric($key)) {
					// if the key is numeric, we APPEND the value to the $a array
					$a[] = $val;
				} else {
					// if the key is alphanum, we REPLACE the value of the $a array
					$a[$key] = $val;
				}
			}
		}
	}
	return $a;
}


function load_mod($module,$action) {
	global $conf;
	// load the $mod array
	$mod = Spyc::YAMLLoad($conf['ffw']."mod_crud/crud.yml");
	$mod = recursive_replace('(module)',$module,$mod);
	// we look for local configs (local to the module) within FFW
	if (file_exists($conf['ffw']."mod_$module/$module.yml")) {
		$mod2 = Spyc::YAMLLoad($conf['ffw']."mod_$module/$module.yml");
		$mod2 = recursive_replace('(module)',$module,$mod2);
		$mod = my_array_merge($mod,$mod2);
		unset($mod2);
//		echo "<pre>";var_dump($mod2);
	}
	// we look for local configs (local to the action) within FFW
	if (file_exists($conf['ffw']."mod_$module/{$module}_$action.yml")) {
		$mod2 = Spyc::YAMLLoad($conf['ffw']."mod_$module/{$module}_$action.yml");
		$mod2 = recursive_replace('(module)',$module,$mod2);
		$mod['actions'][$action] = my_array_merge($mod['actions'][$action],$mod2);
		unset($mod2);
	}
	// we can have local module configs out of the /mod_module/ directory
	if (file_exists($conf['mods']."mod_$module.yml")) {
		$mod2 = Spyc::YAMLLoad($conf['mods']."mod_$module.yml");
		$mod2 = recursive_replace('(module)',$module,$mod2);
		$mod = my_array_merge($mod,$mod2);
		unset($mod2);
	}
	// we look for local configs (local to the module)
	if (file_exists($conf['mods']."mod_$module/$module.yml")) {
		$mod2 = Spyc::YAMLLoad($conf['mods']."mod_$module/$module.yml");
		$mod2 = recursive_replace('(module)',$module,$mod2);
		$mod = my_array_merge($mod,$mod2);
		unset($mod2);
	}
	// we look for local configs (local to the action)
	if (file_exists($conf['mods']."mod_$module/{$module}_$action.yml")) {
		$mod2 = Spyc::YAMLLoad($conf['mods']."mod_$module/{$module}_$action.yml");
		$mod2 = recursive_replace('(module)',$module,$mod2);
		$mod['actions'][$action] = my_array_merge($mod['actions'][$action],$mod2);
		unset($mod2);
	}
	return $mod;
}



// cette fonction affiche un tableau associatif dans un tableau html
function display_array($arr,$arr_name='') {
	$txt = '<table cellspacing="2" cellpadding="2" border="0" style="font:11px tahoma;">';
	if ($arr_name) $txt .= "<tr><th colspan='2'>$arr_name</th></tr>";
	$txt .= "<tr><th style='background:#F9C;'>key</th><th style='background:#DDD;'>val</th></tr>";
	foreach ($arr as $k => $v) {
		$txt .= "
			<tr>
				<td style='background:#F9C;'><strong>$k</strong></td>
				<td style='background:#DDD;'>$v</td>
			</tr>
			";
	}
	$txt .= "\n</table>";

	return $txt;
}

// cette fonction affiche un tableau associatif à 2 dimensions dans un tableau html
function display_2Darray($arr,$arr_name='') {
	$nb_cols = count($arr[0]);
	$txt = '<table cellspacing="2" cellpadding="2" border="0" style="font:11px tahoma;">';
	if ($arr_name) $txt .= "<tr style='background:#9CF;'><th colspan='$nb_cols'>$arr_name</th></tr>";
	// header of the table
	$txt .= "\n	<tr style='background:#9CF;'>";
	foreach ($arr[0] as $k => $v) {
		$txt .= "\n		<th>$k</th>";
	}
	$txt .= "\n	</tr>";
	// data
	foreach ($arr as $row) {
		$txt .= "\n <tr style='background:#DDD;'>";
		foreach ($row as $v)
			$txt .= "\n		<td>$v</td>";
		$txt .= "\n </tr>";
	}
	$txt .= "\n</table>";
	
	return $txt;
}

// une fonction pour trier un tableau à double dimension
function sort_2Darray($array, $colname) {
    $function_code = "
        if (\$a['$colname'] == \$b['$colname'])    return 0;
        return (\$a['$colname'] < \$b['$colname']) ? -1 : 1;
    ";

    return usort($array,create_function('$a,$b',$function_code));
}

// une fonction pour remplacer dans un string tous les :toto: par $arr['toto']
// attention, a ne pas utiliser cette fonction pour preparer une reqête SQL,
// car il n'y a pas les appels à mysql_escape...
function str_arr_replace($string, $arr) {
	if (is_array($arr)) {
		foreach ($arr as $key => $val) {
			$string = str_replace(":$key:",$val,$string);
		}
	} else {
		// if we have something else, we replace all ? by $arr
		$string = str_replace("?", $arr, $string);
	}
	return $string;
}


// password hash function: defined here so the algorythm is easy to replace
function pwd_hash($pwd) { return sha1($pwd); }


// filter that displays euros nicely
// 26.2658 => 26<small>,27</small>&nbsp;€
function euros($dec) {
	$str = number_format($dec,2,',',' ');
	$str = str_replace(' ','&nbsp;',$str);
	$str = str_replace(',','<small>,',$str).'</small>&nbsp;€';
	return $str;
}


