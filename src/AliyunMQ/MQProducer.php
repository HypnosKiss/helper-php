<?php

namespace Sweeper\HelperPhp\AliyunMQ;

use MQ\Constants;
use MQ\Model\TopicMessage;
use MQ\MQClient;

/**
 * MQ普通消息发布者
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/23 12:35
 * @Package \Sweeper\HelperPhp\AliyunMQ\MQProducer
 */
class MQProducer
{

    /** @var string HTTP接入域名 */
    private $endPoint;

    /** @var string AccessKey 阿里云身份验证，在阿里云服务器管理控制台创建 */
    private $accessKey;

    /** @var string SecretKey 阿里云身份验证，在阿里云服务器管理控制台创建 */
    private $secretKey;

    /** @var string  所属的 Topic */
    private $topic;

    /** @var string Topic所属实例ID，默认实例为空NULL */
    private $instanceId;

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
     * 获取 所属的 Topic
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
     * MQProducer constructor.
     * @param null $topic      消息主题 Topic
     * @param null $instanceId Topic所属实例ID
     */
    public function __construct($endPoint = null, $accessKey = null, $secretKey = null, $topic = null, $instanceId = null)
    {
        $this->setEndPoint($endPoint)->setAccessKey($accessKey)->setSecretKey($secretKey)->setTopic($topic)->setInstanceId($instanceId);
    }

    /**
     * 发布消息
     * 存储的消息最多保存 3 天，超过 3 天未消费的消息会被删除。建议配置监控报警实时监控消费进度，并根据报警信息人工介入处理。
     * @param string $messageBody  消息内容(消息大小根据消息类型来限制。具体限制如下所述： 普通消息和顺序消息：4 MB 事务消息和定时/延时消息：64 KB)
     * @param null   $property_key 设置属性KEY
     * @param null   $property_val 设置属性VALUE
     * @param null   $message_key  设置消息KEY
     * @param null   $messageTag   设置消息TAG
     * @return mixed
     * @throws \Exception
     */
    public function publishMessage(string $messageBody = 'messageBody', $property_key = null, $property_val = null, $message_key = null, $messageTag = null)
    {
        $publishMessage = new TopicMessage($messageBody);
        if ($property_key && $property_val) {
            $publishMessage->putProperty($property_key, $property_val);// 设置属性
        }
        $publishMessage->setMessageKey($message_key);
        $publishMessage->setMessageTag($messageTag);
        $producer = null;
        try {
            $client   = new MQClient($this->getEndPoint(), $this->getAccessKey(), $this->getSecretKey());
            $producer = $client->getProducer($this->getInstanceId(), $this->getTopic());
            /** @var TopicMessage $topicMessage */
            $topicMessage = $producer->publishMessage($publishMessage);
            $message_id   = $topicMessage->getMessageId();
            $publishMessage->putProperty('title', '发布消息成功');
            $publishMessage->putProperty(Constants::MESSAGE_ID, $message_id);
        } catch (\Exception $e) {
            $publishMessage->putProperty('title', '发布消息失败');
            throw $e;
        }

        return $message_id;
    }

}