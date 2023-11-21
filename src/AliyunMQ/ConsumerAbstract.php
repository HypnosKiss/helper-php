<?php

namespace Sweeper\HelperPhp\AliyunMQ;

use Exception;
use MQ\Exception\AckMessageException;
use MQ\Exception\MessageNotExistException;
use MQ\Model\AckMessageErrorItem;
use MQ\Model\Message;
use MQ\MQClient;
use MyServerConfig\ServerConfig;
use Sweeper\HelperPhp\MultiProcess\MultiProcessManagerAbstract;
use Sweeper\HelperPhp\Tool\Hooker;

/**
 * MQ消费者
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/21 9:08
 * @Package \Sweeper\HelperPhp\AliyunMQ\ConsumerAbstract
 * 使用必读
 * 一个 Group ID 代表一个 Consumer 实例群组。同一个消费者 Group ID 下所有的 Consumer 实例必须保证订阅的 Topic 一致，并且也必须保证订阅 Topic 时设置的过滤规则（Tag）一致。否则您的消息可能会丢失。
 */
abstract class ConsumerAbstract extends MultiProcessManagerAbstract
{

    /** @var string 普通消息 */
    public const GENERAL_TYPE = 'general';

    /** @var string 事务消息 */
    public const TRANSACTION_TYPE = 'transaction';

    /** @var int 一次最多消费16条(最多可设置为16条) */
    public const MAX_NUM_OF_MESSAGES = 16;

    /** @var int 长轮询时间1秒（最多可设置为30秒） */
    public const MAX_WAIT_SECONDS = 30;

    /** @var string HTTP接入域名 */
    private $endPoint = '';

    /** @var string AccessKey 阿里云身份验证，在阿里云服务器管理控制台创建 */
    private $accessKey = '';

    /** @var string SecretKey 阿里云身份验证，在阿里云服务器管理控制台创建 */
    private $secretKey = '';

    /** @var string  所属的 Topic */
    private $topic = '';

    /** @var string Topic所属实例ID，默认实例为空NULL */
    private $instanceId = '';

    /** @var string 您在控制台创建的 Consumer ID(Group ID) 【业务不同 GroupId 需要区分开】 */
    private $groupId = '';

    /** @var \MQ\MQClient MQ 客户端 */
    private $client;

    /** @var \MQ\MQConsumer 消费者 */
    private $consumer;

    /** @var int 一次最多消费消息条数 - 一次最多消费3条(最多可设置为16条) */
    private $numOfMessages = self::MAX_NUM_OF_MESSAGES;

    /** @var int 长轮询时间秒数 - 长轮询时间3秒（最多可设置为30秒） */
    private $waitSeconds = self::MAX_WAIT_SECONDS;

    /** @var string 消息标签 */
    private $messageTag = null;

    /** @var string 消息类型 */
    private $type = self::GENERAL_TYPE;

    /** @var string 日志目录 */
    private $logDir = 'rocket_mq';

    /**
     * 获取 HTTP接入域名（此处以公共云生产环境为例）
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/23 12:41
     * @return string
     */
    public function getEndPoint(): string
    {
        return $this->endPoint;
    }

    public function setEndPoint(string $endPoint): self
    {
        $this->endPoint = $endPoint;

        return $this;
    }

    /**
     * 获取 AccessKey 阿里云身份验证，在阿里云服务器管理控制台创建
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/23 12:43
     * @return string
     */
    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    public function setAccessKey(string $accessKey): self
    {
        $this->accessKey = $accessKey;

        return $this;
    }

