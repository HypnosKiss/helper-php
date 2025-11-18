<?php
/**
 * Created by Administrator PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>${CARET}
 * Time: 2025/10/17 11:46:15
 */

namespace Sweeper\HelperPhp\Test;

use app\logic\platform\aliexpress\HostingOrder;
use PHPUnit\Framework\TestCase;
use redisd\Redisd;

class WorkermanTest extends TestCase
{

    public const HEARTBEAT_TIME = 55;

    public function testWorkerman()
    {
        // 创建一个文本协议的Worker监听1234接口
        $port               = 1234;
        $worker             = new Worker("text://0.0.0.0:$port");
        $worker->name       = class_basename($this) . '::' . __FUNCTION__;
        $worker->count      = 2;
        $worker->reloadable = true;
        $worker->reusePort  = true;

        $logFile                                 = "worker_{$worker->name}_$port.log";
        Worker::$daemonize                       = false;
        Worker::$pidFile                         = runtime_path("runtime/worker.pid_$port"); //设置 pid 文件
        Worker::$logFile                         = runtime_path("log/$logFile.log");         //设置 log 文件
        TcpConnection::$defaultMaxSendBufferSize = 1048576;                                  //设置所有连接的默认应用层发送缓冲区大小，单位字节
        TcpConnection::$defaultMaxPackageSize    = 10485760;                                 //默认的最大可接受数据包大小。

        // 创建一个文本协议的Worker监听1234接口
        $port = 1234;
        $worker             = new Worker('text://0.0.0.0:1234');
        $worker->name       = class_basename($this) . '::' . __FUNCTION__;
        $worker->count      = 2;
        $worker->reloadable = true;
        $worker->reusePort  = true;

        $logFile                                 = "worker_{$worker->name}_$port.log";
        Worker::$daemonize                       = false;
        Worker::$pidFile                         = runtime_path("runtime/worker.pid_$port"); //设置 pid 文件
        Worker::$logFile                         = runtime_path("log/$logFile.log");         //设置 log 文件
        TcpConnection::$defaultMaxSendBufferSize = 1048576;                                  //设置所有连接的默认应用层发送缓冲区大小，单位字节
        TcpConnection::$defaultMaxPackageSize    = 10485760;                                 //默认的最大可接受数据包大小。

        // 进程启动后设置一个每10秒运行一次的定时器
        $worker->onWorkerStart  = function(Worker $worker) {
            Worker::log("Worker $worker->id starting.");

            $_timerId = Timer::add(10, function() use ($worker) {
                $time_now = time();
                foreach ($worker->connections as $connection) {
                    // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                    if (empty($connection->lastMessageTime)) {
                        $connection->lastMessageTime = $time_now;
                        continue;
                    }
                    // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                    if ($time_now - $connection->lastMessageTime > static::HEARTBEAT_TIME) {
                        $connection->close();
                    }
                }
            });

            while (true) {
                $this->debug("Worker $worker->id while start.");


                $this->debug("Worker $worker->id while end.");
                sleep(600);
            }
        };
        $worker->onWorkerStop   = function(Worker $worker) {
            Worker::log("Worker $worker->id stopping.");
            foreach ($worker->connections as $connection) {
                $connection->send('worker stoping...');
            }
        };
        $worker->onWorkerReload = function(Worker $worker) {
            Worker::log("Worker $worker->id reloading.");
            foreach ($worker->connections as $connection) {
                $connection->send('worker reloading...');
            }
        };
        $worker->onConnect      = function(TcpConnection $connection) {
            Worker::log('new connection from ip :' . $connection->getRemoteAddress());

            $connection->maxSendBufferSize = $connection::$defaultMaxSendBufferSize;// 设置当前连接发送缓冲区，单位字节,设置当前连接的最大发送缓冲区大小。发送缓冲区已满时，将发出OnBufferFull回调。
            $connection->maxPackageSize    = $connection::$defaultMaxPackageSize;   // 设置当前连接的最大可接受数据包大小。
        };
        $worker->onMessage      = function(TcpConnection $connection, $data) {
            Worker::log('Received data:' . $connection->getRemoteAddress());

            // 给connection临时设置一个lastMessageTime属性，用来记录上次收到消息的时间
            $connection->lastMessageTime = time();
            static $request_count;
            if (++$request_count > 10000) {
                // 请求数达到最大限制后退出当前进程，主进程会自动重启一个新的进程
                Worker::stopAll();
            }
        };
        $worker->onClose        = function(TcpConnection $connection) {
            Worker::log('connection closed:' . $connection->getRemoteAddress());
        };
        $worker->onError        = function(TcpConnection $connection, $code, $msg) {
            Worker::log('error:' . $msg . "[$code]" . $connection->getRemoteAddress());
        };
        $worker->onBufferFull   = function(TcpConnection $connection) {
            Worker::log('bufferFull and do not send again:' . $connection->getRemoteAddress());
        };
        $worker->onBufferDrain  = function(TcpConnection $connection) {
            Worker::log('buffer drain and continue send:' . $connection->getRemoteAddress());
        };

        Worker::runAll();
    }

}