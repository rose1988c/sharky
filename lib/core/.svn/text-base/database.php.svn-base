<?php

class Transaction {/*{{{*/
    public  $db;
    public  $finished = false;
    public  $started = false;
    private $level = 0;

    private $outer_trans;
    private $inner_trans;

    public function __construct($db) {
        $this->db = $db;
    }

    public function join($trans) {
        if ($trans !== $this) {
            $this->outer_trans = $trans;
            $trans->inner_trans = $this;
        }
    }

    public function begin() {
        if (!$this->started) {
            mysqli_autocommit($this->db->link, false);
            $this->started = true;
        }
        $this->level += 1;
    }

    public function rollback() {
        if ($this->finished === true) return;

        mysqli_rollback($this->db->link);
        mysqli_autocommit($this->db->link, true);
        $this->finished = true;
        $this->started  = false;

        if (!is_null($this->outer_trans))
            $this->outer_trans->rollback();
        if (!is_null($this->inner_trans))
            $this->inner_trans->rollback();
    }

    public function commit() {
        if ($this->finished === true) return;

        if (!is_null($this->inner_trans))
            $this->inner_trans->commit();

        $this->level -= 1;
        if ($this->level <= 0) {
            mysqli_commit($this->db->link);
            mysqli_autocommit($this->db->link, true);
            $this->finished = true;
            $this->started  = false;
        }
    }
}/*}}}*/

class Database {/*{{{*/
    // Singleton object. Leave $instance alone.
    private static $instance;

    public $link;
    public $host;
    public $port;
    public $name;
    public $username;
    public $password;
    public $die_on_error;
    public $queries;
    public $result;

    private $transaction = false;

    // Singleton constructor
    public function __construct($host, $port, $name, $username, $password, $connect = false) {
		$this->host         = $host;
		$this->port         = $port;
		$this->name         = $name;
		$this->username     = $username;
		$this->password     = $password;
		$this->die_on_error = config_get('database.die_on_error', false);

        $this->link    = false;
        $this->queries = array();

        if ($connect === true)
            $this->connect();
    }

