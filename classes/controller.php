<?php
class Controller {
	protected $db;
	
	function __construct() {
		$f3 = Base::instance();
		$db = new DB\SQL(
			$f3->get('dbdsn'),
			$f3->get('dbuser'),
			$f3->get('dbpassword')
		);
		
		$this->db = $db;
	}
	
	protected function addToCsvUnique($string, $csv) {
		$str_clean = str_replace(";", ",", $string);
		if (!isset($string) || empty($string)) {
			return $csv;
		}
		else if (!isset($csv) || empty($csv)) {
			return $str_clean;
		}
		else {
			$cols = explode(";", $csv);
			if (!in_array($str_clean, $cols))	return $csv . ";$str_clean";
			else return $csv;
		}
	}
}
?>