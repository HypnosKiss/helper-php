<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/5 0:52
 */

namespace Sweeper\HelperPhp\Test\Cache;

use Sweeper\HelperPhp\Cache\CacheFile;
use PHPUnit\Framework\TestCase;

class CacheFileTest extends TestCase
{

    public function testCacheFile(): void
    {
        $data = CacheFile::instance()->cache(__METHOD__, function() {
            return random_int(1000, 1000000000);
        });
        $this->assertNotNull($data);
        $this->assertIsInt($data, '结果不是INT类型');
    }

}
