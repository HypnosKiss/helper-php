<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/15 19:05
 */

namespace Sweeper\HelperPhp\Test\MultiProcess;

use Sweeper\HelperPhp\MultiProcess\MultiProcess;
use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\MultiProcess\MultiProcessManagerAbstract;
use Sweeper\HelperPhp\MultiProcess\Signal;

class MultiProcessTest extends TestCase
{

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/17 19:41
     * @return void
     * @throws \Exception
     */
    public function testMultiProcessRun(): void
    {
        $opts = getopt('hds:n:');
        if (isset($opts['h'])) {
            echo 'php ' . basename(__FILE__), PHP_EOL, ' -h show this help', PHP_EOL, ' -d debug mode', PHP_EOL, ' -s wait Seconds', PHP_EOL;
            die;
        }
        $worker_num = (int)($opts['n'] ?? null);
        $app        = new MultiProcess($worker_num ?: 2, isset($opts['d']));
        $app->run($_SERVER['argv']);
        $this->assertEquals(0, $app->getProcessCount());
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/17 19:41
     * @return void
     * @throws \Exception
     */
    public function testMultiProcessStart(): void
    {
        $opts = getopt('hds:n:');
        if (isset($opts['h'])) {
            echo 'php ' . basename(__FILE__), PHP_EOL, ' -h show this help', PHP_EOL, ' -d debug mode', PHP_EOL, ' -s wait Seconds', PHP_EOL;
            die;
        }
        $worker_num = (int)($opts['n'] ?? null);
        $app        = new MultiProcess($worker_num ?: 2, isset($opts['d']));
        $app->start(10, 10);
        $this->assertEquals(0, $app->getProcessCount());
    }

}
