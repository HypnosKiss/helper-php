<?php

namespace Sweeper\HelperPhp\Workerman;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/24 14:17
 * @Package \Sweeper\HelperPhp\Workerman\WorkermanInterface
 */
interface WorkermanInterface
{

    /**
     * 设置Worker子进程启动时的回调函数，每个子进程启动时都会执行。
     * @param \Workerman\Worker $worker
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onWorkerStart(Worker $worker): WorkermanInterface;

    /**
     * 设置Worker子进程停止时的回调函数，每个子进程启动时都会执行。
     * @param \Workerman\Worker $worker
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onWorkerStop(Worker $worker): WorkermanInterface;

    /**
     * 设置Worker收到reload信号后执行的回调
     * @param \Workerman\Worker $worker
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onWorkerReload(Worker $worker): WorkermanInterface;

    /**
     * 当客户端与Workerman建立连接时(TCP三次握手完成后)触发的回调函数。每个连接只会触发一次回调。
     * @param \Workerman\Connection\TcpConnection $connection 连接对象，即TcpConnection实例，用于操作客户端连接，如发送数据，关闭连接等
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onConnect(TcpConnection $connection): WorkermanInterface;

    /**
     * 当客户端连接与Workerman断开时触发的回调函数。不管连接是如何断开的，只要断开就会触发。每个连接只会触发一次
     * @param \Workerman\Connection\TcpConnection $connection 连接对象，即TcpConnection实例，用于操作客户端连接，如发送数据，关闭连接等
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onClose(TcpConnection $connection): WorkermanInterface;

    /**
     * 当客户端的连接上发生错误时触发
     * @param \Workerman\Connection\TcpConnection $connection 连接对象，即TcpConnection实例，用于操作客户端连接，如发送数据，关闭连接等
     * @param int                                 $code       错误码
     * @param string                              $msg        错误消息
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onError(TcpConnection $connection, int $code, string $msg): WorkermanInterface;

    /**
     * 每个连接都有一个单独的应用层发送缓冲区，如果客户端接收速度小于服务端发送速度，数据会在应用层缓冲区暂存，如果缓冲区满则会触发onBufferFull回调
     * @param \Workerman\Connection\TcpConnection $connection 连接对象，即TcpConnection实例，用于操作客户端连接，如发送数据，关闭连接等
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onBufferFull(TcpConnection $connection): WorkermanInterface;

    /**
     * 每个连接都有一个单独的应用层发送缓冲区，该回调在应用层发送缓冲区数据全部发送完毕后触发。
     * 一般与onBufferFull配合使用，例如在onBufferFull时停止向对端继续send数据，在onBufferDrain恢复写入数据。
     * @param \Workerman\Connection\TcpConnection $connection 连接对象，即TcpConnection实例，用于操作客户端连接，如发送数据，关闭连接等
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onBufferDrain(TcpConnection $connection): WorkermanInterface;

    /**
     * 当客户端通过连接发来数据时(Workerman收到数据时)触发的回调函数
     * @param \Workerman\Connection\TcpConnection $connection 连接对象，即TcpConnection实例，用于操作客户端连接，如发送数据，关闭连接等
     * @param mixed                               $data       客户端连接上发来的数据，如果Worker指定了协议，则$data是对应协议decode（解码）了的数据
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onMessage(TcpConnection $connection, $data): WorkermanInterface;

    /**
     * 初始化
     * @return mixed
     */
    public function init(): WorkermanInterface;

    /**
     * 设置要启动的 Worker
     * @param string      $service         Worker 服务类型
     * @param string|null $ip
     * @param int|null    $port
     * @param array       $context_options 一个数组。用于传递socket的上下文选项，参见套接字上下文选项 http://php.net/manual/zh/context.socket.php
     * @param array       $properties      Worker 属性/回调函数
     * @return \Workerman\Worker
     */
    public function setWorker(string $service, string $ip = null, int $port = null, array $context_options = [], array $properties = []): Worker;

    /**
     * 设置属性
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function setProperties(): WorkermanInterface;

    /**
     * 设置 Worker 属性
     * @param \Workerman\Worker $worker
     * @param array             $properties
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function setWorkerProperties(Worker $worker, array $properties = []): WorkermanInterface;

    /**
     * 启动所有服务
     * @return mixed
     */
    public function run();

}
