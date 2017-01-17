<?php
function array_to_html_params($array) {
	$str = "";
	foreach($array as $key=>$val) {
		if($val)
			$str .= " " . $key . "=\"" . htmlentities($val, ENT_QUOTES) . "\"";
	}
	return $str;
}
?>