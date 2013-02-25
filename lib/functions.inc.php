<?php
    /**
     * 获得所有模块的名称以及链接地址
     *
     * @access      public
     * @param       string      $directory      插件存放的目录
     * @return      array
     * @author      bill 
     * @date        2009-09-08
     */
    function read_modules($directory = '.') 
    {
        $dir         = @opendir($directory);
        $set_modules = true;
        $modules     = array();

        while (false !== ($file = @readdir($dir)))
        {
            if (preg_match("/^.*?\.php$/", $file))
            {
                include_once($directory. '/' .$file);
            }
        }
        @closedir($dir);
        unset($set_modules);

        foreach ($modules as $key => $value)
        {
            ksort($modules[$key]);
        }
        ksort($modules);

        return $modules;
    }
    
    
    function add_include_path($path) {
        set_include_path(get_include_path() . PATH_SEPARATOR . $path); 
    }
    
    function _require($file)
    {
    	static $loaded = array();
    	if (in_array($file, $loaded)) return;
    	$loaded[] = $file;
    	return require($file);
    }

    // Class Autloader
    function __autoload($class_name) {
        global $autoloads;
        if (isset($autoloads) && isset($autoloads[$class_name])) {
            require($autoloads[$class_name]);
        }
        if (strpos($class_name, '\\') !== false) {
	        require(str_replace('\\', '/', $class_name) . '.php');
        } else {
	        require(str_replace('_', '/', strtolower($class_name)) . '.php');
        }
    }

    // Fixes MAGIC_QUOTES
    function fix_slashes($arr = '') {
        if (is_null($arr) || $arr == '') return null;
        if (!get_magic_quotes_gpc()) return $arr;
        return is_array($arr) ? array_map('fix_slashes', $arr) : stripslashes($arr);
    } 

    function join_path() {
        $args  = func_get_args();
        $len = count($args);
        if ($len == 1) {
            return $args[0];
        }
        $paths = array();
        foreach ($args as $arg) {
            $paths = array_merge($paths, (array) $arg);
        }
        foreach ($paths as $idx => &$path) {
            if ($idx == 0)
                $path = rtrim($path, '/');
            else
                $path = trim($path, '/');
        }
        return join('/', $paths);
    }
        
    function require_file($filename) {
        require($filename);

        $vars = get_defined_vars();
        foreach ($vars as $key => $value) {
            if ($key != 'filename')
                $GLOBALS[$key] = $value;
        }
    }

    /**
     * 一次性包含整个目录里的PHP代码，用于包含框架基础代码（几乎每个请求都必须用到的代码）.
     * 为了提高性能，我把所有需要包含的代码合并成了一个文件，并进行了优化(php_strip_whitespace).
     * 当源文件更新时，会自动重新合并文件。
     * 结合APC、XCache等byte code缓存的适用可以极大提高性能。
     **/
    function require_path($dir, $files = null) {/*{{{*/
        if (!file_exists($dir)) die("目录{$dir}不存在");

        if (is_null($files)) {
            $files = glob($dir . '/*.php');
        } else {
            $tmp = array();
            foreach ($files as $name) {
                $tmp[] = join_path($dir, $name);
            }
            $files = $tmp;
        }

        if (!defined('OPTIMIZE_REQUIRE_DIR') || !OPTIMIZE_REQUIRE_DIR) {
            // 不用合并文件，一个个加载
            foreach ($files as $filename) {
                require_file($filename);
            }
            return true;
        }

        $scripts_cache_dir = CACHE_DIR . '/phpscripts';

        if (!file_exists($scripts_cache_dir)) {
            mkdir($scripts_cache_dir, 0777, true);    
        }

        $cached_file = $scripts_cache_dir . '/' . md5($dir) . '.php';

        if (file_exists($cached_file) && filemtime($cached_file) > filemtime($dir)) {
            // 文件已经合并，并且源文件目录没有更新，加载合并的文件
            require_file($cached_file);
            return true;
        }

        // 那么我们来合并目录里的php文件
        $lock = fopen($scripts_cache_dir . '/scripts_optimzer.lock', 'w');

        if (!flock($lock, LOCK_EX | LOCK_NB)) { // do an exclusive lock
            // 已经有其他进程在合并了，我们先直接加载源文件
            foreach ($files as $filename) {
                require_file($filename);
            }
            return true;
        }

        $fd = fopen($cached_file, 'w') or die("文件{$cached_file}打不开");
        fwrite($fd, "<?php\n");
        
        // compile files into one file and cache it
        foreach ($files as $filename) {
            $lines = file($filename);
            $lines[0] = '';
            fwrite($fd, implode('', $lines));
        }
        fclose($fd);

        // 优化，去掉注释什么的
        $stripped = php_strip_whitespace($cached_file);
        $fd = fopen($cached_file, 'w');
        fwrite($fd, $stripped);
        fclose($fd);

        flock($lock, LOCK_UN); // release the lock
        fclose($lock);

        require_file($cached_file);
        return true;
    }/*}}}*/


    /************************************************************************************
     * array functions
     ************************************************************************************/
    /*{{{1*/
    function array_get($array, $key, $default = null) {
        if (isset($array[$key]))
            return $array[$key];
        return $default;
    }

    function array_flatten($array) {
        $flat = array();

        foreach ($array as $value) {
            if (is_array($value))
                $flat = array_merge($flat, array_flatten($value));
            else 
                $flat[] = $value;
        }
        return $flat;
    }
    /*}}}*/

    /************************************************************************************
     * misc functions
     ************************************************************************************/
    /*{{{1*/

    // Converts a date/timestamp into the specified format
    function format_date($date = null, $format = null) {/*{{{*/
        if (is_null($format))
            $format = 'Y-m-d H:i:s';

        if (is_null($date))
            $date = time();

        // if $date contains only numbers, treat it as a timestamp
        if (ctype_digit($date) === true)
            return date($format, $date);
        else
            return date($format, strtotime($date));
    }/*}}}*/

    // Returns an Chinese representation of a past date within the last month
    /**
     * @param unknown_type $ts
     * @return string|string|string|string|string|string|string|string|string|string|string|string|string|string|string|string|string|string|string|string|string
     */
    function time2str($ts) {/*{{{*/
        if (!ctype_digit($ts))
            $ts = strtotime($ts);

        $diff = time() - $ts;
        if ($diff == 0) {
            return '现在';
        } else if ($diff > 0) {
            $day_diff = floor($diff / 86400);
            if ($day_diff == 0) {
                if($diff < 60) return '刚刚';
                if($diff < 120) return '1分钟前';
                if($diff < 3600) return floor($diff / 60) . '分钟前';
                if($diff < 7200) return '1小时前';
                if($diff < 86400) return floor($diff / 3600) . '小时前';
            }
            if($day_diff == 1) return '昨天';
            if($day_diff < 7) return $day_diff . '天前';
            if($day_diff < 31) return ceil($day_diff / 7) . '周前';
            if($day_diff < 60) return '上个月';
            return date('Y-m-d', $ts);
        }
        else
        {
            $diff = abs($diff);
            $day_diff = floor($diff / 86400);
            if ($day_diff == 0) {
                if($diff < 120) return '一分钟内';
                if($diff < 3600) return floor($diff / 60) . '分钟后';
                if($diff < 7200) return '一小时内';
                if($diff < 86400) return floor($diff / 3600) . '小时内';
            }
            if($day_diff == 1) return '明天';
            if($day_diff < 4) return date('l', $ts);
            if($day_diff < 7 + (7 - date('w'))) return '下周';
            if(ceil($day_diff / 7) < 4) return ceil($day_diff / 7) . '周后';
            if(date('n', $ts) == date('n') + 1) return '下个月';
            return date('Y-m-d', $ts);
        }
    }/*}}}*/

    /**
     * Sanitizes a string to make sure it is valid UTF that will not break in
     * json_encode or something else dastaradly like that.
     *
     * @param string $str String with potentially invalid UTF8
     * @return string Valid utf-8 string
     */
    function utf8_sanitize($str) {
        if (!is_string($str)) $str = strval($str);
        return iconv('utf-8', 'utf-8//IGNORE', $str);
    }

    function utf8_substr($str, $from, $len = 180, $suffix = '') { 
        $str     = trim($str);
        $str     = strip_tags($str);
        $pre_len = strlen($str);
        $str     = preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $from .'}'.'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $len .'}).*#s', '$1', $str);
        if ($pre_len != strlen($str)) {
            $str .= $suffix;
        }
        return $str;
    } 
     
    function utf8_cutstr($str,$cutlength,$suffix = '...') {
    	$str       = trim($str);
        $str       = strip_tags($str);
    	$returnstr = '';
    	$i = 0;
    	$n = 0;
    	$str_length = strlen($str);//字符串的字节数
    	$cutlength = $cutlength * 3;
        //print_r($str_length);
    	while ($n <= $cutlength - 3) {
            $temp_str = substr($str,$i,1);
            $ascnum = ord($temp_str);//得到字符串中第$i位字符的ascii码
            if ($ascnum >= 224) {  //如果ASCII位高与224，
                $returnstr = $returnstr.substr($str,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符  
                $i = $i + 3;
      	        $n = $n + 3;            //字串长度计3
            } elseif ($ascnum >= 192) { //如果ASCII位高与192，
                $returnstr = $returnstr.substr($str,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
                $i = $i + 2;
		      	$n = $n + 3;         //字串长度计2
		    } elseif ($ascnum >= 65 && $ascnum <= 90) {//如果是大写字母，
		        $returnstr = $returnstr.substr($str,$i,1);
                $i = $i + 1;
		      	$n = $n + 3;          //但考虑整体美观，大写字母计成一个高位字符
		    }
            else {              //其他情况下，包括小写字母和半角标点符号，
	            $returnstr = $returnstr.substr($str,$i,1);
                $i = $i + 1;
		      	$n = $n + 1.5;          //小写字母和半角标点等与半个高位字符宽...
		    }
    	}
        if(strlen($returnstr) >= $str_length) {
            return $returnstr;
        }
        $returnstr = $returnstr . $suffix;//超过长度时在尾处加上省略号
    	return $returnstr;
    }
        

    /**
     * Escapes text to make it safe to use with Javascript
     *
     * It is usable as, e.g.:
     *  echo '<script>aiert(\'begin'.escape_js_quotes($mid_part).'end\');</script>';
     * OR
     *  echo '<tag onclick="aiert(\'begin'.escape_js_quotes($mid_part).'end\');">';
     * Notice that this function happily works in both cases; i.e. you don't need:
     *  echo '<tag onclick="aiert(\'begin'.txt2html_old(escape_js_quotes($mid_part)).'end\');">';
     * That would also work but is not necessary.
     *
     * @param  string $str    The data to escape
     * @param  bool   $quotes should wrap in quotes (isn't this kind of silly?)
     * @return string         Escaped data
     */
    function escape_js_quotes($str, $quotes=false) {
        if ($str === null) {
            return '';
        }
        $str = strtr($str, array('\\'=>'\\\\', "\n"=>'\\n', "\r"=>'\\r', '"'=>'\\x22', '\''=>'\\\'', '<'=>'\\x3c', '>'=>'\\x3e', '&'=>'\\x26'));
        return $quotes ? '"'. $str . '"' : $str;
    }

    function strip_tags_attributes($string, $allowtags = null, $allowattributes = null) {/*{{{*/
        $string = strip_tags($string, $allowtags); 

        if (!is_null($allowattributes)) { 
            if (!is_array($allowattributes)) 
                $allowattributes = explode(',', $allowattributes); 
            if (is_array($allowattributes)) 
                $allowattributes = implode(')(?<!', $allowattributes); 
            if (strlen($allowattributes) > 0) 
                $allowattributes = '(?<!' . $allowattributes . ')'; 
            $expr = '/ [^ =]*' . $allowattributes . '=(\"[^\"]*\"|\'[^\']*\')/i';
            $string = preg_replace_callback('/<[^>]*>/i',
                function($matches) use($expr) { return preg_replace($expr, '', $matches[0]); }, $string); 
        } 
        // XSS protection: <a href="javascript: alert(...
        $string = preg_replace('/href=([\'"]).*?javascript:(.*)?\\1/i', 'href="#$2"', $string); 
        return $string; 
    } /*}}}*/

    /*{{{*/
            /*
    function strip_tags_attributes($string, $allowtags = null, $allowattributes = null) {
        if ($allowattributes) { 
            if (!is_array($allowattributes)) 
                $allowattributes = explode(',', $allowattributes); 
            if (is_array($allowattributes)) 
                $allowattributes = implode('|', $allowattributes); 
            $rep = '/([^>]*) ('.$allowattributes.')(=)(\'.*\'|".*")/i'; 
            $string = preg_replace($rep, '$1 $2_-_-$4', $string); 
        } 
        if (preg_match('/([^>]*) (.*)(=\'.*\'|=".*")(.*)/i', $string) > 0) { 
            $string = preg_replace('/([^>]*) (.*)(=\'.*\'|=".*")(.*)/i', '$1$4', $string); 
        } 
        $rep = '/([^>]*) ('.$allowattributes.')(_-_-)(\'.*\'|".*")/i'; 
        if ($allowattributes) 
            $string = preg_replace($rep, '$1 $2=$4', $string); 
        return strip_tags($string, $allowtags); 
    } */
    /*}}}*/
    
    function clean_richtext($text) {/*{{{*/
        $allowtags       = '<a><b><i><u><blockquote><img><strong><em><font><p><ol><ul><li><h1><h2><h3><h4><h5><h6><strike><span><br><table><tbody><th><tr><td><caption><colgroup><div><embed>';
        $allowattributes = 'href,target,src,width,height,alt,title,size,face,color,align,style,name,rowspan,colspan,border,rev,class';

		$text = preg_replace("/<(script.*?)>(.*?)<(\/script.*?)>/si","", $text);
        // strip out any \r characters. all we need is \n
//        $text = strtr($text, array("\r" => '', '&' => '&amp;'));
//        $text = strtr($text, array("onmouseover" => '', 'onmouseout' => '', 'on'));
        $text = utf8_sanitize($text);
        $text = strip_tags_attributes($text, $allowtags, $allowattributes);
        $text = preg_replace('/mso-.*?:.*?(;|\"|\'|>)/si', '$1', $text);
//        $text = preg_replace('/(?<!href=")(?<!src=")((https?:\/\/)([-a-zA-Z0-9@:%_\+.~#?&\/=]+))/i', '<a href="$1" target="_blank">$3</a>', $text);

        // XSS protection: <a href="javascript: alert(...
        $text = preg_replace('/href=([\'"]).*?javascript:(.*)?\\1/i', 'href="#$2"', $text); 
        $text = tidy_html($text);

        return $text;
    }/*}}}*/

    function clean_string($string, $quote_style = ENT_COMPAT) {
        $string = utf8_sanitize($string);
        return htmlspecialchars($string, $quote_style);
    }
    
    function strlength($string, $encode = 'utf8') {
    	if (function_exists('mb_strlen'))
            $length = mb_strlen($string, $encode);
        else
            $length = strlen($string);
        return $length;
    }

    /*}}}*/

    /************************************************************************************
     * request functions
     ************************************************************************************/
    function assert_http_method($method, $error_msg = null) {
        if (strtolower($_SERVER['REQUEST_METHOD']) != strtolower($method)) {
            if (is_null($error_msg))
                $error_msg = '请发送' . strtoupper($method) . '请求';
            throw new Exception($error_msg, 400);
        }
    }

    function param_value($scope, $name, $required = false, $default = false) {
        if (!isset($scope[$name])) {
            if ($required) throw new Exception("缺少参数'$name'", 400);
            return $default;
        }
        return $scope[$name];
    }

    function param_richtext($scope, $name, $required = false, $default = '') {
        $value = param_value($scope, $name, $required, $default);
        if ($value) return clean_richtext($value);
        return $value;
    }

    function param_string($scope, $name, $required = false, $default = '') {
        $value = param_value($scope, $name, $required, $default);
        if ($value) return clean_string($value);
        return $value;
    }

	function param_tags($scope, $name, $required = false, $default ='') {
        $value = param_value($scope, $name, $required, $default);
        if ($value) return clean_string($value, ENT_NOQUOTES);
        return $value;
	}

    function param_int($scope, $name, $required = false, $default = 0) {
        $value = param_value($scope, $name, $required, null);

        if (is_null($value)) {
            if ($required)
                throw new Exception("参数'$name'必须提供", 400);
            return $default;
        }

        if (ctype_digit($value) || is_int($value)) {
            return intval($value);    
        }

        if ($value[0] == '-' && ctype_digit(substr($value, 1))) {
            return intval($value);
        }
        throw new Exception("参数'$name'必须为整数类型", 500);
    }

    function param_uint($scope, $name, $required = false, $default = 0) {
        $value = param_value($scope, $name, $required, null);

        if (is_null($value)) {
            if ($required)
                throw new Exception("参数'$name'必须提供", 400);
            return $default;
        }

        if (ctype_digit($value) || is_int($value)) {
            return intval($value);    
        }
        throw new Exception("参数'$name'必须为正整数类型", 500);
    }

    function param_float($scope, $name, $required = false, $default = 0.0) {
        $value = param_value($scope, $name, $required, null);

        if (is_null($value)) {
            if ($required)
                throw new Exception("参数'$name'必须提供", 400);
            return $default;
        }
        
        if (preg_match('/^[0-9\.]*$/i', $value)) {
            return floatval($value);
        }
        throw new Exception("参数'$name'必须为浮点数类型", 500);
    }
    
    function param_email($scope, $name, $required = false, $default = '') {
        $value = param_value($scope, $name, $required, null);

        if (is_null($value)) {
            if ($required)
                throw new Exception("参数'$name'必须提供", 400);
            return $default;
        }
        
        if (is_email($value)) {
            return $value;
        }
        throw new Exception("参数'$name'必须为浮点数类型", 500);
    }
    
    function is_email($val) {
        if (preg_match('/^([_a-z0-9+-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $val) == 0) {
            return false;
        }
        return true;
    }
    
    function is_phone_number($val) {
        if (preg_match('/^((13[0-9])|(15[0-3,5-9])|(18[0-9])|147)[0-9]{8}$/', $val) == 0) {
            return false;
        }
        return true;
    }
    
    // Computes the *full* URL of the current page (protocol, server, path, query parameters, etc)
    function full_request_url() {
        $s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
        $protocol = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, strpos(strtolower($_SERVER['SERVER_PROTOCOL']), '/')) . $s;
        $port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (":".$_SERVER['SERVER_PORT']);
        return $protocol . "://" . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI'];
    }

    function get_remote_addr($as_number = false) {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        preg_match("/[\d\.]{7,15}/", $ip, $ipmatches);
        $ip = $ipmatches[0] ? $ipmatches[0] : 'unknown';

        if ($as_number) {
            if ($ip == 'unknown') return 0;
            $ip = sprintf("%u",ip2long($ip)); 
            return $ip;
        } else {
            return $ip;
        }
    }

    function get_formhash() {
        if (!isset($_SESSION['formhash']))
            $_SESSION['formhash'] = md5(uniqid(mt_rand(), true));
        return $_SESSION['formhash'];
    }
    
    function reset_formhash() {
        $_SESSION['formhash'] = md5(uniqid(mt_rand(), true));
        return $_SESSION['formhash'];
    }

    function echo_formhash() {
        echo "<input type=\"hidden\" name=\"formhash\" value=\"" . get_formhash() . "\" />\n";
    }

    function check_formhash() {
        if (!isset($_POST['formhash']))
            return false;
        $cookie = get_formhash();
        return $_POST['formhash'] == $cookie;
    }

    /************************************************************************************
     * response functions
     ************************************************************************************/
    /*{{{1*/
    // Redirects user to $url
    function redirect($url = null, $permanently = false) {
        if (class_exists('Message')) {
            $msg = Message::get_instance();
            $msg->store();
        }
        if (is_null($url)) $url = $_SERVER['PHP_SELF'];
        if (function_exists('http_redirect')) {
            http_redirect($url, array(), false, $permanently);
        } else {
        	if ($permanently) {
        		header("HTTP/1.1 301 Moved Permanently") ;
        	}
            header("Location: $url");
        }
        exit();
    }
    

    function make_url($format) {
        $url = WEB_ROOT . $format;
        if (func_num_args() > 1) {
            $args = array_slice(func_get_args(), 1);
            $args = array_flatten($args);
            $url = vsprintf($url, $args);
        }
        return $url;
    }
    /*}}}*/

    function browser_short_name($default = 'std') {/*{{{*/
        global $browser_short_name;

        if (isset($browser_short_name))
            return $browser_short_name;

        if (!isset($_SERVER['HTTP_USER_AGENT']))
            return $default;

        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        $browsers = array(
                'msie 9'      => 'ie ie9',
                'msie 8'      => 'ie ie8',
                'msie 7'      => 'ie ie7',
                'msie 6'      => 'ie ie6',
                'firefox/9'   => 'ff ff9',
                'firefox/8'   => 'ff ff8',
                'firefox/7'   => 'ff ff7',
                'firefox/6'   => 'ff ff6',
                'firefox/5'   => 'ff ff5',
                'firefox/4'   => 'ff ff4',
                'firefox/3'   => 'ff ff3',
                'firefox/2'   => 'ff ff2',
                'firefox/10'  => 'ff ff10',
                'chrome/16'	  => 'chrome chrome16',
                'chrome/15'	  => 'chrome chrome15',
                'chrome/14'	  => 'chrome chrome14',
                'chrome/13'	  => 'chrome chrome13',
                'chrome/12'	  => 'chrome chrome12',
                'chrome/8'	  => 'chrome chrome8',
                'chrome/7'	  => 'chrome chrome7',
                'chrome/17'	  => 'chrome chrome17',
                'chrome/18'	  => 'chrome chrome18',
                'ipad'		  => 'ipad',
                'iphone os 4' => 'iphone iphone4', 
                'safari/528'  => 'safari safari4',
                'safari/52'   => 'safari safari3',
                'safari/53'   => 'safari safari4',
                'opera/9'     => 'opera opera9',
            );

        foreach($browsers as $k => $v) {
            if (strpos($agent, $k) !== false) {
                $browser_short_name = $v;
                return $browser_short_name;
            }
        }

        $browser_short_name = $default;
        return $browser_short_name;
    }/*}}}*/
    
    function browser_is_ie() {
    	global $browser_is_ie;
    	if (isset($browser_is_ie))
            return $browser_is_ie;
        
        $name = browser_short_name();
        $browser_is_ie = strpos($name, 'ie ie') !== false;

        return $browser_is_ie;
    }

    function browser_is_ie6() {/*{{{*/
        global $browser_is_ie6;
        if (isset($browser_is_ie6))
            return $browser_is_ie6;

        $name = browser_short_name();
        $browser_is_ie6 = strpos($name, 'ie6') !== false;

        return $browser_is_ie6;
    }/*}}}*/

    function browser_is_search_engine() {/*{{{*/
        global $browser_is_search_engine;

        if (isset($browser_is_search_engine))
            return $browser_is_search_engine;

        $browser_is_search_engine = false;

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = $_SERVER['HTTP_USER_AGENT'];

            $search_engine_tests = array('Googlebot', 'Baiduspider', 'Yahoo! Slurp', 'Sogou web spider', 'Sosospider', 'msnbot', 'spider', 'YoudaoBot', 'YodaoBot', 'YandexBot');
            foreach ($search_engine_tests as $key) {
                if (strpos($agent, $key) !== false) {
                    $browser_is_search_engine = true;
                    break;
                }
            }
        }

        return $browser_is_search_engine;
    }/*}}}*/
    
    function seo_echo($text, $seo_text = '') {
        echo browser_is_search_engine() ? $seo_text : $text;
    }
    
    /**
	 * Generates a Universally Unique IDentifier, version 4.
	 * 
	 * RFC 4122 (http://www.ietf.org/rfc/rfc4122.txt) defines a special type of Globally
	 * Unique IDentifiers (GUID), as well as several methods for producing them. One
	 * such method, described in section 4.4, is based on truly random or pseudo-random
	 * number generators, and is therefore implementable in a language like PHP.
	 * 
	 * We choose to produce pseudo-random numbers with the Mersenne Twister, and to always
	 * limit single generated numbers to 16 bits (ie. the decimal value 65535). That is
	 * because, even on 32-bit systems, PHP's RAND_MAX will often be the maximum *signed*
	 * value, with only the equivalent of 31 significant bits. Producing two 16-bit random
	 * numbers to make up a 32-bit one is less efficient, but guarantees that all 32 bits
	 * are random.
	 * 
	 * The algorithm for version 4 UUIDs (ie. those based on random number generators)
	 * states that all 128 bits separated into the various fields (32 bits, 16 bits, 16 bits,
	 * 8 bits and 8 bits, 48 bits) should be random, except : (a) the version number should
	 * be the last 4 bits in the 3rd field, and (b) bits 6 and 7 of the 4th field should
	 * be 01. We try to conform to that definition as efficiently as possible, generating
	 * smaller values where possible, and minimizing the number of base conversions.
	 * 
	 * @copyright   Copyright (c) CFD Labs, 2006. This function may be used freely for
	 *              any purpose ; it is distributed without any form of warranty whatsoever.
	 * @author      David Holmes <dholmes@cfdsoftware.net>
	 * 
	 * @return  string  A UUID, made up of 32 hex digits and 4 hyphens.
	 */

	function uuid($hyphened = false) {
	    
	    // The field names refer to RFC 4122 section 4.1.2
		$pattern = '%04x%04x%04x%03x4%04x%04x%04x%04x';
		if ($hyphened) $pattern = '%04x%04x-%04x-%03x4-%04x-%04x%04x%04x';
	    return sprintf($pattern,
	        mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
	        mt_rand(0, 65535), // 16 bits for "time_mid"
	        mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
	        bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
	            // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
	            // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
	            // 8 bits for "clk_seq_low"
	        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"  
	    );  
	}
	
	function floor_pad_zero($num, $precision = 0) {
		$num = floor($num);
		return number_format($num, $precision, ".", ",");
	}
	
	function round_pad_zero($num, $precision = 2) {
		$num = round($num, $precision);
		return number_format($num, $precision, ".", ",");
	}
	
	function round_pad_zero_clear($num, $precision = 2) {
		$num = round($num, $precision);
		$num = number_format($num, $precision, ".", ",");
		list($integer, $decimal) = explode('.', $num);
		if($decimal == '00') {
		    $num = str_replace('.00', '', $num);
		}
		return $num;
	}
	
	function has_chinese($s) {
		return preg_match('/([^x00-xff])+/',$s);
	}
	
	function month_diff($year, $month) {

         $starttime = strtotime($year.'-'.$month.'-01');
         $endmonth = $month == 12 ? 1 : $month + 1;
         $endyear = $month == 12 ? $year + 1 : $year;
         
         $endtime = strtotime($endyear.'-'.$endmonth.'-01') - 1;
         return array($starttime, $endtime);
    }
    
    /*
     * 将字节转换成Kb或者Mb...
     * 参数 $num为字节大小
     */
    function bitsize($num){
        if(!preg_match("/^[0-9]+$/", $num)) return 0;
        $type = array( "B", "KB", "MB", "GB", "TB", "PB" );
        
        $j = 0;
        while( $num >= 1024 ) {
            if( $j >= 5 ) return $num.$type[$j];
            $num = $num / 1024;
            $j++;
        }
        $num = round_pad_zero($num, 2);
        
        return $num.$type[$j];
    }
    
    /**
	 * 
	 * @param string $html
	 * return string
	 */
	function tidy_html( $html, $show_body_only = true )
	{
		$config = array(
			//'clean' => true ,
			'drop-proprietary-attributes' => FALSE ,
			//'output-xhtml' => true ,
			'show-body-only' => $show_body_only ,
			'word-2000' => true ,
			'wrap' => '0',
			"char-encoding" => "raw",
			"input-encoding" => "raw",
			"output-encoding" => "raw"		
		);
		$html = str_replace('&nbsp;', '[~`{^^]`~}', $html);	//tidy会使&nbsp;乱码
		$html = tidy_repair_string( $html, $config );	
		$html = str_replace('[~`{^^]`~}', '&nbsp;', $html);	//替换回来
		return $html;
	}
	
	 /**
     * 获取字符串长度，一个中文占两个字符
     * @param unknown_type $str
     */
    function get_string_length($str){
        return (strlen($str) + mb_strlen($str,'UTF8')) / 2; 
    }
    
    /**
     * 字符串转换成数字
     */
    function from10to62($num) {
    	$wordmap = array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    	$c = '';
    	$num = intval($num);
    	while ($num > 62) {
    		$r = $num % 62;
    		$c = $wordmap[$r] . $c;
    		$num = ($num - $r) / 62;
    	}
    	$c = $wordmap[$num] . $c;
    	
    	return $c;
    }
    function from62to10($str) {
    	$wordmap = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	while (strpos($str, '0') === 0) {
    		$str = substr($str, 1);
    	}
		$len = strlen($str);
		if ($len === 0) return 0;
				
		$value = 0;
		for ($i = $len; $i > 0; $i--) {
			$c = substr($str, $len - $i, 1);
			$pos = strpos($wordmap, $c);
			if ($pos === false) return false;
			$value += $pos * pow(62, ($i-1));
		}
		return $value;
    }
	  
    
    /***
     * 去除字符串的空格
     */
    function trim_str($content){
        $content = trim( $content );
        $arr = array( "\r\n" => "", "\n" => "", "\r" => "", "\t" => "", chr(9) => "", "\\n" => "", "&nbsp;" => "");
        $content = strtr( $content, $arr );
        
        return $content;
    }
    
    function curl_setopt_check($url, $options = array()) {
        
        $options = empty($options) ? array('connecttimeout' => 3, 'timeout' => 3) : $options;
        
        //开启线程资源
	    $ci = curl_init($url); 
	    curl_setopt($ci, CURLOPT_FAILONERROR, true);
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $options['connecttimeout']); 
        curl_setopt($ci, CURLOPT_TIMEOUT, $options['timeout']); //设置超时时间
        $result = curl_exec($ci);//读取数据
        
        //关闭线程
        curl_close($ci);
        
        return $result;

    }
     
    function mcrypt_encode( $str ) {
     	$str = trim($str);
     	$key 	= 	md5(date('Ymd'));
		$td = mcrypt_module_open('des', '', 'ecb', '');
		$key = substr($key, 0, mcrypt_enc_get_key_size($td));
		$iv_size = mcrypt_enc_get_iv_size($td);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	
		mcrypt_generic_init($td, $key, $iv);
		$encrypted = mcrypt_generic($td, $str);
	   	mcrypt_generic_deinit($td);
	    mcrypt_module_close($td);
	
	    return base64_encode($encrypted);
	}
	  
	  
    function mcrypt_decode( $str ) {
     	$str = base64_decode($str);
     	$key = md5(date('Ymd'));
		$td = mcrypt_module_open('des', '', 'ecb', '');
    	$key = substr($key, 0, mcrypt_enc_get_key_size($td));
    	$iv_size = mcrypt_enc_get_iv_size($td);
    	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

    	mcrypt_generic_init($td, $key, $iv);
    	$decrypted = mdecrypt_generic($td, $str);
       	mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        
        $decrypted =  rtrim($decrypted, "\0");
	
	    return $decrypted;
	 }
	  
	 function log_error($message, $error_file='',$error_line='') {
	  	error_log("ERROR\t$message\t$error_file\t$error_line", 0);       
	 }
	 
	function pagecache_start($cachetime = 120, $suffix = '', $dest='/tmp') {
		$param = '';
		if (!empty($_GET)) {
			try { 
				$param = implode('', array_keys($_GET)) . implode('', array_values($_GET));
			} catch (Exception $e) {}
		}
		$cachefile = $dest.'/'.sha1($_SERVER['REQUEST_URI'].$param) . $suffix;
	  	ob_start();$cachetime = 0;
	  	if(file_exists($cachefile) && (time()-filemtime($cachefile) < $cachetime)) {
		    include($cachefile);
		    ob_end_flush();
		    exit;
	 	}
	}
	function pagecache_end($suffix = '', $dest='/tmp') {
		$param = '';
		if (!empty($_GET)) {
			try { 
				$param = implode('', array_keys($_GET)) . implode('', array_values($_GET));
			} catch (Exception $e) {}
		}
		
		$lock = fopen($dest . '/' . sha1($_SERVER['REQUEST_URI']) . '.lock', 'w');
		if (flock($lock, LOCK_EX | LOCK_NB)) { // do an exclusive lock
			$cachefile = $dest.'/'.sha1($_SERVER['REQUEST_URI'].$param) . $suffix;
		  	$fp = fopen($cachefile, 'w');
		  	fwrite($fp, ob_get_contents());
		  	fclose($fp);
		  	
		  	flock($lock, LOCK_UN); // release the lock
		  	fclose($lock);
		}
	  	ob_end_flush();
	}
	function pagecache_clean($uri, $dest='/tmp') {
		$cachefile = $dest.'/'.sha1($uri);
		if(file_exists($cachefile)) {
			unlink($cachefile);
		}
	}

	function file_get_contents_ex($url, $timeout = 6) {
		$ctx = stream_context_create(
					array(
						'http' => array('timeout' => $timeout)
					)
				);
		return file_get_contents($url, 0, $ctx);
	}