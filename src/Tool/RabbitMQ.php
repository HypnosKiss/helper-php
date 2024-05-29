<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/3/23 13:41
 */

namespace Sweeper\HelperPhp\Tool;

use LogicException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Sweeper\DesignPattern\Traits\MultiPattern;
use Throwable;

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/3/23 13:41
 * @doc https://www.rabbitmq.com/tutorials/tutorial-one-php.html
 * @Path \rabbit\RabbitMQ
 * @doc https://segmentfault.com/a/1190000038779279
 * @doc https://cloud.tencent.com/developer/article/1915072
 * @mixin AMQPStreamConnection
 * @mixin AMQPChannel
 * @example RabbitMQ::instance(config('queue.sales_queue'))->setQueueConfigName('listing_sync_task')->declareQueue()->publishMessage($body);
 * @example RabbitMQ::instance(config('queue.sales_queue'))->setQueueConfig(config('queue.sales_queue.queue.listing_sync_task'))->declareQueue()->publishMessage($body)
 * @example RabbitMQ::instance(config('queue.rabbit_mq_sync_task'))->setQueueConfig(['queue_name' => 'order_profit_ids', 'exchange_name' => 'default', 'routing_key' => 'order_profit_ids'])->declareQueue()->produceMessage(['order_id' => 123456789]);
 * @example RabbitMQ::instance(config('配置文件.指定配置'))->setQueueConfig(config('配置文件.指定配置.queue.指定队列配置'))->publishMessage($body)
 * // RabbitMQ::instance(config('配置文件.指定配置'))
 * //                        ->setQueueConfig([
 * //                            'queue_name'    => '队列名',
 * //                            'exchange_name' => '交换机名',
 * //                            'routing_key'   => '路由',
 * //                        ])
 * //                        ->declareQueue('队列名')// 配置队列（声明交换机、声明队列、绑定队列到交换机）
 * //                        ->produceMessage(['order_id' => 1234567], 'order_profit_ids')// 发布消息到指定的路由，不指定路由会发布到所有绑定当前交换机的队列
 * // RabbitMQ::instance(config('queue.rabbit_mq_pre'), static::CONSUME_QUEUE_NAME)
 * //                      ->declareQueue(static::CONSUME_QUEUE_NAME, static::CONSUME_QUEUE_NAME)
 * //                      ->produceMessage([
 * //                          'platform_code' => 'SE', 'item_id' => rand(999, 999999), 'account_id' => 155, 'salesman_name' => '李翔1', 'time' => date('Y-m-d H:i:s'),
 * //                      ], static::CONSUME_QUEUE_NAME);
 * @example
 * // $client = RabbitMQ::instance(config('queue.rabbit_mq_pre'))
 * //           ->setQueueConfigName('order_profit_ids')
 * //           ->setQueueConfig(['queue_name' => 'order_profit_ids','exchange_name' => 'default','routing_key' => 'order_profit_ids'])
 * //           ->declareQueue('order_profit_ids');
 * //         for ($i = 0; $i < 10; $i++) {
 * //             $pushData = [
 * //                 'item_id'       => rand(1, 99) . 5125686379 . rand(10, 99),
 * //                 'platform_code' => SalePlatform::CODE_EBAY,
 * //                 'account_id'    => rand(100, 99999),
 * //             ];
 * //             $ret      = $client->publishMessage($pushData, 'order_profit_ids');
 * //             dump($pushData);
 * //             dump("推送结果：{$ret}");
 * //         }
 * //         sleep(30);
 * //         $client->consumeMessage(function(AMQPMessage $msg) {
 * //             $rawData = $msg->body;
 * //             if (empty($rawData)) {
 * //                 return;
 * //             }
 * //             # 业务逻辑处理开始
 * //             //1.取队列里面的数据
 * //             $data = json_decode($rawData, true) ?: [];
 * //             dump($data);
 * //
 * //             return true;
 * //             // 这里不返回 true，表示不确认消息
 * //         }, null, ['no_local' => true, 'prefetch_count' => 2]);
 * //        $client->basicGetMessage(null, function() {},1000);
 */
class RabbitMQ
{

    use MultiPattern;

    /** @var AMQPStreamConnection MQ 连接 */
    private $connection;

    /** @var AMQPChannel 通道 */
    private $channel;

