<?php

namespace Sweeper\HelperPhp\Cache;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/3 9:51
 * @Package \Sweeper\HelperPhp\Cache\CacheAdapter
 */
abstract class CacheAdapter implements CacheInterface
{

    /** @var static[] */
    private static $instances;

    /** @var array $config */
    private $config;

    /**
     * 构造器，禁止外部公开调用
     * @param array $config
     */
    protected function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * 单例
     * @param array $config
     * @return static
     */
    final public static function instance(array $config = []): self
    {
        $class = static::class;
        $key   = $class . serialize($config);
        if (!isset(self::$instances[$key]) || !self::$instances[$key]) {
            self::$instances[$key] = new $class($config);
        }

        return self::$instances[$key];
    }

    /**
     * 快速调用方法，不提供配置参数传入
     * @param string   $key            缓存key
     * @param callable $fetcher        数据获取回调
     * @param int      $expiredSeconds 缓存过期时间
     * @param bool     $refreshCache   是否刷新缓存，默认false为仅在缓存过期时才更新
     * @return mixed
     */
    final public function cache(string $key, callable $fetcher, int $expiredSeconds = 60, bool $refreshCache = false)
    {
        $cacheClass = static::class;
        if ($cacheClass === self::class) {
            throw new \LogicException('Cache method not callable in ' . self::class);
        }

        if ($refreshCache) {
            $data = $fetcher();
            $this->set($key, $data, $expiredSeconds);

            return $data;
        }

        $data = $this->get($key);
        if (!isset($data)) {
            $data = $fetcher();
            $this->set($key, $data, $expiredSeconds);
        }

        return $data;
    }

    /**
     * 分布式缓存存储
     * @param       $cachePrefixKey
     * @param array $dataList
     * @param int   $expired
     * @return static
     */
    final public function setDistributed(string $cachePrefixKey, array $dataList, int $expired = 60): self
    {
        foreach ($dataList as $k => $data) {
            $this->set($cachePrefixKey . $k, $data, $expired);
        }

        return $this;
    }

    /**
     * 获取配置
     * @param string $key
     * @return mixed
     */
    public function getConfig(string $key = '')
    {
        if ($key) {
            return $this->config[$key];
        }

        return $this->config;
    }

    /**
     * 设置配置
     * @param $config
     * @return static
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

}
