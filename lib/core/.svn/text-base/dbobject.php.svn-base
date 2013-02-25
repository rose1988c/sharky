<?php

class DBObjectException extends Exception {}
class FieldNotDefinedException extends DBObjectException {}

function get_table($name) { 
    return DBTable::get_table($name);
}

function load_assoc($table_name, $objs, $obj_id_attr = null, $attach_attr = null) {
    return load_objects($table_name, $objs, $obj_id_attr, $attach_attr);
}

function load_objects($table_name, $objs, $obj_id_attr = null, $attach_attr = null) {
	if (!$objs) return $objs;
    if (!is_array($objs)) {
        $objs = array($objs);
    }

    $ids = $objs;

    if (!is_null($obj_id_attr)) {
        $ids = array();
        if (is_array($obj_id_attr)) {
            foreach ($objs as $obj) {
                $id = array();
                foreach ($obj_id_attr as $attr) {
                	if (!$obj) continue;
                    if (is_array($obj)) 
                        $id[] = $obj[$attr];
                    else
                    	$id[] = $obj->$attr;
                }
                $ids[] = $id;
            }
        } else {
            foreach ($objs as $obj) {
            	if (!$obj) continue;
                if (is_array($obj))
                    $ids[] = $obj[$obj_id_attr];
                else 
                    $ids[] = $obj->$obj_id_attr;
            }
        }
    }

    $table = get_table($table_name);
    if ($table === null)
        throw new DBObjectException("Table '$table_name' not exists");
    $objects = $table->load_multi($ids);

    if (is_null($attach_attr)) {
        return $objects;
    } else {
        $parents = array_values($objs);
        for ($i = 0, $l = count($parents); $i < $l; $i++) {
            $parents[$i]->$attach_attr = $objects[$i];
        }
        return $objs;
    }
}

function next_global_id($table_name) {
    global $dbobject_connection_id;

    if (!isset($dbobject_connection_id)) {
		$dbobject_connection_id = new Database(config_get('database.id.host'), 
                                               config_get('database.id.port'),
                                               config_get('database.id.db'),
                                               config_get('database.id.user'),
                                               config_get('database.id.password'));
    }

    $dbobject_connection_id->query("INSERT INTO `{$table_name}` () VALUES()");
    return $dbobject_connection_id->insert_id();
}


/**
 * DBTable
 **/
class DBTable {
    private static $tables;

    public $name;
    public $table_name;

    public $pk_columns     = array();
    public $non_pk_columns = array();
    public $columns        = array();
    public $pk_count       = 0;

    public $sqls           = array();

    public $cache_page_size = 100;
    public $isolate_column  = null;
    public $rev_id          = false;

    public function __construct($name, $table_name, $definitions, $isolate_column = null) {
        $this->name       = $name;
        $this->table_name = $table_name;
        $this->init_definitions($definitions);
        $this->isolate_column = $isolate_column;

        DBTable::$tables[$name] = $this;
    }

    protected function init_definitions($definitions) {
        foreach ($definitions as $key => $value) {
            $col = array_merge(array(
                    'type'                  => 'string',
                    'primary'               => false,
                    'auto_increment'        => false,
                    'global_auto_increment' => false,
                    'null'                  => false,
                    'default'               => false,
                ), $value);

            if (isset($col['autoincrement'])) {
                $col['auto_increment'] = $col['autoincrement'];
                unset($col['autoincrement']);
            }

            if ($col['default'] === false) {
                switch ($col['type']) {
                    case 'integer':
                    case 'long':
                    case 'float':
                    case 'decimal':
                        $col['default'] = 0; break;
                    case 'string':
                        if (!$col['null']) {
                            $col['default'] = '';
                            break;
                        }
                    default:
                        $col['default'] = null;
                }    
            }

            $this->columns[$key] = $col;
            
            if ($col['primary'] === false) {
                $this->non_pk_columns[] = $key;
            } else {
                $this->pk_columns[]  = $key;
                $this->pk_count += 1;
            }

            if ($key == 'rev_id') $this->rev_id = true;
        }
    }

    /********************************************************************************
     * static functions
     ********************************************************************************/
    public static function get_table($name) {
        return array_get(self::$tables, $name, null);
    }

