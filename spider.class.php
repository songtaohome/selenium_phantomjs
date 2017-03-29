<?php
/**
 * spider for amazon
 *
 * User: qiuyu, chenyu, minsongtao
 * Date: 2017/3/24
 */
namespace Facebook\WebDriver;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

require_once('vendor/autoload.php');
require_once('./log.php');

class spider
{
    // browser driver
    private $driver;

    // search html element time, ms
    private $findElementTime;

    // debug switch
    public $debug = false;

    // list page handle
    private $listHandle;

    // home page handle
    private $homeHandle;

    // detail page handle
    private $detailHandle;

    // list page rules
    private $listRules;

    // css selector
    private $cssSelector;

    private $_config;

    private $capabilities;

    private $time;

    /**
     * spider begin
     *
     * author: qiuyu
     * date: 2017-03-29
     * @param: array $_config
     */
    public function begin($_config)
    {
        $this->time = microtime(TRUE);
        $this->debug("\n\n\n************开始************");
        if (empty($_config)) {
            die('config is empty, should be a array');
        }
        $this->_config = $_config;

        if (!isset($this->_config['listRules']) || empty($this->_config['listRules'])) {
            $this->debug('配置文件不能为空');die;
        }

        $this->listRules = $_config['listRules'];

        $this->capabilities = DesiredCapabilities::phantomjs();
        $this->capabilities->setCapability("phantomjs.page.settings.userAgent", $this->_config['useragent']['mac_safari']);
        $this->driver = RemoteWebDriver::create(
            $this->_config['host'],
            $this->capabilities,
            $this->_config['connectionTimeout'],
            $this->_config['requestTimeout']
        );
        // $window = new WebDriverDimension(1920, 1080);
        // $this->driver->manage()->window()->setSize($window);
        $this->driver->manage()->window()->maximize();
        $this->driver->manage()->timeouts()->implicitlyWait($this->_config['implicitlyWait']);

        /*↓↓↓↓↓↓↓↓↓↓↓ qiuyu test begin ↓↓↓↓↓↓↓↓↓↓↓*/
            // $this->driver->get("https://www.amazon.com/KUKOME-SHOP-Canvas-Ballet-Different-Children/dp/B013FNRFJC/ref=gbps_img_s-3_596a_63d9463b?smid=A1LLZN48YALBBN&pf_rd_p=af76a610-c28c-4f12-9308-ccc69eba596a&pf_rd_s=slot-3&pf_rd_t=701&pf_rd_i=gb_main&pf_rd_m=ATVPDKIKX0DER&pf_rd_r=GJWH2F6DHTBWW4X7X6W4");
            // $this->detailPageAction();
            // die;
        /*↑↑↑↑↑↑↑↑↑↑↑ qiuyu test over  ↑↑↑↑↑↑↑↑↑↑↑*/

        $this->debug('打开网页');
        $this->driver->get($this->_config['url']);

        $this->homePageAction();
    }

