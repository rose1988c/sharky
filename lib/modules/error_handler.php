<?php

global $tpl;

if (!isset($error_code))
    $error_code = 500;

$tpl->set('error_code', $error_code);
$tpl->set('error_message', $error_message);

switch ($error_code)
{
    case 404:
        $tpl->set('title', '页面未找到');
        $tpl->render('404.tpl.php');
        break;
    default:
        $tpl->set('title', '哦哦,服务器好像出错了,刷新下看看？');
        $tpl->render('error.tpl.php');
}
?>