    /**
     * 获取 SecretKey 阿里云身份验证，在阿里云服务器管理控制台创建
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/23 12:45
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey): self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    /**
     * 获取 所属的 Topic【订阅的 Topic 中的 Tag 必须一致】
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/23 12:49
     * @return string|null
     */
    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(?string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * 获取 Topic所属实例ID，默认实例为空NULL
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/23 12:50
     * @return string|null
     */
    public function getInstanceId(): ?string
    {
        return $this->instanceId;
    }

    public function setInstanceId(?string $instanceId): self
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    /**
     * 获取 您在控制台创建的 Consumer ID(Group ID) 【业务不同 GroupId 需要区分开】
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/21 9:23
     * @return string
     */
    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function setGroupId(string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return \MQ\MQClient
     */
    public function getClient(): MQClient
    {
        if (!($this->client instanceof MQClient)) {
            $this->client = new MQClient($this->getEndPoint(), $this->getAccessKey(), $this->getSecretKey());
        }

        return $this->client;
    }

    /**
     * @param \MQ\MQClient $client
     * @return $this
     */
    public function setClient(MQClient $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getConsumer(): \MQ\MQConsumer
    {
        if (!($this->consumer instanceof MQConsumer)) {
            $this->consumer = $this->getClient()->getConsumer($this->getInstanceId(), $this->getTopic(), $this->getGroupId(), $this->getMessageTag());
        }

        return $this->consumer;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/21 9:17
     * @param \MQ\MQConsumer $consumer
     * @return $this
     */
    public function setConsumer(\MQ\MQConsumer $consumer): self
    {
        $this->consumer = $consumer;

        return $this;
    }

    public function getNumOfMessages(): int
    {
        return $this->numOfMessages;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/21 9:18
     * @param int $numOfMessages
     * @return $this
     */
    public function setNumOfMessages(int $numOfMessages): self
    {
        $this->numOfMessages = $numOfMessages;

        return $this;
    }

    public function getWaitSeconds(): int
    {
        return $this->waitSeconds;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/21 9:18
     * @param int $waitSeconds
     * @return $this
     */
    public function setWaitSeconds(int $waitSeconds): self
    {
        $this->waitSeconds = $waitSeconds;

        return $this;
    }

    /**
     * messageTag 【订阅的 Topic 中的 Tag 必须一致】
     * @return string
     */
    public function getMessageTag(): ?string
    {
        return $this->messageTag;
    }

    public function setMessageTag(string $messageTag): self
    {
        $this->messageTag = $messageTag;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function setLogDir(string $logDir): self
    {
        $this->logDir = $logDir;

        return $this;
    }

    /**
     * ConsumerAbstract constructor.
     * @param int $maxProcessCount 最大进程数
     */
    public function __construct($maxProcessCount = 1, bool $debug = null)
    {
        parent::__construct($maxProcessCount, $debug);
    }

    /**
     * 断言允许执行(声明当前时间允许执行) 常驻后台等待 MQ 消息
     * @return bool
     */
    protected function assertWithInTheTimeRange(): bool
    {
        return true;
    }

    /**
     * 主进程 长轮询消费消息
     * @param array $params
     */
    public function master(array $params = []): void
    {
        // 在当前线程循环消费消息，建议是多开个几个线程并发消费消息
        while (true) {
            $this->signalDispatch();
            // 长轮询表示如果topic没有消息则请求会在服务端挂住 {$this->waitSeconds}s，{$this->waitSeconds}s内如果有消息可以消费则立即返回
            Hooker::fire(self::$EVENT_CHECK_SLAVE_PROCESS);
            try {
                $messages = $this->getConsumer()->consumeMessage($this->getNumOfMessages(), $this->getWaitSeconds());
            } catch (\Throwable $e) {
                if ($e instanceof MessageNotExistException) {
                    // 没有消息可以消费，接着轮询
                    $this->debug("No message, continue long polling!RequestId:{$e->getRequestId()}.");
                    continue;
                }
                $this->error('consumeMessage:' . $e->getMessage());
                Hooker::fire(static::$EVENT_SLEEP_MONITOR_SLAVE, $this->getWaitSeconds());
                continue;
            }
            $this->debug('consume finish.');

            /** @var Message[] $messages */
            $receiptHandles = [];
            foreach ($messages as $message) {
                $this->debug('消息消费开始');
                $receiptHandles[] = $message->getReceiptHandle();
                // 业务逻辑处理程序 每个消息产生一个子进程
                $this->taskWorker($message);
                $this->debug('消息消费结束');
                Hooker::fire(self::$EVENT_CHECK_SLAVE_PROCESS);//处理完每个消息都检测一下子进程
            }

            try {
                $this->getConsumer()->ackMessage($receiptHandles);
            } catch (\Throwable $e) {
                if ($e instanceof AckMessageException) {
                    // 某些消息的句柄可能超时了会导致确认不成功
                    $this->error("Ack Error, RequestId:{$e->getRequestId()}\n");
                    foreach ($e->getAckMessageErrorItems() as $errorItem) {
                        /** @var AckMessageErrorItem $errorItem */
                        $this->error("ReceiptHandle:{$errorItem->getReceiptHandle()}, ErrorCode:{$errorItem->getErrorCode()}, ErrorMsg:{$errorItem->getErrorCode()}.");
                    }
                }
            }
            $this->debug('ack finish.');
            Hooker::fire(self::$EVENT_CHECK_SLAVE_PROCESS);
            gc_collect_cycles();//强制收集所有现存的垃圾循环周期
        }
    }

    /**
     * 业务逻辑处理程序
     * @param Message $message
     */
    abstract public function taskWorker(Message $message);

}