<?php
// $settings_link = '<a href="options-general.php?page=xb_nmc_config">Settings</a>';
function paginate($reload, $page, $tpages) {
	$adjacents = 2;
	$prevlabel = "&lsaquo; Prev ";
	$nextlabel = " Next &rsaquo;";
	$out = "";
	// previous
	if ($page == 1) {
		// $out.= "<tr><span style='font-size:11px'>".$prevlabel."</span></tr>";
		$out .= "<tr><span style='font-size:11px'>" . $prevlabel . "</span>&nbsp</tr>";
	} elseif ($page == 2) {
		$out .= "<tr><a href=\"" . $reload . "\">" . $prevlabel . "</a>&nbsp&nbsp</tr>";
	} else {
		$out .= "<tr><a href=\"" . $reload . "&amp;curpage=" . ($page - 1) . "\">" . $prevlabel . "</a>&nbsp</tr>";
	}
	$pmin = ($page > $adjacents) ? ($page - $adjacents) : 1;
	$pmax = ($page < ($tpages - $adjacents)) ? ($page + $adjacents) : $tpages;
	for($i = $pmin; $i <= $pmax; $i ++) {
		if ($i == $page) {
			$out .= "<tr class=\"active\">" . $i . "&nbsp</tr>";
		} elseif ($i == 1) {
			$out .= "<tr><a href=\"" . $reload . "\">" . $i . "</a>&nbsp</tr>";
		} else {
			$out .= "<tr><a href=\"" . $reload . "&amp;curpage=" . $i . "\">" . $i . "</a>&nbsp</tr>";
		}
	}
	
	if ($page < ($tpages - $adjacents)) {
		$out .= "<tr><a style='font-size:11px' href=\"" . $reload . "&amp;curpage=" . $tpages . "\">" . $tpages . "</a>&nbsp</tr>";
	}
	// next
	if ($page < $tpages) {
		$out .= "<tr><a href=\"" . $reload . "&amp;curpage=" . ($page + 1) . "\">" . $nextlabel . "</a>&nbsp</tr>";
	} else {
		$out .= "&nbsp<tr><span style='font-size:11px'>" . $nextlabel . "</span></tr>";
	}
	$out .= "";
	return $out;
}
