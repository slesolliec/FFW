<?php

// ce module ne fait que lister les tables de la base de données

// restriction d'accès
if (!$user['is_admin'])
	stop("Il faut être administrateur pour accéder à ce module","Accès restreint aux Admins");

if ($action == 'default') {
	$page['title'] = "Liste des tables de la base de ".$conf['title'];
	$smarty->assign('dbs',$dbs);
	$page['content'] = $smarty->fetch($conf['ffw']."mod_table/views/default.html");
} else {
	stop("Action non trouvée","Erreur 404 : action non trouvée",404);
}


