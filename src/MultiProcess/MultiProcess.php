<?php

namespace Sweeper\HelperPhp\MultiProcess;

use Generator;
use Sweeper\HelperPhp\Tool\Hooker;

/**
 * 多进程消费者
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/15 19:00
 * @Package \Sweeper\HelperPhp\MultiProcess\MultiProcess
 */
class MultiProcess extends MultiProcessManagerAbstract
{

    /**
     * 断言允许执行(声明当前时间允许执行) 常驻后台
     * @param bool $throwException
     * @return bool
     */
    protected function assertWithInTheTimeRange(bool $throwException = true): bool
    {
        return true;
    }

    /**
     * 主进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/13 9:19
     * @param array $params
     * @return void
     * @throws \Exception
     */
    public function master(array $params = []): void
    {
        $array = range(1, 10000);
        $i     = 10;
        while (--$i) {
            $this->signalDispatch();
            // 长轮询表示如果topic没有消息则请求会在服务端挂住 {$this->waitSeconds}s，{$this->waitSeconds}s内如果有消息可以消费则立即返回
            Hooker::fire(static::$EVENT_CHECK_SLAVE_PROCESS);
            try {
                $this->debug(__METHOD__);
                if (!$array) {
                    break;
                }
                $this->createSlaveProcessTask(array_shift($array));
            } catch (\Throwable $ex) {
                $this->error("{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})");
                continue;
            } finally {
                Hooker::fire(static::$EVENT_SLEEP_MONITOR_SLAVE, 1);
            }
            Hooker::fire(self::$EVENT_CHECK_SLAVE_PROCESS);
            gc_collect_cycles();//强制收集所有现存的垃圾循环周期

        }
        // $this->shutdownMaster(10000);//关闭 master 进程
    }

    /**
     * 子进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/13 9:19
     * @param array $params
     * @return void
     */
    public function slave(array $params = []): void
    {
        $this->debug(__METHOD__ . " task end .", $params);
        $this->shutdownSlave();
    }

    /**
     * 执行任务
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/13 9:20
     * @return void
     * @throws \Exception
     */
    protected function executeTask(): void
    {
        $command = 'php -r "echo implode(PHP_EOL, $_SERVER[\'argv\']);"';
        $process = $this->createSlaveProcessTaskViaCmd($command);

        dump($process->getProcessPid());

        $this->debug('createSlaveProcessTaskViaCmd', compact('command'));
    }

}