    public static function db_escape_wrapper($x) { 
        return '`' . $x . '`';
    }

    /********************************************************************************
     * 单库API 方法
     ********************************************************************************/
    /* {{{2 */
    public function insert($data) {
        $this->db_insert($this->get_db($data), $data);
    }

    public function update($obj) {
        $this->db_update($this->get_db($obj), $obj);
    }

    public function delete($obj) {
        $this->db_delete($this->get_db($obj), $obj);
    }

    public function load() {
        if (func_num_args() == 1) {
            $args = func_get_args();

            if (is_array($args[0]))
                $args = array_flatten($args);
        
        } else {
            if (func_num_args() != $this->pk_count) throw new DBObjectException("参数不足, 表{$this->table_name}有{$this->pk_count}个主健");
            $args = func_get_args();
        }
        
        return $this->db_load($this->get_db($args), $args);
    }

    public function load_multi($pks, $assoc = false, $db = false) {
        return $this->do_load_multi($pks, $db, $assoc);
    }

    public function count($query) {
        return $this->db_count($this->get_db($query), $query);
    }

    public function fetch_one($query, $id_only = false) {
        $query['limit'] = 1;
        return $this->db_fetch_one($this->get_db($query), $query, $id_only);
    }

    public function fetch_all($id_only = false) {
        return $this->fetch(null, $id_only);
    }

    public function fetch($query, $id_only = false) {
        return $this->db_fetch($this->get_db($query), $query, $id_only);
    }
    

    /********************************************************************************
     * 可指定数据库的API方法
     ********************************************************************************/
    /* {{{ */
    public function db_insert($db, $data) {
        $obj = false;

        if (is_object($data) && is_a($data, 'DBObject')) {
            $obj = $data;
            $data = $data->raw_data();
        } else {
            $obj = $this->new_object($data);
        }

        $sql = $this->get_sql_insert($data);

        $args = array();
        foreach ($this->columns as $key => $col) {
            if ($col['global_auto_increment']) {
            	if (isset($data[$key]) && $data[$key] > 0) {
            		$args[] = $this->to_sql_args($key, $data[$key]);
            	} else { 
	                $id = next_global_id($this->table_name);
	                $obj->$key = $id;
	                $args[] = $this->to_sql_args($key, $id);
            	}
            } else {
                if (isset($data[$key]))
                    $args[] = $this->to_sql_args($key, $data[$key]);
                else
                    $args[] = $this->to_sql_args($key, $col['default']);
            }
        }

        if ($db->query($sql, $args)) {
            $this->reset_cache_revision($data);

            foreach ($this->columns as $key => $col) {
                if ($col['auto_increment'] === true && !(isset($data[$key]) && $data[$key] > 0)) {
                    $obj->$key = $db->insert_id();
                }
            }
            $obj->clear_modification();

            /* 有可能这个key，已经存在于缓存中，值为空 */
            cache_delete($obj->get_key(), $this->table_name);

            return $obj;
        }
        return false;
    }

    public function db_update($db, $obj) {
        if ($this->rev_id) {
            $obj->rev_id = $obj->rev_id + 1;
        }

        $columns = $obj->get_modification();

        if (is_null($columns) || count($columns) == 0) return;

        $args = array();
        $sql = "UPDATE `{$this->table_name}` SET ";
        foreach($columns as $k) {
            $sql .= "`$k`= ?,";
            $args[] = $this->to_sql_args($k, $obj->$k);
        }
        $sql[strlen($sql) - 1] = ' ';

        $conditions = $this->get_sql_pk_condition();
        if ($this->rev_id) $conditions .= " AND `rev_id` = ?";

        $sql .= "WHERE $conditions LIMIT 1";

        $pk_args = array();
        foreach ($this->pk_columns as $k)
            $pk_args[] = $this->to_sql_args($k, $obj->$k);

        if ($this->rev_id) $pk_args[] = $obj->rev_id - 1;

        $db->query($sql, array_merge($args, $pk_args));

        $obj->clear_modification();

        // update cache
        $this->reset_cache_revision($obj);
        cache_delete($this->get_cache_key($pk_args), $this->table_name);

        return $db->affected_rows();
    }

