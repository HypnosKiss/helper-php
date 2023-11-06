<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/5 1:20
 */

namespace Sweeper\HelperPhp\Test\Cache;

use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\Cache\CacheRedis;

class CacheRedisTest extends TestCase
{

    public function testCacheRedis(): void
    {
        $data = CacheRedis::instance()->cache(__METHOD__, function() {
            return random_int(1000, 1000000000);
        }, 60, false);
        $this->assertNotNull($data);
        $this->assertIsInt($data, '结果不是INT类型');
    }

}
