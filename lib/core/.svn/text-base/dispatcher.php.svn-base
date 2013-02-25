<?php
function dispatcher_action_route() {
    if (func_num_args() < 1)
        throw new Exception('Action name required');
    $args = func_get_args();
    $action_name = array_shift($args);

    if (function_exists('dispatcher_action_prefix'))
        $action_name = call_user_func('dispatcher_action_prefix') . $action_name;

    if (!function_exists($action_name))
		throw new Exception('页面不存在', 404);

    call_user_func_array($action_name, $args);
}

function dispatcher_render_template($template, $vars = null, $decorator = null) {
    global $tpl;
    $tpl->render($template, $vars, $decorator);
}

interface Filter {
    public function before($args);
    public function after($args);
}

class DefaultFilter {
    public function before($args) {}
    public function after($args) {}
}

class Dispatcher {

	private $mapping;
    private $module_dir; // module存放位置
	private $error_module;
	private $filters = array();
	private static $http_header_to_desc = array(
						100 => 'Continue',
						101 => 'Switching Protocols',
			
						200 => 'OK',
						201 => 'Created',
						202 => 'Accepted',
						203 => 'Non-Authoritative Information',
						204 => 'No Content',
						205 => 'Reset Content',
						206 => 'Partial Content',
			
						300 => 'Multiple Choices',
						301 => 'Moved Permanently',
						302 => 'Found',
						303 => 'See Other',
						304 => 'Not Modified',
						305 => 'Use Proxy',
						307 => 'Temporary Redirect',
			
						400 => 'Bad Request',
						401 => 'Unauthorized',
						403 => 'Forbidden',
						404 => 'Not Found',
						405 => 'Method Not Allowed',
						406 => 'Not Acceptable',
						407 => 'Proxy Authentication Required',
						408 => 'Request Timeout',
						409 => 'Conflict',
						410 => 'Gone',
						411 => 'Length Required',
						412 => 'Precondition Failed',
						413 => 'Request Entity Too Large',
						414 => 'Request-URI Too Long',
						415 => 'Unsupported Media Type',
						416 => 'Requested Range Not Satisfiable',
						417 => 'Expectation Failed',
			
						500 => 'Internal Server Error',
						501 => 'Not Implemented',
						502 => 'Bad Gateway',
						503 => 'Service Unavailable',
						504 => 'Gateway Timeout',
						505 => 'HTTP Version Not Supported'
					);

	public function __construct($mapping, $module_dir = null, $error_module = 'error_handler.php') {
		$this->mapping = $mapping;
		$this->error_module = $error_module;
        if (is_null($module_dir))
            $this->module_dir = DOC_ROOT . '/lib/modules/';
        else
            $this->module_dir = $module_dir;
	}

    public function register_filter($filter) {
        $this->filters[] = $filter;
    }

    public function set_error_handler($module) {
        $this->error_module = $module;
    }

	public function dispatch($uri) {
		$info = @parse_url($uri);
		$path = $info['path'];

 		try {
			header('Content-Type: text/html; charset=utf-8;');
			$this->do_dispatch($this->mapping, $path);
 		} catch (Exception $e) {
 			$this->handle_error($e);
 		}
	}

	private function do_dispatch($mappings, $path) {
		foreach ($mappings as $k => $v) {
            if (preg_match($this->prepare_url_pattern($k), $path, $matches))   {
				if (isset($v['include'])) {
					if (isset($v['decorator'])) {
						global $tpl;
						$tpl->set_decorator($v['decorator']);
					}
					return $this->includes($this->module_dir . $v['include'], $path);
				}

				$args = $this->prepare_args($matches, $v);

                $dispatcher = $this;
                if (isset($v['module'])) {
                    require_once($this->module_dir . $v['module']);
                }

                foreach ($this->filters as $filter) {
                    $filter->before($args);
				}
				
				// 调用view方法
				call_user_func_array($v['function'], $args);
                foreach ($this->filters as $filter) {
                    $filter->after($args);
				}

				return true;
			}
		}
		throw new Exception('页面不存在', 404);
	}

	function includes($file, $path) {
		require_once($file);   
		if (isset($urls))
			$this->do_dispatch($urls, $path);
	}

	private function handle_error($error, $uri = null) {
		$message = $error->getMessage();
		$code = $this->get_status_header_code($error);
		$msg = $this->get_status_header_desc($error);
		@header("HTTP/1.1 $code $msg");     
		$error_code = $code;
		$error_message = $message;
		include($this->module_dir . $this->error_module);
		exit();
	}

	private function prepare_url_pattern($reg) {
		return '/' . str_replace('/', '\/', $reg) . '/';
	}

	private function prepare_args($matches, $config) {
		$args = array();
		if (isset($config['arguments'])) {
			$defaults = isset($config['defaults']) ? $config['defaults'] : array();

			foreach($config['arguments'] as $key) {
				$value = null;

//				if (isset($matches[$key])) {
				if (isset($matches[$key]) && $matches[$key] != '') {
					$value = $matches[$key];
					if (!is_null($value))
						$value = @urldecode(trim($value));
				} 
				else if (array_key_exists($key, $defaults)) {
					$value = $defaults[$key];
				}
				$args[] = $value;
				$_GET[$key] = $value;
			}
		}
		return $args;
	}
	
	/**
	 * 
	 * 获取异常错误号
	 * @param object $e
	 */
	private static function get_status_header_code($e)
	{
		if( is_object($e) && method_exists($e, 'getCode') )
		{
			$code = $e->getCode();
		}
		if( !isset($code) || !in_array($code, array_keys(self::$http_header_to_desc)) )
		{
			$code = '500';
		}
		return $code;
	}
	
	/**
	 * 
	 * 获取错误号描述
	 * @param object $e
	 */
	private static function get_status_header_desc($e) 
	{
		$code = self::get_status_header_code($e);
		if( isset(self::$http_header_to_desc[$code]) )
		{
			return self::$http_header_to_desc[$code];
		}
		else 
		{
			return '';
		}
	}
}
