<?php

namespace Sweeper\HelperPhp\Cache;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/3 9:50
 * @Package \Sweeper\HelperPhp\Cache\CacheInterface
 */
interface CacheInterface
{

    /**
     * 设置缓存接口
     * @param string $cacheKey
     * @param        $data
     * @param int    $expired
     * @return mixed
     */
    public function set(string $cacheKey, $data, int $expired = 60);

    /**
     * 获取数据接口
     * @param string $cacheKey
     * @return mixed
     */
    public function get(string $cacheKey);

    /**
     * 删除缓存接口
     * @param string $cacheKey
     * @return mixed
     */
    public function delete(string $cacheKey);

    /**
     * 清空整个缓存区域接口
     * @return mixed
     */
    public function flush();

}