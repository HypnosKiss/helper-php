<?php

namespace Sweeper\HelperPhp\Test;

use Sweeper\HelperPhp\Logger\LoggerLevel;
use Sweeper\HelperPhp\Logger\Logic\Log;
use Sweeper\HelperPhp\Logger\Traits\LoggerTrait;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/7/24 11:53
 */
class testLogger
{

    use LoggerTrait;

    public function __construct()
    {
        $this->setLogPath(__DIR__ . '/log')->setFilename('test');
        // $this->setLogFile(__DIR__ . '/logs/test.info.log');
        $this->debug('__construct');
    }

    public function __destruct()
    {
        $this->debug('__destruct');
    }

    public function run()
    {
        $this->info('run', 123, 456);
        $this->info('test log message', ['todo' => 'some message']);
    }

    protected function log()
    {

    }

}

$logger = Log::instance()->setLogPath(__DIR__ . '/log')->logger($logId ?? 'test', LoggerLevel::DEBUG, LoggerLevel::INFO);
$logger->debug('123');
$logger->debug('456');
$logger->info('789');

$obj = new testLogger();
$obj->run();