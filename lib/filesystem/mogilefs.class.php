<?php
/* MogileFS.class.php - Class for accessing the Mogile File System
 * Copyright (C) 2007 Interactive Path, Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/* File Authors:
 *   Erik Osterman <eosterman@interactivepath.com>
 *   Jon Skarpeteig <jon.skarpeteig@gmail.com>
 *
 * Thanks to the MogileFS mailing list and the creator of the MediaWiki
 * MogileFS client.
 */

/* Changelog
 *
 * 21.01.2010
 *
 * - setResource with key == null now close file handle before throwing exception
 * - unknown key, and empty file now produce a warning, and not fatal error in doRequest
 * - setFile now tries upload 10 times before failing - example; tracker dies during upload
 * - 0byte check now before upload, and not relying on tracker
 * - added pathcount and noverify options to getPaths (copied from perl client 1.08)
 */


class MogileFS
{
	const DELETE       = 'DELETE';
	const GET_DOMAINS  = 'GET_DOMAINS';
	const GET_PATHS    = 'GET_PATHS';
	const RENAME       = 'RENAME';
	const LIST_KEYS    = 'LIST_KEYS';
	const CREATE_OPEN  = 'CREATE_OPEN';
	const CREATE_CLOSE = 'CREATE_CLOSE';

	const SUCCESS      = 'OK';    // Tracker success code
	const ERROR        = 'ERR';   // Tracker error code
	const DEFAULT_PORT = 7001;    // Tracker port

	protected $retrycount = 10;
	protected $domain;
	protected $class;
	protected $trackers;
	protected $socket;
	protected $requestTimeout;
	protected $putTimeout;
	protected $getTimeout;
	protected $debug;

	public function __construct($domain, $class, $trackers)
	{
		$this->setDomain($domain);
		$this->setClass($class);
		$this->setHosts($trackers);
		$this->setRequestTimeout(10);
		$this->setPutTimeout(4);
		$this->setGetTimeout(10);
		$this->setDebug(0);
	}

	public function getDebug()
	{
		return $this->debug;
	}

	public function setDebug($level)
	{
		return $this->debug = $level;
	}

	public function getRequestTimeout()
	{
		return $this->requestTimeout;
	}

	public function setRequestTimeout($timeout)
	{
		if($timeout > 0)
		return $this->requestTimeout = $timeout;
		else
		throw new Exception(get_class($this) . "::setRequestTimeout expects a positive integer");
	}

	public function getPutTimeout()
	{
		return $this->putTimeout;
	}

	public function setPutTimeout($timeout)
	{
		if($timeout > 0)
		return $this->putTimeout = $timeout;
		else
		throw new Exception(get_class($this) . "::setPutTimeout expects a positive integer");
	}

	public function getGetTimeout()
	{
		return $this->getTimeout;
	}

	public function setGetTimeout($timeout)
	{
		if($timeout > 0)
		return $this->getTimeout = $timeout;
		else
		throw new Exception(get_class($this) . "::setGetTimeout expects a positive integer");
	}

	public function getHosts()
	{
		return $this->trackers;
	}

	public function setHosts($trackers)
	{
		if(is_scalar($trackers))
		$this->trackers = Array($trackers);
		elseif(is_array($trackers))
		$this->trackers = $trackers;
		else
		throw new Exception(get_class($this) . "::setHosts unrecognized host argument");
	}

	public function getDomain()
	{
		return $this->domain;
	}

	public function setDomain($domain)
	{
		if(is_scalar($domain))
		return $this->domain = $domain;
		else
		throw new Exception(get_class($this) . "::setDomain unrecognized domain argument");
	}

	public function getClass()
	{
		return $this->class;
	}

	public function setClass($class)
	{
		if(is_scalar($class))
		return $this->class = $class;
		else
		throw new Exception(get_class($this) . "::setClass unrecognized class argument");
	}

	// Connect to a mogilefsd; scans through the list of daemons and tries to connect one.
	public function getConnection()
	{
		if($this->socket && is_resource($this->socket) && !feof($this->socket))
		return $this->socket;

		foreach($this->trackers as $host)
		{
			$parts = parse_url($host);
			if(!isset($parts['port']))
			$parts['port'] = MogileFS::DEFAULT_PORT;

			$errno = null;
			$errstr = null;
			$this->socket = fsockopen($parts['host'], $parts['port'], $errno, $errstr, $this->requestTimeout);
			if($this->socket)
			break;
		}

		if(!is_resource($this->socket) || feof($this->socket))
		throw new Exception(get_class($this) . "::doConnection failed to obtain connection");
		else
		return $this->socket;
	}


