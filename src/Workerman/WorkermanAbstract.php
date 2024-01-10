<?php

namespace Sweeper\HelperPhp\Workerman;

use BadFunctionCallException;
use InvalidArgumentException;
use RuntimeException;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/24 14:17
 * @Package \Sweeper\HelperPhp\Workerman\WorkermanAbstract
 */
abstract class WorkermanAbstract implements WorkermanInterface
{

    use WorkermanMiddleware;

    /** @var string tcp Worker，直接基于socket传输，不使用任何应用层协议 */
    public const WORKER_TCP = 'WORKER_TCP';

    /** @var string udp Worker 不使用任何应用层协议 */
    public const WORKER_UDP = 'WORKER_UDP';

    /** @var string http Worker 表明用 http 协议 */
    public const WORKER_HTTP = 'WORKER_HTTP';

    /** @var string websocket Worker 表明用 websocket 协议 */
    public const WORKER_WEBSOCKET = 'WORKER_WEBSOCKET';

    /** @var string 表明用 text 协议 */
    public const WORKER_TEXT = 'WORKER_TEXT';

    /** @var string 表明用 ws 协议 */
    public const WORKER_WS = 'WORKER_WS';

    /** @var string 表明用 frame 协议 */
    public const WORKER_FRAME = 'WORKER_FRAME';

    /** @var string[] 协议映射 */
    public static $protocol_map = [
        self::WORKER_TCP       => 'tcp',
        self::WORKER_UDP       => 'udp',
        self::WORKER_HTTP      => 'http',
        self::WORKER_WEBSOCKET => 'websocket',
        self::WORKER_TEXT      => 'text',
        self::WORKER_FRAME     => 'frame',
        self::WORKER_WS        => 'ws',
    ];

    /** @var Worker[] 多 Worker 列表 */
    protected $workerList = [];

    /** @var int 心跳间隔55秒 */
    protected $heartbeat_time = 55;

    /** @var string 监听地址 */
    protected $ip = '0.0.0.0';

    /** @var int 监听端口 */
    protected $port = 9500;

    /** @var string 设置当前Worker实例的名称，方便运行status命令时识别进程。不设置时默认为none。 */
    protected $workerName = 'none';

    /** @var int 设置当前Worker实例启动多少个进程，不设置时默认为1。注意：此属性必须在Worker::runAll();运行前设置才有效。windows系统不支持此特性。 */
    protected $worker_num = 1;

    /** @var int 处理一定请求后重启当前进程(请求数达到 10000 后退出当前进程，主进程会自动重启一个新的进程) */
    protected $max_request = 10000;

    /** @var bool 启用标准输出 -> 所有的打印输出全部保存在 /tmp/workerman/xxx.stdout.log 文件中 */
    protected $enableStdout = false;

    /**
     * WorkerAbstract constructor.
     * @param int         $worker_num
     * @param string|null $ip
     * @param int|null    $port
     * @param bool|null   $enableStdout
     * @doc http://doc.workerman.net/
     */
    public function __construct(int $worker_num = 1, string $ip = null, int $port = null, bool $enableStdout = null)
    {
        $this->ip           = $ip ?? $this->ip;
        $this->port         = $port ?? $this->port;
        $this->worker_num   = $worker_num ?? $this->worker_num;
        $this->enableStdout = $enableStdout ?? $this->enableStdout;
        $this->setErrorReporting();
        $this->setProperties();
    }

    /**
     * 启动所有 worker
     */
    final public function run()
    {
        if (!$this->workerList) {
            throw new BadFunctionCallException('请先设置要启动的 Worker.');
        }
        // Run all workers
        $this->onRunBefore();
        Worker::runAll();
        $this->onRunAfter();
    }

    /**
     * 停止所有 worker
     */
    final public function stop(): void
    {
        // Stop all workers
        $this->onStopBefore();
        Worker::stopAll();
        $this->onStopAfter();
    }

    /**
     * 设置Worker子进程启动时的回调函数，每个子进程启动时都会执行。
     * @param \Workerman\Worker $worker
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     * @throws \Exception
     */
    public function onWorkerStart(Worker $worker): WorkermanInterface
    {
        Worker::log('Worker starting.');

        $this->init()->heartbeatDetection($worker);

        return $this;
    }

