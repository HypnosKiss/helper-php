<?php

namespace Sweeper\HelperPhp\Cache;

use Redis;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/3 10:41
 * @Package \Sweeper\HelperPhp\Cache\CacheRedis
 */
class CacheRedis extends CacheAdapter
{

    /** @var \Redis */
    private $redis       = null;              //缓存对象

    private $defaultHost = '127.0.0.1';       //默认服务器地址

    private $defaultPort = 6379;              //默认端口号

    private $queueName   = 'redis_queue';

    protected function __construct(array $config)
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('No redis extension found');
        }
        parent::__construct($config);
        $server      = $config ?: [
            'host'     => $this->defaultHost,
            'port'     => $this->defaultPort,
            'database' => '',
            'password' => '',
        ];
        $this->redis = new Redis();
        $this->redis->connect($server['host'], $server['port']);
        if ($server['password']) {
            $this->redis->auth($server['password']);
        }
        if ($server['database']) {
            $this->select($server['database']);
        }
    }

    /**
     * @param $db_index
     * @return bool
     */
    public function select($db_index): bool
    {
        return $this->redis->select($db_index);
    }

    /**
     * swap database
     * @param $fromDbIndex
     * @param $toDbIndex
     * @return bool
     */
    public function swapDb($fromDbIndex, $toDbIndex): bool
    {
        return $this->redis->swapdb($fromDbIndex, $toDbIndex);
    }

    /**
     * set cache
     * @param string $cacheKey
     * @param        $data
     * @param int    $expired
     * @return bool
     */
    public function set(string $cacheKey, $data, int $expired = 60): bool
    {
        $data = serialize($data);

        return $this->redis->setex($cacheKey, $expired, $data);
    }

    /**
     * set cache
     * @param string $cacheKey
     * @param        $data
     * @param int    $expired
     * @return bool
     */
    public function setByText(string $cacheKey, $data, int $expired = 60): bool
    {
        return $this->redis->setex($cacheKey, $expired, $data);
    }

    /**
     * get data
     * @param string $cacheKey
     * @return mixed|null
     */
    public function get(string $cacheKey)
    {
        $data = $this->redis->get($cacheKey);

        return $data === false ? null : unserialize($data);
    }

    /**
     * get data
     * @param string $cacheKey
     * @return mixed|null
     */
    public function getByText(string $cacheKey)
    {
        $data = $this->redis->get($cacheKey);

        return $data === false ? null : $data;
    }

    /**
     * @param string $cacheKey
     * @return void
     */
    public function delete(string $cacheKey): void
    {
        $this->redis->del($cacheKey);
    }

    /**
     * @return bool
     */
    public function flush(): bool
    {
        return $this->redis->flushAll();
    }

    /**
     * 设置队列名称
     * @param $queueName
     * @return static
     */
    public function setQueueName($queueName): self
    {
        $this->queueName = $queueName;

        return $this;
    }

    /**
     * 取得队列的长度
     */
    public function lSize()
    {
        return $this->redis->lLen($this->queueName);
    }

    /**
     * 从队列中取出多少个数据
     * @param $num
     * @return array
     */
    public function lRang($num): array
    {
        return $this->redis->lRange($this->queueName, 0, $num);
    }

    /**
     * 给队列添加一个数据
     * @param $value
     * @return bool|int
     */
    public function rPush($value)
    {
        return $this->redis->rPush($this->queueName, $value);
    }

    /**
     * 从队列中取出一个数据
     */
    public function lPop()
    {
        return $this->redis->lPop($this->queueName);
    }

    /**
     * 从队列中删除数据
     * @param number $start 开始index
     * @param number $stop  结束index
     * @return array|bool
     */
    public function lTrim($start, $stop)
    {
        return $this->redis->lTrim($this->queueName, $start, $stop);
    }

}
