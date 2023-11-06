<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/5 11:32
 */

namespace Sweeper\HelperPhp\Test\Process;

use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\Process\Parallel;
use Sweeper\HelperPhp\Tool\Console;

class ParallelTest extends TestCase
{

    public function testParallelProcess(): void
    {
        $cmd      = Console::buildCommand('D:\software\BtSoft\php\72\php.exe D:/wwwroot/php/sweeper/helper-php/vendor/phpunit/phpunit/phpunit --no-configuration --filter "/(Sweeper\\Test\\Cache\\CacheVarTest::testCacheVar)( .*)?$/" --test-suffix CacheVarTest.php D:\wwwroot\php\sweeper\helper-php\test\Cache --teamcity --cache-result-file=D:\wwwroot\php\sweeper\.phpunit.result.cache');
        $parallel = new Parallel($cmd, [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
            ['id' => 5],
            ['id' => 6],
            ['id' => 7],
            ['id' => 8],
            ['id' => 9],
            ['id' => 10],
        ]);
        $parallel->useConsoleDebugger()->start()->waitForFinish();
        $this->assertEquals(Parallel::STATE_ALL_DONE, $parallel->getState(), '进程没有全部完成');
    }

}
