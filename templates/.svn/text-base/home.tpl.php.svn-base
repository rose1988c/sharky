<?php
    $tpl->set('page_title', '个人主页');
    $tpl->set('body_class', 'home');
?>

<div id="main">

    <div id="statuses">
        <h3>近况</h3>
        <div>
            <form id="status_form" action="<?=$web_root?>/statuses/update/" method="post">
                <textarea name="status" class="text"></textarea>
                <p><input type="submit" class="ws-button" value="发布" /></p>
            </form>
        </div>
        <?php
        foreach ($statuses as $status) {
            echo "<p>$status->__text</p>";
        }
        ?>
    </div>


    <div>
        <h3>喜欢的品牌</h3>
        .
        .
        .
    </div>

    <?php if ($friend_requests > 0) { ?>    
    <div>你收到<a href="<?=$web_root?>/friends/requests/"><?=$friend_requests?>朋友请求</a></div>
    <?php } ?>
</div>
