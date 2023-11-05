<?php

namespace Sweeper\HelperPhp\Process;

use Sweeper\HelperPhp\Tool\Console;

/**
 * 并发进程管理
 * 通过按照最大并发数量，拆分输入的参数集合，实现并发任务动态调度。
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/25 22:49
 * @Package \Sweeper\HelperPhp\Process\Parallel
 */
class Parallel
{

    /** @var int 中断标记 - 中断剩余任务执行 */
    public const PS_INT_CLEAN_LEFT_FLAG = 0x20160928123001;

    /** @var int 中断标记 - 强制清理当前运行进程，并中断剩余任务执行 */
    public const PS_INT_TERMINAL_FLAG = 0x20190509001;

    //状态

    /** @var string 初始化 */
    public const STATE_INIT = 'INIT';

    /** @var string 运行中 */
    public const STATE_RUNNING = 'RUNNING';

    /** @var string 所有已完成 */
    public const STATE_ALL_DONE = 'ALL_DONE';

    //事件
    public const EVENT_ON_START             = __CLASS__ . 'ON_START';

    public const EVENT_ON_ALL_DONE          = __CLASS__ . 'ON_ALL_DONE';

    public const EVENT_ON_PROCESS_ERROR     = __CLASS__ . 'ON_PROCESS_ERROR';

    public const EVENT_ON_PROCESS_ADD       = __CLASS__ . 'ON_PROCESS_ADD';

    public const EVENT_ON_PROCESS_FINISH    = __CLASS__ . 'ON_PROCESS_FINISH';

    public const EVENT_ON_PROCESS_RUNNING   = __CLASS__ . 'ON_PROCESS_RUNNING';

    public const EVENT_ON_PROCESS_INTERRUPT = __CLASS__ . 'ON_PROCESS_INTERRUPT';

    /** @var string 执行命令 */
    private $cmd;

    /** @var array 剩余命令参数集合 */
    private $params = [];

    /** @var string 当前状态 */
    private $state;

    /** @var int 总进程执行时间 */
    private $totalMaxExecutionTime = 0;

    /** @var int 进程最大执行时间（超过时间主动杀死进程） */
    private $processMaxExecutionTime = 0;

    /** @var int 并发数量 */
    private $parallelCount = 10;

    /** @var int 进程状态检测间隔时间(秒) */
    private $checkInterval = 1;

    /** @var int 开始执行时间 */
    private $startTimeFloat = 0;

    /** @var int 当前任务索引下标 */
    private $processIndex = 0;

    /** @var int 总任务数量 */
    private $totalTaskCount = 1;

    /** @var array 当前正在运行进程列表，格式：[索引，进程] */
    private $processList = [];

    /** @var array 运行结果，格式：[索引=>[开始时间，结束时间，结果内容],...] */
    private $resultList = [];

    /** @var array 回调列表 */
    private $callbackList = [];

    /** @var null debugger */
    private $debugger = null;

    /**
     * Parallel constructor.
     * @param string $cmd
     * @param array  $params 参数集合，进程任务分配通过参数个数进行拆分。
     *                       参数格式如:<pre>
     *                       $params = [['id'=>1], ['id'=>2]]，任务将被分配到两个进程中执行
     *                       </pre>
     */
    public function __construct(string $cmd, array $params)
    {
        if (count($params) < 1) {
            throw new \InvalidArgumentException('Parameters count must grater than 1');
        }
        $this->cmd            = $cmd;
        $this->params         = $params;
        $this->totalTaskCount = count($params);
        $this->state          = static::STATE_INIT;
        $this->bindDebug();
    }

    /**
     * 获取设置进程池最大执行时间
     * @return int
     */
    public function getTotalMaxExecutionTime(): int
    {
        return $this->totalMaxExecutionTime;
    }

    /**
     * 设置进程池最大执行时间
     * @param int $totalMaxExecutionTime
     * @return $this
     */
    public function setTotalMaxExecutionTime(int $totalMaxExecutionTime): self
    {
        $this->totalMaxExecutionTime = $totalMaxExecutionTime;
        set_time_limit($this->totalMaxExecutionTime);

        return $this;
    }

    /**
     * 获取设置单个进程最大执行时间
     * @return int
     */
    public function getProcessMaxExecutionTime(): int
    {
        return $this->processMaxExecutionTime;
    }

