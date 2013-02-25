<?php
	define('WEB_ROOT',   '');
	define('MEDIA_ROOT', '');

    $cfg['devmode'] = false;

    // 各个环境的域名(正则表达式)，以便选择不同的配置文件
    $cfg['hosts.production'] = '';
    $cfg['hosts.staging']    = '';
    $cfg['hosts.local']      = '';

    // 域名
    $cfg['site_domain'] = '';
     
    // 数据库配置
	$cfg['database.host']         = '127.0.0.1';
	$cfg['database.port']         = 3306;
	$cfg['database.db']           = 'mcc';
	$cfg['database.user']         = 'root';
	$cfg['database.password']     = '';
	$cfg['database.die_on_error'] = true;

    // Settings for the Auth class
    $cfg['cookie_domain'] = '.' . $cfg['site_domain'];
    $cfg['auth_salt']     = 'qr48)DF"4&%3789ah4324&Y*Gd34OJF*#$x)'; // Pick any random string of characters

    // Session
    $cfg['session.name']         = 'sid';
    $cfg['session.save_handler'] = 'memcached';
    $cfg['session.save_path']    = '127.0.0.1:11211';

    // Cache configurations
    $cfg['cache.enabled'] = true;

    // Memcached Configurations
    $cfg['memcached.enabled'] = true;
    $cfg['memcached.conn_id'] = false;
    $cfg['memcached.servers'] = array(
            array('127.0.0.1', 11211, 33),
        );

    // 临时目录
    $cfg['tmp_dir'] = '/tmp';

    $cfg['photos.max_width']  = 5000;
    $cfg['photos.max_height'] = 5000;

    // 文件存储
    $cfg['filestore.backend'] = 'webdav';
    $cfg['filestore.domain']  = 'sharky.com';

    // searching
    $cfg['searching.provider'] = false;
    $cfg['solr'] = array(
                    'host' => 'localhost',
                    'port' => 8983,
                    'path' => '/solr/',
                );
?>