    public function db_delete($db, $obj) {
        $sql = $this->get_sql_delete();

        $args = array();
        foreach ($this->pk_columns as $col)
            $args[] = $this->to_sql_args($col, $obj->$col);

        $db->query($sql, $args);

        // update cache
        $this->reset_cache_revision($obj);
        cache_delete($this->get_cache_key($args), $this->table_name);

        return $db->affected_rows();
    }

    public function new_object($data = null) {
        return new DBObject($this, $data);
    }

    /**
     * 根据主键加载单个对象
     **/
    public function db_load($db) {
        if (func_num_args() == 2) {
            $args = array_slice(func_get_args(), 1);

            if (is_array($args[0]))
                $args = array_flatten($args);
        
        } else {
            if (func_num_args() < $this->pk_count + 1) throw new DBObjectException("参数不足, 表{$this->table_name}有{$this->pk_count}个主健");
            $args = array_slice(func_get_args(), 1);
        }

        if (count($args) > $this->pk_count)
            $args = array_slice($args, count($args) - $this->pk_count);

        $row = $this->do_db_load($db, $args);
        if ($row === false) return false;
        return $this->new_object($row);
    }

    public function db_fetch_one($db, $query, $id_only = false) {
        $objs = $this->do_db_fetch($db, $query, $id_only);
        return empty($objs) ? false : array_shift($objs);
    }

    public function db_fetch($db, $query, $id_only = false) {
        return $this->do_db_fetch($db, $query, $id_only);
    }

    public function db_count($db, $query) {
        $clauses = $this->parse_query($query);
        return $this->do_db_count($db, $clauses);
    }

    /********************************************************************************
     * Protected methods. 有需要的话，在子类中覆盖这些方法
     ********************************************************************************/
    
    protected function do_db_load($db, $args) {
        // load from cache
        $cache_key = $this->get_cache_key($args);
        $row = cache_get($cache_key, $this->table_name);

        if ($row === false) {
            // load from db
            $db->query($this->get_sql_load(), $args);
            if ($db->has_rows()) {
                $row = $db->get_row();

                // store in cache
                cache_set($cache_key, $row, $this->table_name);
            }
            /*
            else {
                cache_set($cache_key, null, $this->table_name);
            }
            */
        }
        if (is_null($row))
            $row = false;
        return $row;
    }

    protected function do_load_multi($pks, $db = false, $assoc = false) {
        $all_keys    = array();
        $unique_keys = array();
        $keys        = array();

        foreach ($pks as $pk) {
            $key = is_array($pk) ? implode('-', $pk) : strval($pk);
            $all_keys[] = $key;
            $unique_keys[$key] = $pk;

            if ((is_array($pk) && count(array_filter($pk)) === count($pk)) || (!is_array($pk) && $pk)) {
                $keys[] = $key;
            }
        }

        // 过滤掉重复的
        $unique_str_keys = array_values(array_unique($keys));

        // 先从缓存中查找
        $rows = cache_get_multi($unique_str_keys, $this->table_name);

        if ($rows === false) {
            $missed = $unique_str_keys;
        } else {
            $missed = array();
            foreach ($rows as $k => $v) {
                if ($v === false) $missed[] = $k;
            }
        }

        if (count($missed) > 0) {
            // 从数据库中查找
            $load_keys = array();
            foreach ($missed as $key) {
                $load_keys[] = $unique_keys[$key];
            }
            $missed_rows = $this->_do_load_multi($load_keys, $db);

            // cache them
            //cache_set_multi($missed_rows, $this->table_name);

            $rows = array_merge($rows, $missed_rows);
        }

        $objs = array();
        foreach ($rows as $row) {
            if (is_array($row)) {
                $o = $this->new_object($row);
                $objs[$o->get_key()] = $o;
            }
        }

        $result = array();
        
        foreach ($all_keys as $key) {
            if ($assoc) {
                $result[$key] = isset($objs[$key]) ? $objs[$key] : false;
            } else {
                $result[] = isset($objs[$key]) ? $objs[$key] : false;
            }
        }

        return $result;
    }

