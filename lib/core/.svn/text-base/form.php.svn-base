<?php
class Form {
    protected $data;
    protected $errors;
    protected $cleaned;

    public function __construct($data = null, $validate = true) {
        if (is_null($data)) $data = $_POST;

        if ($validate === false) {
            $this->cleaned = array();
            foreach ($data as $key => $value)
                $this->cleaned[$key] = $value;
        } else {
            $this->data = array();
            foreach ($data as $key => $value)
                $this->data[$key] = $value;
        }

        $this->errors = array();
    }

    public function add_raw($key, $value) {
        $this->data[$key] = $value;
    }

    public function __get($key) {
        if (!is_null($this->cleaned)) {
            if (array_key_exists($key, $this->cleaned))
                return $this->cleaned[$key];

            if ((substr($key, 0, 2) == '__') && array_key_exists(substr($key, 2), $this->cleaned))
                return htmlspecialchars($this->cleaned[substr($key, 2)]);
        }    
        return null;
    }

    public function __isset($key)
    {
    	if( $this->$key === null )
    	{
    		return false;
    	}
    	return true;
    }
    
    protected function add_error($key, $msg) {
        if (isset($this->errors[$key]) && !is_array($this->errors[$key]))
            $this->errors[$key] = array($msg);
        else
            $this->errors[$key][] = $msg;
    }

    public function has_error($key = null) {
        if (is_null($key)) return !$this->is_valid();

        return isset($this->errors[$key]);
    }

    public function error_pick($key, $error = '', $success = '') {
        if (isset($this->errors[$key]))
            return $error;
        return $success;
    }

    public function error_as_p($key, $class = 'error') {
        if (!isset($this->errors[$key]))
            return '';
        $tag = '<p class="'.$class.'">';
        return $tag . implode('</p>'.$tag, $this->errors[$key]) . '</p>' . chr(13) . chr(10);
    }
    
    public function error_as_div($key, $class = 'error') {
        if (!isset($this->errors[$key]))
            return '';
        $tag = '<div class="'.$class.'">';
        return $tag . implode('</p>'.$tag, $this->errors[$key]) . '</div>' . chr(13) . chr(10);
    }
    
    public function error_as($key, $class = 'error', $tag = 'p') {
    	if (!isset($this->errors[$key]))
            return '';
        $start = "<$tag class=\"$class\">";
        return $start . implode("</$tag>$start", $this->errors[$key]) . "</$tag>" . chr(13) . chr(10);
    }

    public function get_errors($key) {
        if (!isset($this->errors[$key]))
            return '';
        return $this->errors[$key];
    }

    public function get_cleaned_data() {
        if (is_null($this->cleaned))
            $this->clean();

        return $this->cleaned;
    }

    public function is_valid() {
        if (is_null($this->cleaned))
            $this->clean();

        return empty($this->errors);
    }

    public function clean() {
        if (!is_null($this->cleaned)) return;

        $this->cleaned = array();

        foreach($this->data as $key => $value) {
            $this->{'clean_'.$key}($key, $value);
        }
    }

    public function __call($name, $arguments) {
        if (count($arguments) >= 2) {
            $key   = $arguments[0];
            $value = $arguments[1];
            $this->cleaned[$key] = $value;    
        }
    }

