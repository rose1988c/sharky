<?php
    $urls = array(
        '^/accounts/login/?$' => array(
                    'module'    => 'accounts/account.php',
                    'function'  => 'login'
                ),
        '^/accounts/logout/?$' => array(
                    'module'    => 'accounts/account.php',
                    'function'  => 'logout'
                ),
        '^/accounts/signup|register/?$' => array(
                    'module'    => 'accounts/account.php',
                    'function'  => 'signup'
                ),
        '^/accounts/reset/?$' => array(
                    'module'    => 'accounts/account.php',
                    'function'  => 'forget_pass'
                ),
        '^/accounts/resetpass/(?<hash>[a-z0-9]+)/?$' => array(
                    'module'    => 'accounts/account.php',
                    'function'  => 'reset_pass',
                    'arguments'	=> array('hash'),
                ),
        '^/accounts/buddyicon/?$' => array (
        			'module'	=> 'accounts/buddyicon.php',
        			'function'	=> 'buddyicon_edit',
                ),
        '^/accounts/buddyicon/upload/?$' => array (
        			'module'	=> 'accounts/buddyicon.php',
        			'function'	=> 'buddyicon_upload',
                ),
        '^/accounts/buddyicon/delete/?$' => array (
        			'module'	=> 'accounts/buddyicon.php',
        			'function'	=> 'buddyicon_delete',
                ),
    );
?>
