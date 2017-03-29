<?php
/**
 * Created by PhpStorm.
 * User: qiuyu
 * Date: 2017/3/24
 * Time: 下午5:26
 */
// namespace Facebook\WebDriver;

header("Content-Type: text/html; charset=UTF-8");
error_reporting( E_ALL );
// 永不超时
ini_set('max_execution_time', 0);
set_time_limit(0);
// 设置时区
date_default_timezone_set('Asia/Shanghai');

$_config = include('./config.php');

include("./spider.class.php");

$spider = new Facebook\WebDriver\spider();
$spider->begin($_config);










