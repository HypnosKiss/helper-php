<?php

namespace Sweeper\HelperPhp\CoroutineScheduler;

use Generator;

/**
 * 协程间通信器
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2024/1/10 14:27
 * @Package \Sweeper\HelperPhp\CoroutineScheduler\YieldCall
 */
class YieldCall
{

    /** @var callable 回调 */
    protected $callback;

    /**
     * YieldCall constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * 调用时将返回结果
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:28
     * @param Coroutine $task
     * @param Scheduler $scheduler
     * @return mixed
     */
    public function __invoke(Coroutine $task, Scheduler $scheduler)
    {
        $callback = $this->callback;

        return $callback($task, $scheduler);
    }

    /**
     * 传入一个生成器函数用于新增任务给调度器调用
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:28
     * @param Generator $coroutine
     * @return YieldCall
     */
    public function newTask(Generator $coroutine): YieldCall
    {
        return new YieldCall(
        //该匿名函数,会在调度器中新增一个任务
            static function(Coroutine $task, Scheduler $scheduler) use ($coroutine) {
                $task->setSendValue($scheduler->newTask($coroutine));
                $scheduler->schedule($task);
            }
        );
    }

    /**
     * 杀死一个任务
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:28
     * @param $taskId
     * @return YieldCall
     */
    public function killTask($taskId): YieldCall
    {
        return new YieldCall(
        //该匿名函数,传入一个任务id,然后让调度器去杀死该任务
            static function(Coroutine $task, Scheduler $scheduler) use ($taskId) {
                $task->setSendValue($scheduler->killTask($taskId));
                $scheduler->schedule($task);
            }
        );
    }

}