    // Variable tests
    function validate_blank($val, $id, $name = null) {
        $val = trim($val);
        if ($val == '') {
            if (is_null($name)) $name = ucwords($id);
            $this->add_error($id, "{$name} 为必填项.");
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }

    function validate_range($val, $id, $lower, $upper, $name = null) {
        if ($val < $lower || $val > $upper) {
            if (is_null($name)) $name = ucwords($id);
            $this->add_error($id, "$name 必须在 {$lower}-{$upper} 之间.");
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }
    
    function validate_max($val, $id, $upper, $name = null) {
        if ($val > $upper) {
            if (is_null($name)) $name = ucwords($id);
            $this->add_error($id, "$name 必须大于 {$upper}.");
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }
    
    function validate_min($val, $id, $lower, $name = null) {
        if ($val < $lower) {
            if (is_null($name)) $name = ucwords($id);
            $this->add_error($id, "$name 必须小于 {$lower}.");
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }

    function validate_length($val, $id, $lower, $upper, $name = null) {
        if (function_exists('mb_strlen'))
            $length = mb_strlen($val);
        else
            $length = strlen($val);
        if (!is_null($lower) && $length < $lower) {
            if (is_null($name)) $name = ucwords($id);
            $this->add_error($id, "$name 至少{$lower}个字符.");
            return false;
        }
        elseif (!is_null($lower) && $length > $upper) {
            if (is_null($name)) $name = ucwords($id);
            $this->add_error($id, "$name 不能超过{$upper}个字符.");
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }

    function validate_passwords($id, $pass1, $pass2) {
        if ($pass1 !== $pass2) {
            $this->add_error($id, '两次输入的密码不符合');
            return false;
        }

        $this->cleaned[$id] = $pass2;
        return true;
    }

    function validate_regex($val, $regex, $id, $msg) {
        if (preg_match($regex, $val) === 0) {
            $this->add_error($id, $msg);
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }

	function validate_mobile($val, $id = 'mobile') {
		if (preg_match('/^1[3-9][0-9]{9}$/', $val) == 0) {
			$this->add_error($id, '手机号码不正确');
			return false;
		}
		
		$this->cleaned[$id] = $val;
		return true;
	}

    function validate_email($val, $id = 'email') {
        if (preg_match('/^([_a-z0-9+-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $val) == 0) {
            $this->add_error($id, 'Email格式不正确');
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }

    function validate_date($val, $id) {
        $date = strtotime($val);
        if ($date === false) {
            $this->add_error($id, '日期格式不正确');
            return false;
        }

        $this->cleaned[$id] = $date;
        return true;
    }

    function validate_phone($val, $id) {
        // $val = preg_replace('/[^0-9]/', '', $val);
        //         if (strlen($val) != 7 && strlen($val) != 10) {
        //             $this->add_error($id, 'Please enter a valid 7 or 10 digit phone number.');
        //             return false;
        //         }
		if (preg_match('/^([0][0-9]{2,3}-)?([0-9]{7,8})(-[0-9]*)?$/', $val) == 0) {
			$this->add_error($id, '电话号码不正确,区号-电话-分机');
			return false;
		}

        $this->cleaned[$id] = $val;
        return true;
    }

    function validate_upload($val, $id) {
        if (!is_uploaded_file($val['tmp_name']) || !is_readable($val['tmp_name'])) {
            $this->add_error($id, '文件上传失败. 请重试.');
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }

    function validate_zip($val, $id, $name = null) {
        if (preg_match('/^[0-9]{6}$/', $val) == 0) {
            if (is_null($name)) $name = ucwords($id);
            $this->add_error($id, "请输入正确的, 6位数字邮编.");
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }

    function validate_integer($val, $id, $name = null) {
        if (is_null($name)) $name = ucwords($id);
        return $this->validate_regex($val, '/^[0-9]+$/', $id, "$name 必须为阿拉伯数字");
    }

    // Test if string $val is a valid, decimal number.
    function validate_nan($val, $id, $name = null) {
        if (is_null($name)) $name = ucwords($id);
        return $this->validate_regex($val, '/^-?[0-9]+(\.[0-9]+)?$/', $id, "$name 不是合法的数字");
    }

    function validate_url($val, $id, $name = null) {
        $info = @parse_url($val);
        if (($info === false) || (!isset($info['scheme'])) || (!isset($info['host'])) || ($info['scheme'] != 'http' && $info['scheme'] != 'https') || ($info['host'] == '')) {
            if (is_null($name)) $name = ucwords($id);
            $this->add_error($id, "${name} 不是正确的URL地址");
            return false;
        }

        $this->cleaned[$id] = $val;
        return true;
    }
}
