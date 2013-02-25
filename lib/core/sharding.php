<?php

class ShardingException extends Exception {}

class Shards {/*{{{*/
    /**
     * 缓存数据库连接
     **/
    private static $connections = array();

    public static function get_shard($sharding_clue) {
        $db = Database::get_instance();    

        // 从缓存中找
        $shard_id = cache_get($sharding_clue, config_get('site_domain') . '-shard_routing');

        if ($shard_id === false) {
            // 从数据库找
            $shard_id = $db->get_value('SELECT `shard_id` FROM `sharding_routing` WHERE `sharding_clue` = ' . $db->quote($sharding_clue));

            if ($shard_id === false) {
                $shard_id = self::assign_shard($sharding_clue);
            }

            // 放进缓存
            cache_set($sharding_clue, $shard_id, config_get('site_domain') . '-shard_routing');
        }

        return $shard_id;
    }

    public static function get_database($sharding_clue) {
        return self::get_node(self::get_shard($sharding_clue));
    }

    public static function get_node($shard_id) {
        $db = Database::get_instance();    
        // Load shard nodes
        $nodes = cache_get($shard_id, config_get('site_domain') . '-shard_nodes');
        if ($nodes === false) {
            $nodes = $db->get_rows('SELECT `node_id`, `shard_id`, `host`, `db_name`, `user`, `password` FROM `sharding_nodes` WHERE `shard_id` = ' . $db->quote($shard_id) . ' ORDER BY `node_id`');
            cache_set($shard_id, $nodes, config_get('site_domain') . '-shard_nodes');
        }

        $count = count($nodes);

        $index = isset($_SESSION['sharding_node_index']) ? $_SESSION['sharding_node_index'] : false;
        if ($index === false || $index >= $count) {
            $index = rand(0, $count - 1);
            $_SESSION['sharding_node_index'] = $index;
        }
        $node = $nodes[$index];

        if (!isset(self::$connections[$node['node_id']])) {
            self::$connections[$node['node_id']] = new Database($node['host'], 3306, $node['db_name'], $node['user'], $node['password']);
        }

        return self::$connections[$node['node_id']];
    }

    public static function assign_shard($sharding_clue) {
        $db = Database::get_instance();

        $shards = cache_get('all_assignable_shards_id', config_get('site_domain') . '-shards');
        if ($shards === false) {
            $shards = $db->get_values('SELECT `shard_id` FROM `sharding_shards` WHERE `status` = 0');    
            cache_set('all_assignable_shards_id', $shards, config_get('site_domain') . '-shards');
        }

        $shard_id = $shards[rand(0, count($shards) - 1)];

        $db->query('INSERT INTO `sharding_routing` (`sharding_clue`, `shard_id`) VALUES (?, ?)', $sharding_clue, $shard_id);

        return $shard_id;
    }

    public static function parse_object_key($key) {
        $key = trim($key);
        if (empty($key)) return false;

        $parts = explode('-', $key);
        if (count($parts) != 2) return false;

        return array('sharding_clue' => $parts[0], 'object_id' => $parts[1]);
    }
}/*}}}*/

class ShardedDBTable extends DBTable {/*{{{*/
    public $sharding_clue = false;

    public function __construct($name, $table_name, $sharding_clue, $definitions, $isolate_column = null) {
        parent::__construct($name, $table_name, $definitions, $isolate_column);

        $this->sharding_clue = $sharding_clue;
    }

    public function new_object($data = null) {
        return new ShardedDBObject($this, $data);
    }

    private function get_sharding_clue_from_query($query, $db = false) {
        if (is_object($query) && is_a($query, 'DBObject'))
            $query = $query->raw_data();
        $clue = false;

		// why comment code below EVER?
        if (is_array($query)) {
            if (isset($query[$this->sharding_clue]))    
                $clue = $query[$this->sharding_clue];
            else if (isset($query[0]))
                $clue = $query[0];
            else if (!empty($query))
                $clue = reset($query);
            else
                throw new ShardingException('对象不存在', 404);
        } else {
            $clue = $query;
        }
        if (is_array($query) && isset($query[$this->sharding_clue]))    
        	$clue = $query[$this->sharding_clue];
        elseif ($db !== false)
        	$clue = $db->host . $db->port . $db->name . $this->table_name;
        
        return strval($clue);
    }

    public function get_db($query = null) {
        if (is_null($query))
            return Database::get_instance();

        $clue = $this->get_sharding_clue_from_query($query);
        return Shards::get_database($this->sharding_clue . '@' . $clue);
    }

    protected function get_revision_cache_key($clauses = null, $db = false) {
        if (is_null($clauses))    
            return parent::get_revision_cache_key($clauses);

        $clue = '';

        if (is_array($clauses) ) {
            if (isset($clauses['query']))
                $clue = $this->get_sharding_clue_from_query($clauses['query'], $db);
            else
                $clue = $this->get_sharding_clue_from_query($clauses, $db);
        } elseif (is_object($clauses) && is_a($clauses, 'DBObject')) {
            $clue = $this->get_sharding_clue_from_query($clauses, $db);
        } else {
            $clue = $clauses;
        }

        return parent::get_revision_cache_key($clauses) . '-' . strval($clue);
    }

    protected function get_list_cache_key($clauses, $db = false) {
        $clue = $this->get_sharding_clue_from_query($clauses['query'], $db);
        return $clue . '-' . md5($this->sql_where_clause($clauses) . $clauses['order_by'] . $clauses['limit']) . '-' . $this->get_cache_revision($clauses, $db); 
    }

    protected function get_count_cache_key($clauses) {
        $clue = $this->get_sharding_clue_from_query($clauses['query']);
        return $clue . '-' . md5($this->sql_where_clause($clauses)) . '-' . $this->get_cache_revision($clauses); 
    }
}/*}}}*/

class ShardedDBObject extends DBObject {/*{{{*/
    protected $_sharding_clue = false;

    public function get_sharding_clue() {
        if ($this->_sharding_clue !== false)
            return $this->_sharding_clue;

        if ($this->table->sharding_clue)
            $this->_sharding_clue = $this->table->sharding_clue . '@' . $this->data[$this->table->sharding_clue];

        return $this->_sharding_clue;
    }

    /*
    public function set_sharding_clue($value) {
        if ($this->table->sharding_clue)
            $this->columns[$this->table->sharding_clue] = $value;
        return false;
    }
    public function get_db() {
        if (!is_null($this->db))
            return $this->db;

        $clue = $this->get_sharding_clue();

        if (!$clue) throw new ShardingException('Can not locate correct database without sharding_clue');

        $this->db = Shards::get_database($clue);
        return $this->db;
    }
    */

    protected function is_attached() {
        return true;
    }
}/*}}}*/
