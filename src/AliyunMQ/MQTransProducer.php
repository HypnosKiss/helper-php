<?php

namespace Sweeper\HelperPhp\AliyunMQ;

use MQ\Exception\AckMessageException;
use MQ\Exception\MessageNotExistException;
use MQ\Model\AckMessageErrorItem;
use MQ\Model\Message;
use MQ\Model\TopicMessage;
use MQ\MQClient;
use Sweeper\HelperPhp\Traits\LogTrait;

/**
 * MQ事务消息发布者
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/24 12:49
 * @Package \Sweeper\HelperPhp\AliyunMQ\MQTransProducer
 */
class MQTransProducer extends MQProducer
{

    use LogTrait;

    /** @var int 一次最多消费16条(最多可设置为16条) */
    public const MAX_NUM_OF_MESSAGES = 16;

    /** @var int 长轮询时间1秒（最多可设置为30秒） */
    public const MAX_WAIT_SECONDS = 30;

    /** @var int 获取 您在控制台创建的 Consumer ID(Group ID) */
    protected $groupId;

    /** @var \MQ\MQClient */
    protected $client;

    /** @var \MQ\MQTransProducer */
    protected $transProducer;

    /** @var int 一次最多消费消息条数 */
    protected $numOfMessages;

    /** @var int 长轮询时间秒数 */
    protected $waitSeconds;

    private   $count       = 0;

    private   $popMsgCount = 0;