    /**
     * 设置单个进程最大执行时间
     * @param int $processMaxExecutionTime
     * @return $this
     */
    public function setProcessMaxExecutionTime(int $processMaxExecutionTime): self
    {
        $this->processMaxExecutionTime = $processMaxExecutionTime;

        return $this;
    }

    /**
     * 获取设置进程并发数量
     * @return int
     */
    public function getParallelCount(): int
    {
        return $this->parallelCount;
    }

    /**
     * 设置进程并发数量
     * @param int $parallelCount
     * @return $this
     */
    public function setParallelCount(int $parallelCount): self
    {
        $this->parallelCount = $parallelCount;

        return $this;
    }

    /**
     * 获取检测间隔时间
     * @return int
     */
    public function getCheckInterval(): int
    {
        return $this->checkInterval;
    }

    /**
     * 获取进程统一执行命令
     * @return string
     */
    public function getCmd(): string
    {
        return $this->cmd;
    }

    /**
     * 设置进程状态检测时间
     * @param int $checkInterval
     * @return $this
     */
    public function setCheckInterval(int $checkInterval): self
    {
        $this->checkInterval = $checkInterval;

        return $this;
    }

    /**
     * 获取进程池总状态
     * get master state
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * 开始
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/25 23:16
     * @return $this
     */
    public function start(): self
    {
        $this->state          = static::STATE_RUNNING;
        $this->startTimeFloat = microtime(true);

        return $this->triggerEvent(static::EVENT_ON_START)
                    ->dispatch()
                    ->loopCheck();
    }

    /**
     * 进程任务分发
     * @param bool $result 任务是否分派成功
     * @return $this 任务是否分派成功
     */
    private function dispatch(bool &$result = false): self
    {
        if ($this->params) {
            $runningCount = count($this->processList);
            $startCount   = min($this->parallelCount - $runningCount, count($this->params));
            $now          = microtime(true);

            //预计结束时间
            $tmpPerTask       = ($now - $this->startTimeFloat) / ($this->processIndex + 1);
            $forecastLeftTime = $tmpPerTask * ($this->totalTaskCount - $this->processIndex + 1);

            //进程下标
            $idxOffset = $this->totalTaskCount - count($this->params);
            for ($i = 0; $i < $startCount; $i++) {
                $cmd                               = Console::buildCommand($this->cmd, $this->params[$i]);
                $process                           = new Process($cmd);
                $this->processList[]               = [$idxOffset + $i, $process];
                $this->resultList[$idxOffset + $i] = [$now];
                $this->triggerEvent(static::EVENT_ON_PROCESS_ADD, $idxOffset + $i, $process, $forecastLeftTime);
            }
            $this->params = array_slice($this->params, $this->parallelCount - $runningCount);

            $result = true;
        } else {
            //no task to dispatch
            $result = false;
        }

        return $this;
    }

    /**
     * 心跳检测
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/25 23:10
     * @return $this
     */
    private function loopCheck(): self
    {
        while ($this->state !== static::STATE_ALL_DONE) {
            if (!$this->processList && !$this->params) {
                $this->state = static::STATE_ALL_DONE;
                $this->triggerEvent(static::EVENT_ON_ALL_DONE);
                // unset($this);
                break;
            }

            /** @var Process $process */
            foreach ($this->processList as $k => [$index, $process]) {
                if ($process->isFinished()) {
                    $output                      = $process->getOutput();
                    $this->resultList[$index][1] = microtime(true);
                    $this->resultList[$index][2] = $output;

                    $event = $process->isFail() ? static::EVENT_ON_PROCESS_ERROR : static::EVENT_ON_PROCESS_FINISH;
                    $this->triggerEvent($event, $index, $process);

                    unset($this->processList[$k]);
                    if (strpos($output, static::PS_INT_CLEAN_LEFT_FLAG) !== false) {
                        $this->triggerEvent(static::EVENT_ON_PROCESS_INTERRUPT, $index, $process);
                        $this->triggerEvent(static::EVENT_ON_ALL_DONE);

                        // unset($this);
                        // return false;
                        die();
                    }
                    $this->dispatch();
                } elseif ($process->isRunning()) {
                    if ($this->processMaxExecutionTime) {
                        $startTime = $this->resultList[$index][0];
                        if (microtime(true) - $startTime > $this->processMaxExecutionTime) {
                            $process->terminate();
                            $this->triggerEvent(static::EVENT_ON_PROCESS_INTERRUPT, $index, $process);
                            continue;
                        }
                    }
                    $this->triggerEvent(static::EVENT_ON_PROCESS_RUNNING, $index, $process);
                }
            }
            usleep($this->checkInterval * 1000000);
        }

        return $this;
    }

