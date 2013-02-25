<?php

function index() {
    global $auth, $tpl;

    if ($auth->is_logged_in()) {
        return home();
    }

    $tpl->render('index.tpl.php', array(
            'title' => '首页',
        ));
}

function home() {
    require_once(DOC_ROOT . '/lib/services/friends.php');

    global $auth, $tpl, $Statuses;

    $statuses = $Statuses->fetch(array(
            'user_id'  => $auth->id,
            'order_by' => 'posted_at desc',
        ));

    // friend requests
    $friend_requests = mcc_count_friend_requests($auth->id);


    $tpl->render('home.tpl.php', array(
            'title'           => '个人主页',
            'statuses'        => $statuses,
            'friend_requests' => $friend_requests,
        ));
}
