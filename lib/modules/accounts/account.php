<?php

/**
 * 登录系统
 **/
function login() {/*{{{*/
    global $auth, $msg, $tpl;

    // Kick out user if already logged in.
    if ($auth->is_logged_in()) redirect(WEB_ROOT . '/');

    // Try to log in...
    if (!empty($_POST['username'])) {
        $username = param_string($_POST, 'username');
        $password = param_string($_POST, 'password');

        $auth->login($username, $password);

        if ($auth->is_logged_in()) {
            $url = WEB_ROOT . '/';
            if (isset($_SESSION['redirect_to'])) {
                $url = $_SESSION['redirect_to'];
                unset($_SESSION['redirect_to']);
            }
            redirect($url);
        }
        else
            $msg->add_error('对不起，用户名或密码不正确，请重试');
    }

    $username = isset($_POST['username']) ? $_POST['username'] : "";
    $username = htmlspecialchars($username);

    $tpl->render('accounts/login.tpl.php', $vars=array(
        'title'       => '登录',
        'username'    => $username
    ));
}/*}}}*/

function logout() {/*{{{*/
    global $auth;

	$auth->logout();

	redirect(WEB_ROOT . '/');
}/*}}}*/

function signup() {/*{{{*/
    global $auth, $tpl;

	// Kick out user if already logged in.
	if ($auth->is_logged_in()) redirect(WEB_ROOT . '/');

    $form = new SignupForm($_POST);

	// Try to register ...
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $privacy = isset($_POST['privacy']) ? $_POST['privacy'] : '';
        $form->add_raw('privacy', $privacy);

        // form validation
        if ($form->is_valid()) {
            // the data is valid.
            require_once(DOC_ROOT . '/lib/services/users.php');

            $user = mcc_signup_user($form->get_cleaned_data());

            $_SESSION['signup'] = true;

            // login
            $auth->impersonate($user->user_id);
            redirect(WEB_ROOT . '/');
        }
	}

    $tpl->render('accounts/signup.tpl.php', $vars=array(
        'title'   => '注册帐号',
        'form'    => $form
    ));
}/*}}}*/

class SignupForm extends Form {/*{{{*/
    function clean_username($key, $value) {
        $value = clean_string($value);
        $this->validate_blank($value, $key, '用户名');
        // 检查用户名是否已经存在
        if (!$this->has_error($key)) {
            $this->validate_regex($value, '/^[a-z0-9-]{4,32}$/', $key, '用户名只能包含4-32个小写字母、阿拉伯数字和减号');

            if (!$this->has_error($key)) {
                global $Users;
                if ($Users->fetch_one(array('username' => $value)))
                    $this->add_error($key, "用户名 {$value} 已经存在");
            }
        }
    }   

    function clean_password($key, $value) {
        $this->validate_blank($value, $key, '密码');
    }

    function clean_password2($key, $value) {
        $this->validate_blank($value, $key, '确认密码');
        if (!$this->has_error($key))
            $this->validate_passwords('password', $this->password, $value);
    }

    function clean_email($key, $value) {
        $this->validate_blank($value, $key, 'Email');
        if (!$this->has_error($key))
            $this->validate_email($value, $key);
    }

    function clean_privacy($key, $value) {
        if (trim($value) == '')
            $this->add_error($key, '您必须同意我们的用户服务协议');
    }
}/*}}}*/
// vim: fdm=marker