    public function __destruct() {
        if ($this->is_connected()) {
            mysqli_close($this->link);
        }
    }

	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new Database( config_get('database.host'), 
											config_get('database.port'),
											config_get('database.db'),
											config_get('database.user'),
											config_get('database.password')
										);
		}
	    return self::$instance;
	}

    // Do we have a valid database connection?
    public function is_connected() {
        return is_object($this->link);
    }

    public function connect() {
        $this->link = mysqli_connect($this->host, $this->username, $this->password, $this->name, $this->port) or $this->notify();

        mysqli_set_charset($this->link, 'utf8');

        return $this->is_connected();
    }

    public function begin_transaction() {
        if (!$this->is_connected()) $this->connect();
        if (!$this->transaction || $this->transaction->finished) {
            $this->transaction = new Transaction($this);
        }
        $this->transaction->begin();
        return $this->transaction;
    }

    public function join_transaction($trans) {
        if (!$this->is_connected()) $this->connect();
        if ($trans->db === $this) {
            return false;
        }
        $inner_trans = $this->begin_transaction();
        $inner_trans->join($trans);
        return $inner_trans;
    }

    public function query($sql) {
        if (!$this->is_connected()) $this->connect();

        // Optionally allow extra args which are escaped and inserted in place of '?'.
        if (func_num_args() > 1) {
            $args = array_slice(func_get_args(), 1);
            $args = array_flatten($args);
            for ($i = 0; $i < count($args); $i++) {
                if (is_null($args[$i]))
                    $args[$i] = 'NULL';
                else
                    $args[$i] = $this->quote($args[$i]);
            }
            $sql = vsprintf(str_replace('?', '%s', $sql), $args);
        }

        $this->queries[] = $sql;
        $this->result = mysqli_query($this->link, $sql) or $this->notify();
        return $this->result;
    }

    // Returns the number of rows.
    // You can pass in nothing, a string, or a db result
    public function num_rows($arg = null) {
        $result = $this->resulter($arg);
        return ($result !== false) ? mysqli_num_rows($result) : false;
    }

    // Returns true / false if the result has one or more rows
    public function has_rows($arg = null) {
        $result = $this->resulter($arg);
        return is_object($result) && (mysqli_num_rows($result) > 0);
    }

    // Returns the number of rows affected by the previous operation
    public function affected_rows() {
        if (!$this->is_connected()) return false;
        return mysqli_affected_rows($this->link);
    }

    // Returns the auto increment ID generated by the previous insert statement
    public function insert_id() {
        if (!$this->is_connected()) return false;
        $id = mysqli_insert_id($this->link);
        if ($id === 0 || $id === false)
            return false;
        else
            return $id;
    }

    // Returns a single value.
    // You can pass in nothing, a string, or a db result
    public function get_value($arg = null) {
        $result = $this->resulter($arg);
        if ($this->has_rows($result)) {
            $row   = mysqli_fetch_row($result);
            $value = $row[0];
            mysqli_free_result($result);
            return $value;
        }
        return false;
    }

    // Returns an array of the first value in each row.
    // You can pass in nothing, a string, or a db result
    public function get_values($arg = null) {
        $result = $this->resulter($arg);
        if (!$this->has_rows($result)) return array();

        $values = array();
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
            $values[] = array_pop($row);
        mysqli_free_result($result);
        return $values;
    }

    // Returns the first row.
    // You can pass in nothing, a string, or a db result
    public function get_row($arg = null) {
        $result = $this->resulter($arg);
        if (!$this->has_rows()) return false;
        $rows = $this->__mysql_fetch_array($result);
        return $rows[0];
    }

    // Returns an array of all the rows.
    // You can pass in nothing, a string, or a db result
    public function get_rows($arg = null) {
        $result = $this->resulter($arg);
        if (!$this->has_rows($result)) return array();

        return $this->__mysql_fetch_array($result);
    }

    public function get_array($arg = null) {
        $result = $this->resulter($arg);
        if (!$this->has_rows($result)) return array();

        return $this->__mysql_fetch_array($result, MYSQLI_NUM);
    }

    private function __mysql_fetch_array($result, $resulttype = MYSQLI_ASSOC) {
        $rows = array();
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_array($result, $resulttype))
            $rows[] = $row;
        mysqli_free_result($result);
        return $rows;
    }

    // Escapes a value and wraps it in single quotes.
    public function quote($var) {
        if (!$this->is_connected()) $this->connect();
        return '\'' . $this->escape($var) . '\'';
    }

    // Escapes a value.
    public function escape($var) {
        if (!$this->is_connected()) $this->connect();
        return mysqli_real_escape_string($this->link, $var);
    }

    public function num_queries() {
        return count($this->queries);
    }

    public function last_query() {
        if ($this->num_queries() > 0)
            return $this->queries[$this->num_queries() - 1];
        else
            return false;
    }

    public function get_lock($name, $timeout = 0) {
        $result = $this->get_value('SELECT GET_LOCK(' . $this->quote($name) . ', ' . $this->quote($timeout) . ')');
        return $result == 1;
    }

    public function release_lock($name) {
        $result = $this->get_value('SELECT RELEASE_LOCK(' . $this->quote($name) . ')');
        return $result == 1;
    }

    private function notify() {/*{{{*/
        if ($this->transaction && !$this->transaction->finished)
            $this->transaction->rollback();

        if ($this->link) {
            $err_no  = mysqli_errno($this->link);
            $err_msg = mysqli_error($this->link);
        } else {
            $err_no  = mysqli_connect_errno();
            $err_msg = mysqli_connect_error();
        }
        error_log($err_msg);

        if ($this->die_on_error === true) {
            echo "<p style='border:5px solid red;background-color:#fff;padding:5px;'><strong>Database Error:</strong><br/>$err_msg</p>";
            echo "<p style='border:5px solid red;background-color:#fff;padding:5px;'><strong>Last Query:</strong><br/>" . $this->last_query() . "</p>";
            echo "<pre>";
            debug_print_backtrace();
            echo "</pre>";
            exit;
        }

        throw new Exception($err_msg, $err_no);
    }/*}}}*/

    // Takes nothing, a MySQL result, or a query string and returns
    // the correspsonding MySQL result resource or false if none available.
    private function resulter($arg = null) {
        if (is_null($arg) && is_object($this->result))
            return $this->result;
        elseif (is_object($arg))
            return $arg;
        elseif (is_string($arg)) {
            $this->query($arg);
            if (is_object($this->result))
                return $this->result;
            else
                return false;
        }
        else
            return false;
    }
}/*}}}*/

/* vim:set fdm=marker: */
