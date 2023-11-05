<?php

namespace Sweeper\HelperPhp\Cache;

/**
 * 运行时内存变量缓存（进程内共享）
 * Class CacheVar
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/3 10:44
 * @Package \Sweeper\HelperPhp\Cache\CacheVar
 */
class CacheVar extends CacheAdapter
{

    private static $DATA_STORE     = [];

    private        $cacheKeyPrefix = '_var_cache_';

    private function getCacheKey(string $cacheKey): string
    {
        return $this->cacheKeyPrefix . $cacheKey;
    }

    public function set(string $cacheKey, $data, int $expired = 0): void
    {
        $cacheKey                    = $this->getCacheKey($cacheKey);
        self::$DATA_STORE[$cacheKey] = $data;
    }

    public function get(string $cacheKey)
    {
        $cacheKey = $this->getCacheKey($cacheKey);

        return self::$DATA_STORE[$cacheKey] ?? null;
    }

    public function delete(string $cacheKey): bool
    {
        $cacheKey                    = $this->getCacheKey($cacheKey);
        self::$DATA_STORE[$cacheKey] = null;

        return true;
    }

    public function flush(): void
    {
        self::$DATA_STORE = [];
    }

    public function getAll(): array
    {
        return self::$DATA_STORE;
    }

}
