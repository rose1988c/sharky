<?php
class Message {
    // Singleton object. Leave $me alone.
    private static $instance;

    public $messages;
    public $errors; // Array of errors

    public function __construct() {
        $this->messages = array();
        $this->errors = array();
    }

    // Get Singleton object
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new Message();
            if (isset($_SESSION['messages'])) {
                self::$instance->messages = $_SESSION['messages'];
                unset($_SESSION['messages']);
            }
            if (isset($_SESSION['errors'])) {
                self::$instance->errors = $_SESSION['errors'];
                unset($_SESSION['errors']);
            }
        }
        return self::$instance;
    }

    public function store() {
        if (!empty($this->messages))
            $_SESSION['messages'] = $this->messages;
        if (!empty($this->errors))
            $_SESSION['errors'] = $this->errors;
    }

    // Returns true if there are no errors
    public function ok() {
        return empty($this->errors);
    }

    public function has_error() {
        return !empty($this->errors);
    }

    public function has_msg() {
        return !empty($this->messages);
    }

    public function has_message() {
        return $this->has_msg();
    }

    public function add_error($msg) {
        $this->errors[] = $msg;
    }

    public function add_msg($msg) {
        $this->add_message($msg);
    }

    public function add_message($msg) {
        $this->messages[] = $msg;
    }

    public function print_error($tag = 'li', $class = 'error') {
        return $this->to_html('error', $tag, $class);
    }

    public function print_msg($tag = 'li', $class = 'success') {
        return $this->to_html('message', $tag, $class);
    }

    public function flush_error($tag = 'li', $class = 'error') {
        $html = $this->print_error($tag, $class);
        $this->errors = array();
        return $html;
    }

    public function flush_msg($tag = 'li', $class = 'success') {
        $html = $this->print_msg($tag, $class);
        $this->messages = array();
        return $html;
    }

    public function to_html($type, $tag, $class) {
        $array = ($type == 'error') ? $this->errors : $this->messages;
        $nl = chr(13) . chr(10);

        $html = '';
        $html.= $type == 'error' ? '<span class="icon-16-error">&nbsp;</span>' : '<span class="icon-16-succeed">&nbsp;</span>';
        foreach($array as $msg) {
        	if(!$tag) {
        		$html .= $msg;
        	} else { 
	            $html .= '<' . $tag;
	            if ($class) $html .= ' class="' . $class . '"';
	            $html .= '>' . $msg . '</' . $tag . '>' . $nl; 
        	}
        }
        return $html;
    }
}
