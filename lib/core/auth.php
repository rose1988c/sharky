<?php

$GLOBALS['Users'] = new DBTable('Users', 'sharky_users', array(
        'user_id'     => array('type' => 'long', 'primary' => true, 'autoincrement' => true),
        'username'    => array('type' => 'string'),
        'password'    => array('type' => 'string'),
        'email'       => array('type' => 'string'),
        'roles'       => array('type' => 'string'),
    ));

$GLOBALS['UserTokens'] = new DBTable('UserTokens', 'sharky_user_tokens', array(
        'user_id'     => array('type' => 'long', 'primary' => true),
        'token'       => array('type' => 'string', 'primary' => true),
        'login_ip'    => array('type' => 'long'),
        'created_at'  => array('type' => 'datetime'),
        'expire_date' => array('type' => 'datetime'),
        'token_type'  => array('type' => 'string', 'default' => 'auth'),
    ));


class Auth {
    // Singleton object. Leave $me alone.
    private static $me;

    public $id;
    public $username;
    public $roles = array(); // Admin, User, etc.
    public $user;            // DBObject User class if available

    private $auth_by_email = false;

    private $logged_in = false;

    // Call with no arguments to attempt to restore a previous logged in session
    // which then falls back to a guest user (which can then be logged in using
    // $this->login($username, $password). Or pass a user_id to simply login that user. The
    // $seriously is just a safeguard to be certain you really do want to blindly
    // login a user. Set it to true.
    public function __construct($auth_by_email = false) {
        $this->id             = null;
        $this->username       = null;
        $this->user           = null;
        $this->roles          = array('guest');

        $this->auth_by_email  = $auth_by_email;
        $this->logged_in      = false;
        
        if ($this->attempt_session_login())
            return true;

        if ($this->attempt_cookie_login())
            return true;

        return false;
    }


    // Get Singleton object
    public static function get_instance($user_to_impersonate = null, $auth_by_email = false) {
        if (is_null(self::$me))
            self::$me = new Auth($user_to_impersonate, $auth_by_email);
        return self::$me;
    }

    public function is_logged_in() {
        return $this->logged_in;
    }

    public function login($username, $password, $rememberme = 0) {
    	
        $password = $this->create_hashed_password($password);
        return $this->attempt_login($username, $password, $rememberme);
    }

    public function logout($request_only = false) {
        $user_id = $this->id;
        $this->id        = null;
        $this->username  = null;
        $this->roles     = array('guest');
        $this->user      = null;
        $this->logged_in = false;

        if ($request_only === false) {
            unset($_SESSION['user_id']);
            unset($_SESSION['user_token']);

            
            $params = session_get_cookie_params();
            // 再干掉保存session id的cookie
            setcookie(session_name(), session_id(), 1,
            		$params['path'], $params['domain'],
            		$params['secure'], $params['httponly']);
            
            setcookie('s', '', 1, '/', config_get('cookie_domain'));
            session_destroy(); // 消灭掉所有Session里的变量

            if ($user_id) {
                $tokens = get_table('UserTokens')->fetch(array('user_id' => $user_id));
                foreach ($tokens as $token) {
                    $token->delete();
                }
            }
        }
    }
    
    public function kickuser($user) {
    	$this->require_manage();
    	$user_id = is_object($user) ? $user->user_id : $user;
    	if ($user_id) {
            $tokens = get_table('UserTokens')->fetch(array('user_id' => $user_id));
            foreach ($tokens as $token) {
                $token->delete();
            }
        }
    }

    // Assumes you have already checked for duplicate usernames
    public function change_username($new_username) {
        $this->user->username = $new_username;
        $this->user->update();

        $this->impersonate($this->id);
    }

    public function change_password($new_password) {
        $new_password = $this->create_hashed_password($new_password);

        $this->user->password = $new_password;
        $this->user->update();

        $this->impersonate($this->id);
    }

    // Helper function that redirects away from 'admin only' pages
    public function require_admin($url = null) {
        return $this->require_role('admin', $url);
    }

    public function is_user_in_role($role) {
        return $this->is_logged_in() && in_array($role, $this->roles);
    }

    public function is_admin() {
        return $this->is_user_in_role('admin');
    }
    
	public function is_editor() {        
		return $this->is_user_in_role('editor');   
	}
    
    public function is_manage() {
        return $this->is_admin() || $this->is_user_manager() || $this->is_user_cs();
    }
    
    public function is_user_admin() {
        return $this->is_user_in_role(USER_ADMIN);
    }
    
    public function is_user_cs() {
        return $this->is_user_in_role(USER_CS);
    }
    
    public function is_user_manager() {
        return $this->is_user_in_role(USER_MANAGER);
    }
    
    /**
     * 该方法对普通管理员进行限制
     */
    public function is_super_manager () {
            
        $super_roles = array (
                        USER_MANAGER,
                        USER_ADMIN,
        );
        $is_super = false ;
        foreach ($this->roles as $role) {
            if(in_array($role, $super_roles)) {
	    		$is_super = true;
	    		break;
    	    }   
        }
	    return $this->is_logged_in() && $is_super;
    } 
    
    public function is_vip_user() {
    	
    	$vip_roles = array(
					USER_ACE, 		//炒股高手
					USER_ADVISER, 	//投资顾问
					USER_ANALYST,	//证券分析师
		        	USER_MINGREN 	//名人堂高手
        );
        $is_vip = false;
        foreach ($this->roles as $role) {
	    	if(in_array($role, $vip_roles)) {
	    		$is_vip = true;
	    		break;
	    	}    	
        }
        return $is_vip;
    }