    /**
     * 触发事件
     * @param $event
     * @return $this
     */
    private function triggerEvent($event): self
    {
        $args = func_get_args();
        $args = array_slice($args, 1);
        foreach ($this->callbackList[$event] ?: [] as $callback) {
            call_user_func_array($callback, $args);
        }

        return $this;
    }

    /**
     * 事件监听
     * @param string   $event event name
     * @param callable $handler
     * @return $this
     */
    public function listen(string $event, callable $handler): self
    {
        $this->callbackList[$event][] = $handler;

        return $this;
    }

    /**
     * 等待结束（父进程阻塞）
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/25 23:03
     * @param int $int
     * @return $this
     * @throws \RuntimeException
     */
    public function waitForFinish(int $int = 100000): self
    {
        if ($this->state === static::STATE_INIT) {
            throw new \RuntimeException('master should start first');
        }
        while ($this->state !== static::STATE_ALL_DONE) {
            usleep($int);
        }

        return $this;
    }

    /**
     * 设置调试器
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/25 23:03
     * @param callable|null $debugger
     * @return $this
     */
    public function setDebugger(callable $debugger = null): self
    {
        $this->debugger = $debugger;

        return $this;
    }

    /**
     * 使用默认调试器
     */
    public function useConsoleDebugger(): self
    {
        return $this->setDebugger(function(...$messages) {
            $consoleColorMap = [
                'starting'  => Console::FORE_COLOR_PURPLE,
                'add'       => Console::FORE_COLOR_PURPLE,
                'running'   => Console::FORE_COLOR_CYAN,
                'interrupt' => Console::FORE_COLOR_RED,
                'failure'   => Console::FORE_COLOR_RED,
                'finished'  => Console::FORE_COLOR_GREEN,
                'all_done'  => Console::FORE_COLOR_LIGHT_GREEN,
            ];
            $messages[0]     = Console::getColorString($messages[0], $consoleColorMap[$messages[0]]);

            return call_user_func_array([Console::class, 'debug'], $messages);
        });
    }

    /**
     * 绑定调试输出（仅在开启调试时有效）
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/25 23:06
     * @return $this
     */
    private function bindDebug(): self
    {
        return $this->listen(static::EVENT_ON_START, function() {
            $this->debug('starting', date('Y-m-d H:i:s'));
        })->listen(static::EVENT_ON_PROCESS_ADD, function($index, Process $process) {
            $this->debug('add', "#$index/$this->totalTaskCount", "PID:{$process->getProcessPid()}", $process->getCommand());
        })->listen(static::EVENT_ON_PROCESS_RUNNING, function($index, Process $process) {
            $this->debug('running', "#$index/$this->totalTaskCount", "PID:{$process->getProcessPid()}", $process->getCommand());
        })->listen(static::EVENT_ON_PROCESS_INTERRUPT, function($index, Process $process) {
            $output = $this->resultList[$index][2];
            $this->debug('interrupt', "#$index/$this->totalTaskCount", "PID:{$process->getProcessPid()}", $process->getCommand(), $output);
        })->listen(static::EVENT_ON_PROCESS_ERROR, function($index, Process $process) {
            $output = $this->resultList[$index][2];
            $this->debug('failure', "#$index/$this->totalTaskCount", "PID:{$process->getProcessPid()}", $process->getCommand(), $output);
        })->listen(static::EVENT_ON_PROCESS_FINISH, function($index, Process $process) {
            $output = $this->resultList[$index][2];
            $this->debug('finished', "#$index/$this->totalTaskCount", "PID:{$process->getProcessPid()}", $process->getCommand(), $output);
        })->listen(static::EVENT_ON_ALL_DONE, function() {
            $this->debug('all_done', date('Y-m-d H:i:s'), $this->getState(), $this->getCmd());
        });
    }

    /**
     * 输出调试信息
     */
    private function debug(): void
    {
        if (!$this->debugger) {
            return;
        }
        $args = func_get_args();
        call_user_func_array($this->debugger, $args);
    }

}