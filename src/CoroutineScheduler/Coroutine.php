<?php

namespace Sweeper\HelperPhp\CoroutineScheduler;

use Generator;

/**
 * 协程
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2024/1/10 14:30
 * @Package \Sweeper\HelperPhp\CoroutineScheduler\Coroutine
 */
class Coroutine
{
    /** @var int 任务ID */
    protected $taskId;

    /** @var Generator 生成器 */
    protected $coroutine;

    /** @var mixed 生成器send值 */
    protected $sendValue;

    /** @var bool 迭代指针是否是第一个 */
    protected $beforeFirstYield = true;

    /**
     * Coroutine constructor.
     * @param int $taskId
     * @param Generator $coroutine
     */
    public function __construct(int $taskId, Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
    }

    /**
     * 获取任务ID
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:30
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->taskId;
    }

    /**
     * 设置插入数据
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:30
     * @param $sendValue
     * @return void
     */
    public function setSendValue($sendValue): void
    {
        $this->sendValue = $sendValue;
    }

    /**
     * 执行
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:31
     * @return mixed
     */
    public function run()
    {
        //如果是第一个
        if ($this->beforeFirstYield === true) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        }
        //send数据进行迭代
        $retval = $this->coroutine->send($this->sendValue);
        $this->sendValue = null;
        return $retval;
    }

    /**
     * 是否执行完成
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2024/1/10 14:31
     * @return bool
     */
    public function isFinished(): bool
    {
        return !$this->coroutine->valid();
    }


}


