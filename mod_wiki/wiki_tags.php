<?php

$page['title'] = 'Les tags (étiquettes) de votre wiki';
$page['content'] .= '<strong>La liste de tous les tags (étiquettes) utilisés sur votre wiki :</strong><br /><br />';

$tags = $db->getall('select tag,count(*) as size from tag_index group by tag order by tag');

if ($tags)
	foreach ($tags as $t)
		$page['content'] .= '&nbsp;<a href="'.$wiki['url'].urlencode(str_replace(' ','_',$t['tag'])).'" style="font-size:'.($t['size']+8).'px">'.$t['tag'].'</a>&nbsp; '."\n";