	// Send a request to mogilefsd and parse the result.
	protected function doRequest($cmd, $args = Array())
	{
		try {
			$args['domain'] = $this->domain;
			$args['class'] = $this->class;
			$params = '';
			foreach ($args as $key => $value)
			$params .= '&'.urlencode($key).'='.urlencode($value);

			$socket = $this->getConnection();

			$result = fwrite($socket, $cmd . $params . "\n");
			if($result === false)
			throw new Exception(get_class($this) . "::doRequest write failed");
			$line = fgets($socket);
			if($line === false)
			throw new Exception(get_class($this) . "::doRequest read failed");

			//print "[$line]\n";
			$words = explode(' ', $line);
			if($words[0] == MogileFS::SUCCESS)
			parse_str(trim($words[1]), $result);
			else
			{
				if(!isset($words[1]))
				$words[1] = null;
				switch($words[1])
				{
					case 'unknown_key':
						//trigger_error(get_class($this) . "::doRequest unknown_key {$args['key']}",E_USER_WARNING);
						break;
					case 'empty_file':
						//trigger_error(get_class($this) . "::doRequest empty_file {$args['key']}",E_USER_WARNING);
						break;
					default:
						throw new Exception(get_class($this) . "::doRequest " . trim(urldecode($line)));
				}
			}
			return $result;
		} catch(Exception $e)
		{
			// Clean up
			if(isset($socket))
			fclose($socket);
			// Recast the exception
			throw $e;
		}
	}

	// Return a list of domains
	public function getDomains()
	{
		$res = $this->doRequest(MogileFS::GET_DOMAINS);

		$domains = Array();
		for($i=1; $i <= $res['domains']; $i++)
		{
			$dom = 'domain'.$i;
			$classes = Array();
			for($j=1; $j <= $res[$dom.'classes']; $j++)
			$classes[$res[$dom.'class'.$j.'name']] = $res[$dom.'class'.$j.'mindevcount'];
			$domains[] = Array('name' => $res[$dom],'classes' => $classes);
		}
		return $domains;
	}

	public function exists($key)
	{
		if($key === null)
		throw new Exception(get_class($this) . "::exists key cannot be null");

		try {
			$this->doRequest(MogileFS::GET_PATHS, Array('key' => $key));
			return true;
		} catch(Exception $e)
		{
			return false;
		}
	}

	// Get an array of paths
	public function getPaths($key,$pathcount = 2, $noverify=0)
	{
		if($key === null)
		throw new Exception(get_class($this) . "::getPaths key cannot be null");

		$result = $this->doRequest(MogileFS::GET_PATHS, Array(
					'key' => $key,
					'pathcount' => $pathcount,
					'noverify' => $noverify
					)
		);

		unset($result['paths']);
		return $result;
	}

	// Delete a file from system
	public function delete($key)
	{
		if($key === null)
		throw new Exception(get_class($this) . "::delete key cannot be null");
		for($i=0;$i<$this->retrycount+1;$i++)
		{
			try
			{
				$status = $this->doRequest(MogileFS::DELETE, Array('key' => $key));
				if (!$this->exists($key)) return $status;
				else fclose($this->socket); // In case tracker failed, try another if possible
			}catch (Exception $e) {
				if ($i>=$this->retrycount) throw $e;
			}
		}
		return false;
	}

	// Rename a file
	public function rename($from, $to)
	{
		if($from === null)
		throw new Exception(get_class($this) . "::rename from key cannot be null");
		elseif($to === null)
		throw new Exception(get_class($this) . "::rename to key cannot be null");
		$this->doRequest(MogileFS::RENAME, Array('from_key' => $from, 'to_key' => $to));
		return true;
	}

	// Rename a file
	public function listKeys($prefix = null, $lastKey = null, $limit = null)
	{
		try {
			return $this->doRequest(MogileFS::LIST_KEYS, Array('prefix' => $prefix, 'after' => $lastKey, 'limit' => $limit));
		} catch(Exception $e)
		{
			if(strstr($e->getMessage(), 'ERR none_match'))
			return Array();
			else
			throw $e;
		}
	}

