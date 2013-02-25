<?php

class SimpleFS {/*{{{*/

    const DELETE       = 'DELETE';
    const GET_PATHS    = 'GET_PATHS';
    const CREATE_OPEN  = 'CREATE_OPEN';
    const CREATE_CLOSE = 'CREATE_CLOSE';
 
    const SUCCESS      = 'OK';    // Tracker success code
    const ERROR        = 'ERR';   // Tracker error code
    const DEFAULT_PORT = 7002;    // Tracker port

    protected $bucket;
    protected $trackers;
    protected $socket;
    protected $request_timeout;
    protected $put_timeout;
    protected $get_timeout;
    protected $debug;


    public function __construct($bucket, $trackers) {
        $this->set_bucket($bucket);
        $this->set_hosts($trackers);
        $this->set_request_timeout(10);
        $this->set_put_timeout(4);
        $this->set_get_timeout(10);
        $this->set_debug(0);
    }

    
    public function get_debug() {
        return $this->debug;
    }

    public function set_debug($level) {
        return $this->debug = $level;
    }

    public function get_request_timeout() {
        return $this->request_timeout;
    }

    public function set_request_timeout($timeout) {
        if ($timeout > 0)
            return $this->request_timeout = $timeout;
        else
            throw new Exception(get_class($this) . '::set_request_timeout expects a positive integer');
    }

    public function get_put_timeout() {
        return $this->put_timeout;
    }

    public function set_put_timeout($timeout) {
        if ($timeout > 0)
            return $this->put_timeout = $timeout;
        else
            throw new Exception(get_class($this) . '::set_put_timeout expects a positive integer');
    }

    public function get_get_timeout() {
        return $this->get_timeout;
    }

    public function set_get_timeout($timeout) {
        if ($timeout > 0)
            return $this->get_timeout = $timeout;
        else
            throw new Exception(get_class($this) . '::set_get_timeout expects a positive integer');
    }

    public function get_hosts() {
        return $this->trackers;
    }

    public function set_hosts($trackers) {
        if (is_scalar($trackers))
            $trackers = array($trackers);

        if (!is_array($trackers))
            throw new Exception(get_class($this) . '::set_hosts unrecognized host argument');

        $this->trackers = array();
        foreach ($trackers as $tracker) {
            $this->trackers[] = parse_url($tracker);
        }
    }

    public function get_bucket() {
        return $this->bucket;
    }

    public function set_bucket($bucket) {
      if (is_scalar($bucket))
          return $this->bucket = $bucket;
      else
          throw new Exception(get_class($this) . '::set_bucket unrecognized bucket argument');
    }


    // Connect to a mogilefsd; scans through the list of daemons and tries to connect one.
    protected function get_connection() {
        if ($this->socket && is_resource($this->socket) && !feof($this->socket))
            return $this->socket;

        shuffle($this->trackers);

        foreach ($this->trackers as $tracker) {
            if (!isset($tracker['port']))
                $tracker['port'] = SimpleFS::DEFAULT_PORT;

            $errno  = null;
            $errstr = null;

            $this->socket = fsockopen($tracker['host'], $tracker['port'], $errno, $errstr, $this->request_timeout);
            if ($this->socket)
                break;
        }

        if (!is_resource($this->socket) || feof($this->socket))
            throw new Exception(get_class($this) . '::do_connection failed to obtain connection');
        else
            return $this->socket;
    }


    // Send a request to mogilefsd and parse the result.
    protected function do_request($cmd, $args = array()) {
        try {
            if (!isset($args['bucket']) || $args['bucket'] === false)
                $args['bucket'] = $this->bucket;

            $params = array();
            foreach ($args as $key => $value)
                $params[] = urlencode($key) . '=' . urlencode($value);

            $params = implode('&', $params); 

            $socket = $this->get_connection();
            
            $result = fwrite($socket, $cmd . ' ' . $params . "\r\n");
            if ($result === false)
                throw new Exception(get_class($this) . '::do_request write failed');
            $line = fgets($socket);
            if ($line === false)
                throw new Exception(get_class($this) . '::do_request read failed');

            //print "[$line]\n";
            $words = explode(' ', trim($line));
            if ($words[0] == SimpleFS::SUCCESS) {
                if (isset($words[1]))
                    parse_str(trim($words[1]), $result);
                else
                    $result = true;
            } else {
                if (!isset($words[1]))
                    $words[1] = null;

                switch($words[1])
                {
                case 'unknown_key':
                    throw new Exception(get_class($this) . "::do_request unknown_key {$args['key']}");

                case 'empty_file':
                    throw new Exception(get_class($this) . "::do_request empty_file {$args['key']}");

                default:
                    throw new Exception(get_class($this) . '::do_request ' . trim(urldecode($line)));
                }
            }
            return $result;
        } catch(Exception $e) {
            // Clean up
            if (isset($socket))
                fclose($socket);
            // Recast the exception
            throw $e; 
        }
    }


