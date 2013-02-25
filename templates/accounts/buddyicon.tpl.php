<?php
    $tpl->set('page_title', '修改头像');
    $tpl->set('body_class', 'buddyicon');

    $tpl->add_stylesheet('buddyicon.css', 'buddyicon');
    //$tpl->add_stylesheet('uvumi-crop.css', 'buddyicon');
    //$tpl->add_javascript('UvumiCrop.js', 'buddyicon');
    //$tpl->add_javascript('Swiff.Uploader.js',  'uploader');
?>

<div id="main">
    <div id="buddyicon_editor">
        <?php if ($user->icon_bucket && $user->icon_key) { ?>
        <div id="buddyicon_delete">
            <h4>当前头像:</h4>
            <div id="buddyicon_current">
                <img src="<?php echo urls::get_buddyicon_url($user);?>" width="75" height="75" alt="<?php echo $user->__username  ?>" />
            </div>

            <div id="form_delete">
                <form id="delete_form" action="<?=$web_root?>/accounts/buddyicon/delete/" method="post">
                    <p class="instruct">删除头像后，将使用我们提供的默认图片作为你的头像</p>
                    <input id="delete_submit" type="submit" class="ws-button gray" value="删除头像" />
                </form>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php } ?>

        <div id="buddyicon_uploader" class="clearfix">
            <!-- uploader form -->
            <div id="form_upload">
                <h4>上传头像:</h4>
                <form id="upload_form" action="<?=$web_root?>/accounts/buddyicon/upload/" enctype="multipart/form-data" method="post" class="x-form">
                    <fieldset>
                        <ol>
                            <li>
                                <input type="file" name="icon_file" size="35" />
                                <p class="instruct">你可以选择<span class="highlight">JPEG、GIF、PNG</span>格式的图片文件</p>
                            </li>
                        </ol>
                    </fieldset>
                    <fieldset class="submit">
                        <input type="submit" class="ws-button" value="上传头像" />
                    </fieldset>
                </form>
            </div>
            <!-- end uploader form -->
        </div>
    </div>

    <div class="clearfix"></div>
</div>

