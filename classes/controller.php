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
}
?>