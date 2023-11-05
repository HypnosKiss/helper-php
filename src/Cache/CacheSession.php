<?php

namespace Sweeper\HelperPhp\Cache;

use function Sweeper\HelperPhp\Func\session_start_once;
use function Sweeper\HelperPhp\Func\session_write_once;

/**
 * Session缓存
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/3 10:41
 * @Package \Sweeper\HelperPhp\Cache\CacheSession
 */
class CacheSession extends CacheAdapter
{

    private $cachePrefix = __CLASS__;

    protected function __construct(array $config = [])
    {
        session_start_once();
        parent::__construct($config);
    }

    public function set(string $cacheKey, $data, int $expired = 60): void
    {
        $name            = $this->getName($cacheKey);
        $_SESSION[$name] = [
            'data'    => $data,
            'expired' => time() + $expired,
        ];
        session_write_once();
    }

    private function getName(string $cacheKey): string
    {
        return $this->cachePrefix . urlencode($cacheKey);
    }

    public function get(string $cacheKey)
    {
        $name = $this->getName($cacheKey);
        $data = $_SESSION[$name];
        if ($data && $data['expired'] > time()) {
            return $data['data'];
        }

        return null;
    }

    public function delete(string $cacheKey): void
    {
        $name = $this->getName($cacheKey);
        if ($_SESSION[$name]) {
            unset($_SESSION[$name]);
            session_write_once();
        }
    }

    public function flush(): void
    {
        foreach ($_SESSION as $key => $val) {
            if (strpos($key, $this->cachePrefix) !== false) {
                unset($_SESSION[$key]);
                session_write_once();
            }
        }
    }

}