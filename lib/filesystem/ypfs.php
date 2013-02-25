<?php
class YPFS {
    const DELETE       = 'DELETE';
    const GET_PATHS    = 'GET_PATHS';
    const CREATE_OPEN  = 'CREATE_OPEN';
    const CREATE_CLOSE = 'CREATE_CLOSE';
 
    const SUCCESS      = 'OK';    // Tracker success code
    const ERROR        = 'ERR';   // Tracker error code
    const DEFAULT_PORT = 7001;    // Tracker port

    protected $bucket;
    protected $trackers;
    protected $socket;
    protected $request_timeout;
    protected $put_timeout;
    protected $get_timeout;
    protected $debug;

    public function __construct($trackers, $bucket = 'default') {
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


    public function get_bucket() {
        return $this->bucket;
    }

    public function set_bucket($bucket) {
        return $this->bucket = $bucket;
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

    // Connect to a mogilefsd; scans through the list of daemons and tries to connect one.
    protected function get_connection() {
        if ($this->socket && is_resource($this->socket) && !feof($this->socket))
            return $this->socket;

        shuffle($this->trackers);

        foreach ($this->trackers as $tracker) {
            if (!isset($tracker['port']))
                $tracker['port'] = YPFS::DEFAULT_PORT;

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


    // Send a request to ypfs server and parse the result.
    public function do_request($cmd, $args = array()) {
        try {
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
            if ($words[0] == YPFS::SUCCESS) {
                if (isset($words[1]))
                    parse_str(trim($words[1]), $result);
                else
                    $result = true;
            } else {
                if (!isset($words[1]))
                    $words[1] = null;

                if (!isset($words[2]))
                    $words[2] = 'Unknown Error';

                throw new Exception(get_class($this) . "::do_request {$words[1]} {$words[2]}");
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
    public function get_paths($key, $info = array()) {
        $type   = isset($info['class']) ? $info['class'] : 'photo';
        $secret = isset($info['secret']) ? $info['secret'] : 'secret';
        $result = $this->do_request(YPFS::GET_PATHS, array(
                                        'class'    => $type,
                                        'username' => $this->bucket, 
                                        'filename' => $key,
                                        'secret'   => $secret));
        unset($result['paths']);
        return array_values($result);
    }

    // Delete a file from system
    public function delete($key, $info = array()) {
        $type = isset($info['class']) ? $info['class'] : 'photo';
        $this->do_request(YPFS::DELETE, array('class'    => $type, 
                                              'username' => $this->bucket, 
                                              'filename' => $key));
        return true;
    }

    // Get a file from mogstored and return it as a string
    public function get($key, $info = array()) {
        $paths = $this->get_paths($key, $info);

        foreach ($paths as $path) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, ($this->debug > 0 ? 1 : 0));
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);

            if (isset($info['save_to'])) {
                $file = fopen($info['save_to'], 'w');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_FILE, $file);
                curl_exec($ch);
                curl_close($ch);
                fclose($file);
                return true;
            } else {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                if ($response === false)
                    continue; // Try next source
                curl_close($ch);
                return $response;
            }
        }
        throw new Exception(get_class($this) . "::get unable to retrieve {$key}");
    }


    // Save a file to YPFS
    public function put($file, $info = array()) {/*{{{*/
        if (is_file($file)) {
            $fh = fopen($file, 'r');
            if ($fh === false)
                throw new Exception(get_class($this) . "::put failed to open path {$file}");
        } else {
            $fh = fopen('php://temp', 'r+');
            fputs($fh, $file);
        }
        
        fseek($fh, 0, SEEK_END);
        $length = ftell($fh);

        $type = isset($info['class']) ? $info['class'] : 'photo';
        $args = array('class' => $type, 'username' => $this->bucket);
        if (isset($info['key'])) $args['filename'] = $info['key'];
        if (isset($info['secret'])) $args['secret'] = $info['secret'];

        $result = $this->do_request(YPFS::CREATE_OPEN, $args);

        $file_key    = $result['filename'];
        $file_secret = $type == 'photo' ? $result['secret'] : '';
        $fid         = $result['fid'];
        $devcount    = intval($result['dev_count']);
        $paths       = array();

        for ($i = 0; $i < $devcount; $i++) {
            $idx = $i + 1;
            $paths[$result['devid_' . $idx]] = $result['path_' . $idx];
        }

        foreach ($paths as $devid => $uri) {
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
                $args = array_merge(array('class'    => $type,
                                          'username' => $this->bucket,
                                          'filename' => $file_key,
                                          'secret'   => $file_secret,
                                          'size'     => $length,
                                          'devid'    => $devid,
                                          'fid'      => $fid,
                                          'path'     => urldecode($uri)
                                    ), $info);
                $this->do_request(YPFS::CREATE_CLOSE, $args);
                return array('key' => $file_key, 'secret' => $file_secret);
            }
        }

        throw new Exception(get_class($this) . '::put failed');
    }/*}}}*/
    
    public function disconnect() {
        if ($this->socket) @fclose($this->socket);
        $this->socket = null;
    }


}
?>
