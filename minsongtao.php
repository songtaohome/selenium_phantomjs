<?php
namespace Facebook\WebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
require_once('vendor/autoload.php');
set_time_limit(0);
ignore_user_abort(true);
header("Content-Type: text/html; charset=UTF-8");

$host = 'http://localhost:4444/wd/hub'; // this is the default
$capabilities = DesiredCapabilities::chrome();

$waitSeconds = 100;

// 创建
$driver = RemoteWebDriver::create($host, $capabilities, 5000, 50000);
//$driver->get('file:///usr/local/selenium/minamazon.html');
$driver->get('https://www.amazon.com/gp/goldbox/ref=nav_cs_gb');
$driver->manage()->timeouts()->implicitlyWait(15);    //隐性设置15秒
click_list($driver,$waitSeconds);
/**
 * 列表点击进入详情页
 * @param $driver object 浏览器对象
 * @return bool
 */
function click_list($driver,$waitSeconds){
    $js = <<<js
        var tag_a = document.getElementsByTagName("a");
        for (var i = tag_a.length - 1; i >= 0; i--) {
            tag_a[i].setAttribute("target","_blank");
        };
js;
    $driver->executeScript($js);
//     等待加载....
//    $driver->wait($waitSeconds)->until(
//        WebDriverExpectedCondition::visibilityOfElementLocated(
//        //加载标签
//            WebDriverBy::cssSelector("#FilterItemView_page_pagination li.a-selected")
//        )
//    );

    try{
        //滚动至最底部，可将scoll的值设置大一点，这样就达到底部的效果了。
        $elements = $driver->findElements(WebDriverBy::cssSelector('#widgetContent a#dealImage'));
        foreach ($elements as $v) {
            $v->click();
            sleep(rand(5,10));
            echo "--点击成功---";
        }

    }catch(Exception $e){
        echo "点击失败";
    }
    echo "遍历成功一页";
    $bool = isElementExsit($driver,WebDriverBy::cssSelector("#FilterItemView_page_pagination ul.a-pagination li.a-last a"));
    if($bool){
        $check = $driver->findElement(WebDriverBy::cssSelector("#FilterItemView_page_pagination ul.a-pagination li.a-last a"));
        $check_status = $check->isEnabled();
        if($check_status){
            //点击
            try{
                $driver->findElement(WebDriverBy::cssSelector("#FilterItemView_page_pagination ul.a-pagination li.a-last a"))->click();
                //递归
                click_list($driver,$waitSeconds);
            }catch (\Exception $e){
                echo "点击下一页失败";die;
            }
        }else{
            echo "遍历下一页失败";
        }
    }else{
        echo "没有发现下一页标签";
    }
}
$driver->quit();die;


/**
 * 判断元素是否存在
 *
 * @param WebDriver $driver
 * @param WebDriverBy $locator
 */
function isElementExsit($driver,$locator){
    try {
        $nextbtn = $driver->findElement($locator);
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * 切换为新打开的窗口.
 * @param $driver
 */
function switchToEndWindow($driver){
    $arr = $driver->getWindowHandles();
    foreach ($arr as $k=>$v){
        if($k == (count($arr)-1)){
            $driver->switchTo()->window($v);
        }
    }
}
