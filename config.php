<?php
/**
 * Created by PhpStorm.
 * User: qiuyu
 * Date: 2017/3/22
 * Time: 下午6:29
 */

return (
    array(
        /**
         * author: qiuyu
         * date: 2017-03-29
         * 爬虫控制参数
         */
        'host'=>'http://localhost:4444/wd/hub',     // selenium服务器地址    http://localhost:4444/wd/hub
        'url'=>'https://www.amazon.com/gp/goldbox', // 主页地址, 需要爬取的deals主页.
        'browser'=>'phantomjs',                     // 浏览器驱动名称  phantomjs, chrome
        // 浏览器 User-Agent.
        'useragent'=>array(
            'mac_chrome'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
            'mac_safari'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_3) AppleWebKit/602.4.8 (KHTML, like Gecko) Version/10.0.3 Safari/602.4.8',
            'mac_firefox'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:52.0) Gecko/20100101 Firefox/52.0',
            'win_chrome'=>'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.98 Safari/537.36',
            'win_firefox'=>'',
        ),
        'requestTimeout'=>100000,                   // 请求selenium服务器的时间, 单位毫秒   30000
        'waitSeconds'=>1,                           // 网页等待元素出现的事件. 单位, 秒.
        'connectionTimeout'=>2000,                  // 连接selenium服务器超时时间, 单位毫秒. 5000
        'implicitlyWait'=>1,                        // 查找元素的超时时间, 搜索元素不存在时等待的时间, 单位秒.
        'blank'=>true,                              // 是否新窗口打开页面, true, false
        'debug'=>true,

        /**
         * author: qiuyu
         * date: 2017-03-29
         * 主页点击商品, 判断是列表页还是详情页的规则.
         */
        'listRules'=> array(
            array(
                'html_flag'=>'<div id="search-results" class="a-section" role="contentinfo">',
                'cssSelector'=>'#search-results a[title]:not([title="Image View"]',
            ),
            array(
                'html_flag'=>'<div id="resultsCol" class="">',
                'cssSelector'=>'#resultsCol a[title]:not([title="Image View"])',
            ),
        ),

        /**
         * author: qiuyu
         * date: 2017-03-29
         * 详细页需要点击元素的匹配规则, 下表越小优先级越高, 目前最多支持匹配到2个规则. 下表是4的, 是下拉列表. 所以是个数组.
         */
        'detailRules'=>array(
            1=>'#variation_style_name li',
            2=>'#variation_size_name li',
            3=>'#shelfSwatchSection-size_name div[id^="size_name_"]',
            4=>array(
                "#native_dropdown_selected_size_name",
                '#native_dropdown_selected_size_name option[id^="native_size_name_"]'
            ),
            5=>'#variation_edition li[id^="edition_"]',
            6=>'#buyboxPrices_feature_div div[id^=mocaBuyBoxQtyOpt_]',
            100=>'#variation_color_name li:not(.swatchUnavailable)',
            101=>"#shelfSwatchSection-color_name div[id^='color_name']",
        ),
    )
);