    // Get an array of paths
    public function get_paths($key, $bucket = false) {
        if ($key === null)
            throw new Exception(get_class($this) . '::get_paths key cannot be null');

        $result = $this->do_request(SimpleFS::GET_PATHS, array('key' => $key, 'bucket' => $bucket));
        unset($result['paths']);
        return $result;
    }

    // Delete a file from system
    public function delete($key, $bucket = false) {
        if ($key === null)
            throw new Exception(get_class($this) . '::delete key cannot be null');
        $this->do_request(SimpleFS::DELETE, array('key' => $key, 'bucket' => $bucket));
        return true;
    }


    // Get a file from mogstored and return it as a string
    public function get($key, $bucket = false) {
        if ($key === null)
            throw new Exception(get_class($this) . '::get key cannot be null');

        $paths = $this->get_paths($key, $bucket);

        foreach($paths as $path) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, ($this->debug > 0 ? 1 : 0));
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if ($response === false)
                continue; // Try next source
            curl_close($ch);
            return $response;
        }
        throw new Exception(get_class($this) . "::get unable to retrieve {$key}");
    }


    // Save a file to the SimpleFS
    public function put($key, $filename, $bucket = false) {
        /*
        if ($key === null)
            throw new Exception(get_class($this) . '::put key cannot be null');
         */
        $fh = fopen($filename, 'r');
        if ($fh === false)
            throw new Exception(get_class($this) . "::put failed to open path {$filename}");
        
        fseek($fh, 0, SEEK_END);
        $length = ftell($fh);

        $args = array('length' => $length, 'bucket' => $bucket);
        if ($key)
            $args['key'] = $key;

        $result = $this->do_request(SimpleFS::CREATE_OPEN, $args);

        $key      = $result['key'];
        $fid      = $result['fid'];
        $devcount = intval($result['dev_count']);
        $paths    = array();

        for ($i = 0; $i < $devcount; $i++) {
            $idx = $i + 1;
            $paths[$result['devid_' . $idx]] = $result['path_' . $idx];
        }

        foreach($paths as $devid => $uri) {
            $parts = parse_url($uri);
            $host = $parts['host'];
            $port = $parts['port'];
            $path = $parts['path'];

            fseek($fh, 0);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, ($this->debug > 0 ? 1 : 0));
            curl_setopt($ch, CURLOPT_INFILE, $fh);
            curl_setopt($ch, CURLOPT_INFILESIZE, $length);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);
            curl_setopt($ch, CURLOPT_PUT, $this->put_timeout);
            curl_setopt($ch, CURLOPT_URL, $uri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect: '));
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response !== false) {
                fclose($fh);
                $this->do_request(SimpleFS::CREATE_CLOSE, array('key'    => $key,
                                                                'bucket' => $bucket,
                                                                'devid'  => $devid,
                                                                'fid'    => $fid,
                                                                'path'   => urldecode($uri)
                                                           ));
                return $key;
            }
        }

        throw new Exception(get_class($this) . '::put failed');
    }

}/*}}}*/


class SimpleFSProxy {/*{{{*/

    protected $proxy_headers = array('Content-Length', 'Cache-Control', 'ETag', 'Expires', 'Last-Modified', 'Server');

    protected $file_key      = false;
    protected $content_type  = 'image/jpeg';
    protected $last_modified = false;
    protected $cache_expires = false;
    protected $_etag = false;

    protected $code = 0;
    protected $content_length = 0;  // 用户生成etag, conditional get
    protected $out_total = 0;  // 文件实际长度
    protected $out_bytes = 0;  // 用于重试其它节点
    protected $finish_write_headers = false;

    protected $timeout = 10;
    protected $simplefs;

    protected $headers = array();


    public function __construct($simplefs) {
        $this->simplefs = $simplefs;
    }


    public function set_content_type($content_type) {
        $this->content_type = $content_type;
    }


    public function set_content_length($length) {
        if ($length > 0)
            return $this->content_length = $length;
        else
            throw new Exception(get_class($this) . '::set_content_length expects a positive integer');
    }