    private function _do_load_multi($ids, $db = false) {
        /*{{{
        if (config_get('enable_async_sql', false) && count($ids) > config_get('async_min_sql', 10)) {
            $sql = $this->get_sql_load();

            $reqs = array();
            foreach ($ids as $id) {
                $db   = $this->get_db($id);
                $args = is_array($id) ? $id : array($id);
                $reqs[] = array(
                        'db'    => $db,
                        'sql'   => $sql,
                        'args'  => $args,
                    );
            }

            $backbone = Backbone::get_instance();
            $result = $backbone->async_exec_sqls($reqs);

            if ($result !== false) {
                $rows = array();
                $ids_count = count($ids);
                for ($i = 0; $i < $ids_count; $i++) {
                    $id  = $ids[$i];
                    $db  = $this->get_db($id);
                    $key = is_array($id) ? implode('-', $id) : $id;
                    $rows[$key] = $result[$i] === false ? $this->do_db_load($db, $id) : $result[$i][0];
                }

                return $rows;
            }
        }
        }}}*/

        $rows = array();
        foreach ($ids as $id) {
            if (!is_array($id)) $id = array($id);
            if (!$db) 
                $conn = $this->get_db($id);
            else
                $conn = $db;
            $key = is_array($id) ? implode('-', $id) : $id;
            $rows[$key] = $this->do_db_load($conn, $id);
        }

        return $rows;
    }

    protected function do_db_fetch($db, $query, $id_only = false) {
        $clauses = $this->parse_query($query);

        if ($clauses['page'] === false) {
            return $this->do_db_glob($db, $clauses, $id_only);
        }
        else {
            $total = $this->do_db_count($db, $clauses);
            $pager = new Pager($clauses['page'], $clauses['per_page'], $total);
            $objs  = $this->do_db_glob($db, $clauses, $id_only);
            return array('data' => $objs, 'pager' => $pager);
        }
    }

    protected function to_sql_args($key, $value) {
        if (is_null($value)) return null;

        $col = $this->columns[$key];
        switch ($col['type']) {
            //case 'string':
            //    return utf8_sanitize($value); // 我们只接受utf8字符, 假设前面的代码已经处理过
            case 'date':
                return is_numeric($value) === true ? date('Y-m-d', $value) : $value;
            case 'datetime':
                return is_numeric($value) === true ? date('Y-m-d H:i:s', $value) : $value;
            case 'json':
                return json_encode($value);
            default:
                return $value;
        }
    }

    protected function parse_where_query($query) {
        static $keywords = array('order_by', 'page', 'per_page', 'limit');
        $i = 0;
        $where = array();

        if (is_null($query)) return $where;

        foreach ($query as $k => $v) {
            if (is_int($k)) {
                if (is_array($v)) {
                    $where['sub_' . ++$i] = $this->parse_where_query($v);
                }
                else if (is_string($v) && in_array(strtolower($v), array('and', 'or'))) {
                    $where[] = $v;
                }
            }
            else {
                @list($col, $suffix) = explode('__', $k, 2);

                if (in_array($col, $keywords)) 
                    continue;
                
                $op    = '=';
                $value = $v;
                
              	if ($suffix == 'in' or $suffix == 'nin') { 
              		if (!is_array($value)) continue;
                	$cond = array();
                	foreach ($value as $index => $item) {
                		if ($suffix == 'in') { 
	                		$cond[] = array($col => $item);
	                		if ($index != count($value) - 1) $cond[] = 'or';
                		} else {
	                		$cond[] = array($col.'__ne' => $item);
	                		if ($index != count($value) - 1) $cond[] = 'and';
                		}
                	}
                	$where['sub_' . ++$i] = $this->parse_where_query($cond);
                	continue;
              	}

                switch ($suffix) {
                    case 'gt':
                        $op = '>';
                        break;
                    case 'gte':
                        $op = '>=';
                        break;
                    case 'lt':
                        $op = '<';
                        break;
                    case 'lte':
                        $op = '<=';
                        break;
                    case 'ne':
                        $op = '!=';
                        break;
                    case 'isnull':
                        $op    = 'IS';
                        $value = $v ? 'NULL' : 'NOT NULL';
                        break;
                    case 'startswith':
                        $op    = 'LIKE';
                        //$value = $db->quote($v . '%');
                        $value = $v . '%';
                        break;
                    case 'endswith':
                        $op    = 'LIKE';
                        //$value = $db->quote('%' . $v);
                        $value = '%' . $v;
                        break;
                    case 'like':
                        $op = 'LIKE';
                        $value = '%' . $v . '%';
                        break;
                    default:
                        break;
                }

                $where[] = array($col, $op, $value);
            }
        }

        return $where;
    }

