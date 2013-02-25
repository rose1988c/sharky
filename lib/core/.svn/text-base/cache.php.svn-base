<?php

function cache_init() {
    $local_cache = config_get('cache.enabled') === true ? new LocalCache() : false;
    $remote_cache = false;
    
    if (config_get('memcached.enabled', false) === true) {
        $remote_cache = new MemcachedCache(config_get('memcached.servers'), false);
    }
    
    if (config_get('memcache.enabled', false) === true) {
        $remote_cache = new MemcacheCache(config_get('memcached.servers'), false);
    }

    if ($local_cache && $remote_cache)
        $GLOBALS['cache'] = new LayeredCache($local_cache, $remote_cache);
    elseif ($local_cache)
        $GLOBALS['cache'] = $local_cache;
    elseif ($remote_cache)
        $GLOBALS['cache'] = $remote_cache;
    else
        $GLOBALS['cache'] = false;
}

function cache_add($key, $data, $group = 'default', $expire = 0) {
	global $cache;
    if ($cache === false) return false;
	return $cache->add($key, $data, $group, $expire);
}

function cache_set($key, $data, $group = 'default', $expire = 0) {
	global $cache;
    if ($cache === false) return false;
	return $cache->set($key, $data, $group, $expire);
}

function cache_replace($key, $data, $group = 'default', $expire = 0) {
	global $cache;
    if ($cache === false) return false;
	return $cache->replace($key, $data, $group, $expire);
}

function cache_get($key, $group = 'default') {
	global $cache;
    if ($cache === false) return false;
	return $cache->get($key, $group);
}

function cache_delete($key, $group = 'default') {
	global $cache;
    if ($cache === false) return false;
	return $cache->delete($key, $group);
}

function cache_flush() {
	global $cache;
    if ($cache === false) return false;
	return $cache->flush();
}

function cache_get_multi($keys, $group = 'default', $fallback_func = false, $multi_fallback_func = false) {
    global $cache;
    if ($cache === false) return false;
    $result = $cache->get_multi($keys, $group);

    $missed = array();
    foreach ($result as $key => $value) {
        if ($value === false) $missed[] = $key;
    }

    if (count($missed) > 0) {
        if (is_string($multi_fallback_func) && function_exists($multi_fallback_func)) {
            $missed = $multi_fallback_func($missed);
            foreach ($missed as $key => $value) {
                $result[$key] = $value;
            }
        } else if (is_string($fallback_func) && function_exists($fallback_func)) {
            foreach ($missed as $id) {
                $result[$id] = $fallback_func($id);
            }
        }
    }

    return $result;
}

function cache_set_multi($items, $group = 'default', $expire = 0) {
    global $cache;
    if ($cache === false) return false;
    return $cache->set_multi($items, $group, $expire);
}


interface Cache {
    function get($id, $group);
    
    function add($id, $data, $group, $expire = 0);
    function set($id, $data, $group, $expire = 0);
    function replace($id, $data, $group, $expire = 0);

	function get_multi($keys, $group);
    function set_multi($items, $group, $expire = 0);

    function delete($id, $group);
    function flush();
}

class LocalCache implements Cache {

	private $cache = array();

	public function __construct() {
	}

	function add($id, $data, $group, $expire = 0) {
		if (false !== $this->get($id, $group, false))
			return false;

		return $this->set($id, $data, $group, $expire);
	}

	function delete($id, $group) {
		if (false === $this->get($id, $group, false))
			return false;

		unset($this->cache[$group][$id]);
		return true;
	}

	function flush() {
		$this->cache = array();

		return true;
	}

	function get($id, $group) {
		if (isset($this->cache[$group][$id])) {
            return $this->cache[$group][$id];
		}

		return false;
	}

	function replace($id, $data, $group, $expire = 0) {
		if (false === $this->get($id, $group, false))
			return false;

		return $this->set($id, $data, $group, $expire);
	}

	function set($id, $data, $group, $expire = 0) {
		$this->cache[$group][$id] = $data;

		return true;
	}

    function get_multi($keys, $group) {
        $result = array();

        foreach ($keys as $key) {
            $result[$key] = isset($this->cache[$group][$key]) ? $this->cache[$group][$key] : false;
        }

        return $result;
    }

    function set_multi($items, $group, $expire = 0) {
        foreach ($items as $key => $value) {
            $this->set($key, $value, $group, $expire);    
        }
        return true;
    }
}



class MemcachedCache {
    protected $memcached;

