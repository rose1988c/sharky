<?php

class LoginRequiredFilter extends DefaultFilter {
    public function before($args) {
        global $auth;
        $auth->require_login();
    }
}
