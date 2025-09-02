<?php
//开启session
session_start();
//引入验证码类
require 'phpclass/psn.class.php';
//实例化验证码对象
$c=new Captcha();
//显示验证码
$c->showCaptcha();
//保存验证码字符到session,方便后台验证
$_SESSION['excellence_fun_code']=$c->getCode();
?>