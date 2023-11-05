<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/5 1:01
 */

namespace Sweeper\Test\Cache;

use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\Cache\CacheMemcache;

class CacheMemcacheTest extends TestCase
{

    public function testCacheMemcache(): void
    {
        $data = CacheMemcache::instance()->cache(__METHOD__, function() {
            return random_int(1000, 1000000000);
        }, 60, true);
        $this->assertNotNull($data);
        $this->assertIsInt($data, '结果不是INT类型');
    }

}
