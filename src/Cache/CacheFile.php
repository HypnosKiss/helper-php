<?php

namespace Sweeper\HelperPhp\Cache;


/**
 * 文件缓存
 * 默认缓存在system temporary临时目录中
 * 默认开启进程内变量缓存，避免多次获取变量读取文件 config:cache_in_process
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/3 10:41
 * @Package \Sweeper\HelperPhp\Cache\CacheFile
 */
class CacheFile extends CacheAdapter
{

    private        $cacheInProcess = true;

    private static $processCache   = [];

    protected function __construct(array $config = [])
    {
        if (!isset($config['cache_in_process'])) {
            $this->cacheInProcess = true;
        }
        if (!isset($config['dir']) || !$config['dir']) {
            $dir           = sys_get_temp_dir();
            $config['dir'] = $dir . '/cache/';
        }
        $dir = trim($config['dir'], '/');
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        parent::__construct($config);
    }

    /**
     * 设置缓存
     * @param string $cacheKey
     * @param        $data
     * @param int    $expired
     * @return bool|int
     */
    public function set(string $cacheKey, $data, int $expired = 60)
    {
        $file   = $this->getFileName($cacheKey);
        $string = serialize([
            'cache_key' => $cacheKey,
            'expired'   => date('Y-m-d H:i:s', time() + $expired),
            'data'      => $data,
        ]);
        if ($handle = @fopen($file, 'w')) {
            $result = fwrite($handle, $string);
            fclose($handle);
            @chmod($file, 0777);
            if ($result && $this->cacheInProcess) {
                self::$processCache[$cacheKey] = $data;
            }

            return $result;
        }

        return false;
    }

    /**
     * 获取缓存文件名
     * @param string $cacheKey
     * @return string
     */
    public function getFileName(string $cacheKey): string
    {
        return $this->getConfig('dir') . md5($cacheKey);
    }

    /**
     * 获取缓存
     * @param string $cacheKey
     * @return null
     */
    public function get(string $cacheKey)
    {
        if ($this->cacheInProcess && isset(self::$processCache[$cacheKey])) {
            return self::$processCache[$cacheKey];
        }
        $file = $this->getFileName($cacheKey);
        if (file_exists($file)) {
            $string = is_file($file) ? @file_get_contents($file) : '';
            if ($string) {
                $data = unserialize($string);
                if ($data && strtotime($data['expired']) > time()) {
                    if ($this->cacheInProcess) {
                        self::$processCache[$cacheKey] = $data['data'];
                    }

                    return $data['data'];
                }
            }
            //清空无效缓存，防止缓存文件膨胀
            $this->delete($cacheKey);
        }

        return null;
    }

    /**
     * 删除缓存
     * @param string $cacheKey
     * @return bool
     */
    public function delete(string $cacheKey): bool
    {
        if (isset(self::$processCache[$cacheKey])) {
            unset(self::$processCache[$cacheKey]);
        }
        $file = $this->getFileName($cacheKey);
        if (file_exists($file)) {
            return is_file($file) && @unlink($file);
        }

        return false;
    }

    /**
     * 清空缓存
     * flush cache dir
     */
    public function flush(): void
    {
        self::$processCache = [];
        $dir                = $this->getConfig('dir');
        if (is_dir($dir)) {
            array_map('unlink', glob($dir . '/*'));
        }
    }

}
