<?php

namespace Sweeper\HelperPhp\CoroutineScheduler;

use Generator;
use SplQueue;

/**
 * 任务调度器
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2024/1/10 14:28
 * @Package \Sweeper\HelperPhp\CoroutineScheduler\Scheduler
 */
class Scheduler
{

    /** @var int 任务ID */
    protected $maxTaskId = 0;

    /** @var array taskId => task */
    protected $taskMap = [];

    /** @var SplQueue 任务队列 */
    protected $taskQueue;

    /**
     * Scheduler constructor.
     */
    public function __construct()
    {
        $this->taskQueue = new SplQueue();
    }

    /**
     * 添加任务
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:29
     * @param Generator $coroutine
     * @return int
     */
    public function newTask(Generator $coroutine): int
    {
        $tid = ++$this->maxTaskId;
        //新增任务
        $task                = new Coroutine($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);

        return $tid;
    }

    /**
     * 任务入列
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:29
     * @param Coroutine $task
     * @return void
     */
    public function schedule(Coroutine $task): void
    {
        $this->taskQueue->enqueue($task);
    }

    /**
     * 执行
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:29
     * @return void
     */
    public function run(): void
    {
        while (!$this->taskQueue->isEmpty()) {
            $task   = $this->taskQueue->dequeue();
            $retval = $task->run();

            //如果返回的是YieldCall实例,则先执行
            if ($retval instanceof YieldCall) {
                $retval($task, $this);
                continue;
            }
            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }

    /**
     * 获取任务
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:29
     * @return YieldCall
     */
    public function getTask(): YieldCall
    {
        //返回一个YieldCall的实例
        return new YieldCall(
        //该匿名函数会先获取任务id,然后send给生成器,并且由YieldCall将task_id返回给生成器函数
            static function(Coroutine $task, Scheduler $scheduler) {
                $task->setSendValue($task->getTaskId());
                $scheduler->schedule($task);
            }
        );
    }

    /**
     * 杀死一个任务
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:29
     * @param $taskId
     * @return bool
     */
    public function killTask($taskId): bool
    {
        if (!isset($this->taskMap[$taskId])) {
            return false;
        }

        unset($this->taskMap[$taskId]);

        //遍历队列,找出id相同的则删除
        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $taskId) {
                unset($this->taskQueue[$i]);
                break;
            }
        }

        return true;
    }

}
