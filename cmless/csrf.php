<?php

class CSRF {

    public static function generate_token() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrfToken'] = $token;
        return $token;
    }

    public static function validate_token($token) {
        if(isset($_SESSION['csrfToken']) && $_SESSION['csrfToken'] != $token)
            Cmless::getInstance()->Http403();
    }

}

?>