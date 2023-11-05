<?php

namespace Sweeper\HelperPhp\Workerman;

use Workerman\Timer;
use Workerman\Worker;

trait WorkermanMiddleware
{

    /** @var string 日志目录 */
    protected $logDir = 'workerman';

    /**
     * 获取存储文件信息
     * @return array ['目录','文件名','目录文件名前缀']
     */
    protected function getStorageFile(): array
    {
        $path_list      = explode(DIRECTORY_SEPARATOR, str_replace('\\', DIRECTORY_SEPARATOR, static::class ?? pathinfo(__FILE__, PATHINFO_FILENAME)));
        $filename       = end($path_list);
        $dir            = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->logDir;
        $log_dir_prefix = $dir . DIRECTORY_SEPARATOR . $filename;

        return [$dir, $filename, $log_dir_prefix];
    }

    /**
     * 心跳检测
     * @param \Workerman\Worker $worker
     */
    private function heartbeatDetection(Worker $worker): void
    {
        Timer::add(10, function() use ($worker) {
            $time_now = time();
            foreach ($worker->connections as $connection) {
                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if ($time_now - $connection->lastMessageTime > $this->heartbeat_time) {
                    $connection->close();
                }
            }
        });
    }

    /**
     * 设置错误报告
     * 报告级别
     * 值    常量                描述
     * 1    E_ERROR             运行时致命的错误。不能修复的错误。停止执行脚本。
     * 2    E_WARNING           运行时非致命的错误。没有停止执行脚本。
     * 4    E_PARSE             编译时的解析错误。解析错误应该只由解析器生成。
     * 8    E_NOTICE            运行时的通知。脚本发现可能是一个错误，但也可能在正常运行脚本时发生。
     * 16   E_CORE_ERROR        PHP 启动时的致命错误。这就如同 PHP 核心的 E_ERROR。
     * 32   E_CORE_WARNING      PHP 启动时的非致命错误。这就如同 PHP 核心的 E_WARNING。
     * 64   E_COMPILE_ERROR     编译时致命的错误。这就如同由 Zend 脚本引擎生成的 E_ERROR。
     * 128  E_COMPILE_WARNING   编译时非致命的错误。这就如同由 Zend 脚本引擎生成的 E_WARNING。
     * 256  E_USER_ERROR        用户生成的致命错误。这就如同由程序员使用 PHP 函数 trigger_error() 生成的 E_ERROR。
     * 512  E_USER_WARNING      用户生成的非致命错误。这就如同由程序员使用 PHP 函数 trigger_error() 生成的 E_WARNING。
     * 1024 E_USER_NOTICE       用户生成的通知。这就如同由程序员使用 PHP 函数 trigger_error() 生成的 E_NOTICE。
     * 2048 E_STRICT            运行时的通知。PHP 建议您改变代码，以提高代码的互用性和兼容性。
     * 4096 E_RECOVERABLE_ERROR 可捕获的致命错误。这就如同一个可以由用户定义的句柄捕获的 E_ERROR（见 set_error_handler()）。
     * 8191 E_ALL               所有的错误和警告的级别，除了 E_STRICT（自 PHP 6.0 起，E_STRICT 将作为 E_ALL的一部分）。
     * 关闭所有PHP错误报告 error_reporting(0);
     * 报告 E_NOTICE也挺好 (报告未初始化的变量或者捕获变量名的错误拼写) error_reporting(E_ERROR|E_WARNING|E_PARSE|E_NOTICE);
     * 报告 runtime 错误 error_reporting(E_ERROR|E_WARNING|E_PARSE);
     * 报告所有 PHP 错误 error_reporting(-1);
     * 报告所有错误 error_reporting(E_ALL); 等同 ini_set("error_reporting", E_ALL);
     * 报告 E_NOTICE 之外的所有错误 error_reporting(E_ALL^E_NOTICE); || error_reporting(E_ALL&~E_NOTICE);
     */
    public function setErrorReporting(): void
    {
        error_reporting(E_ALL&~E_DEPRECATED&~E_STRICT&~E_NOTICE);
    }

    /**
     * 执行前
     */
    public function onRunBefore() { }

    /**
     * 执行后
     */
    public function onRunAfter() { }

    /**
     * 停止前
     */
    public function onStopBefore() { }

    /**
     * 停止后
     */
    public function onStopAfter() { }

}