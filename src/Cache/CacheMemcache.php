<?php

namespace Sweeper\HelperPhp\Cache;

use Memcache;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/3 10:41
 * @Package \Sweeper\HelperPhp\Cache\CacheMemcache
 */
class CacheMemcache extends CacheAdapter
{

    /** @var Memcache * */
    private $cache;                     //缓存对象

    private $defaultHost = '127.0.0.1'; //默认服务器地址

    private $defaultPort = 11211;       //默认端口号

    public function __construct(array $config)
    {
        if (!extension_loaded('memcache')) {
            throw new \RuntimeException('Can not find the memcache extension', 403);
        }

        $servers     = $config['servers'] ?? [];
        $this->cache = new Memcache;
        if (!empty($servers)) {
            foreach ($servers as $server) {
                $this->addServe($server);
            }
        } else {
            $this->addServe($this->defaultHost . ':' . $this->defaultPort);
        }
        parent::__construct($config);
    }

    /**
     * @brief  添加服务器到连接池
     * @param string $address 服务器地址
     * @return bool   true:成功;false:失败;
     */
    private function addServe(string $address): bool
    {
        [$host, $port] = explode(':', $address);
        $port = $port ?: $this->defaultPort;

        return $this->cache->addserver($host, $port);
    }

    /**
     * @brief  写入缓存
     * @param string $cacheKey 缓存的唯一key值
     * @param mixed  $data     要写入的缓存数据
     * @param int    $expired  缓存数据失效时间,单位：秒
     * @return bool   true:成功;false:失败;
     */
    public function set(string $cacheKey, $data, int $expired = 0): bool
    {
        return $this->cache->set($cacheKey, $data, MEMCACHE_COMPRESSED, $expired);
    }

    /**
     * @brief  读取缓存
     * @param string $cacheKey 缓存的唯一key值,当要返回多个值时可以写成数组
     * @return array|false|string  读取出的缓存数据;null:没有取到数据;
     */
    public function get(string $cacheKey)
    {
        return $this->cache->get($cacheKey);
    }

    /**
     * @brief  删除缓存
     * @param string     $cacheKey 缓存的唯一key值
     * @param int|string $timeout  在间隔单位时间内自动删除,单位：秒
     * @return bool true:成功; false:失败;
     */
    public function delete(string $cacheKey, $timeout = 0): bool
    {
        return $this->cache->delete($cacheKey, $timeout);
    }

    /**
     * @brief  删除全部缓存
     */
    public function flush(): void
    {
        $this->cache->flush();
    }

}
