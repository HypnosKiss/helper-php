<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/2 23:21
 */

namespace Sweeper\HelperPhp\Test\Process;

use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\MultiProcess\MultiProcessAbstract;
use Sweeper\HelperPhp\Process\Process;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

class MultiProcess extends MultiProcessAbstract
{

    public function executeTask()
    {
        $process                                       = new Process($this->buildCommand('php -r "echo 123;"'));
        $this->process_list[$process->getProcessPid()] = $process;
        $this->info('test MultiProcessTest', [$process->getProcessPid()]);
    }

}

class MultiProcessAbstractTest extends TestCase
{

    public function testMultiProcessAbstract()
    {
        $process = new MultiProcess(3);
        $process->run();
    }

}

