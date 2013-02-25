<?php
	require_once(realpath(dirname(__FILE__) . '/../functions.inc.php'));
/**
 * 给一个建议的key
 **/
function ws_filestore_new_key() {/*{{{*/
    // explode the IP of the remote client into four parts
    $ip = explode('.', get_remote_addr());
    // get both seconds and microseconds parts of the time
    list($usec, $sec) = explode(' ', microtime());
    // fudge the time we just got to create two 16 bit words
    $usec = (integer) ($usec * 65536);
    $sec  = ((integer) $sec) & 0xFFFF;
    // fun bit--convert the remote client's IP into a 32 bit
    // hex number then tag on the time.
    // Result of this operation looks like this xxxxxxxx-xxxx-xxxx
    return date('Ymd') . sprintf("%08x-%04x-%04x", ($ip[0] << 24) | ($ip[1] << 16) | ($ip[2] << 8) | $ip[3], $sec, $usec);
}


/*
if (config_get('filestore.backend') == 'ypfs') {
    require_once(WFM_ROOT . '/lib/filesystem/ypfs.php');

    if (!isset($GLOBALS['ypfs']))
       $GLOBALS['ypfs'] = new YPFS(config_get('filestore.ypfs_trackers'));

    function ws_filestore_store($bucket, $key, $filename) {
        global $ypfs;

        $ypfs->set_bucket($bucket);
        return $simplefs->put($key, $filename);
    }

    function ws_filestore_delete($bucket, $key) {
        global $simplefs;
        $simplefs->set_bucket($bucket);
        return $simplefs->delete($key);
    }

} else {

    function ws_filestore_get_url($bucket, $key) {
        $domain = config_get('filestore.domain');
        return "http://{$bucket}.{$domain}/$key";
    }

    function ws_filestore_store($bucket, $key, $filename) {
        $url = ws_filestore_get_url($bucket, $key);

		$fh = fopen($filename, 'r');
        if ($fh === false)
            throw new Exception("failed to open file {$filename}");
        
        fseek($fh, 0, SEEK_END);
        $length = ftell($fh);
		fseek($fh, 0);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $length);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_PUT, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		try {
			$response = curl_exec($ch);
			curl_close($ch);
			fclose($fh);
			return $response;
		} catch (Exception $e) {
            fclose($fh);
			throw $e;
		}
    }

    function ws_filestore_delete($bucket, $key) {
        $url = ws_filestore_get_url($bucket, $key);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
    }

    function ws_filestore_get($bucket, $key) {
        $url = ws_filestore_get_url($bucket, $key);

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $data = curl_exec($ch);
		curl_close($ch);
        return $data;
    }

}
*/
?>
