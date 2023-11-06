<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/2 17:12
 */

namespace Sweeper\HelperPhp\MultiProcess;

use Sweeper\HelperPhp\Traits\LogTrait;
use Symfony\Component\Process\Process;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/2 17:18
 * @Package \app\common\logic\MultiProcessAbstract
 */
abstract class SymfonyMultiProcessAbstract
{

    use LogTrait;

    /** @var int 进程最大个数 */
    private $maxProcessCount = 1;

    /** @var int 检查进程间隔时间 */
    private $microSeconds = 1000;

    /** @var Process[] $processList 正在执行线程列表 */
    private $processList = [];

    /**
     * ProcessMaster constructor.
     * @param int $maxProcessCount
     */
    public function __construct($maxProcessCount = 1)
    {
        $this->setMaxProcessCount($maxProcessCount);
    }

    public function getMaxProcessCount(): int
    {
        return $this->maxProcessCount;
    }

    public function setMaxProcessCount(int $maxProcessCount): self
    {
        $this->maxProcessCount = $maxProcessCount;

        return $this;
    }

    public function getMicroSeconds(): int
    {
        return $this->microSeconds;
    }

    public function setMicroSeconds(int $microSeconds): self
    {
        $this->microSeconds = $microSeconds;

        return $this;
    }

    /**
     * @return \Symfony\Component\Process\Process[]
     */
    public function getProcessList(): array
    {
        return $this->processList;
    }

    /**
     * @param \Symfony\Component\Process\Process[] $processList
     */
    public function setProcessList(array $processList): self
    {
        $this->processList = $processList;

        return $this;
    }

    public function getProcessCount(): int
    {
        return count($this->getProcessList());
    }

    /**
     * 添加进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:43
     * @param \Symfony\Component\Process\Process $process
     * @return $this
     */
    public function addProcess(Process $process): self
    {
        $index                                            = $this->getProcessCount() + 1;
        $this->processList["$index#{$process->getPid()}"] = $process;

        return $this;
    }

    /**
     * 移除进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:54
     * @param $idx
     * @return $this
     */
    public function removeProcess($idx): self
    {
        unset($this->processList[$idx]);

        return $this;
    }

    /**
     * 添加/连接进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:43
     * @param \Symfony\Component\Process\Process $process
     * @return $this
     */
    public function attachProcess(Process $process): self
    {
        return $this->addProcess($process);
    }

    /**
     * 移除/分离进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:54
     * @param $idx
     * @return $this
     */
    public function detachProcess($idx): self
    {
        return $this->removeProcess($idx);
    }

    /**
     * 移除(分离)所有进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:59
     * @return $this
     */
    public function detachAllProcess(): self
    {
        $this->processList = [];

        return $this;
    }

    /**
     * 开启进程执行命令
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:02
     * @param string        $command
     * @param string|null   $cwd
     * @param array|null    $env
     * @param null          $input
     * @param float|null    $timeout
     * @param callable|null $callback
     * @return \Symfony\Component\Process\Process
     */
    public function process(string $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60, callable $callback = null): Process
    {
        $process = Process::fromShellCommandline($command, $cwd, $env, $input, $timeout);
        $process->start($callback ?? function($type, $buffer) {
            if (Process::ERR === $type) {
                $this->debug('ERR >>> ' . $buffer);
            } else {
                $this->debug('OUT >>> ' . $buffer);
            }
        });

        return $process;
    }

    /**
     * 创建子进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:45
     * @param string        $command
     * @param string|null   $cwd
     * @param array|null    $env
     * @param               $input
     * @param float|null    $timeout
     * @param callable|null $callback
     * @return $this
     */
    public function createSlaveProcess(string $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60, callable $callback = null): self
    {
        return $this->attachProcess($this->process($command, $cwd, $env, $input, $timeout, $callback));
    }

    /**
     * 程序执行入口
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:27
     * @doc https://symfony.com/doc/current/components/process.html
     * @return void
     */
    public function run(): void
    {
        for ($i = 0; $i < $this->getMaxProcessCount(); $i++) {
            $this->executeTask();
        }
        $this->loop();
    }

    /**
     * 循环检查进程(等待所有进程执行完毕)
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:28
     * @return void
     */
    public function loop(): void
    {
        // 等待所有进程执行完毕
        while ($this->getProcessCount() > 0) {
            foreach ($this->getProcessList() as $idx => $process) {
                try {
                    $process->checkTimeout();
                } catch (\RuntimeException $e) {
                    // 进程超时异常处理
                    $this->error("进程超时异常：{$e->getMessage()}");
                    $process->stop();
                }

                if (!$process->isRunning()) {
                    // 进程已结束
                    $this->debug("进程ID: {$idx} 执行完毕，输出结果为: {$process->getOutput()}");
                    $this->detachProcess($idx)->executeTask();
                }
            }
            // 可以添加一些延时，避免CPU过度占用
            usleep($this->getMicroSeconds());
        }
        $this->debug('所有进程执行完毕');
    }

    /**
     * 执行任务
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:27
     * @return mixed
     */
    abstract public function executeTask();

}