<?php

namespace Sweeper\HelperPhp\Alibaba;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/22 18:19
 * Download：https://github.com/aliyun/openapi-sdk-php
 * Usage：https://github.com/aliyun/openapi-sdk-php/blob/master/README.md
 * Class AlibabaCloudRpc
 * @Package \Sweeper\HelperPhp\AlibabaCloud\AlibabaCloudRpc
 */
class AlibabaCloudRpc
{

    /** @var string */
    private $accessKeyId = '';

    /** @var string */
    private $accessSecret = '';

    /** @var string */
    private $regionId = 'cn-shenzhen';

    /** @var string */
    private $host = 'ons.cn-shenzhen.aliyuncs.com';

    /** @var self[] 实例列表 */
    private static $instances;

    /**
     * 构造器，禁止外部公开调用
     * @param string $accessKeyId
     * @param string $accessSecret
     * @param string $regionId
     * @param string $host
     */
    protected function __construct(string $accessKeyId = '', string $accessSecret = '', string $regionId = '', string $host = '')
    {
        $this->setAccessKeyId($accessKeyId)->setAccessSecret($accessSecret)->setRegionId($regionId)->setHost($host);
    }

    /**
     * 单例
     * @param string $accessKeyId
     * @param string $accessSecret
     * @param string $regionId
     * @param string $host
     * @return static
     */
    public static function instance(string $accessKeyId = '', string $accessSecret = '', string $regionId = '', string $host = ''): AlibabaCloudRpc
    {
        $key = implode('-', [$accessKeyId, $accessSecret, $regionId, $host]);
        if (empty(self::$instances[$key])) {
            self::$instances[$key] = new self($accessKeyId, $accessSecret, $regionId, $host);
        }

        return self::$instances[$key];
    }

    public function getAccessKeyId(): string
    {
        return $this->accessKeyId;
    }

    public function setAccessKeyId(string $accessKeyId): self
    {
        $this->accessKeyId = $accessKeyId;

        return $this;
    }

    public function getAccessSecret(): string
    {
        return $this->accessSecret;
    }

    public function setAccessSecret(string $accessSecret): self
    {
        $this->accessSecret = $accessSecret;

        return $this;
    }

    public function getRegionId(): string
    {
        return $this->regionId;
    }

    public function setRegionId(string $regionId): self
    {
        $this->regionId = $regionId;

        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * 使用 OnsTopicList 查询账号下所有 Topic 的信息列表
     * @param      $instanceId
     * @param null $topic
     * @return mixed
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @help_url https://help.aliyun.com/document_detail/29590.html
     */
    public function getOnsTopicList($instanceId, $topic = null)
    {
        AlibabaCloud::accessKeyClient($this->getAccessKeyId(), $this->getAccessSecret())->regionId($this->getRegionId())->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()->product('Ons')->version('2019-02-14')->action('OnsTopicList')->method('POST')->host($this->getHost())->options([
                'query' => [
                    'RegionId'   => $this->getRegionId(),
                    'InstanceId' => $instanceId,
                    'Topic'      => $topic,
                ],
            ])->request();

            return $result->toArray()['Data']['PublishInfoDo'];
        } catch (ClientException|ServerException $e) {
            throw new \RuntimeException($e->getErrorMessage());
        }
    }

    /**
     * 使用 OnsTopicStatus 查询当前 Topic 下的消息总量以及 Topic 的最后更新时间。
     * @param $instanceId
     * @param $topic
     * @return mixed
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @help_url https://help.aliyun.com/document_detail/29595.html
     */
    public function getOnsTopicStatus($instanceId, $topic)
    {
        AlibabaCloud::accessKeyClient($this->getAccessKeyId(), $this->getAccessSecret())->regionId($this->getRegionId())->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()->product('Ons')->version('2019-02-14')->action('OnsTopicStatus')->method('POST')->host($this->getHost())->options([
                'query' => [
                    'RegionId'   => $this->getRegionId(),
                    'InstanceId' => $instanceId,
                    'Topic'      => $topic,
                ],
            ])->request();

            return $result->toArray()['Data'];
        } catch (ClientException|ServerException $e) {
            throw new \RuntimeException($e->getErrorMessage());
        }
    }

    /**
     * 一般使用 OnsTopicSubDetail 查看有哪些在线订阅组订阅了这个 Topic
     * @param $instanceId
     * @param $topic
     * @return mixed
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @help_url https://help.aliyun.com/document_detail/111726.html
     */
    public function OnsTopicSubDetail($instanceId, $topic)
    {
        AlibabaCloud::accessKeyClient($this->getAccessKeyId(), $this->getAccessSecret())->regionId($this->getRegionId())->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()->product('Ons')->version('2019-02-14')->action('OnsTopicSubDetail')->method('POST')->host($this->getHost())->options([
                'query' => [
                    'RegionId'   => $this->getRegionId(),
                    'InstanceId' => $instanceId,
                    'Topic'      => $topic,
                ],
            ])->request();

            return $result->toArray()['Data'];
        } catch (ClientException|ServerException $e) {
            throw new \RuntimeException($e->getErrorMessage());
        }
    }

}
