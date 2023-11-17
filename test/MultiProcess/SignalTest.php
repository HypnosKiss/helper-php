<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/14 17:45
 */

namespace Sweeper\HelperPhp\Test\MultiProcess;

use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\MultiProcess\Signal;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

class SignalTest extends TestCase
{

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/16 10:25
     * @return void
     * @throws \Exception
     */
    public function testSignalRun(): void
    {
        $opts = getopt('hds:n:');
        if (isset($opts['h'])) {
            echo 'php ' . basename(__FILE__), PHP_EOL, ' -h show this help', PHP_EOL, ' -d debug mode', PHP_EOL, ' -s wait Seconds', PHP_EOL;
            die;
        }
        $worker_num = (int)($opts['n'] ?? null);
        $app        = new Signal($worker_num ?: 2, isset($opts['d']));
        $app->run($_SERVER['argv']);
        $this->assertEquals(0, $app->getProcessCount());
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/16 10:25
     * @return void
     * @throws \Exception
     */
    public function testSignalStart(): void
    {
        // $filename = 'D:\wwwroot\php\sweeper\helper-php\test\MultiProcess\SignalTest.php/namedpipe';
        // posix_mknod($filename, $mode);
        // if (posix_mkfifo($filename, 0666)) {
        //     echo 'Named pipe created successfully.';
        // } else {
        //     echo 'Failed to create named pipe.';
        // }
        // $pipe = posix_mkfifo('my_pipe', 0666);
        // fwrite($pipe, 'hello world');
        // dd($pipe);
        $opts = getopt('hds:n:');
        if (isset($opts['h'])) {
            echo 'php ' . basename(__FILE__), PHP_EOL, ' -h show this help', PHP_EOL, ' -d debug mode', PHP_EOL, ' -s wait Seconds', PHP_EOL;
            die;
        }
        $worker_num = (int)($opts['n'] ?? null);
        $app        = new Signal($worker_num ?: 2, isset($opts['d']));
        $app->start(10, 10);
        $this->assertEquals(0, $app->getProcessCount());
    }

}
