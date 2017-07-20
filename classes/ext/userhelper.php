<?php
class Userhelper {
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
	
	function getUserPlaylists($plids) {
		return $this->db->exec("SELECT * FROM playlists WHERE id IN VALUES($plids)");
	}
}
?>