<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/11 10:38
 */

namespace Sweeper\HelperPhp\Traits;

use Sweeper\HelperPhp\Tool\RedisClient;

/**
 * 缓存通用特征
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/13 11:41
 * @Package \Sweeper\HelperPhp\Traits\RedisCache
 */
trait RedisCache
{

    /** @var RedisClient Redis 处理器 */
    private $redisHandler;

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/18 14:05
     * @return \Sweeper\HelperPhp\Tool\RedisClient
     */
    public function getRedisHandler(): RedisClient
    {
        return $this->redisHandler ?: RedisClient::instance();
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/18 14:04
     * @param $handler
     * @return $this
     */
    public function setRedisHandler($handler): self
    {
        $this->redisHandler = $handler;

        return $this;
    }

    /**
     * 通过 hGet 获取数据
     * User: Sweeper
     * Time: 2023/8/11 11:03
     * @param string   $cacheKey        缓存 KEY
     * @param string   $cacheField      缓存 FIELD
     * @param callable $getDataCallback 获取数据的回调
     * @param int      $emptyDataExpire 空数据过期时间
     * @param int      $expire          缓存过期时间
     * @param bool     $refresh         强制刷新
     * @param mixed    ...$args         回调的参数
     * @return array [$data, $errors]
     */
    public function getDataByHGet(string $cacheKey, string $cacheField, callable $getDataCallback, int $emptyDataExpire = 300, int $expire = 86400, bool $refresh = false, ...$args): array
    {
        $errors   = [];
        $cacheKey = $this->getRedisHandler()->generateKey($cacheKey);
        try {
            $cache     = $this->getRedisHandler()->hGet($cacheKey, $cacheField) ?: '';
            $cacheData = json_decode($cache, true) ?: [];
            if (!empty($cacheData['expire']) && $cacheData['expire'] <= time()) {// 校验过期时间，超过过期时间重新获取数据
                $cacheData = [];                                                 // 置空，重新获取数据，再设置到当前缓存字段
            }
        } catch (\Throwable $ex) {
            $errors[]  = "hGet exception:{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})";
            $cacheData = [];
        }
        if (empty($cacheData) || $refresh) {
            $cacheData['data']   = $getDataCallback(...$args) ?: [];
            $cacheData['expire'] = time() + (empty($cacheData['data']) ? $emptyDataExpire : $expire);
            try {
                $this->getRedisHandler()->hSet($cacheKey, $cacheField, json_encode($cacheData, JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $ex) {
                $errors[] = "hSet exception:{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})";
            }
        }

        return [$cacheData['data'] ?? [], $errors];
    }

    /**
     * 获取缓存数据
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/3/15 10:37
     * @param string        $cacheKey        缓存 KEY
     * @param string        $cacheValue      缓存 VALUE
     * @param callable|null $getDataCallback 获取数据的回调函数
     * @param int           $expire          过期时间
     * @param bool          $refresh         强制刷新数据
     * @param mixed         ...$args         回调函数的参数
     * @return array
     */
    public function getCacheData(string $cacheKey, string $cacheValue = '', callable $getDataCallback = null, int $expire = 86400, bool $refresh = false, ...$args): array
    {
        $cacheData = [];
        $errors    = [];
        $cacheKey  = $this->getRedisHandler()->generateKey($cacheKey);
        try {
            if ($data = RedisClient::instance()->get($cacheKey)) {
                $cacheData = json_decode($data, true) ?: [];
            }
        } catch (\Throwable $ex) {
            $errors[] = "get exception:{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})";
        }
        if ($refresh || empty($cacheData)) {
            if (is_callable($getDataCallback)) {
                $cacheData = call_user_func_array($getDataCallback, $args) ?: [];
            } else {
                $cacheData = $cacheValue;
            }
            try {
                if (!empty($cacheData)) {
                    $this->getRedisHandler()->set($cacheKey, json_encode($cacheData, JSON_UNESCAPED_UNICODE), 'EX', $expire);
                }
            } catch (\Throwable $ex) {
                $errors[] = "set exception:{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})";
            }
        }

        return [$cacheData, $errors];
    }

}