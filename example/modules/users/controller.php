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
			return Cmless::getInstance()->Redirect('defaultController.DefaultController.index');
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
					return Cmless::getInstance()->Redirect('defaultController.DefaultController.index');
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
		return Cmless::getInstance()->Redirect('defaultController.DefaultController.index');
	}

	public function account()
	{
		// Check if logged in or 403
		Cmless::Auth()->verify();
		// Get current user
		$user = Cmless::Auth()->get_current_user();
		$form = array(
			'error'=>array(),
			'message'=>null,
			'name'=>$user->name,
			'email'=>$user->email,
			'alert'=>"danger",
		);

		// Check if POST
		if(isset($_POST['email'])):
			CSRF::validate_token($_POST['csrf_token']);
			$form['name'] = htmlspecialchars(trim($_POST['name']));
            $form['email'] = htmlspecialchars(trim($_POST['email']));
            // Validate name, email
            if(strlen(trim($_POST['name'])) == 0 || strlen(trim($_POST['name'])) > 35):
                $form['error']['name'] = true;
                $form['message'] = "You must enter a valid name of maximum 35 characters.";
            elseif(strlen(trim($_POST['email'])) == 0 || strlen(trim($_POST['email'])) > 255 
                || !filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)):
                $form['error']['email'] = true;
                $form['message'] = "You must enter a valid email address.";
            endif;
            // Validate password if changed
            if(strlen(trim($_POST['password'])) > 0):
                if(strlen(trim($_POST['password'])) < 6 || strlen(trim($_POST['password'])) > 255 || 
                        !(preg_match('/[A-Za-z]/', trim($_POST['password'])) && preg_match('/[0-9]/', trim($_POST['password'])))):
                    $form['error']['password'] = true;
                    $form['error']['password2'] = true;
                    $form['message'] = "The password must be at least 6 characters and contain a numeric value.";
                elseif(trim($_POST['password']) != trim($_POST['password2'])):
                    $form['error']['password1'] = true;
                    $form['error']['password2'] = true;
                    $form['message'] = "The two passwords are not the same.";
                endif;
            endif;
            // If no error, saving changes
            if(!$form['message']):
                $user->name = htmlspecialchars(trim($_POST['name']));
                $user->email = htmlspecialchars(trim($_POST['email']));
                if(strlen(trim($_POST['password'])) > 0):
                    $user->password = password_hash(trim($_POST['password']), Cmless::$config['hashing']['algo']);
                endif;
                try{
                    $user->save();
                    $form['message'] = "Your account was updated with success.";
                    $form['alert'] = "success";
                }
                catch(Exception $e) {
                    $form['message'] = "An error occured during the update. Maybe the email entered is already in use by another account?";
                    $form['error']['email'] = true;
                }
            endif;
		endif;

		$csrf_token = CSRF::generate_token();
		return Cmless::Template()->render_file('users_account', 'Users/account.html', array(
			'form'=>$form, 
			'csrf_token'=>$csrf_token
		));
	}
	
}

?>