    protected function sql_where_subclause($columns, $db) {
        $sql = '';
        $op  = false; 
        foreach ($columns as $k => $v) {
            if (is_string($v)) {
                $sql .= ' ' . strtoupper($v) . ' ';
                $op = false;
            } 
            else if (is_int($k)) {
                $value = ($db && $v[1] != 'IS') ? $db->quote($this->to_sql_args($v[0], $v[2])) : $v[2];
                if ($op) $sql .= ' AND ';
                $sql .= "`{$v[0]}` {$v[1]} {$value}";
                $op = true;
            }
            else {
                if ($op) $sql .= ' AND ';
                $sql .= '(' . $this->sql_where_subclause($v, $db) . ')';
                $op = true;
            }
        }
        return $sql;
    }

    protected function sql_where_clause($clauses, $db = false) {
        $columns = $clauses['where'];
        if (empty($columns)) {
            return '';
        }
        return $this->sql_where_subclause($columns, $db);
    }

    protected function parse_query($query) {
        $where    = false;
        $order_by = '';
        $limit    = false;
        $page     = false;
        $per_page = false;


        $where = $this->parse_where_query($query);
        if (!is_null($query)) {
            $page     = (isset($query['page']) && ($query['page'] !== false)) ? intval($query['page']) : false;
            $per_page = isset($query['per_page']) ? intval($query['per_page']) : false;

            $limit    = isset($query['limit']) ? intval($query['limit']) : false;
            //if ($per_page === false && isset($query['limit'])) $per_page = intval($query['limit']);

            if ($page     !== false && $page < 1)     $page = 1;
            if ($per_page !== false && $per_page < 0) $per_page = 0;
            if ($limit    !== false && $limit < 0)    $limit = 0;

            if (isset($query['order_by'])) {
                $order_by_cols = explode(',', $query['order_by']);
                $cols = array();
                foreach ($order_by_cols as $col) {
                    @list($col, $direction) = explode(' ', trim($col), 2);
                    if (empty($direction)) $direction = 'ASC';
                    if (strpos($col, '__') !== false) {
	                	$col = str_replace('__', ',', $col);
                    	$cols[] = $col . ' ' . strtoupper($direction);
                    } elseif (strtolower($col) == 'rand()')
                        $cols[] = 'RAND()';
                    else
                        $cols[] = '`' . $col . '` ' . strtoupper($direction);
                }
                $order_by = 'ORDER BY ' . implode(', ', $cols);
            }
        }

        $result = array('query'    => $query, 
                        'where'    => $where, 
                        'order_by' => $order_by,
                        'limit'    => '',
                        'page'     => false,
                        'per_page' => false,
                        'sublist'  => false);

        if ($page) {
            $page     = intval($page);
            $per_page = $per_page ? intval($per_page) : 20;

            if ($page <= 0) $page = 1;

            $result['page']     = $page;
            $result['per_page'] = $per_page;
        }
        else if ($per_page) {
            $result['limit'] = 'LIMIT ' . $per_page;
            return $result;
        } 
        else if ($limit) {
            $result['limit'] = 'LIMIT ' . $limit;
            return $result;
        } 
        else {
            return $result;
        }

        $limit_query = $this->parse_limit_query($page, $per_page);

        return array_merge($result, $limit_query);
    } 

    public function parse_limit_query($page, $per_page) {
        $offset = ($page - 1) * $per_page;

        $cache_offset = 0;
        while ($cache_offset <= $offset) {
            if ($cache_offset + $this->cache_page_size >= $offset + $per_page) {
                break;
            }
            $cache_offset += $this->cache_page_size;
        }

        $result = array();

        if ($cache_offset <= $offset) {
            $result['limit']   = 'LIMIT ' . $cache_offset . ', ' . $this->cache_page_size;
            $result['sublist'] = array('offset' => ($offset - $cache_offset), 
                                       'length' => $per_page);
        } else {
            $result['limit']   = 'LIMIT ' . $offset . ', ' . $per_page;
            $result['sublist'] = false;
        }

        return $result;
    }

