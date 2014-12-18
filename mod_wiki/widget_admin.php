<?php

// admin widget
if ($user['is_admin']) {
	
	if (file_exists($conf['mods']."mod_wiki/views/widget_admin.html")) {
		$box = $smarty->fetch($conf['mods']."mod_wiki/views/widget_admin.html");
	} else {
		$box = $smarty->fetch($conf['ffw']."mod_wiki/views/widget_admin.html");
	}

	if ($mod['widgets_position'] == 'before')
		$page['column'] = $box . $page['column'];
	else
		$page['column'] .= $box;
}