    /** @var array 队列配置 */
    private $queueConfig = [];

    /** @var string 队列配置名 */
    private $queueConfigName;

    /** @var string (默认)直接交换器，工作方式类似于单播，Exchange会将消息发送完全匹配ROUTING_KEY的Queue, */
    public const DIRECT = 'direct';

    /** @var string 主题交换器，工作方式类似于组播，Exchange会将消息转发和ROUTING_KEY匹配模式相同的所有队列，比如，ROUTING_KEY为user.stock的Message会转发给绑定匹配模式为 * .stock,user.stock， * . * 和#.user.stock.#的队列。(* 表是匹配一个任意词组，#表示匹配0个或多个词组), */
    public const TOPIC = 'topic';

    /** @var string 根据消息体的header匹配 */
    public const HEADERS = 'headers';

    /** @var string 广播是式交换器，不管消息的ROUTING_KEY设置为什么，Exchange都会将消息转发给所有绑定的Queue, */
    public const FANOUT = 'fanout';

    /** @var string[] 交换机名称 */
    private static $exchangeNameMap = [
        self::DIRECT  => 'direct_exchange',
        self::TOPIC   => 'topic_exchange',
        self::HEADERS => 'headers_exchange',
        self::FANOUT  => 'fanout_exchange',
    ];