    protected function do_db_count($db, $clauses) {
        // check out the cache first
        $cache_key = $this->get_count_cache_key($clauses);
        $id_count  = cache_get($cache_key, $this->table_name . '_count');

        if ($id_count === false) {
            $where    = $this->sql_where_clause($clauses, $db);
            if (!empty($where))
                $where = 'WHERE ' . $where;
            $id_count = $db->get_value("SELECT COUNT(1) FROM `{$this->table_name}` {$where}");
            cache_set($cache_key, $id_count, $this->table_name . '_count');
        }

        return intval($id_count);
    }

    /**
     * 获取列表时，我们只获得ID列表，然后根据ID单独获取对象. 这样做是为了充分利用缓存.
     * 我们的对象基本上都已经在缓存中。
     * ID列表也会缓存起来，以md5(TableName+Where+OrderBy+Limit SQL Clause)为Cache Key.
     **/
    protected function do_db_glob($db, $clauses, $id_only = false) {
        // check out the cache first
        $cache_key    = $this->get_list_cache_key($clauses, $db);
        if (isset($clauses['order_by']) && $clauses['order_by'] == 'ORDER BY RAND()') {
        	$id_list  = false;
        } else {
	        $id_list  = cache_get($cache_key, $this->table_name . '_list');
        }

        $query        = $clauses['query'];
        $select_cols  = array();
        $inquery_cols = array();
        
        foreach ($this->pk_columns as $pk) {
            if (isset($query[$pk]))        
                $inquery_cols[] = $pk;
            else
                $select_cols[] = $pk;
        }

        if (empty($select_cols)) {
            $args = array();
            foreach ($this->pk_columns as $pk) {
                $args[] = $query[$pk];
            }
            $result = array();
            $row = $this->do_db_load($db, $args);
            if ($row) $result[] = $this->new_object($row);
            return $result;
        }

        if ($id_list === false) {
            $id_list = array();

            $sql_select_cols = implode(', ', array_map(array('DBTable', 'db_escape_wrapper'), $select_cols));

            $where  = $this->sql_where_clause($clauses, $db);
            if (!empty($where))
                $where = 'WHERE ' . $where;

            $sql     = "SELECT {$sql_select_cols} FROM `{$this->table_name}` {$where} {$clauses['order_by']} {$clauses['limit']}";
            $id_list = $db->get_array($sql);

            cache_set($cache_key, $id_list, $this->table_name . '_list');
        }

        if ($clauses['sublist']) {
            $id_list = array_slice($id_list, $clauses['sublist']['offset'], $clauses['sublist']['length']);    
        }

        if (empty($inquery_cols)) {
            $pks = $id_list;
        } else {
            $pks = array();
            foreach ($id_list as $id) {
                $key = array();
                $i = 0;
                foreach ($this->pk_columns as $pk) {
                    if (in_array($pk, $inquery_cols)) {
                        $key[] = $query[$pk];
                    } else {
                        $key[] = $id[$i++];
                    }
                }        
                $pks[] = $key;
            }
        }

        if ($id_only) {
            $ids = array();
            foreach ($pks as $pk) {
                $item = array();
                foreach ($this->pk_columns as $idx => $col) {
                    $item[$col] = $pk[$idx];
                }
                $ids[] = $item;
            }
            return $ids;
        }

        return $this->do_load_multi($pks, $db);
    }

    public function wrap_objects($rows) {
        $objs = array();

        foreach($rows as $row) {
            if (is_array($row)) {
                $o = $this->new_object($row);
            } else {
                $o = $row;
            }
            $objs[] = $o;
        }
        return $objs;
    }

    /* 基本上覆盖以下这些方法就可以了: {{{*/
    public function get_db($query = null) {
        return Database::get_instance();
    }

    protected function get_cache_key($args) {
        return $this->pk_count == 1 ? $args[0] : implode('-', $args);
    }

