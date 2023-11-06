<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/6 19:36
 */

namespace Sweeper\HelperPhp\Test\MultiProcess;

use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\MultiProcess\SymfonyMultiProcessAbstract;

class SymfonyMultiProcess extends SymfonyMultiProcessAbstract
{

    public function executeTask()
    {
        $command = 'php -r "echo 123;"';
        $process = $this->createSlaveProcess($command);
        $this->info('createSlaveProcess', [$process->getProcessCount(), $process->getMaxProcessCount()]);
    }

}

class SymfonyMultiProcessAbstractTest extends TestCase
{

    public function testSymfonyMultiProcessAbstract()
    {
        $process = new SymfonyMultiProcess(3);
        $process->run();
    }

}
