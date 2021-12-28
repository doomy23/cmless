<?php

class Auth {

    private $current_user = null;

    /**
     * Check in the session and get the User object
     */
    public function get_current_user() {
        if($this->current_user !== null):
            return $this->current_user;
        elseif(isset($_SESSION['cmless_user_id']) && isset($_SESSION['cmless_user_email'])):
            try {
                $user = User::objects()->get(array('id'=>$_SESSION['cmless_user_id'], 'email'=>$_SESSION['cmless_user_email']));
                $this->current_user = $user;
                return $user;
            }
            catch(ModelNotFoundQueryException $e) {
                unset($_SESSION['cmless_user_id']);
                unset($_SESSION['cmless_user_email']);
            }
        endif;

        return null;
    }

    /**
     * Set the session and the current user
     */
    public function login(User $user) {
        $_SESSION['cmless_user_id'] = $user->id;
        $_SESSION['cmless_user_email'] = $user->email;
        $this->current_user = $user;
        // Update last_login
        $datetime = new DateTime('NOW', new DateTimeZone(Cmless::$config['datetime']['save_as']));
		$user->last_login = $datetime->format('Y-m-d H:i:s');
        $user->save();
    }

    /**
     * Logout of the user and reset the current user
     */
    public function logout() {
        unset($_SESSION['cmless_user_id']);
        unset($_SESSION['cmless_user_email']);
        $this->current_user = null;
    }

    /**
     * Check if the user is logged in and has the right to see the page
     */
    public function verify($admin=false) {
        $user = $this->get_current_user();
        if($user === null):
            Cmless::getInstance()->Http403();
        elseif($admin && !$user->admin):
            Cmless::getInstance()->Http403();
        elseif($user->banned):
            Cmless::getInstance()->Http403();
        endif;
    }

}

?>