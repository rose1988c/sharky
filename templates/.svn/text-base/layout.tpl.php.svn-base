<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="zh-cn" xml:lang="zh-cn">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <title><?=$title?> - Sharky</title>

    <!-- HTTP 1.1 -->
    <meta http-equiv="Cache-Control" content="no-store"/>
    <!-- HTTP 1.0 -->
    <meta http-equiv="Pragma" content="no-cache"/>
    <!-- Prevents caching at the Proxy Server -->
    <meta http-equiv="Expires" content="0"/>
    
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
    <meta http-equiv="imagetoolbar" content="no" />
    <meta name="author" content="Widget Star" />
    <meta name="copyright" content="Widget Star" />
    <meta name="company" content="Widget Star" />

    <!-- all in one seo pack 1.4.9 [196,217] -->
    <meta name="description" content="Widget Start Inc · Web, Logo, Identity Design company · " />
    <meta name="keywords" content="css,xhtml,javascript,jquery,design,web design,logo,identity design,icon design,icon" />
    <link rel="canonical" href="http://www.sharky.com" />
    <!-- /all in one seo pack -->

    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />

<?php 
    $tpl->add_stylesheet('screen.css');
    $tpl->output_stylesheets(); 
?>
    <!--[if lte IE 7]><link rel="stylesheet" href="<?=print_m('/css/ie.css')?>" type="text/css" media="screen, projection" /><![endif]-->

<?php if (isset($inline_head_scripts)) echo $inline_head_scripts; ?>
<?php if (isset($inline_head_styles)) echo $inline_head_styles; ?>
</head>

<body class="<?=short_browser_name();?><?php if(isset($body_class)) echo ' ' . $body_class; ?>">
    <div id="page">
        <!-- HEADER -->
        <div id="header">
            <div class="wrap">
                <div id="branding">
                    <a id="logo" href="<?=$web_root?>/" title="">Sharky</a>
                </div>

                <?php if ($auth->is_logged_in()) { ?>
                <div id="account">
                    你好，<?php echo $auth->username; ?>
                    <a href="<?=$web_root?>/messages/inbox/">短信</a> |
                    <a href="<?=$web_root?>/accounts/settings/">设置</a> | 
                    <a href="<?=$web_root?>/accounts/logout/">退出</a>
                </div>
                <?php } else { ?>
                <div id="account">
                    <a href="<?=$web_root?>/accounts/signup/">注册</a> | 
                    <a href="<?=$web_root?>/accounts/login/">登录</a>
                </div>
                <?php } ?>

                <div id="nav">
                    <ul class="menu">
                        <li><a href="<?=$web_root?>/">首页</a></li>
                        <li><a href="<?=$web_root?>/brands/">品牌</a></li>
                        <li><a href="<?=$web_root?>/friends/">朋友</a></li>
                        <li><a href="<?=$web_root?>/albums/">相册</a></li>
                        <li><a href="<?=$web_root?>/reviews/">点评</a></li>
                        <li><a href="<?=$web_root?>/help/">帮助</a></li>
                    </ul>
                </div>
                <!--
                <div id="search">
                    <form action="" id="searchform" method="get" enctype="text/plain">
                        <fieldset>
                            <input id="search_input" type="text" name="s" value="" />
                        </fieldset>
                    </form>
                </div>
                -->
            </div>
        </div>
        <!-- END HEADER -->

        <?php if (isset($page_title)) { ?>
        <!-- PAGE TITLE -->
        <div id="title">
            <h1><?=$page_title?></h1>
        </div>
        <!-- END PAGE TITLE -->
        <?php } ?>

        <!-- MESSAGES -->
        <?php if ($msg->has_error()) { ?>
        <div id="errors" class="error" style="display:none;">
            <ul class="msg">
                <?php echo $msg->flush_error('li', false); ?>
            </ul>
        </div>
        <?php } ?>
        <?php if ($msg->has_msg()) { ?>
        <div id="messages" class="success" style="display:none;">
            <ul class="msg">
                <?php echo $msg->flush_msg('li', false); ?>
            </ul>
        </div>
        <?php } ?>
        <!-- END MESSAGES -->

        <!-- BODY -->
        <div id="body">
            <div id="body_header"></div>

<!-- CONTENT -->
<?php echo $body;?>
<!-- END CONTENT -->

            <div id="body_footer" class="clear"></div>
        </div>
        <!-- END BODY -->

        <!-- FOOTER -->
        <div id="footer">
            <!-- SITEMAP -->
            <div id="sitemap">
                <div class="wrap">
                    <div id="sitenav">    
                        <dl>
                            <dt>品牌</dt>
                            <dd>
                                <ul>
                                    <li><a href="/brands/mine/">我喜欢的</a></li>
                                    <li><a href="/brands/contacts/">朋友们喜欢的</a></li>
                                    <li><a href="/brands/lists/">大家喜欢的</a></li>
                                    <li><a href="/brands/categories/">品牌分类</a></li>
                                    <li><a href="/brands/all/">品牌大全</a></li>
                                </ul>
                            </dd>
                        </dl>
                        
                        <dl>
                            <dt>我</dt>
                            <dd>
                                <ul>
                                    <li><a href="/people/{#username}">个人主页</a></li>
                                    <li><a href="/mine/reviews/">发表的点评</a></li>
                                    <li><a href="/mine/discussion/">参与的讨论</a></li>
                                    <li><a href="/mine/statuses/">短话</a></li>
                                    <li><a href="/mine/votes/">发起的投票</a></li>
                                    <li><a href="/mine/lists/">收藏列表</a></li>
                                    <li><a href="/mine/shops/">去过/想去的商家</a></li>
                                    <li><a href="/mine/items/">买过/想买的产品</a></li>
                                    <li><a href="/mine/contacts/">朋友们</a></li>
                                </ul>
                            </dd>
                        </dl>
                        <dl>
                            <dt>快捷链接</dt>
                            <dd>
                                <ul>
                                    <li><a href="/links/quick/">编辑快捷方式</a></li>
                                </ul>
                            </dd>
                        </dl>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
            <!-- END SITEMAP -->

            <div id="copyright">
                <div class="wrap">
                    <div id="aboutus">
                        <a href="http://www.miibeian.gov.cn/">浙ICP备09005798</a> |
                        <a href="<?=$web_root?>/aboutus/#aboutus">关于我们</a> |
                        <a href="<?=$web_root?>/aboutus/#contact">联系我们</a> |
                        <a href="<?=$web_root?>/qos/">服务条款</a>
                    </div>
                    <div id="company">
                        &copy; 2009 <a href="http://www.sharky.com/">sharky.com</a> All Rights Reserved  
                    </div>
                </div>
            </div>
        </div>
        <!-- END FOOTER -->
    </div>
<?php 

    $tpl->output_javascripts();

    if (isset($tail_scripts)) {
        echo $tail_scripts;
    }

//echo time_profiler();
?>
<!--img src="http://www.atj.com/count_rt.php?site_id=90230"/-->
</body>
</html>
<?php /* vim: set ft=php.html : */ ?>