    /**
     * deals home page action
     *
     * author: minsongtao and qiuyu
     * date: 2017-03-29
     */
    public function homePageAction(){
        $this->debug('遍历主页');
        $this->openPageType('_blank');

        $this->driver->wait($this->_config['waitSeconds'])->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::cssSelector("#FilterItemView_page_pagination li.a-selected")
            )
        );

        try{
            // 获取首页的窗口句柄
            $homeHandle = $this->driver->getWindowHandles();
            $this->homeHandle = $homeHandle[0];

            $elements = $this->driver->findElements(WebDriverBy::cssSelector("#widgetContent a#dealImage"));
            foreach ($elements as $v) {
                $this->debug("主页中点击商品");
                $v->click();
                $tmp_time = rand(1,3);
                $this->debug("延时 $tmp_time 秒");
                sleep($tmp_time);
                $this->switchToEndWindow();

                if (!$this->isListPage()) {
                    $this->debug("不是列表页");
                    $this->detailPageAction();
                    $this->debug("切换为主页的窗口句柄");
                    $this->driver->switchTo()->window($this->homeHandle);
                } else {
                    $this->debug("是列表页");
                    $this->debug("遍历列表页开始");

                    do {
                        $this->listPageAction();
                        $loop = $this->isExistNextPage();
                    } while ($loop);
                    $this->debug("关闭列表页");
                    $this->driver->close();
                    $this->debug("切换为主页的窗口句柄");
                    $this->driver->switchTo()->window($this->homeHandle);
                }
            }
        }catch(Exception $e){
            $this->debug('点击失败');
        }
        $this->debug('遍历成功一页');
        $bool = $this->isElementsExsit(WebDriverBy::cssSelector("#FilterItemView_page_pagination ul.a-pagination li.a-last a"));
        if($bool){
            $check = $this->driver->findElement(WebDriverBy::cssSelector("#FilterItemView_page_pagination ul.a-pagination li.a-last a"));
            $check_status = $check->isEnabled();
            if($check_status){
                // 点击
                try{
                    $this->driver->findElement(WebDriverBy::partialLinkText('Next'))->click();
                    // 递归
                    $this->homePageAction();
                }catch (\Exception $e){
                    $this->debug('点击下一页失败');
                    die;
                }
            }else{
                $this->debug('遍历下一页失败');
            }
        }else{
            $this->debug('没有发现下一页标签');
        }
    }

    /**
     * list page action
     *
     * author: qiuyu
     * date: 2017-03-29
     */
    public function listPageAction()
    {
        $this->debug("设置窗口为新窗口打开");
        $this->openPageType('_blank');

        $this->debug("获取列表页源码");
        $pageSource = $this->driver->getPageSource();

        $this->debug("根据列表页的源码, 匹配规则");
        $cssSelector = '';
        foreach ($this->listRules as $rule) {
            $isExist = strpos($pageSource, $rule['html_flag']);
            if ($isExist !== false) {
                $cssSelector = $rule['cssSelector'];
                break;
            }
        }

        if (empty($cssSelector)) {
            $this->debug('列表页根据匹配规则, 没有找到匹配的结果.');
            die;
        }
        $this->debug("获取列表页窗口句柄");
        $listHandle = $this->driver->getWindowHandles();
        $listKey = count($listHandle) - 1;
        $this->listHandle = $listHandle[$listKey];

        // $elements = $this->driver->findElements(WebDriverBy::cssSelector($cssSelector));
        //
        // $this->debug("开始遍历列表页中的商品");
        // foreach ($elements as $v) {
        //     $this->debug("切换为列表页的窗口句柄");
        //     $this->driver->switchTo()->window($this->listHandle);
        //     $this->debug("列表页中点击商品");
        //     $v->click();
        //     sleep(1);
        //     $this->debug("切换为详情页的窗口句柄");
        //     $this->switchToEndWindow();
        //     $this->detailPageAction();
        //     sleep(1);
        // }

        // 切换为列表句柄
        $this->debug("切换为列表页的窗口句柄");
        $this->driver->switchTo()->window($this->listHandle);
    }

    /**
     * is exist next page
     *
     * author: qiuyu
     * date: 2017-03-29
     * @return bool
     */
    public function isExistNextPage()
    {
        $this->debug("判断是否存在下一页");
        $loop = false;

        $isExistNextPage = $this->isElementsExsit('#pagnNextLink', true);

        if ($isExistNextPage) {
            $loop = true;

            // 载入JS, 在原窗口中打开页面.
            $js = <<<js
            var tag_a = document.getElementsByTagName("a");
            for (var i = tag_a.length - 1; i >= 0; i--) {
                tag_a[i].setAttribute("target","_self");
            };
js;
            $nextPage = $this->$driver->findElement(WebDriverBy::cssSelector('#pagnNextLink'));
            $nextPage->sendKeys('xxx')->click();
            sleep(1);
        }
        return $loop;
    }


    /**
     * is list page
     *
     * author: qiuyu
     * date: 2017-03-29
     * @return bool
     */
    public function isListPage()
    {
        $this->debug('判断是否是列表页');

        try {
            $element = $this->driver->findElement(WebDriverBy::cssSelector('#sx-hms-heading>h4'));
            $text = $element->getText();
            if ($text == 'Search Feedback') {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * open new page type
     *
     * author: qiuyu
     * date: 2017-03-29
     * @param string $type, _self , _blank, default: _self
     * @return bool
     */
    public function openPageType($type)
    {
        if ($type == "_blank") {
            $js = <<<js
            var tag_a = document.getElementsByTagName("a");
            for (var i = tag_a.length - 1; i >= 0; i--) {
                tag_a[i].setAttribute("target", "_blank");
            };
js;
        } elseif($type == "_self") {
            $js = <<<js
            var tag_a = document.getElementsByTagName("a");
            for (var i = tag_a.length - 1; i >= 0; i--) {
                tag_a[i].setAttribute("target", "_self");
            };
js;
        }else{
            log::error("openPageType param is error");
            die;
        }

        $this->driver->executeScript($js);
        return array('errNo'=>0, 'result'=>true);
    }

    /**
     * switch last one window
     *
     * author: qiuyu
     * date: 2017-03-29
     */
    public function switchToEndWindow()
    {
        $this->debug("切换为最后一个窗口句柄");
        $arr = $this->driver->getWindowHandles();
        foreach ($arr as $k=>$v){
            if($k == (count($arr)-1)){
                $this->driver->switchTo()->window($v);
            }
        }
    }

    /**
     * is exist elements
     *
     * author: qiuyu
     * date: 2017-03-29
     * @param WebDriverBy $locator
     * @param bool  $onlyOne
     * @return $elements or false
     */
    function isElementsExsit($locator, $onlyOne = false)
    {
        try {
            if ($onlyOne) {
                $elements = $this->driver->findElement(WebDriverBy::cssSelector($locator));
            } else {
                if (is_array($locator) && count($locator) == 2) {
                    $elements = $this->driver->findElement(WebDriverBy::cssSelector($locator[0]))
                        ->findElements(WebDriverBy::cssSelector($locator[1]));
                } else {
                    $elements = $this->driver->findElements(WebDriverBy::cssSelector($locator));
                }
            }
            return $elements;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * detail page action
     *
     * author: qiuyu
     * date: 2017-03-29
     */
    public function detailPageAction()
    {
        // $this->debug("进入详细页, 判断是否有需要点击的元素");
        // $flag = false;
        //
        // $goodsStyleArray = array();
        // foreach ($this->_config['detailRules'] as $key=>$rule) {
        //     $elements = $this->isElementsExsit($rule);
        //
        //     if ($elements) {
        //         $flag = true;
        //         $goodsStyleArray[] = $elements;
        //     }
        // }
        //
        // ksort($goodsStyleArray);
        // $goodsStyleArray = array_values($goodsStyleArray);
        //
        // // whether there is a need to click on the element
        // // exist click element, traversal
        // if ($flag) {
        //     $this->debug("有需要点击的元素");
        //     foreach ($goodsStyleArray[0] as $v) {
        //         $this->debug("点击第一种元素");
        //         $v->click();
        //         sleep(1);
        //         if (count($goodsStyleArray) > 1) {
        //             $this->debug("有第二种需要点击的元素");
        //             foreach ($goodsStyleArray[1] as $color) {
        //                 $class = $color->getAttribute('class');
        //                 if ($class == 'swatchUnavailable') {
        //                     continue;
        //                 }
        //                 $this->debug("点击第二种元素");
        //                 $color->click();
        //                 sleep(1);
        //                 $this->debug("切换为最后一个窗口句柄");
        //                 $this->switchToEndWindow();
        //                 $this->debug("获取详细页源码");
        //                 $html = $this->driver->getPageSource();
        //                 // TODO ChenYu
        //                 // $this->Detail();
        //
        //             }
        //         } else {
        //             $this->debug("切换为最后一个窗口句柄");
        //             $this->switchToEndWindow();
        //             $this->debug("获取详细页源码");
        //             $html = $this->driver->getPageSource();
        //             // TODO ChenYu
        //             // $this->Detail();
        //         }
        //     }
        // // no exist click element
        // } else {
        //     $this->debug("无需要点击的元素");
        //     $this->switchToEndWindow();
        //     $html = $this->driver->getPageSource();
        //     $this->debug("获取详细页源码");
        //     // TODO ChenYu
        //     // $this->Detail();
        //
        // }

        // close detail page window, and switch window handle
        $this->debug("关闭详情页窗口");
        $this->driver->close();
        sleep(1);
        $this->switchToEndWindow();
    }

    /**
     * Details record
     *
     * author:chenyu
     * date:2017-3-29
     * Detial start
     */
    public function Detail(){
        $this->debug("开始记录");
        $this->writes("<<<<<<<<START################\r\n");
        //得到标题
        $title = $this->driver->getTitle();

        //$driver->quit();
        //die;
        $this->writes("<<<标题::\r\n".$title);
        $this->writes("\r\n__________________________________\r\n");
        //分类
        if($this->isElementsExsit('#wayfinding-breadcrumbs_feature_div')) {
            $select = $this->driver->findElement(WebDriverBy::id('wayfinding-breadcrumbs_feature_div'));
            $seletcts = $select->getText();
            $this->writes("<<<分类::\r\n" . $seletcts);
            $this->writes("\r\n__________________________________\r\n");
        }
        //商品
        if($this->isElementsExsit('#aiv-main-content')){//判断是否电影系列
            $this->each('#aiv-main-content','<<<电影::');
            $this->each('#dv-center-features','<<<Product details::');
        }elseif($this->isElementsExsit('#swaReminderHeader_feature_div')){
            $this->each('#swaDetailPage','<<<food::');
            $this->each('#swaProductDetail','<<<Product Details::');
            $this->each('#swaComparePlans','<<<COMPARE PLANS::');
            $this->each('#swaHowItWorks','<<<How it Works::');
            $this->each('#swaFinePrint','<<<THE FINE PRINT::');
        }else{//商品系列
            $bool = $this->isElementsExsit('#centerCol #title');
            if($bool){
                $this->each('#centerCol','<<<商品详情::');
            }elseif($this->isElementsExsit('#leftCol #title')){
                $this->each('#leftCol','<<<商品详情::');
            }
            if($this->isElementsExsit('#rightCol #centerCol')){
                $this->each('#rightCol','<<<商品价格属性::');
            }
            //个别价格标签
            $this->ifsh('#price','<<<价格::');
            //From the Manufacturer
            $this->ifsh('#aplus_feature_div','<<<FROM_THE_MANUFACTURER::');
            //product description
            $this->ifsh('#descriptionAndDetails','<<<PRODUCT_DESCRIPTION::');

            //product information
            $this->ifsh('#prodDetails','<<PRODUCT_INFORMATION::');

            //section
            $this->ifsh('.kmd-section-text-body',"SECTION");

            //TECHNICAL-DETAILS
            $this->ifsh('#technical-details-table','<<<TECHNICAL-DETAILS::');

            //COMPARE
            $this->ifsh('#ce-comp-lt-table','<<<COMPARE::');

            //PRODUCT_DETAILS
            $this->ifsh('#detail-bullets','<<<PRODUCT_DETAILS::');
            //IMPORTANT_INFORMATION
            $this->ifsh('#importantInformation','<<<IMPORTANT_INFORMATION::');
        }


        $this->writes("END>>>>>>>>>>>>>>>>>>>>>>>>>>\r\n");
        $this->debug('记录结束');
    }

    /**
     * debug, write log, print msg
     *
     * author: qiuyu
     * date: 2017-03-29
     * @param $msg
     * @param bool $show
     * @param $logPath
     */
    public function debug($msg, $show = true, $logPath = "./spider.log")
    {
        $time = microtime(TRUE);
        $useTime = $time - $this->time;
        $this->time = $time;
        $msg = date("Y-m-d H:i:s")." useTime: ".sprintf("%.3f", $useTime)."  ".$msg. "\n";
        if ($show) {
            echo $msg;
        }
        file_put_contents($logPath, $msg, FILE_APPEND | LOCK_EX);
    }

    /**
     * The judgment element is present and output
     *
     * @param $type
     * @param  $Attributes
     * author:chenyu
     * date:2017-3-29
     */
    public function ifsh($type,$Attributes){
        if($this->isElementsExsit($type)){
            $this->each($type,$Attributes);
        }
    }

    /**
     * Loop the tag element
     *
     * @param WebDriver $driver
     * @param  $name
     * @param  $Attributes
     * author:chenyu
     * date:2017-3-29
     */
    public function each($name,$Attributes){
        $this->writes($Attributes."\r\n");
        $data = $this->driver->findElements(WebDriverBy::cssSelector($name));
        foreach($data as $v) {
            $word = $v->getText();
            $this->writes($word);
        }
        $this->writes("\r\n__________________________________\r\n");

    }

    /**
     *Write the file
     *
     * @param $word
     * author:chenyu
     * date:2017-3-29
     */

    public function writes($word){
        $filename = 'Detail_log.txt';
        $fh = fopen($filename, "a");
        fwrite($fh, $word);
        fclose($fh);
    }
}

