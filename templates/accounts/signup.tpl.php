<?php
    $tpl->set('page_title', '注册帐号');
    $tpl->set('body_class', 'signup');
?>
<div id="main">
    <!-- register form  -->
    <div class="fwrap">
        <form id="regform" name="regform" action="<?=$_SERVER['REQUEST_URI']?>" method="post" class="x-form">
            <fieldset>
                <ol>
                    <li id="regfoli1"<?=$form->error_pick('username', ' class="error"')?>>
                        <label class="desc" id="title1" for="field1">用户名<span id="req_1" class="req">*</span></label>
                        <div>
                            <input id="field1" name="username" type="text" class="field text medium ifocus" value="<?=$form->username?>" maxlength="255" tabindex="1"/>
                        </div>
                        <p class="instruct" id="instruct1"><small>不超过60个字符, 建议用旺旺名</small></p>
                        <?=$form->error_as_p('username')?>
                    </li>

                    <li id="regfoli2"<?=$form->error_pick('password', ' class="error"')?>>
                        <label class="desc" id="title2" for="field2">密码<span id="req_2" class="req">*</span></label>
                        <div>
                            <input id="field2" name="password" type="password" class="field text medium" value="" maxlength="255" tabindex="2"/>
                        </div>
                        <p class="instruct" id="instruct2"><small>为保证你的数据安全，请不要用你的淘宝密码和简单的数字作为密码</small></p>
                        <?=$form->error_as_p('password')?>
                    </li>

                    <li id="regfoli3"<?=$form->error_pick('password2', ' class="error"')?>>
                        <label class="desc" id="title3" for="field3">确认密码<span id="req_3" class="req">*</span></label>
                        <div>
                            <input id="field3" name="password2" type="password" class="field text medium" value="" maxlength="255" tabindex="3"/>
                        </div>
                        <p class="instruct" id="instruct3"><small>请再次输入密码，以确保输入无误</small></p>
                        <?=$form->error_as_p('password2')?>
                    </li>

                    <li id="regfoli4"<?=$form->error_pick('email', ' class="error"')?>>
                        <label class="desc" id="title4" for="field4">Email<span id="req_4" class="req">*</span></label>
                        <div>
                            <input id="field4" name="email" type="text" class="field text medium" value="<?=$form->email?>" maxlength="255" tabindex="4" /> 
                        </div>
                        <p class="instruct" id="instruct4"><small>帮助您找回密码，收取爱统计重要更新信息</small></p>
                        <?=$form->error_as_p('email')?>
                    </li>

                    <li id="field_privacy"<?=$form->error_pick('privacy', ' class="error"')?>>
                        <label class="choice" for="field6"><input id="field6" name="privacy" type="checkbox" class="field checkbox" value="1" tabindex="5" />我已经阅读并完全同意《用户服务协议》<span id="req_6" class="req">*</span></label>
                        <?=$form->error_as_p('privacy')?>
                    </li>
                </ol>
            </fieldset>

            <fieldset class="submit clearfix">
                <input id="submit" class="ws-button" type="submit" value="马上注册" />
            </fieldset>
        </form>
        <!-- end register form  -->
    </div>
</div>
<?php /* vim: set ft=php.html : */?>
