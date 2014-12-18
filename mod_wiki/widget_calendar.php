<?php

// calendar

$months = array("","Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre");
$short_months = array("","Jan.","Fév.","Mars","Avr.","Mai","Juin","Jui.","Août","Sept.","Oct.","Nov.","Déc.");



if (!function_exists('calendar')) {
	function calendar($y,$m) {
		global $db,$conf,$months,$short_months,$user,$wiki;
	
		$html = "<table class='cal'>";
	
		$total_jours = date('t',mktime(0,0,0,$m,1,$y));
		
		// we look for days that have a post
		$active_days = array();
		$sql = "
			select distinct substring(page_date,9) as jour from pages
			where page_date like '$y-$m-%'
				";
		$get_active_days = $db->getall($sql);
		if ($get_active_days)
			foreach ($get_active_days as $gad)
				$active_days[] = intval($gad['jour']);
	
		// affiche les jours de la semaine
		$html .= '<tr class="day_names">';
		$jours_semaine = array('Lu','Ma','Me','Je','Ve','Sa','Di');
		while (list($k,$v) = each($jours_semaine)) {
			$html .= "<td>$v</td>";
		}
		$html .= '</tr><tr>';
	
		// aligne le premier jour du mois avec le jour correspondant de la semaine
		$decalage_jour = date("w",mktime(0,0,0,$m,1,$y))-1;
		if ($decalage_jour==-1) $decalage_jour=6;
		if ($decalage_jour > 0) {
			for ($i=0; $i<$decalage_jour; $i++)
				$html .= "<td class='empty'>&nbsp;</td>";
		}
		$hier = time() - 86400;
	
		// affiche les jours
		if (($m==date('m')) and ($y==date('Y'))) {
			$aujourdhui = date('j');
		} else {
			// on est pas dans le bon mois
			$aujourdhui = 0;
		}
		for ($jour=1; $jour <= $total_jours; $jour++) {
			$jour_secs = mktime(0,0,0,$m,$jour,$y);
			if (in_array($jour,$active_days)) {
				if ($jour<10) $d = '0'.$jour; else $d = $jour;
				if ($jour_secs >= $hier) {
					if ($jour == $aujourdhui) {
						$html .= "<td class='today'><a href='{$wiki['url']}archive/$y/$m/$d'>$jour</a></td>";
					} else {
						$html .= "<td class='futur'><a href='{$wiki['url']}archive/$y/$m/$d'>$jour</a></td>";
					}
				} else {
					$html .= "<td class='past'><a href='{$wiki['url']}archive/$y/$m/$d'>$jour</a></td>";
				}
			} else {
				if ($jour_secs >= $hier) {
					if ($jour == $aujourdhui) {
						$html .= "<td class='today'>$jour</td>";
					} else {
						$html .= "<td class='futur'>$jour</td>";
					}
				} else {
					$html .= "<td class='past'>$jour</td>";
				}
			}
			$decalage_jour++;
		
			// demarre une nouvelle ligne chaque semaine
			if ($decalage_jour == 7) {
				$decalage_jour = 0;
				$html .= "</tr>\n";
				if ($jour < $total_jours)
					$html .= "<tr>\n";
			}
		}

		// comble la derniere semaine avec des espaces blancs
		if ($decalage_jour > 0)
			$decalage_jour = 7 - $decalage_jour;
		if ($decalage_jour > 0) {
			for ($i=0; $i<$decalage_jour; $i++)
				$html .= "<td class='empty'>&nbsp;</td>";
			$html .= "</tr>";
		}
		
		// circulation sur les mois prec / suiv
		$mois_prec = mktime(0,0,0,$m,-1,$y);
		$mois_suiv = mktime(0,0,0,$m,32,$y);
		$html .= "<tr class='months'>";
		// check if wiki has pages before now
		$sql = "select id from pages
			where page_date < '$y-$m-01'
				and page_date > '0000-00-00'";
//		$db->debug = 1;
		$check_prev = $db->getone($sql);
		if ($check_prev) {
			$html .= "<td colspan='3'><a href='{$wiki['url']}archive/".date('Y',$mois_prec)."/".date('m',$mois_prec)."'>&lt;&lt; ".$short_months[date('n',$mois_prec)]."</a></td>";
		} else {
			$html .= "<td colspan='3'>&nbsp;</a></td>";
		}
		$html .= "<td></td>";
		// check if wiki has pages next month
		$sql = "select id from pages
			where page_date > '".date('Y-m',$mois_suiv)."-00'";
//		$db->debug = 1;
		$check_next = $db->getone($sql);
		if ($check_next) {
			$html .= "<td colspan='3'><a href='{$wiki['url']}archive/".date('Y',$mois_suiv)."/".date('m',$mois_suiv)."'>".$short_months[date('n',$mois_suiv)]." &gt;&gt;</a></td>";
		} else {
			$html .= "<td colspan='3'>&nbsp;</a></td>";
		}

/*		if ($y<date('Y') OR (($y==date('Y')) AND ($m<date('m')))) {
			$html .= "<td colspan='3'><a href='{$wiki['url']}archive/".date('Y',$mois_suiv)."/".date('m',$mois_suiv)."'>".$short_months[date('n',$mois_suiv)]." &gt;&gt;</a></td>";
		} else {
			$html .= "<td colspan='3'>&nbsp;</td>";
		}
*/
		$html .= "</tr>";
		$html .= "</table>";

		return $html;
	}
}


if ($page['cal_year']) {
	$y = $page['cal_year'];
} else {
	$y = date('Y');
}
if ($page['cal_month']) {
	$m = $page['cal_month'];
} else {
	$m = date('m');
}

$widget = array();

if ($w['title']='&nbsp;') {
	$mois = date("F Y",mktime(0,0,0,$m,1,$y));
	$widget['title'] = $months[date("n",mktime(0,0,0,$m,1,$y))].' '.date("Y",mktime(0,0,0,$m,1,$y));
}
$widget['calendar'] = calendar($y,$m);
// echo '<pre>';var_dump($w);exit;
$smarty->assign('widget',$widget);
$box = $smarty->fetch($conf['ffw']."mod_wiki/views/widget_calendar.html");


if ($mod['widgets_position'] == 'before')
	$page['column'] = $box . $page['column'];
else
	$page['column'] .= $box;

