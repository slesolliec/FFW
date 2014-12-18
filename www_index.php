<?php

if ($_SERVER["HTTP_HOST"] == 'cheffes.local') {
	// local configs
	$spyc_path = '../libphp/spyc-0.2.5/spyc.php';
	$config_file = 'mods/config.yml';
} else {
	// server configs
	// path to spyc
	$spyc_path = '/home/metacite/libphp/spyc-0.2.5/spyc.php';
	// path to config file
	$config_file = 'mods/config.yml';
}

// simply include FFW
include('../FFW/FFW.php');

?>
