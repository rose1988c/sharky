<?php
    // 这是所有请求的入口
    require '../lib/bootstrap.inc.php';

    $uri = $_SERVER['REQUEST_URI'];

    if ($_SERVER['REQUEST_METHOD'] == 'GET' 
        && empty($_SERVER['QUERY_STRING'])
        && (rtrim($uri, '/') == $uri)) 
    {
        $_SERVER['REQUEST_URI'] = $uri . '/';
        redirect(full_request_url());
    }

    // 类似Django的URL映射机制，访问最频繁的放最前面
    $urls = array(
        '^/?$' => array(
                    'module'    => 'index.php',
                    'function'  => 'index',
                ),
    );

    $dipatcher = new Dispatcher($urls);
    $dipatcher->dispatch($uri);
?>