    /**
     * 设置Worker子进程停止时的回调函数，每个子进程启动时都会执行。
     * @param \Workerman\Worker $worker
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onWorkerStop(Worker $worker): WorkermanInterface
    {
        Worker::log('Worker stopping.');

        return $this;
    }

    /**
     * 设置Worker收到reload信号后执行的回调
     * @param \Workerman\Worker $worker
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onWorkerReload(Worker $worker): WorkermanInterface
    {
        Worker::log('Worker reloading.');
        foreach ($worker->connections as $connection) {
            $connection->send('worker reloading...');
        }

        return $this;
    }

    /**
     * 当客户端与Workerman建立连接时(TCP三次握手完成后)触发的回调函数。每个连接只会触发一次回调。
     * @param \Workerman\Connection\TcpConnection $connection
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onConnect(TcpConnection $connection): WorkermanInterface
    {
        Worker::log('new connection from ip :' . $connection->getRemoteAddress());
        $connection->maxSendBufferSize = 1048576;// 设置当前连接发送缓冲区，单位字节,设置当前连接的最大发送缓冲区大小。发送缓冲区已满时，将发出OnBufferFull回调。
        $connection->maxPackageSize    = 1048576;// 设置当前连接的最大可接受数据包大小。

        return $this;
    }

    /**
     * 当客户端连接与Workerman断开时触发的回调函数。不管连接是如何断开的，只要断开就会触发。每个连接只会触发一次
     * @param \Workerman\Connection\TcpConnection $connection
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onClose(TcpConnection $connection): WorkermanInterface
    {
        Worker::log('connection closed:' . $connection->getRemoteAddress());

        return $this;
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param \Workerman\Connection\TcpConnection $connection
     * @param int                                 $code
     * @param string                              $msg
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onError(TcpConnection $connection, int $code, string $msg): WorkermanInterface
    {
        Worker::log('error:' . $msg . "[{$code}]" . $connection->getRemoteAddress());

        return $this;
    }

    /**
     * 每个连接都有一个单独的应用层发送缓冲区，如果客户端接收速度小于服务端发送速度，数据会在应用层缓冲区暂存，如果缓冲区满则会触发onBufferFull回调
     * @param \Workerman\Connection\TcpConnection $connection
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onBufferFull(TcpConnection $connection): WorkermanInterface
    {
        Worker::log('bufferFull and do not send again:' . $connection->getRemoteAddress());

        return $this;
    }

    /**
     * 每个连接都有一个单独的应用层发送缓冲区，该回调在应用层发送缓冲区数据全部发送完毕后触发。
     * 一般与onBufferFull配合使用，例如在onBufferFull时停止向对端继续send数据，在onBufferDrain恢复写入数据。
     * @param \Workerman\Connection\TcpConnection $connection
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onBufferDrain(TcpConnection $connection): WorkermanInterface
    {
        Worker::log('buffer drain and continue send:' . $connection->getRemoteAddress());

        return $this;
    }

    /**
     * 当客户端通过连接发来数据时(Workerman收到数据时)触发的回调函数
     * @param \Workerman\Connection\TcpConnection $connection
     * @param                                     $data
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function onMessage(TcpConnection $connection, $data): WorkermanInterface
    {
        if ($data instanceof Request) {
            Worker::log('Received request:' . $connection->getRemoteAddress());
        } else {
            Worker::log('Received data:' . $connection->getRemoteAddress());
        }
        // 给connection临时设置一个lastMessageTime属性，用来记录上次收到消息的时间
        $connection->lastMessageTime = time();
        static $request_count;
        if (++$request_count > $this->max_request) {
            // 请求数达到最大限制后退出当前进程，主进程会自动重启一个新的进程
            Worker::stopAll();
        }

        return $this;
    }

    /**
     * 设置要启动的 Worker
     * @param string $service
     * @param null   $ip
     * @param null   $port
     * @param array  $context_options
     * @param array  $properties
     * @return \Workerman\Worker
     */
    public function setWorker(string $service, $ip = null, $port = null, array $context_options = [], array $properties = []): Worker
    {
        $scheme = self::$protocol_map[$service] ?: $service;
        if (!$scheme || !in_array($scheme, self::$protocol_map)) {
            throw new InvalidArgumentException("不支持的协议({$service}).");
        }
        $ip                            = $ip ?? $this->ip;
        $port                          = $port ?? $this->port;
        $worker                        = new Worker("$scheme://{$ip}:{$port}", $context_options);
        $unique_key                    = md5("$scheme://{$ip}:{$port}" . serialize($context_options));
        $worker->name                  = $scheme;
        $this->workerList[$unique_key] = $worker;
        $this->setWorkerProperties($worker, $properties);

        return $worker;
    }

