<?php
class User extends Controller {

	//Display login form
	function login($f3) {
		if (!$f3->exists('SESSION.uid')) {
			$img = new Image;
			$f3->set('captcha',$f3->base64(
				$img->captcha('fonts/eufoniem.ttf',18,5,'SESSION.captcha')->dump(),'image/png'));
			echo \Template::instance()->render('login.htm');
		}
		else {
			$f3->reroute('/');
		}
	}
	
	//Process login form
	function auth($f3) {
		$captcha = $f3->get('SESSION.captcha');
		if ($captcha && strtoupper($f3->get('POST.captcha')) != $captcha) {
			$f3->push('flash', (object)array(
				'lvl'	=>	'danger',
				'msg'	=>	'Invalid CAPTCHA code'));
		}
		else {
			$db = $this->db;
			$user = new DB\SQL\Mapper($db, 'users');
			$user->load(array('email=?', $f3->get('POST.email')));
			
			//Verify user exists and password matches
			if ($user->dry() || password_verify($f3->get('POST.password'), $user->password) !== true) {
				$f3->push('flash', (object)array(
					'lvl'	=>	'danger',
					'msg'	=>	'Invalid email or password'));
			}
			else {
				$f3->clear('SESSION.captcha');
				$f3->set('SESSION.uid', $user->id);
				$f3->set('SESSION.email', $user->email);
				$f3->reroute('/playlists');
			}
		}
		
		$this->login($f3);
	}
	
	//Logout and redirect to login
	function logout($f3) {
		$f3->clear('SESSION');
		$f3->reroute('/login');
	}
	
	//Show registration form
	function register($f3) {
		echo \Template::instance()->render('register.htm');
	}
	
	//Create new user
	function create($f3) {
		$db = $this->db;
		$user = new DB\SQL\Mapper($db, 'users');
		$user->load(array('email=?', $f3->get('POST.email')));
		
		//Verify user doesn't already exist, else create it
		if (!$user->dry()) {
			$f3->push('flash', (object)array(
				'lvl'	=>	'danger',
				'msg'	=>	'Email address already in use'));
		}
		else {
			$user->email = $f3->get('POST.email');
			$user->password = password_hash($f3->get('POST.password'), PASSWORD_DEFAULT);
			$user->save();
			
			$f3->push('flash', (object)array(
				'lvl'	=>	'success',
				'msg'	=>	'Account successfully created'));
		}
		
		$this->register($f3);
	}
}
?>