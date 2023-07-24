<?php
/**
 * redis扩展
 */

namespace Sweeper\HelperPhp;

use Predis\Client;
use Sweeper\DesignPattern\traits\SinglePattern;

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/1/17 21:39
 * @Path \redis\RedisClient
 * @mixin Client
 */
class RedisClient
{

    use SinglePattern;

    /** @var Client */
    private $client;

    public const CLUSTER_REDIS  = 'redis';
    public const CLUSTER_PREDIS = 'predis';

    public const REDIS_KEY_PREFIX = 'sync:orders:';

    /**
     * 连接 Redis
     * User: Sweeper
     * Time: 2023/1/17 21:34
     * @param array $options
     * @return Client
     */
    public function connection(array $options = []): Client
    {
        // 判断是否有扩展
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if ($this->client instanceof Client) {
            return $this->client;
        }
        if (empty($this->config)) {
            $this->setConfig(config('cache.redis'));
        }
        $options = array_replace($this->config, $options);// read_write_timeout

        if ($options['cluster_list']) {
            $servers      = $options['cluster_list'];
            $option       = [
                'cluster'    => $options['cluster'] ?? static::CLUSTER_REDIS,
                'parameters' => array_replace([
                    'password'   => $options['password'] ?? '',
                    'timeout'    => $options['timeout'] ?? 10,
                    'select'     => $options['select'] ?? 0,// 选择的数据库
                    'expire'     => $options['expire'] ?? 0, // 缓存有效期 0表示永久缓存
                    'prefix'     => $options['prefix'] ?? static::REDIS_KEY_PREFIX, // 缓存前缀
                    'persistent' => $options['persistent'] ?? false,// 是否长连接 false=短连接
                ], $options)
            ];
            $this->client = new Client($servers, $option);
        } else {
            $this->client = new Client(array_replace([
                'scheme'     => 'tcp',
                'host'       => $options['host'] ?? '127.0.0.1',
                'port'       => $options['port'] ?? 6379,
                'password'   => $options['password'] ?? '',
                'timeout'    => $options['timeout'] ?? 10,
                'select'     => $options['select'] ?? 0,// 选择的数据库
                'expire'     => $options['expire'] ?? 0, // 缓存有效期 0表示永久缓存
                'prefix'     => $options['prefix'] ?? static::REDIS_KEY_PREFIX, // 缓存前缀
                'persistent' => $options['persistent'] ?? false,// 是否长连接 false=短连接
            ], $options));
        }

        return $this->client;
    }

    public function __call($name, $arguments)
    {
        return $this->connection()->{$name}(...$arguments);
    }

}