    /**
     * 设置属性
     */
    public function setProperties(): WorkermanInterface
    {
        [$dir, $filename, $log_dir_prefix] = $this->getStorageFile();
        if (!file_exists($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        Worker::$logFile                         = "{$log_dir_prefix}.log";                                                   //设置 log 文件
        Worker::$stdoutFile                      = $this->enableStdout ? "{$log_dir_prefix}.stdout.log" : Worker::$stdoutFile;//所有的打印输出全部保存在/tmp/stdout.log文件中
        TcpConnection::$defaultMaxSendBufferSize = 1048576;                                                                   //设置所有连接的默认应用层发送缓冲区大小，单位字节
        TcpConnection::$defaultMaxPackageSize    = 10485760;                                                                  //默认的最大可接受数据包大小。

        return $this;
    }

    /**
     * 设置 Worker 属性
     * @param \Workerman\Worker $worker
     * @param array             $properties
     * @return \Sweeper\HelperPhp\Workerman\WorkermanInterface
     */
    public function setWorkerProperties(Worker $worker, array $properties = []): WorkermanInterface
    {
        //属性
        $worker->reloadable = true;                                                                                 //设置reloadable为false，即子进程收到reload信号不执行重启
        $worker->reusePort  = true;                                                                                 //设置当前worker是否开启监听端口复用(socket的SO_REUSEPORT选项)。可以提升多进程短连接应用的性能。
        $worker->count      = $properties['worker_num'] ?? $this->worker_num;                                       //工作进程数
        $worker->name       = $properties['worker_name'] ?? $this->workerName ?? strtolower(self::WORKER_WEBSOCKET);//设置实例的名称
        //回调属性
        $worker->onWorkerStart  = function(Worker $worker) use ($properties): WorkermanInterface {
            $onWorkerStart = $properties['onWorkerStart'] && is_callable($properties['onWorkerStart']) ? $properties['onWorkerStart'] : null;

            return $onWorkerStart ? $onWorkerStart($worker) : $this->onWorkerStart($worker);
        };
        $worker->onWorkerStop   = function(Worker $worker) use ($properties): WorkermanInterface {
            $onWorkerStop = $properties['onWorkerStop'] && is_callable($properties['onWorkerStop']) ? $properties['onWorkerStop'] : null;

            return $onWorkerStop ? $onWorkerStop($worker) : $this->onWorkerStop($worker);
        };
        $worker->onWorkerReload = function(Worker $worker) use ($properties): WorkermanInterface {
            $onWorkerReload = $properties['onWorkerReload'] && is_callable($properties['onWorkerReload']) ? $properties['onWorkerReload'] : null;

            return $onWorkerReload ? $onWorkerReload($worker) : $this->onWorkerReload($worker);
        };
        $worker->onConnect      = function(TcpConnection $connection) use ($properties): WorkermanInterface {
            $onConnect = $properties['onConnect'] && is_callable($properties['onConnect']) ? $properties['onConnect'] : null;

            return $onConnect ? $onConnect($connection) : $this->onConnect($connection);
        };
        $worker->onClose        = function(TcpConnection $connection) use ($properties): WorkermanInterface {
            $onClose = $properties['onClose'] && is_callable($properties['onClose']) ? $properties['onClose'] : null;

            return $onClose ? $onClose($connection) : $this->onClose($connection);
        };
        $worker->onError        = function(TcpConnection $connection, $code, string $msg) use ($properties): WorkermanInterface {
            $onError = $properties['onError'] && is_callable($properties['onError']) ? $properties['onError'] : null;

            return $onError ? $onError($connection, $code, $msg) : $this->onError($connection, $code, $msg);
        };
        $worker->onBufferFull   = function(TcpConnection $connection) use ($properties): WorkermanInterface {
            $onBufferFull = $properties['onBufferFull'] && is_callable($properties['onBufferFull']) ? $properties['onBufferFull'] : null;

            return $onBufferFull ? $onBufferFull($connection) : $this->onBufferFull($connection);
        };
        $worker->onBufferDrain  = function(TcpConnection $connection) use ($properties): WorkermanInterface {
            $onBufferDrain = $properties['onBufferDrain'] && is_callable($properties['onBufferDrain']) ? $properties['onBufferDrain'] : null;

            return $onBufferDrain ? $onBufferDrain($connection) : $this->onBufferDrain($connection);
        };
        $worker->onMessage      = function(TcpConnection $connection, $data) use ($properties): WorkermanInterface {
            $onMessage = $properties['onMessage'] && is_callable($properties['onMessage']) ? $properties['onMessage'] : null;

            return $onMessage ? $onMessage($connection, $data) : $this->onMessage($connection, $data);
        };

        return $this;
    }

    /**
     * 初始化框架
     * @throws \Exception
     */
    public function init(): WorkermanInterface
    {

        return $this;
    }

}