	// Get a file from mogstored and return it as a string
	public function get($key)
	{
		if($key === null)
		throw new Exception(get_class($this) . "::get key cannot be null");
		$paths = $this->getPaths($key);
		foreach($paths as $path)
		{
			$contents = '';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_VERBOSE, ($this->debug > 0 ? 1 : 0));
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
			curl_setopt($ch, CURLOPT_URL, $path);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			if($response === false)
			continue; // Try next source
			curl_close($ch);
			return $response;
		}
		throw new Exception(get_class($this) . "::get unable to retrieve {$key}");
	}
	//TODO public function getFile($key) //Return path to tmp file

	// Get a file from mogstored and send it directly to stdout by way of fpassthru()
	function getPassthru($key)
	{
		if($key === null)
		throw new Exception(get_class($this) . "::getPassthru key cannot be null");
		$paths = $this->getPaths($key);
		foreach($paths as $path)
		{
			$fh = fopen($path, 'r');
			if($fh)
			{
				if(fpassthru($fh) === false)
				throw new Exception(get_class($this) . "::getPassthru failed");
				fclose($fh);
			}
			return $success;
		}
		throw new Exception(get_class($this) . "::getPassthru unable to retrieve {$key}");
	}

	// Save a file to the MogileFS
	public function setResource($key, $fh, $length)
	{
		if($key === null)
		{
			if (is_resource($fh)) fclose($fh);
			throw new Exception(get_class($this) . "::setResource key cannot be null");
		}
		
		if (!is_resource($fh)) {
			throw new Exception(get_class($this) . "::setResource invalid File-Handle resource");
		}

		$location = $this->doRequest(MogileFS::CREATE_OPEN, Array('key' => $key));
		$uri = $location['path'];
		$parts = parse_url($uri);
		$host = $parts['host'];
		$port = $parts['port'];
		$path = $parts['path'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_VERBOSE, ($this->debug > 0 ? 1 : 0));
		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $length);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
		curl_setopt($ch, CURLOPT_PUT, $this->putTimeout);
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Expect: '));
		$response = curl_exec($ch);
		fclose($fh);
		if($response === false)
		{
			$error=curl_error($ch);
			curl_close($ch);
			throw new Exception(get_class($this) . "::set $error");
		}
		curl_close($ch);
		$this->doRequest(MogileFS::CREATE_CLOSE, Array('key'   => $key,
                                                   'devid' => $location['devid'],
                                                   'fid'   => $location['fid'],
                                                   'path'  => urldecode($uri)
		));
		return true;
	}

	public function set($key, $value)
	{
		if($key === null)
		throw new Exception(get_class($this) . "::set key cannot be null");
		$fh = fopen('php://memory', 'rw');
		if($fh === false)
		throw new Exception(get_class($this) . "::set failed to open memory stream");
		fwrite($fh, $value);
		rewind($fh);
		return $this->setResource($key, $fh, strlen($value));
	}

	public function setFile($key, $filename)
	{
		if($key === null)
		throw new Exception(get_class($this) . "::setFile key cannot be null");
		$filesize = filesize($filename);
		$fh = fopen($filename, 'r');
		if($fh === false)
		throw new Exception(get_class($this) . "::setFile failed to open path {$filename}");
		if (!($filesize > 0)) throw new Exception(get_class($this) . "::setFile filesize must be greater than 0");
		
		for($i=0;$i<$this->retrycount+1;$i++)
		{
			try
			{
				$status = $this->setResource($key, $fh, $filesize);
				if ($this->exists($key)) return $status;
				else fclose($this->socket); // In case tracker died
			}catch (Exception $e) {
				if ($i>=$this->retrycount) throw $e;
			}
		}
		return false;
	}
}


class MogileFSProxy {/*{{{*/

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


    public function __construct($mogilefs) {
        $this->mogilefs = $mogilefs;
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
        echo $if_none_match;  
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

        $paths = $this->mogilefs->getPaths($key);

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
        $s = $this->file_key;
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
}
/*
 // Usage Example:
 $mfs = new MogileFS('socialverse', 'assets', 'tcp://127.0.0.1');
 //$mfs->setDebug(10);
 $start = microtime(true);
 $mfs->set('test123',  microtime(true));
 printf("EXISTS: %d\n", $mfs->exists('test123'));
 print "GET: [" . $mfs->get('test123') . "]\n";
 $mfs->delete('test123');
 $stop = microtime(true);
 printf("%.4f\n", $stop - $start);
 */
?>
