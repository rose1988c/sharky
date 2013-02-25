<?php

function config_get($key, $default = null) {
    $cfg = Config::get_instance();
    $value = $cfg->get($key);
    if ($value !== false) {
        return $value;
    }
    return $default;
}

function config_set($key, $value) {
    $cfg = Config::get_instance();
    $cfg->set($key, $value);
}


/**
 * 配置信息类，根据HTTP_HOST加载不同的配置，方便开发和部署
 **/    
class Config {

    private static $instance;

    private $configs = array();

    // Singleton constructor
    private function __construct() {
        $this->load('common.php');

		if (isset($_SERVER['HTTP_HOST'])) {
        	$host = $_SERVER['HTTP_HOST'];

			if (preg_match($this->configs['hosts.production'], $host))
	            $this->load('production.php');
	        elseif (preg_match($this->configs['hosts.staging'], $host))
	            $this->load('staging.php');
	        elseif (preg_match($this->configs['hosts.local'], $host))
	            $this->load('local.php');
	        else
	        	die('<h1>Where am I?</h1> <p>You need to setup your server names in <code>conf/common.php</code></p>
	                 <p><code>$_SERVER[\'HTTP_HOST\']</code> reported <code>' . $host . '</code></p>');
	                
		} else {
			$this->load('client.php');
		}
    }

    // Get Singleton object
    public static function get_instance() {
        if (is_null(self::$instance))
            self::$instance = new Config();
        return self::$instance;
    }

    public function get($key) {
        if (array_key_exists($key, $this->configs))
            return $this->configs[$key];
        return false;
    }

    public function set($key, $value) {
        $this->configs[$key] = $value;
        return $value;
    }
    
    private function load($file) {
        $cfg = &$this->configs;
        include(DOC_ROOT . '/conf/' . $file);
    }
}