    protected function get_revision_cache_key($clauses = null) {
        if (is_null($clauses) || is_null($this->isolate_column))    
            return $this->table_name;

        if (is_array($clauses)) {
            if (isset($clauses['query']))
                $data = $clauses['query'];
            else
                $data = $clauses;
        } elseif (is_object($clauses) && is_a($clauses, 'DBObject')) {
            $data = $clauses->raw_data();
        } else {
            return $this->table_name;
        }

        if (isset($data[$this->isolate_column])) {
            $value = $data[$this->isolate_column];
            return $this->table_name . '-' . $this->to_sql_args($this->isolate_column, $value);
        }

        return $this->table_name;
    }

    public function get_cache_revision($clauses = null, $db = false) {
        $cache_key = $this->get_revision_cache_key($clauses, $db);
        $revision  = cache_get($cache_key, 'revision');
        if (!$revision || empty($revision)) {
            list($usec, $sec) = explode(' ', microtime());
            $revision = dechex($sec) . dechex($usec * 1000);
            cache_set($cache_key, $revision, 'revision');
        }
        //echo 'revision: ' . $cache_key . ': ' . $revision . '<br/>';
        return $revision;
    }

    public function reset_cache_revision($clauses = null, $db = false) {
        $cache_key = $this->get_revision_cache_key($clauses, $db);
        list($usec, $sec) = explode(' ', microtime());
        $revision = dechex($sec) . dechex($usec * 1000);
        cache_set($cache_key, $revision, 'revision');
    }

    protected function get_list_cache_key($clauses, $db = false) {
        return md5($this->sql_where_clause($clauses) . $clauses['order_by'] . $clauses['limit']) . '-' . $this->get_cache_revision($clauses, $db); 
    }

    protected function get_count_cache_key($clauses) {
        return md5($this->sql_where_clause($clauses)) . '-' . $this->get_cache_revision($clauses); 
    }

    
    

    /********************************************************************************
     * sql gengeration functions 
     ********************************************************************************/
    
    public function get_sql_all_columns() {
        if (isset($this->sqls['all_columns'])) return $this->sqls['all_columns'];

        $cols = array_keys($this->columns);

        $this->sqls['all_columns'] = implode(', ', array_map(array('DBTable', 'db_escape_wrapper'), $cols));

        return $this->sqls['all_columns'];
    }

    public function get_sql_insert($data = false) {
        if (isset($this->sqls['insert'])) return $this->sqls['insert'];

        $cols = array();
        foreach ($this->columns as $key => $col)
            if ($col['auto_increment'] === false || ($data && isset($data[$key])))
                $cols[] = $key;
        
        $values = implode(', ', array_fill(0, count($cols), '?'));
        $cols = implode(', ', array_map(array('DBTable', 'db_escape_wrapper'), $cols));

        $this->sqls['insert'] = "INSERT INTO `{$this->table_name}` ($cols) VALUES ($values)";
        return $this->sqls['insert'];
    }

    public function get_sql_load() {
        if (isset($this->sqls['load'])) return $this->sqls['load'];

        $cols = $this->get_sql_all_columns();
        $conditions = $this->get_sql_pk_condition();

        $this->sqls['load'] = "SELECT $cols FROM `{$this->table_name}` WHERE $conditions LIMIT 1";
        return $this->sqls['load'];
    }

    public function get_sql_all_pks() {
        if (isset($this->sqls['all_pks'])) return $this->sqls['all_pks'];

        $this->sqls['all_pks'] = implode(', ', array_map(array('DBTable', 'db_escape_wrapper'), $this->pk_columns));
        return $this->sqls['all_pks'];
    }

    public function get_sql_pk_condition() {
        if (isset($this->sqls['pk_condition'])) return $this->sqls['pk_condition'];

        $conditions = array();
        foreach ($this->pk_columns as $col)
            $conditions[] = '`' . $col . '` = ?';

        $this->sqls['pk_condition'] = implode(' AND ', $conditions);
        return $this->sqls['pk_condition'];
    }

    public function get_sql_delete() {
        if (isset($this->sqls['delete'])) return $this->sqls['delete'];

        $conditions = $this->get_sql_pk_condition();

        $this->sqls['delete'] = "DELETE FROM `{$this->table_name}` WHERE $conditions LIMIT 1";
        return $this->sqls['delete'];
    }
    
}



