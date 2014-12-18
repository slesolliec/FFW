<?php

if ($mod['widgets_position'] == 'before')
	$page['column'] = $smarty->fetch($conf['ffw']."mod_wiki/views/widget_search.html") . $page['column'];

else
	$page['column'] .= $smarty->fetch($conf['ffw']."mod_wiki/views/widget_search.html");