    public function require_role($role, $url = null) {
        if (is_null($url))
            $url = WEB_ROOT . '/account/login/';

        if (!$this->is_user_in_role($role)) {
            $_SESSION['redirect_to'] = full_request_url();
            redirect($url);
        }
    }

    // Helper function that redirects away from 'member only' pages
    public function require_login($url = null, $from = null) {
        if (is_null($url))
            $url = WEB_ROOT . '/account/login/';

        if (!$this->is_logged_in()) {
        	if (is_null($from)) $from = full_request_url();
            $_SESSION['redirect_to'] = $from;
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            redirect($url);
        }
        
        if($this->is_user_in_role(USER_DISABLED)) {
            $this->logout();
            throw new UserBlockedException("您的帐号已被管理员锁定", 403);
        }
        return $this->id;
    }
    
    public function require_mlogin($url = '/account/mlogin/', $from = null) {
    	if (is_null($from)) {
    		$from = param_string($_GET, 'from', false, null);
    	}
		$from = urldecode($from);
    	return $this->require_login($url, $from);
    }
    
    public function require_manage($url = null) {
    	global $auth;
    	
        if (is_null($url))
            $url = WEB_ROOT . '/manage/login/';

        if (!$this->is_manage()) {
            $_SESSION['redirect_to'] = full_request_url();
            redirect($url);
        } else { 
        	$ikey = substr($auth->user->password,8);
        	$rand = isset($_SESSION['ikeyrand']) ? $_SESSION['ikeyrand'] : '';
        	$result = md5_hmac($ikey, $rand);
        	$digest = isset($_SESSION['ikeydigest']) ? $_SESSION['ikeydigest'] : '';
            if($result != $digest) {
		 		redirect($url);
            } 
        }
        
        return $this->id;
    }

    // Login a user simply by passing in their username or id. Does
    // not check against a password. Useful for allowing an admin user
    // to temporarily login as a standard user for troubleshooting.
    // Takes an id or username
    public function impersonate($user_to_impersonate) {
        $user = get_table('Users')->load($user_to_impersonate);

        if ($user !== false) {
            if ($this->auth_by_email)
                return $this->attempt_login($user->email, $user->password, 0);
            else
                return $this->attempt_login($user->username, $user->password, 0);
        }

        return false;
    }

    // Attempt to login using data stored in the current session
    private function attempt_session_login() {
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_token'])) {
            return $this->attempt_token_login($_SESSION['user_id'], $_SESSION['user_token'], false);
        }
        return false;
    }

    // Attempt to login using data stored in a cookie
    private function attempt_cookie_login() {
        if (isset($_COOKIE['s']) && is_string($_COOKIE['s'])) {
            @list($user_id, $token) = explode('|', base64_decode($_COOKIE['s']));
            if (isset($user_id) && isset($token)) {
                $token = $this->attempt_token_login($user_id, $token);
                if ($token) {
                    $this->save_login($token);
                    return true;
                }
            }
        }

        return false;
    }

    private function attempt_token_login($user_id, $user_token, $check_expire = true) {
        $token = get_table('UserTokens')->load($user_id, $user_token);

        if (($token !== false) && ($token->token_type == 'auth') && (!$check_expire || $token->expire_date > time())) {
            $user = get_table('Users')->load($token->user_id);

            $this->id        = $user->user_id;
            $this->username  = $user->username;
            $this->user      = $user;
            $this->roles     = explode(',', $user->roles);
            $this->logged_in = true;

            $this->store_session_data($token);
            return $token;
        }

        return false;
    }


    // The function that actually verifies an attempted login and
    // processes it if successful.
    // Takes a username and a *hashed* password
    private function attempt_login($username, $password, $rememberme) {
        if ($this->auth_by_email)
            $user = get_table('Users')->fetch_one(array('email' => $username));
        else
            $user = get_table('Users')->fetch_one(array('username' => $username));
        if ($user === false || $password != $user->password) return false;
        
        $this->id       = $user->user_id;
        $this->username = $user->username;
        $this->user     = $user;
        $this->roles    = explode(',', $user->roles);

        $token = $this->new_login_token($user->user_id);

        $this->store_session_data($token, $rememberme);
        $this->logged_in = true;

        $this->save_login($token);
        return true;
    }

    public function new_login_token($user_id) {
        $token = get_table('UserTokens')->new_object(array(
            'user_id'     => $user_id,
            'token'       => md5(uniqid(mt_rand(), true)),
            'login_ip'    => get_remote_addr(true),
            'created_at'  => time(),
            'expire_date' => time() + 3600 * 24 * 60,
            'token_type'  => 'auth',
        ));

        $token->insert();
        return $token;
    }

    private function save_login($token) {
        $table = get_table('UserLogins');
        if (is_null($table)) return false;

        $login = $table->new_object(array(
                'user_id'  => $token->user_id,
                'login_ip'       => get_remote_addr(true),
                'login_at' => time(),
            ));
        $login->insert();
    }

    private function store_session_data($token, $rememberme = 0) {
        if (!isset($_SESSION['user_token']) || $_SESSION['user_token'] != $token->token) {
            $_SESSION['user_id']    = $token->user_id;
            $_SESSION['user_token'] = $token->token;
        }

        if ($rememberme > 0) {
            if (headers_sent()) return false;
            $s = base64_encode($token->user_id . '|' . $token->token);
            setcookie('s', $s, time() + $rememberme, '/', config_get('cookie_domain'));
        }
    }

    public static function create_hashed_password($password) {
        return sha1($password . config_get('auth_salt', ''));
    }
}