    public function __construct($servers, $persistent_id = false) {
        if ($persistent_id === false)
            $this->memcached = new Memcached();
        else
            $this->memcached = new Memcached($persistent_id);
        $this->memcached->addServers($servers);

        //$this->memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $this->memcached->setOption(Memcached::OPT_HASH,         Memcached::HASH_MD5);
        $this->memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_MODULA);
        /* disable compressing, since pylibmc not support it */
        $this->memcached->setOption(Memcached::OPT_COMPRESSION,  false);
        $this->memcached->setOption(Memcached::OPT_TCP_NODELAY,  true);
    }

    public function get($id, $group) {
		$id = str_replace(' ', '-', $id);
        $value = $this->memcached->get($group . '-' . $id);
        if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return false;
        }
        return $this->_unserialize($value);
    }

    private function _serialize($value) {
        return json_encode($value);    
    }

    protected function _unserialize($data) {
        if (is_string($data))
            return json_decode($data, true);
        return $data;
    }
    
	public function add($id, $data, $group, $expire = 0) {
		$id = str_replace(' ', '-', $id);
        $data = $this->_serialize($data);
        return $this->memcached->add($group . '-' . $id, $data, $expire);
    }

	public function set($id, $data, $group, $expire = 0) {
		$id = str_replace(' ', '-', $id);
        $data = $this->_serialize($data);
        return $this->memcached->set($group . '-' . $id, $data, $expire);
    }

	public function replace($id, $data, $group, $expire = 0) {
		$id = str_replace(' ', '-', $id);
        $data = $this->_serialize($data);
        return $this->memcached->replace($group . '-' . $id, $data, $expire);
    }

    public function delete($id, $group) {
		$id = str_replace(' ', '-', $id);
        return $this->memcached->delete($group . '-' . $id);
    }

    public function flush() {
        // never flush memcache
        return true;
    }

    public function get_multi($keys, $group) {
        $mkeys = array();
        $objs  = array();
        foreach ($keys as $key) {
			$key = str_replace(' ', '-', $key);
            $mkeys[] = $group . '-' . $key;
            $objs[]  = false;
        }

        $result = $this->memcached->getMulti($mkeys);
        foreach ($mkeys as $idx => $key) {
            $objs[$keys[$idx]] = isset($result[$key]) ? $this->_unserialize($result[$key]) : false;
        }
        return $objs;
    }

    public function set_multi($items, $group, $expire = 0) {
        $mitems = array();
        foreach ($items as $key => $value) {
			$key = str_replace(' ', '-', $key);
            $mitems[$group . '-' . $key] = $this->_serialize($value);
        }

        return $this->memcached->setMulti($mitems, $expire);
    }
}


class MemcacheCache extends MemcachedCache {

	public function __construct($servers, $persistent = false) {
		$this->memcached = new Memcache();
		foreach ($servers as $server) {
			list($host, $port, $weight) = $server;
			$result = $this->memcached->addServer($host, $port, $persistent, $weight);
		}

// 		//$this->memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
// 		$this->memcached->setOption(Memcached::OPT_HASH,         Memcached::HASH_MD5);
// 		$this->memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_MODULA);
// 		/* disable compressing, since pylibmc not support it */
// 		$this->memcached->setOption(Memcached::OPT_COMPRESSION,  false);
// 		$this->memcached->setOption(Memcached::OPT_TCP_NODELAY,  true);
	}
	public function get($id, $group) {
		$id = str_replace(' ', '-', $id);
		$value = $this->memcached->get($group . '-' . $id);
		if ($value === false) return false;
		return $this->_unserialize($value);
	}
	
	public function get_multi($keys, $group) {
		$mkeys = array();
		$objs  = array();
		foreach ($keys as $key) {
			$key = str_replace(' ', '-', $key);
			$mkeys[] = $group . '-' . $key;
			$objs[]  = false;
		}
	
		$result = $this->memcached->get($mkeys);
		foreach ($mkeys as $idx => $key) {
			$objs[$keys[$idx]] = isset($result[$key]) ? $this->_unserialize($result[$key]) : false;
		}
		return $objs;
	}
	
	public function set_multi($items, $group, $expire = 0) {
		$mitems = array();
		foreach ($items as $key => $value) {
			$key = str_replace(' ', '-', $key);
			$this->set($group . '-' . $key, $this->_serialize($value), 0, $expire);
		}
	}
}

class LayeredCache implements Cache {

    private $local_cache;
    private $remote_cache;

    public function __construct($local_cache, $remote_cache) {
        $this->local_cache  = $local_cache;
        $this->remote_cache = $remote_cache;
    }
    
    public function get($id, $group) {
        $value = $this->local_cache->get($id, $group);
        if ($value === false) {
            // 没有找到，试试remote_cache
            $value = $this->remote_cache->get($id, $group);

            if ($value !== false)
                $this->local_cache->set($id, $value, $group);
        }
        return $value;
    }
    
	public function add($id, $data, $group, $expire = 0) {
        $this->local_cache->add($id, $data, $group, $expire);
        $this->remote_cache->add($id, $data, $group, $expire);
    }

	public function set($id, $data, $group, $expire = 0) {
        $this->local_cache->set($id, $data, $group, $expire);
        $this->remote_cache->set($id, $data, $group, $expire);
    }

	public function replace($id, $data, $group, $expire = 0) {
        $this->local_cache->replace($id, $data, $group, $expire);
        $this->remote_cache->replace($id, $data, $group, $expire);
    }

    public function delete($id, $group) {
        $this->local_cache->delete($id, $group);
        $this->remote_cache->delete($id, $group);
    }

    public function flush() {
        $this->local_cache->flush();
        $this->remote_cache->flush();
    }

    public function get_multi($keys, $group) {
        $result = $this->local_cache->get_multi($keys, $group);
        $not_found = array();

        foreach ($result as $key => $value) {
            if ($value === false)    
                $not_found[] = $key;
        }

        if (count($not_found) > 0) {
            $items = $this->remote_cache->get_multi($not_found, $group); 
            foreach ($result as $key => $value) {
                if ($value === false && $items[$key] !== false) {
                    $result[$key] = $items[$key];   
                }
            }
        }
        return $result;
    }

    public function set_multi($items, $group, $expire = 0) {
        $this->local_cache->set_multi($items, $group, $expire);
        $this->remote_cache->set_multi($items, $group, $expire);
    }
}
