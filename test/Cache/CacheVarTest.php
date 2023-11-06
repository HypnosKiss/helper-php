<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/5 1:26
 */

namespace Sweeper\HelperPhp\Test\Cache;

use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\Cache\CacheVar;

class CacheVarTest extends TestCase
{

    public function testCacheVar(): void
    {
        $data = CacheVar::instance()->cache(__METHOD__, function() {
            return random_int(1000, 1000000000);
        }, 60, false);
        $this->assertNotNull($data);
        $this->assertIsInt($data, '结果不是INT类型');
    }

}
