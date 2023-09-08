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
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/11 11:16
 * @Path \App\Traits\Cache
 */
trait Cache
{

    /**
     * 通过 hGet 获取数据
     * User: Sweeper
     * Time: 2023/8/11 11:03
     * @param string   $cacheKey        缓存 KEY
     * @param string   $cacheField      缓存 FIELD
     * @param callable $getDataCallback 获取数据的回调
     * @param int      $expire          缓存过期时间
     * @param int      $emptyDataExpire 空数据过期时间
     * @param bool     $refresh         强制刷新
     * @param mixed    ...$args         回调的参数
     * @return array [$data, $errors]
     */
    public function getDataByHGet(string $cacheKey, string $cacheField, callable $getDataCallback, int $expire = 86400, int $emptyDataExpire = 300, bool $refresh = false, ...$args): array
    {
        $errors   = [];
        $cacheKey = RedisClient::instance()->generateKey($cacheKey);
        try {
            $cache     = RedisClient::instance()->hGet($cacheKey, $cacheField) ?: '';
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
                RedisClient::instance()->hSet($cacheKey, $cacheField, json_encode($cacheData, JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $ex) {
                $errors[] = "hSet exception:{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})";
            }
        }

        return [$cacheData['data'] ?? [], $errors];
    }

}