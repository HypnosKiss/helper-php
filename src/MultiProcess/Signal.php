<?php

namespace Sweeper\HelperPhp\MultiProcess;

/**
 * 信号测试
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/14 17:42
 * @Package \Sweeper\HelperPhp\MultiProcess\Signal
 */
class Signal extends MultiProcessManagerAbstract
{

    /**
     * 常驻后台
     * @param bool $throwException
     * @return bool
     */
    protected function assertWithInTheTimeRange(bool $throwException = true): bool
    {
        return true;
    }

    /**
     * 主进程处理逻辑
     * @param array $params
     * @throws \Exception
     */
    public function master(array $params = []): void
    {
        $i = 10;
        while (--$i) {
            $this->signalDispatch();
            $this->createSlaveProcessTask();
            $this->sleepMasterProcessMonitorSlaveProcess(5);//等待监听子进程
        }
    }

    /**
     * 子进程处理逻辑
     * @param array $params
     */
    public function slave(array $params = []): void
    {
        $this->debug($this->outputLogContentAboutProcessId() . 'Program slave start running.');

        sleep(3);

        $this->shutdownSlave();
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/16 10:25
     * @return void
     * @throws \Exception
     */
    protected function executeTask(): void
    {
        $command = 'php -r "echo 123;"';
        $process = $this->createSlaveProcessTaskViaCmd($command);

        dump($process->getProcessPid());

        $this->debug('createSlaveProcessTaskViaCmd', compact('command'));
    }

}