    /**
     * @param string $name
     * @param array  $config
     * @return AMQPStreamConnection
     * @throws \Exception
     */
    public function getConnection(string $name = '', array $config = []): AMQPStreamConnection
    {
        if (!($this->connection instanceof AMQPStreamConnection)) {
            $this->connection($name, $config);
        }

        return $this->connection;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 10:05
     * @param AMQPStreamConnection $connection
     * @return $this
     */
    public function setConnection(AMQPStreamConnection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param null $channelId
     * @return AMQPChannel
     * @throws \Exception
     */
    public function getChannel($channelId = null): AMQPChannel
    {
        if (!($this->channel instanceof AMQPChannel)) {
            $this->setChannel($this->getConnection()->channel($channelId));
        }

        return $this->channel;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 10:05
     * @param AMQPChannel $channel
     * @return $this
     */
    public function setChannel(AMQPChannel $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return string
     */
    public function getQueueConfigName(): ?string
    {
        return $this->queueConfigName;
    }

    /**
     * User: Sweeper
     * Time: 2023/3/23 12:26
     * @param string $queueConfigName
     * @return $this
     */
    public function setQueueConfigName(string $queueConfigName): self
    {
        $this->queueConfigName = $queueConfigName;

        return $this;
    }

    /**
     * @return array
     */
    public function getQueueConfig(): array
    {
        return $this->queueConfig;
    }

    /**
     * User: Sweeper
     * Time: 2023/3/23 13:43
     * @param array $queueConfig
     * @return $this
     */
    public function setQueueConfig(array $queueConfig): self
    {
        $this->queueConfig = $queueConfig;

        return $this;
    }

    /**
     * 获取指定 KEY 配置
     * User: Sweeper
     * Time: 2023/8/15 18:22
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getSpecifyConfig(string $key, $default = '')
    {
        return $this->getQueueConfig()[$key] ?? ($this->getConfig()[$key] ?? $default);
    }

    /**
     * 连接 AMQP
     * User: Sweeper
     * Time: 2023/3/23 14:38
     * @param string $name
     * @param array  $config
     * @return AMQPStreamConnection
     * @throws \Exception
     */
    public function connection(string $name = '', array $config = []): AMQPStreamConnection
    {
        $nameConfig = [];
        if (empty($this->getConfig())) {
            throw new \InvalidArgumentException('配置无效，请配置连接信息');
        }
        if ($name) {// 取MQ配置
            $nameConfig = $this->getConfig()[$name] ?? [];
        }
        /**
         * 队列配置优先级：setQueueConfig > setQueueConfigName > $config['queue']
         * $this->config 为 MQ 主配置如 host、port、username、password、vhost、queue/queue_name
         * $this->queueConfig 为指定的队列配置如 queue_name、exchange_name、routeing_key ...
         */
        $config = $this->setConfig(array_replace($this->loadConfig(), $this->getConfig(), $nameConfig, $config))->getConfig() ?: [];
        $this->setQueueConfig(array_replace($config['queue'] ?? [], $config['queue'][$this->getQueueConfigName()] ?? [], $this->queueConfig))->getQueueConfig();
        if (!$config['host'] || !$config['port'] || !$config['username'] || !$config['password']) {
            throw new \InvalidArgumentException('配置无效，请检查配置信息');
        }

        return $this->setConnection(new AMQPStreamConnection($config['host'], $config['port'], $config['username'], $config['password'], $config['vhost'] ?? '/'))->getConnection();// 实例化连接
    }

    /**
     * 关闭
     * User: Sweeper
     * Time: 2023/8/30 11:10
     * @return $this
     * @throws \Exception
     */
    public function close(): self
    {
        $this->getChannel()->close();
        $this->getConnection()->close();

        return $this;
    }

    /**
     * 确认消息
     * User: Sweeper
     * Time: 2023/8/30 11:35
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     * @return $this
     */
    public function ackMessage(AMQPMessage $message): self
    {
        // 手动发送ACK应答, 删除消息
        $message->ack();// 手动发送ACK应答,$this->basic_ack($message->getDeliveryTag());$this->getChannel()->basic_ack($message->getDeliveryTag());

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/30 10:04
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }
        if (method_exists($this->getChannel(), $name)) {
            return $this->getChannel()->{$name}(...$arguments);
        }
        if (method_exists($this->getConnection(), $name)) {
            return $this->getConnection()->{$name}(...$arguments);
        }

        throw new \BadMethodCallException('Call Undefined method');
    }

    /**
     * 销毁
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 加载配置
     * User: Sweeper
     * Time: 2023/9/10 10:58
     * @return array
     */
    public function loadConfig(): array
    {
        return [
            'host'     => '127.0.0.1', // IP
            'port'     => 5672,// 端口
            'username' => 'test',// 用户名
            'password' => 'test123456',// 密码
            'vhost'    => '/',// mq交换机路径
            // 队列配置
            'queue'    => [
                // 队列名称对应MQ交换机配置
                'test' => [
                    'queue_name'    => 'test',
                    'exchange_name' => null, // default、queue_name一致
                    'routing_key'   => 'test',
                ],
            ],
        ];
    }

    /**
     * 配置队列
     * User: Sweeper
     * Time: 2023/8/29 19:11
     * @param string      $queueName
     * @param string|null $routingKey
     * @param string      $exchangeName
     * @param bool        $exchangeDeclare
     * @param bool        $queueDeclare
     * @param bool        $queueBind
     * @return $this
     * @throws \Exception
     */
    public function declareQueue(string $queueName = '', string $routingKey = '', string $exchangeName = '', bool $exchangeDeclare = true, bool $queueDeclare = true, bool $queueBind = true): self
    {
        $queueName    = $queueName ?: $this->getSpecifyConfig('queue_name');
        $exchangeName = $exchangeName ?: $this->getSpecifyConfig('exchange_name', null);
        $routingKey   = $routingKey ?: $this->getSpecifyConfig('routing_key');
        $exchangeType = $this->getSpecifyConfig('exchange_type', static::DIRECT);
        $passive      = $this->getSpecifyConfig('passive', false);     // 是否检测同名队列
        $durable      = $this->getSpecifyConfig('durable', true);      // 是否开启交换机&队列持久化
        $exclusive    = $this->getSpecifyConfig('exclusive', false);   // 队列是否可以被其他队列访问
        $autoDelete   = $this->getSpecifyConfig('auto_delete', false); // 通道关闭后是否删除队列
        $internal     = $this->getSpecifyConfig('internal', false);
        $nowait       = $this->getSpecifyConfig('nowait', false);
        $arguments    = $this->getSpecifyConfig('arguments', []);
        $ticket       = $this->getSpecifyConfig('ticket', null);
        if (!$queueName) {
            throw new \InvalidArgumentException('The configuration is invalid，queue_name Cannot be empty.');
        }
        // 声明初始化一条队列
        $queueDeclare && $this->getChannel()->queue_declare($queueName, $passive, $durable, $exclusive, $autoDelete, $nowait, $arguments, $ticket);
        // 声明初始化交换机
        $exchangeDeclare && $this->getChannel()->exchange_declare($exchangeName, $exchangeType, $passive, $durable, $autoDelete, $internal, $nowait, $arguments, $ticket);
        // 交换机队列绑定
        $queueBind && $this->getChannel()->queue_bind($queueName, $exchangeName, $routingKey, $nowait, $arguments, $ticket);

        return $this;
    }

    /**
     * 生产消息
     * User: Sweeper
     * Time: 2023/8/29 14:37
     * @param array       $body
     * @param string|null $routingKey
     * @param string      $exchangeName
     * @param array       $properties
     * @return bool
     */
    public function produceMessage(array $body, string $routingKey = '', string $exchangeName = '', array $properties = []): bool
    {
        try {
            // $this->declareQueue();// 不声明初始化一条队列，不会自动创建队列
            // $this->getChannel()->queue_declare($queueName, $passive, $durable, $exclusive, $autoDelete);
            $message = new AMQPMessage(json_encode($body, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), array_replace(['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT], $properties));
            $this->getChannel()->basic_publish($message, $exchangeName ?: $this->getSpecifyConfig('exchange_name', null), $routingKey ?: $this->getSpecifyConfig('routing_key'));
            $flag = true;
        } catch (Throwable $ex) {
            $flag = false;
            echo date('Y-m-d H:i:s'), "{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})", PHP_EOL;
        }

        return $flag;
    }

    /**
     * 消费消息
     * User: Sweeper
     * Time: 2023/3/24 17:30
     * @param callable      $callback
     * @param callable|null $exceptionCallback
     * @param array         $options
     * @param bool          $autoAck
     * @param int           $limit
     * @param int           $totalCount
     * @return void
     * @throws \Exception
     */
    public function consumeMessage(callable $callback, callable $exceptionCallback = null, array $options = [], bool $autoAck = true, int $limit = 0, int &$totalCount = 0): void
    {
        $prefetchSize  = $options['prefetch_size'] ?? null;
        $prefetchCount = $options['prefetch_count'] ?? 0;// 限流：最多允许的消息数量，达到上限则队列不再继续获取消息处理,默认0不限制
        $aGlobal       = $options['a_global'] ?? null;   // 限流：是对整个channel影响，还是只影响当前消费者
        $queueName     = $this->getSpecifyConfig('queue_name');
        $consumerTag   = $this->getSpecifyConfig('consumer_tag');
        $noLocal       = $this->getSpecifyConfig('no_local', true);
        $noAck         = $this->getSpecifyConfig('no_ack', false);
        $exclusive     = $this->getSpecifyConfig('exclusive', false);
        $nowait        = $this->getSpecifyConfig('nowait', false);
        $ticket        = $this->getSpecifyConfig('ticket', null);
        $arguments     = $this->getSpecifyConfig('arguments', []);
        try {
            $this->getChannel()->basic_qos($prefetchSize, $prefetchCount, $aGlobal);// 限流
            //在接收消息的时候调用$callback函数
            $this->getChannel()->basic_consume($queueName, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, function($msg) use ($callback, $exceptionCallback, $autoAck, &$totalCount) {
                /** @var AMQPMessage $msg */
                echo date('Y-m-d H:i:s'), ' [x] Received ', $msg->body, "\n";

                $totalCount++;
                try {
                    # 业务逻辑处理开始
                    $callback($msg, $totalCount, $this->getChannel(), $this->getConnection());

                    echo date('Y-m-d H:i:s'), " [x] Done\n";
                } catch (Throwable $ex) {
                    if (is_callable($exceptionCallback)) {
                        $exceptionCallback($ex);

                        echo date('Y-m-d H:i:s'), " [x] Exception {$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})\n";
                    } else {
                        throw new LogicException("{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})");
                    }
                } finally {
                    if ($autoAck) {
                        // 5.删除消息
                        $this->ackMessage($msg);

                        echo date('Y-m-d H:i:s'), " [x] msg->ack Done\n";
                    }
                }
            }, $ticket, $arguments);

            while ($this->getChannel()->is_open()) {// $this->channel->is_consuming()
                echo date('Y-m-d H:i:s'), " [x] channel->wait\n";

                if ($limit > 0 && $totalCount >= $limit) {
                    echo date('Y-m-d H:i:s'), ' [x] basic_get message ', "已消费[{$totalCount}]个消息, 超出限制[{$limit}]，循环获取队列消息终止", PHP_EOL;
                    break;
                }
                $this->getChannel()->wait();
            }
        } catch (Throwable $ex) {
            // 如果是因为连接关闭，自动重连
            if (strpos($ex->getMessage(), 'Broken pipe or closed connection') !== false) {
                $this->close()->getChannel();// 会自动连接
            }
            is_callable($exceptionCallback) && $exceptionCallback($ex);

            echo date('Y-m-d H:i:s'), " [x] Throwable Exception {$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})\n";
        }
    }

    /**
     * 循环获取队列单个消息
     * User: Sweeper
     * Time: 2023/8/16 10:46
     * @param callable|null $messageHandler 消息处理回调
     * @param string|null   $queueName      队列名字
     * @param bool          $autoAck
     * @param int           $limit          限制条数 0 不限制
     * @param int           $totalCount     总消费的消息数量
     * @return int
     */
    public function basicGetMessage(callable $messageHandler = null, ?string $queueName = '', bool $autoAck = true, int $limit = 0, int &$totalCount = 0): int
    {
        $queueName    = $queueName ?: $this->getSpecifyConfig('queue_name');
        $emptyMessage = false;
        while (true) {
            /** @var AMQPMessage $msg */
            $amqpMessage = $this->basic_get($queueName);
            if ($amqpMessage === null) {
                if (!$emptyMessage) {
                    echo date('Y-m-d H:i:s'), ' [x] basic_get message ', "第[{$totalCount}]个消息后无数据，usleep(200000)", PHP_EOL;
                }
                $emptyMessage = true;// 防止重复输出无用消息

                usleep(200000);// wait for 2 seconds  usleep(2000000);
                continue;
            }
            $totalCount++;
            $emptyMessage = false;

            echo date('Y-m-d H:i:s'), ' [x] basic_get message: ', $amqpMessage->getBody(), PHP_EOL;

            try {
                # 业务逻辑处理开始
                is_callable($messageHandler) && $messageHandler($amqpMessage, $totalCount, $this->getChannel(), $this->getConnection());

                echo date('Y-m-d H:i:s'), " [x] Done\n";
            } catch (Throwable $ex) {
                throw new LogicException("{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})");
            } finally {
                if ($autoAck) {
                    // 5.删除消息
                    $this->ackMessage($amqpMessage);

                    echo date('Y-m-d H:i:s'), " [x] msg->ack Done\n";
                }
            }
            if ($limit > 0 && $totalCount >= $limit) {
                echo date('Y-m-d H:i:s'), ' [x] basic_get message ', "已消费[{$totalCount}]个消息, 超出限制[{$limit}]，循环获取队列消息终止", PHP_EOL;
                break;
            }
        }

        echo date('Y-m-d H:i:s'), ' [x] basic_get message total count：', $totalCount, PHP_EOL;

        return $totalCount;
    }

    /**
     * 尝试创建 MQ 连接、通道
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/7 14:03
     * @param array    $config
     * @param array    $options
     * @param int|null $channelId
     * @return array      [$connect, $channel]
     * @throws \Exception
     * @example
     * // if ($connect === null || $channel === null || !$connect->isConnected()) {
     * //   [$connect, $channel] = static::tryCreateConnection([], ['keepalive' => true, 'heartbeat' => 60]);
     * //   $this->warning('连接失效，重新初始化连接');
     * // }
     * // $connect->checkHeartBeat();
     * // $this->error("Throwable Exception：{$e->getFile()}#{$e->getLine()} ({$e->getMessage()})");
     * // # 如果是因为连接关闭，自动重连
     * // if (strpos($e->getMessage(), 'Broken pipe or closed connection') !== false) {
     * //     $this->warning('连接断开,开始重连');
     * //     try{
     * //         $connect->reconnect();
     * //     } catch (\Throwable $ex){
     * //         continue;
     * //     }
     * // }
     */
    public static function tryCreateConnection(array $config = [], array $options = [], int $channelId = null): array
    {
        if (!$config) {
            throw new \InvalidArgumentException('Configuration cannot be empty', -1);
        }
        /** @var AMQPStreamConnection $connect */
        $connect = AMQPStreamConnection::create_connection([$config], $options);
        $channel = $connect->channel($channelId);

        return [$connect, $channel];
    }

    /**
     * 创建 MQ 连接、通道
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/8 9:40
     * @param array|AMQPConnectionConfig $config
     * @param array                      $options
     * @param string                     $ioType
     * @param int|null                   $channelId
     * @return array [$connect, $channel]
     */
    public static function createConnection($config = [], array $options = [], string $ioType = AMQPConnectionConfig::IO_TYPE_STREAM, int $channelId = null): array
    {
        if (!$config) {
            throw new \InvalidArgumentException('Configuration cannot be empty', -1);
        }
        if (!($config instanceof AMQPConnectionConfig) && is_array($config)) {
            AMQPStreamConnection::validate_host($config);
            // 配置信息
            $host     = $config['host'];
            $port     = $config['port'];
            $user     = $config['user'];
            $password = $config['password'];
            $vhost    = $hostdef['vhost'] ?? '/';
            // 选项信息
            $options = array_filter(array_replace([
                'insist'             => false,
                'login_method'       => AMQPConnectionConfig::AUTH_AMQPPLAIN,
                'login_response'     => null,
                'locale'             => 'en_US',
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'keepalive'          => false,
                'heartbeat'          => 0,
            ], $options), static function($value) {
                return null !== $value && $value !== '';
            });
            // 自动配置连接信息
            $configure = new AMQPConnectionConfig();
            $configure->setIoType($ioType);
            $configure->setIsLazy(false);
            $configure->setHost($host);
            $configure->setPort($port);
            $configure->setUser($user);
            $configure->setPassword($password);
            $configure->setVhost($vhost);
            $configure->setInsist($options['insist'] ?? false);
            $configure->setLoginMethod($options['login_method'] ?? AMQPConnectionConfig::AUTH_AMQPPLAIN);
            $configure->setLocale($options['locale'] ?? 'en_US');
            $configure->setConnectionTimeout($options['connection_timeout'] ?? 3.0);
            $configure->setReadTimeout($options['read_write_timeout'] ?? 3.0);
            $configure->setKeepalive($options['keepalive'] ?? false);
            $configure->setHeartbeat($options['heartbeat'] ?? 0);
        } else {
            $configure = $config;
        }

        $connect = AMQPConnectionFactory::create($configure);
        $channel = $connect->channel($channelId);

        return [$connect, $channel];
    }

    //--------------------------------------------------------- example ---------------------------------------------------------

    /**
     * 简单的发送
     * User: Sweeper
     * Time: 2023/8/30 10:43
     * @param string $queue
     * @param string $body
     * @param bool   $passive    是否检测同名队列
     * @param bool   $durable    是否开启持久化
     * @param bool   $exclusive  队列是否可以被其他队列访问
     * @param bool   $autoDelete 通道关闭后是否删除队列
     * @param array  $properties
     * @return void
     * @throws \Exception
     */
    public function send(string $queue = 'hello', string $body = 'Hello World!', bool $passive = false, bool $durable = false, bool $exclusive = false, bool $autoDelete = true, array $properties = []): void
    {
        $this->getChannel()->queue_declare($queue, $passive, $durable, $exclusive, $autoDelete);
        $message = new AMQPMessage($body, $properties);
        $this->getChannel()->basic_publish($message, '', $queue);

        echo "[X] Sent $body\n";
    }

    /**
     * 简单的接收
     * User: Sweeper
     * Time: 2023/8/30 11:24
     * @param string        $queue
     * @param callable|null $callback
     * @param bool          $passive    是否检测同名队列
     * @param bool          $durable    是否开启持久化
     * @param bool          $autoDelete 通道关闭后是否删除队列
     * @param string        $consumerTag
     * @param bool          $noLocal
     * @param bool          $noAck
     * @param bool          $exclusive  队列是否可以被其他队列访问
     * @param bool          $nowait
     * @return void
     * @throws \Exception
     */
    public function receive(string $queue = 'hello', callable $callback = null, bool $passive = false, bool $durable = false, bool $autoDelete = true, string $consumerTag = '', bool $noLocal = false, bool $noAck = true, bool $exclusive = false, bool $nowait = false): void
    {
        $this->getChannel()->queue_declare($queue, $passive, $durable, $exclusive, $autoDelete, $nowait);
        echo "[*] Waiting for messages. To exit press CTRL+C\n";

        $this->getChannel()->basic_consume($queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $callback);

        while (count($this->getChannel()->callbacks)) {
            $this->getChannel()->wait();
        }
    }

    /**
     * 添加工作队列
     * @param string $queue
     * @param string $data
     * @param bool   $passive    是否检测同名队列
     * @param bool   $durable    是否开启持久化
     * @param bool   $exclusive  队列是否可以被其他队列访问
     * @param bool   $autoDelete 通道关闭后是否删除队列
     * @throws \Exception
     */
    public function addTask(string $queue = 'task_queue', string $data = 'Hello World!', bool $passive = false, bool $durable = true, bool $exclusive = false, bool $autoDelete = true): void
    {
        $this->getChannel()->queue_declare($queue, $passive, $durable, $exclusive, $autoDelete);
        $msg = new AMQPMessage($data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->getChannel()->basic_publish($msg, '', $queue);

        echo "[x] Sent $data \n";
    }

    /**
     * 执行工作队列
     * User: Sweeper
     * Time: 2023/8/30 11:02
     * @param callable $callback
     * @param string   $queue
     * @param bool     $passive    是否检测同名队列
     * @param bool     $durable    是否开启持久化
     * @param bool     $exclusive  队列是否可以被其他队列访问
     * @param bool     $autoDelete 通道关闭后是否删除队列
     * @param string   $consumerTag
     * @param bool     $noLocal
     * @param bool     $noAck
     * @param bool     $nowait
     * @return void
     * @throws \Exception
     */
    public function workTask(callable $callback, string $queue = 'task_queue', bool $passive = false, bool $durable = true, bool $exclusive = false, bool $autoDelete = true, string $consumerTag = '', bool $noLocal = false, bool $noAck = false, bool $nowait = false): void
    {
        $this->getChannel()->queue_declare($queue, $passive, $durable, $exclusive, $autoDelete);
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        $this->getChannel()->basic_qos(null, 1, null);
        $this->getChannel()->basic_consume($queue, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $callback);

        while (count($this->getChannel()->callbacks)) {
            $this->getChannel()->wait();
        }
    }

    /**
     * 发布
     * @param string $data
     * @param string $exchangeName
     * @param string $routingKey
     * @throws \Exception
     */
    public function sendQueue(string $data = 'Hello World!', string $exchangeName = 'default', string $routingKey = ''): void
    {
        $msg = new AMQPMessage($data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->getChannel()->basic_publish($msg, $exchangeName, $routingKey);

        echo "[x] Sent $data \n";
    }

    /**
     * 订阅
     * @param callable $callback
     * @param string   $exchangeName
     * @param string   $routingKey
     * @param string   $queue
     * @param bool     $passive    是否检测同名队列
     * @param bool     $durable    是否开启持久化
     * @param bool     $exclusive  队列是否可以被其他队列访问
     * @param bool     $autoDelete 通道关闭后是否删除队列
     * @param string   $consumerTag
     * @param bool     $noLocal
     * @param bool     $noAck
     * @param bool     $exclusiveQueue
     * @param bool     $nowait
     * @throws \Exception
     */
    public function subscribeQueue(callable $callback, string $exchangeName = 'default', string $routingKey = '',
                                   string   $queue = '', bool $passive = false, bool $durable = true, bool $exclusive = true, bool $autoDelete = false,
                                   string   $consumerTag = '', bool $noLocal = false, bool $noAck = true, bool $exclusiveQueue = false, bool $nowait = false): void
    {
        [$queue_name, ,] = $this->getChannel()->queue_declare(
            $queue,      //队列名称
            $passive,    //don't check if a queue with the same name exists 是否检测同名队列
            $durable,    //the queue will not survive server restarts 是否开启队列持久化
            $exclusive,  //the queue might be accessed by other channels 队列是否可以被其他队列访问
            $autoDelete  //the queue will be deleted once the channel is closed. 通道关闭后是否删除队列
        );
        $this->getChannel()->queue_bind($queue_name, $exchangeName, $routingKey);
        echo "[*] Waiting for logs. To exit press CTRL+C \n";
        $this->getChannel()->basic_consume($queue_name, $consumerTag, $noLocal, $noAck, $exclusiveQueue, $nowait, $callback);

        while (count($this->getChannel()->callbacks)) {
            $this->getChannel()->wait();
        }
    }

    /**
     * 发送（直接交换机）
     * @param string $data
     * @param string $exchangeName
     * @param string $routingKey
     * @throws \Exception
     */
    public function sendDirect(string $data = '', string $exchangeName = 'default', string $routingKey = ''): void
    {
        $msg = new AMQPMessage($data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->getChannel()->basic_publish($msg, $exchangeName, $routingKey);

        echo "[x] Sent $routingKey:$data \n";
    }

    /**
     * 接收（直接交换机）
     * @param callable $callback
     * @param string   $exchangeName
     * @param array    $bindingKeys
     * @param string   $queue
     * @param bool     $passive    是否检测同名队列
     * @param bool     $durable    是否开启持久化
     * @param bool     $exclusive  队列是否可以被其他队列访问
     * @param bool     $autoDelete 通道关闭后是否删除队列
     * @param string   $consumerTag
     * @param bool     $noLocal
     * @param bool     $noAck
     * @param bool     $exclusiveQueue
     * @param bool     $nowait
     * @throws \Exception
     */
    public function receiveDirect(callable $callback, string $exchangeName = 'default', array $bindingKeys = [], string $queue = '', bool $passive = false, bool $durable = true,
                                  bool     $exclusive = true, bool $autoDelete = false, string $consumerTag = '', bool $noLocal = false, bool $noAck = true, bool $exclusiveQueue = false,
                                  bool     $nowait = false): void
    {
        [$queue_name, ,] = $this->getChannel()->queue_declare($queue, $passive, $durable, $exclusive, $autoDelete);
        foreach ($bindingKeys as $bindingKey) {
            $this->getChannel()->queue_bind($queue_name, $exchangeName, $bindingKey);
        }
        echo "[x] Waiting for logs. To exit press CTRL+C \n";
        $this->getChannel()->basic_consume($queue_name, $consumerTag, $noLocal, $noAck, $exclusiveQueue, $nowait, $callback);
        while (count($this->getChannel()->callbacks)) {
            $this->getChannel()->wait();
        }
    }

    /**
     * 发送（主题交换机）
     * @param string $data
     * @param string $exchangeName
     * @param string $routingKey
     * @throws \Exception
     */
    public function sendTopic(string $data = 'Hello World!', string $exchangeName = 'default', string $routingKey = ''): void
    {
        $this->getChannel()->basic_publish(new AMQPMessage($data), $exchangeName, $routingKey);

        echo ' [x] Sent ', $exchangeName . '->' . $routingKey, ':', $data, " \n";
    }

    /**
     * 接收（主题交换机）
     * @param callable $callback
     * @param string   $exchangeName
     * @param array    $bindingKeys
     * @param string   $queue
     * @param bool     $passive    是否检测同名队列
     * @param bool     $durable    是否开启持久化
     * @param bool     $exclusive  队列是否可以被其他队列访问
     * @param bool     $autoDelete 通道关闭后是否删除队列
     * @param string   $consumerTag
     * @param bool     $noLocal
     * @param bool     $noAck
     * @param bool     $exclusiveQueue
     * @param bool     $nowait
     * @throws \Exception
     */
    public function receiveTopic(callable $callback, string $exchangeName = 'default', array $bindingKeys = [],
                                 string   $queue = '', bool $passive = false, bool $durable = true, bool $exclusive = true, bool $autoDelete = false,
                                 string   $consumerTag = '', bool $noLocal = false, bool $noAck = true, bool $exclusiveQueue = false, bool $nowait = false
    ): void
    {
        [$queueName, ,] = $this->getChannel()->queue_declare($queue, $passive, $durable, $exclusive, $autoDelete);
        foreach ($bindingKeys as $bindingKey) {
            $this->getChannel()->queue_bind($queueName, $exchangeName, $bindingKey);
        }

        echo ' [*] Waiting for logs. To exit press CTRL+C', "\n";

        $this->getChannel()->basic_consume($queueName, $consumerTag, $noLocal, $noAck, $exclusiveQueue, $nowait, $callback);

        while (count($this->getChannel()->callbacks)) {
            $this->getChannel()->wait();
        }
    }

}
