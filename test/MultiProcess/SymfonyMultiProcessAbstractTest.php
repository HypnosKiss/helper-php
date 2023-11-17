<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/6 19:36
 */

namespace Sweeper\HelperPhp\Test\MultiProcess;

use PHPUnit\Framework\TestCase;
use Sweeper\HelperPhp\MultiProcess\SymfonyMultiProcessAbstract;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class SymfonyMultiProcess extends SymfonyMultiProcessAbstract
{

    public function executeTask(): void
    {
        $command = 'php -r "echo 123;"';
        $process = $this->createSlaveProcess($command);
        $this->info('createSlaveProcess', [$process->getProcessCount(), $process->getMaxProcessCount()]);
    }

}

class SymfonyMultiProcessAbstractTest extends TestCase
{

    public function testSymfonyMultiProcessAbstract(): void
    {
        $process = new SymfonyMultiProcess(3);
        $process->run();
    }

    protected function loop($processes): void
    {
        // 等待所有进程执行完毕
        while (count($processes) > 0) {
            foreach ($processes as $index => $process) {
                try {
                    $process->checkTimeout();
                } catch (RuntimeException $e) {
                    // 进程超时异常处理
                    echo '进程超时异常：' . $e->getMessage() . PHP_EOL;
                    $process->stop();
                }

                if (!$process->isRunning()) {
                    // 进程已结束
                    echo '进程ID: ' . $index . ' 执行完毕，输出结果为: ' . $process->getOutput() . PHP_EOL;
                    unset($processes[$index]);
                }
            }

            // 可以添加一些延时，避免CPU过度占用
            usleep(1000);
        }
        echo '所有进程执行完毕' . PHP_EOL;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/2 17:01
     * @doc https://symfony.com/doc/current/components/process.html
     * @return void
     */
    public function testProcess(): void
    {
        $data      = [10, 20, 30]; // 要处理的数据
        $processes = [];
        $cmd       = 'php -r "echo implode(PHP_EOL, $_SERVER[\'argv\']);"';
        foreach ($data as $index => $value) {
            $process = Process::fromShellCommandline($cmd . ' ' . $value);
            $process->setTimeout(86400)->start(function($type, $buffer) {
                if (Process::ERR === $type) {
                    echo 'ERR > ' . $buffer, PHP_EOL;
                } else {
                    echo 'OUT > ' . $buffer, PHP_EOL;
                }
            });
            $processes["$index-{$process->getPid()}"] = $process;
        }

        $this->loop($processes);

        $this->assertIsBool(count($processes) === 0);
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/6 19:49
     * @doc https://symfony.com/doc/current/components/process.html
     * @return void
     */
    public function testSymfonyProcess(): void
    {
        $data      = [10, 20, 30]; // 要处理的数据
        $processes = [];
        $cmd       = 'php -r "echo 123;"';
        foreach ($data as $index => $value) {
            $process = Process::fromShellCommandline($cmd);
            // $process = new Process([$cmd]);
            // $process->setOptions(['create_new_console' => true]);
            $process->setTimeout(86400)->start(function($type, $buffer) {
                if (Process::ERR === $type) {
                    echo 'ERR > ' . $buffer, PHP_EOL;
                } else {
                    echo 'OUT > ' . $buffer, PHP_EOL;
                }
            });
            $processes["$index-{$process->getPid()}"] = $process;
            // ... do other things

            // waits until the given anonymous function returns true
            $process->waitUntil(function($type, $output): bool {
                return $output === 'Ready. Waiting for commands...';
            });
        }

        $this->loop($processes);

        $this->assertIsBool(count($processes) === 0);
    }

}
