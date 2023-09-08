<?php
/**
 * 循环调用的时候:从任务队列获取任务常驻内存
 */

use Sweeper\HelperPhp\MultiProcess\MultiProcessManager;

require_once '../../vendor/autoload.php';
require_once 'MultiProcessManager.php';

$mp = new MultiProcessManager(3, 'myProcessName'); // 4代表子进程数, 'myProcessName'是进程的名字

$url = "http://www.baidu.com/";
$mp->master(function(MultiProcessManager $mp) {
    $data = "abc";
    $url  = "http://www.baidu.com/";
    // 主进程的方法请包裹在master里
    while ($mp->loop(1000)) { // 100为等待的毫秒数
        $mp->submit($url, function($data) { // 使用submit方法将其提交到一个空闲的进程，如果没有空闲的，系统会自动等待
            echo $data;
        });
    }
})->slave(function($url, MultiProcessManager $mp) {
    $mp->error_log('fetch %s', $url); // 使用内置的log方法，子进程的log也会被打印到主进程里

    return $url;                      // 直接返回数据，主进程将在回调中收到
});