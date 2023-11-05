<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/5 1:35
 */

namespace Sweeper\Test\Tool;

use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\Tool\Console;

class ConsoleTest extends TestCase
{

    public function testConsole(): void
    {
        $result = Console::runCommand(Console::buildCommand('D:\software\BtSoft\php\72\php.exe D:/wwwroot/php/sweeper/helper-php/vendor/phpunit/phpunit/phpunit --no-configuration --filter "/(Sweeper\\Test\\Cache\\CacheVarTest::testCacheVar)( .*)?$/" --test-suffix CacheVarTest.php D:\wwwroot\php\sweeper\helper-php\test\Cache --teamcity --cache-result-file=D:\wwwroot\php\sweeper\.phpunit.result.cache'));
        $this->assertNotNull($result);
        $this->assertIsString($result, '结果不是String类型');
    }

}