/**
 * Lightweight Data Object Wrapper
 **/
class DBObject {/*{{{1*/
    protected $table;

    protected $data = array();
    protected $attached_data;

    private $__modified_columns = null;
    private $__json_hash = null;
    

    public function __construct($table, $data = null) {
        if (is_string($table))
            $this->table = &DBTable::get_table($table);
        else
            $this->table = &$table;

        // 初始化字段值
        foreach ($table->columns as $key => $col) {
            $this->data[$key] = $col['default'];
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $table->columns)) {
                    $value = $this->convert_type($key, $value, true);
                    $this->data[$key] = $value;
                }
            }
        }
    }


    public function get_modification() {
        if (!is_null($this->__json_hash)) {
            foreach ($this->__json_hash as $key => $hash) {
                if (md5(json_encode($this->$key)) != $hash)
                    $this->mark_modification($key);
            }
        }
        return $this->__modified_columns;
    }

    public function clear_modification() {
        $this->__modified_columns = null;
    }

    public function mark_modification() {
        if (is_null($this->__modified_columns)) $this->__modified_columns = array();

        $args = func_get_args();
        foreach ($args as $key) {
            if (!in_array($key, $this->__modified_columns)) $this->__modified_columns[] = $key;
        }
    }

    public function get_type() {
        return $this->table->name;
    }

    public function __get($key) {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        if ((substr($key, 0, 2) == '__') && array_key_exists(substr($key, 2), $this->data))
            return htmlspecialchars($this->data[substr($key, 2)]);

        if (!is_null($this->attached_data)) {
            if (array_key_exists($key, $this->attached_data))
                return $this->attached_data[$key];

            if ((substr($key, 0, 2) == '__') && array_key_exists(substr($key, 2), $this->attached_data))
                return htmlspecialchars($this->attached_data[substr($key, 2)]);
        }

        throw new FieldNotDefinedException("Field '$key' not exists in table '{$this->table->name}'");
    }
    
    public function __isset($key) {
    	try {
	    	$val = $this->__get($key);
	    	return isset($val);
    	} catch (FieldNotDefinedException $e) {}
    	return false;
    }

    public function has_field($key) {
        if (array_key_exists($key, $this->data))
            return true;
        if (!is_null($this->attached_data) && array_key_exists($key, $this->attached_data))
            return true;
        return false;
    }

    public function __set($key, $value) {
        if (array_key_exists($key, $this->table->columns)) {
            $old = $this->data[$key];

            $value = $this->convert_type($key, $value);

            $this->data[$key] = $value;

            if ($old != $value) {
                $this->mark_modification($key);
            }
            return $old;
        }

        if (is_null($this->attached_data)) {
            $this->attached_data = array();
        }
        $this->attached_data[$key] = $value;
    }

    private function convert_type($key, $value, $initializing = false) {
        if (is_null($value)) return null;

        $col = $this->table->columns[$key];
        switch ($col['type']) {
            case 'integer':
                return intval($value);
            case 'long':
            case 'float':
            case 'decimal':
                return floatval($value);
            case 'date':
            case 'datetime':
                if (is_string($value))
                    return strtotime($value);
            case 'json':
                if (is_string($value)) {
                    if ($initializing) {
                        if (is_null($this->__json_hash)) {
                            $this->__json_hash = array();
                        }
                        $this->__json_hash[$key] = md5($value);
                    }
                    return json_decode($value);
                }
            default:
                return $value;
        }
    }

    public function get_key() {
        if ($this->table->pk_count == 1) {
            return $this->{$this->table->pk_columns[0]};
        }
        return implode('-', $this->get_keys());
    }

    public function get_keys() {
        $keys = array();
        foreach ($this->table->pk_columns as $col) {
            $keys[] = $this->data[$col];
        }
        return $keys;
    }

    public function get_db() {
        return $this->table->get_db($this);
    }

    public function raw_data() {
        return $this->data;
    }
    
    public function insert() {
        return $this->table->db_insert($this->get_db(), $this);
    }

    public function update() {
        return $this->table->db_update($this->get_db(), $this);
    }

    public function delete() {
        return $this->table->db_delete($this->get_db(), $this);
    }

}



// vim: fdm=marker
