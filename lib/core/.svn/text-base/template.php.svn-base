<?php

class Template {
	private $vars; /// Holds all the template variables
	private $path; /// Path to the templates
    private $decorator = 'layout.tpl.php';
    private $buffer_var;

    private $javascripts = array();
    private $stylesheets = array();

	/**
	 * Constructor
	 *
	 * @param string $path the path to the templates
	 *
	 * @return void
	 */
	public function __construct($path = null) {
        if ($path == null) {
            $this->path = DOC_ROOT . '/templates/';
        } else {
            $this->path = $path;
        }
		$this->vars = array();
	}

    public function set_decorator($decorator) {
        $this->decorator = $decorator;
    }

	/**
	 * 设置模板变量
	 *
	 * @param string $name 变量名
	 * @param mixed $value 变量值
	 *
	 * @return void
	 */
	public function set($name, $value) {
		$this->vars[$name] = $value;
	}

	public function add($name, $value) {
		if (isset($this->vars[$name])) {
			$value = $this->vars[$name];
			if (!is_array($value)) {
				$this->vars[$name] = array($value);
			}
		} else {
			$this->vars[$name] = array();
		}
		$this->vars[$name][] = $value;
	}

    public function add_javascript($path, $group = 'global') {
        if (!isset($this->javascripts[$group]))
            $this->javascripts[$group] = array();
        $this->javascripts[$group][] = $path;
    }

    public function add_stylesheet($path, $group = 'global') {
        if (!isset($this->stylesheets[$group]))
            $this->stylesheets[$group] = array();
        $this->stylesheets[$group][] = $path;
    }

	/**
	 * 一次性设置多个变量
	 *
	 * @param array $vars array of vars to set
	 * @param bool $clear whether to completely overwrite the existing vars
	 *
	 * @return void
	 */
	public function set_vars($vars, $clear = false) {
		if ($clear) {
			$this->vars = $vars;
		}
		else {
			if (is_array($vars)) $this->vars = array_merge($this->vars, $vars);
		}
	}

	/**
	 * Open, parse, and return the template file.
	 *
	 * @param string string the template file name
	 *
	 * @return string
	 */
	public function fetch($file) {
		extract($this->vars);          // Extract the vars to local namespace
		ob_start();                    // Start output buffering
        try {
            include($this->path . $file);  // Include the file
            $contents = ob_get_contents(); // Get the contents of the buffer
            ob_end_clean();                // End buffering and discard
        } catch (Exception $e) {
            $contents = ob_get_contents();
            ob_end_clean();
            if (config_get('devmode', false)) {
                ob_start();
                debug_print_backtrace();
                $contents .= ob_get_contents();
                ob_end_clean();
            } else {
                throw $e;
            }
        }
		return $contents;              // Return the contents
	}

    public function start_buffer($var) {
        if (!is_null($this->buffer_var)) {
            return false;
        }
        $this->buffer_var = $var;
        ob_start();
    }

    public function flush_buffer($var = null) {
        if (is_null($var)) $var = $this->buffer_var;
        $contents = ob_get_contents();
        ob_end_clean();
        $this->set($var, $contents);
        $this->buffer_var = null;
        return $contents;
    }

    public function render($template, $vars = null, $decorator = null, $https = false) {
        $this->set('tpl', $this);
        if (!is_null($vars)) $this->set_vars($vars);
        if (is_null($decorator))
            $decorator = $this->decorator;

        $body = $this->fetch($template);
        if ($decorator === false) {
			if ($https) {
				$tohttps = array('http://' => 'https://');
				$body = strtr($body, $tohttps);
			}
            echo $body;
        } else {
            $this->set('body', $body);
            echo $this->fetch($decorator);
        }
    } 

    public function output_stylesheets($indent = 1) {
        $devmode = config_get('devmode', false);

        $files = array();

        if (isset($this->stylesheets['global']))
            $files = array_merge($files, $this->stylesheets['global']);    

        foreach ($this->stylesheets as $key => $value)
            if ($key != 'global') $files = array_merge($files, $value);

        $spaces = str_repeat(' ', $indent * 4);
        $today  = floor(time() / (60 * 60 * 24)) . '000';
        

        $css_base = ($devmode ? '/_src/css/' : '/css/');

        foreach ($files as $file) {
            $version = cache_get($file, 'revision');
            $version = false;
            if ($version === false) {
                $version = $today;
                cache_set($file, $version, 'revision');
            }
            $path = $css_base . $file;
            echo "{$spaces}<link rel=\"stylesheet\" type=\"text/css\" href=\"{$path}?{$version}.css\" media=\"screen\" />\n";
        }
    }

    public function output_javascripts($indent = 1) {
        $devmode = config_get('devmode', false);

        $files = array();

        if ($devmode) {
            if (isset($this->javascripts['global']))
                $files = array_merge($files, $this->javascripts['global']);    
            if (isset($this->javascripts['stock'])) 
            	$files[] = 'stock.js';
            foreach ($this->javascripts as $key => $value)
                if ($key != 'global' and $key != 'stock') $files = array_merge($files, $value);
            
        }
        else {
            if (isset($this->javascripts['global']))
                $files[] = 'global.js';
            if (isset($this->javascripts['stock'])) 
            	$files[] = 'stock.js';
            foreach ($this->javascripts as $key => $value)
                if ($key != 'global' and $key != 'stock') $files[] = $key . '.js';
        }

        $spaces = str_repeat(' ', $indent * 4);
        $today  = floor(time() / (60 * 60 * 24)) . '000';

        $js_base = ($devmode ? '/_src/js/' : '/js/');

        foreach ($files as $file) {
            $version = cache_get($file, 'revision');
            $version = false;
            if ($version === false) {
                $version = $today;
                cache_set($file, $version, 'revision');
            }
            $path = $file[0] == '/' ? $file : ($js_base . $file);
            echo "{$spaces}<script type=\"text/javascript\" src=\"{$path}?{$version}.js\" charset=\"utf-8\"></script>\n";
        }
    }
}
