<?php

namespace Sweeper\HelperPhp\es;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Sweeper\DesignPattern\traits\SinglePattern;

/**
 * Elasticsearch 客户端封装
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/3/13 18:14
 * @example Elasticsearch::instance()->indices()
 * @Path \elasticsearch\Elasticsearch
 * @mixin Client
 * @doc https://www.elastic.co/guide/cn/elasticsearch/guide/current/foreword_id.html
 * @doc https://www.elastic.co/guide/cn/elasticsearch/php/current/_overview.html
 * @doc https://www.elastic.co/guide/cn/elasticsearch/php/current/_future_mode.html
 * @example
 * $params = [
 * 'index'  => 'test_missing',
 * 'type'   => 'test',
 * 'id'     => 1,
 * 'client' => [ 'ignore' => 404 ]
 * ];
 * echo $client->get($params);
 *
 * $params = [
 * 'index'  => 'test_missing',
 * 'type'   => 'test',
 * 'client' => [ 'ignore' => [400, 404] ]
 * ];
 * echo $client->get($params);
 *
 * $params = [
 * 'index' => 'test',
 * 'type' => 'test',
 * 'id' => 1,
 * 'client' => [
 * 'timeout' => 10,        // ten second timeout
 * 'connect_timeout' => 10
 * ]
 * ];
 * $response = $client->get($params);
 */
class Elasticsearch
{

    use SinglePattern;

    /** @var Client 客户端实例 */
    private $client;

    /** @var array 公共请求参数 */
    private $clientParams = [];

    /**
     * @return array
     */
    public function getClientParams(): array
    {
        return $this->clientParams;
    }

    /**
     * User: Sweeper
     * Time: 2023/3/13 19:37
     * @param array $clientParams
     * @return $this
     */
    public function setClientParams(array $clientParams): self
    {
        $this->clientParams = $clientParams;

        return $this;
    }

    /**
     * 构建客户端
     * User: Sweeper
     * Time: 2023/3/13 18:12
     * @param array $options
     * @return Client
     */
    public function build(array $options = []): Client
    {
        if ($this->client instanceof Client) {
            return $this->client;
        }
        $options['hosts'] = config('es.hosts') ?: array_filter(explode(',', str_replace([PHP_EOL, "，", " "], ',', env('es.hosts'))));
        if (empty($this->config)) {
            $this->setConfig(Config::pull('es') ?: []);
        }
        $options = array_replace($this->config, $options);

        if (!$options['hosts']) {
            throw new \InvalidArgumentException('ES 客户端初始化异常，没有找到可用的 host');
        }

        return $this->client = ClientBuilder::create()->setHosts($options['hosts'])->build();
    }

    /**
     * 调用 ES 客户端方法
     * User: Sweeper
     * Time: 2023/3/13 18:10
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $arguments[0] = array_replace($this->getClientParams(), $arguments[0]);// 使用设置的公共参数

        return $this->build()->{$name}(...$arguments);
    }

    /**
     * 使用忽略
     * User: Sweeper
     * Time: 2023/3/13 19:42
     * @param array $ignore [400, 404]
     * @return $this
     */
    public function withIgnore(array $ignore = [404]): self
    {
        $this->clientParams['client']['ignore'] = $ignore;

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2023/3/13 19:39
     * @param int $timeout
     * @return $this
     */
    public function withTimeout(int $timeout = 10): self
    {
        $this->clientParams['client']['timeout'] = $timeout;

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2023/3/13 19:39
     * @param int $connectTimeout
     * @return $this
     */
    public function withConnectTimeout(int $connectTimeout = 10): self
    {
        $this->clientParams['client']['connect_timeout'] = $connectTimeout;

        return $this;
    }

    /**
     * 使用详细信息
     * User: Sweeper
     * Time: 2023/3/15 14:44
     * @param bool $verbose
     * @return $this
     */
    public function withVerbose(bool $verbose = true): self
    {
        $this->clientParams['client']['verbose'] = $verbose;

        return $this;
    }

}
