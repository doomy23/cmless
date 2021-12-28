<?php

class UsersController extends Controller{
	
	/**
	 * Login page
	 */
	public function login()
	{
		// Check if already logged in
		$user = Cmless::Auth()->get_current_user();
		if($user): 
			// Redirect
			return Cmless::Redirect('defaultController.DefaultController.index');
		endif;
		$form = array();
		// Check if POST
		if(isset($_POST['email'])):
			// CSRF check
			CSRF::validate_token($_POST['csrf_token']);
			try {
				$user = User::objects()->get(array('email'=>trim($_POST['email'])));
				// Verifying user password
				// PS: To save a password: $user->password = password_hash(trim($_POST['password']), Cmless::$config['hashing']['algo']);
				if(password_verify(trim($_POST['password']), $user->password)):
					Cmless::Auth()->login($user);
					return Cmless::Redirect('defaultController.DefaultController.index');
				endif;
			}
			catch(ModelNotFoundQueryException $e) {}
			$form = array(
				'message'=>"Email or password is invalid.",
				'error'=>true,
				'email'=>htmlspecialchars(trim($_POST['email']))
			);
		endif;

		$csrf_token = CSRF::generate_token();
		return Cmless::Template()->render_file('users_login', 'Users/login.html', array(
			'form'=>$form, 
			'csrf_token'=>$csrf_token
		));
	}

	public function logout()
	{
		Cmless::Auth()->logout();
		return Cmless::Redirect('defaultController.DefaultController.index');
	}
	
}

?>