<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/5 1:35
 */

namespace Sweeper\HelperPhp\Test;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Sweeper\HelperPhp\Logger\LoggerLevel;
use Sweeper\HelperPhp\Logger\Logic\Log;
use Sweeper\HelperPhp\Tool\Logger;
use Sweeper\HelperPhp\Traits\LogTrait;

class LoggerTest extends TestCase
{

    use LogTrait;

    /**
     * 测试日志特征
     * Author: Sweeper <wili.lixiang@gmail.com>
     * Time: 2024/5/29 11:15:49
     * @return void
     */
    public function testLogTrait()
    {
        $this->setLogPath(Logger::getLogDirPath())->setFilename('LoggerTraitTest');
        $this->info('test log trait', array_replace(['todo' => 'some message'], func_get_args()));

        $this->assertInstanceOf(\Monolog\Logger::class, $this->getLogger());
    }

    /**
     * 测试记录仪
     * Author: Sweeper <wili.lixiang@gmail.com>
     * Time: 2024/5/29 11:16:06
     * @return void
     */
    public function testLogger()
    {
        Logger::instance([], static::class)->setLoggerName(static::class)->setFilename('Logger')->info('test logger');
        Logger::initializeLogger()->info('test initializeLogger');

        $this->assertInstanceOf(\Monolog\Logger::class, $this->getLogger());
    }

    public function testLog()
    {
        $logger = Log::instance()->setLogPath(Logger::getLogDirPath())->logger('test', LogLevel::DEBUG, LogLevel::INFO);
        $logger->debug('123');
        $logger->debug('456');
        $logger->info('789');

        $this->assertInstanceOf(\Monolog\Logger::class, $this->getLogger());
    }

}