    public function set_timeout($timeout) {
        if ($timeout > 0)
            return $this->timeout = $timeout;
        else
            throw new Exception(get_class($this) . '::set_timeout expects a positive integer');
    }


    public function set_cache_expires($expires) {
        if ($expires > 0) {
            $this->cache_expires = $expires;
            $this->add_header('Cache-Control', 'max-age=' . $expires);
            $this->add_header('Expires', gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        } else {
            throw new Exception(get_class($this) . '::set_cache_expires expects a positive integer');
        }
    }


    public function set_last_modified($last_modified) {
        if ($last_modified > 0) {
            $this->last_modified = $last_modified;
            $this->add_header('Last-Modified', gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');
        } else {
            throw new Exception(get_class($this) . '::set_last_modified expects a positive integer');
        }
    }


    public function add_header($name, $value) {
        $this->headers[$name] = $value;    
    }



    public function conditional_output($key) {
        $this->file_key = $key;

        $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
        $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : false;

        if (!$if_modified_since && !$if_none_match) {
            return $this->streaming_output($key);
        }

        $etag = $this->etag();
               
        /*
        echo "tag: $etag<br/>";
        echo "if_none_match: $if_none_match<br/>";
         */
        // At least one of the headers is there - check them
        if ($if_none_match && $if_none_match != $etag) {
            return $this->streaming_output($key); // etag is there but doesn't match
        }

        if ($if_modified_since) {
            $if_modified_since = strtotime($if_modified_since);

            /*
            echo "if_modified_since: $if_modified_since<br/>";
            echo "last_modified: {$this->last_modified}<br/>";
             */

            if (!$this->last_modified || $if_modified_since < $this->last_modified) {
                return $this->streaming_output($key);
            }
        }
        
        // Nothing has changed since their last request - serve a 304 and exit
        header('HTTP/1.1 304 Not Modified');
        header('Cache-Control: private');
        header('ETag: ' . $etag);
        header('Content-Length: 0');
    }


    public function streaming_output($key) {
        $this->file_key = $key;
        $this->code = 0;
        $this->out_bytes = 0;
        $this->finish_write_headers = false;

        $paths = $this->simplefs->get_paths($key);

        foreach ($paths as $path) {
            // initiate curl transfer
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this, 'read_header'));
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, array(&$this, 'read_body'));

            if ($this->out_bytes > 0) {
                // 已经写了部分数据
                curl_setopt($ch, CURLOPT_RANGE, "{$this->out_bytes}-{$this->content_length}");
            }

            $response = curl_exec($ch);
            curl_close($ch);

            if ($response || ($this->code == 302 && $this->finish_write_headers)) {
                return true;
            }
        }

        throw new Exception(get_class($this) . "::get unable to retrieve {$key}");
    }


    private function read_header($ch, $string) {
        //echo "Header: $string <br/>";
        if (preg_match('/^(.*): (.*?)\s?$/', $string, $result)) {
            if (!$this->out_total && $result[1] == 'Content-Length') {
                $this->out_total = intval($result[2]);
                if ($this->out_total != $this->content_length)
                    $this->content_length = $this->out_total;
            }

            if (!$this->finish_write_headers && in_array($result[1], $this->proxy_headers))
                header("{$result[1]}: {$result[2]}");
        }
        else if (preg_match('/^HTTP\/1\.[01] (\d+) (.*)\s?$/', $string, $result)) {
            $this->code = intval($result[1]);
            if ($this->code == 200 || $this->code == 302) {
                // OK, write response
                if (!$this->finish_write_headers) {
                    header("Content-Type: {$this->content_type}");

                    foreach ($this->headers as $key => $value) {
                        header("$key: $value");
                    }
                }
            } else {
                // Error
                return 0;
            }
        }
        return strlen($string);
    }


    protected function etag() {
        if ($this->_etag)
            return $this->_etag;
        $s = $this->simplefs->get_bucket() . $this->file_key;
        if ($this->last_modified)
            $s .= $this->last_modified;
        if ($this->content_length)
            $s .= $this->content_length;
        $this->_etag = '"' . md5($s) . '"';
        return $this->_etag;
    }

    private function read_body($ch, $string) {
        if ($this->out_bytes == 0) {
            header('ETag: ' . $this->etag());
            if (!$this->finish_write_headers) {
                $this->finish_write_headers = true;
            }
        }
        $length = strlen($string);
        //echo "$length\r\n";
        echo $string;
        $this->out_bytes += $length;
        return $length;
    }
}/*}}}*/

?>