    /** @var int 在消息属性中添加第一次消息回查的最快时间，单位秒，并且表征这是一条事务消息 范围: 10~300 */
    private $timeInSeconds = 10;

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function setGroupId(int $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return \MQ\MQClient
     */
    public function getClient(): MQClient
    {
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

    public function getTransProducer(): \MQ\MQTransProducer
    {
        return $this->transProducer;
    }

    public function setTransProducer(\MQ\MQTransProducer $transProducer): self
    {
        $this->transProducer = $transProducer;

        return $this;
    }

    public function getNumOfMessages(): int
    {
        return $this->numOfMessages;
    }

    public function setNumOfMessages(int $numOfMessages): self
    {
        $this->numOfMessages = $numOfMessages;

        return $this;
    }

    public function getWaitSeconds(): int
    {
        return $this->waitSeconds;
    }

    public function setWaitSeconds(int $waitSeconds): self
    {
        $this->waitSeconds = $waitSeconds;

        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getPopMsgCount(): int
    {
        return $this->popMsgCount;
    }

    public function setPopMsgCount(int $popMsgCount): self
    {
        $this->popMsgCount = $popMsgCount;

        return $this;
    }

    public function getTimeInSeconds(): int
    {
        return $this->timeInSeconds;
    }

    public function setTimeInSeconds(int $timeInSeconds): self
    {
        $this->timeInSeconds = $timeInSeconds;

        return $this;
    }

    /**
     * MQTransProducer constructor.
     * @param null $endPoint
     * @param null $accessKey
     * @param null $secretKey
     * @param null $topic
     * @param null $instanceId
     * @param int  $waitSeconds
     * @param int  $numOfMessages
     */
    public function __construct($endPoint = null, $accessKey = null, $secretKey = null, $topic = null, $instanceId = null, int $waitSeconds = 1, int $numOfMessages = 3)
    {
        parent::__construct($endPoint, $accessKey, $secretKey, $topic, $instanceId);

        $this->setClient(new MQClient($this->getEndPoint(), $this->getAccessKey(), $this->getSecretKey()))
             ->setTransProducer($this->getClient()->getTransProducer($this->getInstanceId(), $this->getTopic(), $this->getGroupId()))
             ->setCount(0)
             ->setPopMsgCount(0)
             ->setTimeInSeconds(10)
             ->setWaitSeconds($waitSeconds ?: self::MAX_WAIT_SECONDS)
             ->setNumOfMessages($numOfMessages ?: self::MAX_NUM_OF_MESSAGES);
    }

    /**
     * 处理确认错误
     * @param $exception
     */
    private function processAckError(\Throwable $exception)
    {
        if ($exception instanceof AckMessageException) {
            // 如果Commit/Rollback时超过了TransCheckImmunityTime（针对发送事务消息的句柄）或者超过NextConsumeTime（针对consumeHalfMessage的句柄）则会失败
            $this->error("Commit/Rollback Error, RequestId:", $exception->getRequestId());
            /** @var AckMessageErrorItem $errorItem */
            foreach ($exception->getAckMessageErrorItems() as $errorItem) {
                $this->error("\tReceiptHandle:", $errorItem->getReceiptHandle(), ", ErrorCode:", $errorItem->getErrorCode(), ",ErrorMsg:", $errorItem->getErrorMessage());
            }
        } else {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 提交事务消息
     * @param \MQ\Model\Message $message
     */
    private function commitTransMsg(Message $message)
    {
        $this->info($message);
        try {
            $this->info("commit transaction msg: " . $message->getMessageId());
            $this->getTransProducer()->commit($message->getReceiptHandle());
            $this->count++;
        } catch (\Throwable $e) {
            $this->processAckError($e);
        }
    }

    /**
     * 消费没有确认的事务消息
     * @param bool $daemon 后台守护任务
     */
    private function consumeHalfMsg($daemon = false)
    {
        //每次发一次消息，消费没有确认的消息 || 后台守护任务等待需要出的的事务消息
        while ($daemon || ($this->getCount() < ($this->getNumOfMessages() - 1) && $this->getPopMsgCount() < (self::MAX_NUM_OF_MESSAGES - 1))) {
            $this->popMsgCount++;
            try {
                $messages = $this->getTransProducer()->consumeHalfMessage($this->getNumOfMessages(), $this->getWaitSeconds());
            } catch (\Throwable $e) {
                if ($e instanceof MessageNotExistException) {
                    $this->debug("no half transaction message.");
                    continue;
                }
                $this->error($e->getMessage());
                sleep($this->getWaitSeconds());
                continue;
            }

            /** @var Message $message */
            foreach ($messages as $message) {
                $this->commitTransMsg($message);
            }
        }
    }

    /**
     * 发布消息
     * @param string $messageBody 消息内容
     * @param null   $property_key
     * @param null   $property_val
     * @param null   $message_key
     * @param null   $messageTag
     * @return void
     */
    public function publishMessage($messageBody = "messageBody", $property_key = null, $property_val = null, $message_key = null, $messageTag = null)
    {
        $pubMsg = new TopicMessage($messageBody);
        if ($property_key && $property_val) {
            $pubMsg->putProperty($property_key, $property_val);// 设置属性
        }
        $pubMsg->setMessageKey($message_key);// 设置消息KEY
        $pubMsg->setMessageTag($messageTag); // 设置消息TAG
        //设置事务第一次回查的时间，为相对时间，单位：秒，范围为10~300s之间  第一次事务回查后如果消息没有commit或者rollback，则之后每隔10s左右会回查一次，总共回查一天
        $pubMsg->setTransCheckImmunityTime($this->getTimeInSeconds());

        try {
            /** @var TopicMessage $topicMessage */
            $topicMessage = $this->transProducer->publishMessage($pubMsg);
            $this->info($topicMessage->getMessageId(), $topicMessage->getReceiptHandle());
            // 发送完事务消息后能获取到半消息句柄，可以直接commit/rollback事务消息
            $this->getTransProducer()->commit($topicMessage->getReceiptHandle());
            $this->info("commit transaction msg when publish: " . $topicMessage->getMessageId());
        } catch (\Throwable $e) {
            // 如果Commit/Rollback时超过了TransCheckImmunityTime则会失败
            $this->processAckError($e);
        }

        // 这里最好有一个单独线程或者进程来消费没有确认的事务消息
        // 仅示例：检查没有确认的事务消息
        // $this->checkForUnConfirmedTransactionMessages();
    }

    /**
     * 检查没有确认的事务消息
     * @example
     * //if (strpos('cli', PHP_SAPI) !== false) {
     * //     //单独进程来消费没有确认的事务消息
     * //     (new MQTransProducer())->checkForUnConfirmedTransactionMessages();
     * // }
     */
    public function checkForUnConfirmedTransactionMessages()
    {
        // 这里最好有一个单独线程或者进程来消费没有确认的事务消息
        $this->consumeHalfMsg(true);
    }

}

