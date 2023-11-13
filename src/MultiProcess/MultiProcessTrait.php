<?php

namespace Sweeper\HelperPhp\MultiProcess;

use Exception;
use Monolog\Logger;
use ReflectionClass;
use Sweeper\HelperPhp\Logger\Output\CommonAbstract;
use Sweeper\HelperPhp\Process\Process;
use Sweeper\HelperPhp\Tool\Hooker;
use Sweeper\HelperPhp\Traits\LogTrait;

use Sweeper\HelperPhp\Traits\SignalTrait;

use function Sweeper\HelperPhp\Func\array_clear_empty;
use function Sweeper\HelperPhp\Func\format_size;

/**
 * 多进程
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/11 16:29
 * @Package \Sweeper\HelperPhp\MultiProcess\MultiProcessTrait
 */
trait MultiProcessTrait
{

    use LogTrait, SignalTrait;

    /** @var bool DEBUG 模式开启 */
    private $debug = false;

    /** @var string cli解释器 */
    private $cliInterpreter = 'php';

    /** @var bool 是否为 slave【子进程】 */
    private $forked;

    /** @var string 子进程命令 */
    private $slaveCommand;

    /** @var Process[] $processList 正在执行子进程列表 [taskId => process,...] */
    private $processList = [];

    /** @var Process[] 异步关闭的进程列表 [taskId => process,...] */
    protected $asyncClosingProcessList = [];

    /** @var int 检查子进程个数休眠时间(microsecond) 默认 0.5 second */
    private $sleepMicroseconds = 500000;

    /** @var bool 关闭超时过程 */
    private $closeTheTimeoutProcess = true;

    /** @var int 子进程最大个数 */
    private $maxProcessCount = 1;

    /** @var int 子进程最大运行时间，单位（秒）默认10s */
    private $processMaxRunningTime = 86400;

    /** @var null  标准输入，子进程从此管道中读取数据 */
    private $stdInInput;

    /** @var bool 启用信号调度 */
    private $enableSignalDispatch = false;

    /** @var string 日志目录 */
    private $logDir = 'multi_process_log';

    /** @var string 开始执行日期 $this->setStartExecuteDate('7:30')->setEndExecuteDate('23:05');// 只有7点30-23点05分执行任务。其余的时间不执行 */
    private $startExecuteDate;

    /** @var string 结束执行日期 $this->setStartExecuteDate('7:30')->setEndExecuteDate('23:05');// 只有7点30-23点05分执行任务。其余的时间不执行 */
    private $endExecuteDate;

    /** @var bool 自动执行任务 用于 start 开启任务 */
    private $autoExecuteTask = false;

    /** @var string 进程类型标识 */
    private static $processTypeKey = 'm';

    /** @var string 标识 - 主进程 */
    private static $identifyMaster = 'master';

    /** @var string 标识 - 子进程 */
    private static $identifySlave = 'slave';

    /** @var string 命令行参数 key=>val 分隔符 */
    private static $delimiter = '=';

    /** @var string 创建子进程前的事件 */
    public static $EVENT_BEFORE_CREATE_PROCESS = 'EVENT_BEFORE_CREATE_PROCESS';

    /** @var string 创建子进程后的事件 */
    public static $EVENT_AFTER_CREATE_PROCESS = 'EVENT_AFTER_CREATE_PROCESS';

    /** @var string 创建子进程失败事件 */
    public static $EVENT_CREATE_PROCESS_FAIL = 'EVENT_CREATE_PROCESS_FAIL';

    /** @var string 子进程运行中事件 */
    public static $EVENT_PROCESS_IS_RUNNING = 'EVENT_PROCESS_IS_RUNNING';

    /** @var string 子进程完成事件 */
    public static $EVENT_PROCESS_IS_FINISHED = 'EVENT_PROCESS_IS_FINISHED';

    /** @var string 检测子进程事件 */
    public static $EVENT_CHECK_SLAVE_PROCESS = 'EVENT_CHECK_SLAVE_PROCESS';

    /** @var string 检测异步终止的进程事件 */
    public static $EVENT_CHECK_TERMINATE_PROCESS = 'EVENT_CHECK_TERMINATE_PROCESS';

    /** @var string 主进程睡眠监控子进程 事件 */
    public static $EVENT_SLEEP_MONITOR_SLAVE = 'EVENT_SLEEP_MASTER_PROCESS_MONITOR_SLAVE_PROCESS';

    public function isEnableSignalDispatch(): bool
    {
        return $this->enableSignalDispatch;
    }

    public function setEnableSignalDispatch(bool $enableSignalDispatch): self
    {
        $this->enableSignalDispatch = $enableSignalDispatch;

        return $this;
    }

    /**
     * @return null
     */
    public function getStdInInput()
    {
        return $this->stdInInput;
    }

    /**
     * @param null $stdInInput
     */
    public function setStdInInput($stdInInput): self
    {
        $this->stdInInput = $stdInInput;

        return $this;
    }

    public function isAutoExecuteTask(): bool
    {
        return $this->autoExecuteTask;
    }

    public function setAutoExecuteTask(bool $autoExecuteTask): self
    {
        $this->autoExecuteTask = $autoExecuteTask;

        return $this;
    }

    public static function getProcessTypeKey(): string
    {
        return static::$processTypeKey;
    }

    public static function setProcessTypeKey(string $processTypeKey): void
    {
        static::$processTypeKey = $processTypeKey;
    }

    public static function getIdentifyMaster(): string
    {
        return static::$identifyMaster;
    }

    public static function setIdentifyMaster(string $identifyMaster): void
    {
        static::$identifyMaster = $identifyMaster;
    }

    public static function getIdentifySlave(): string
    {
        return static::$identifySlave;
    }

    public static function setIdentifySlave(string $identifySlave): void
    {
        static::$identifySlave = $identifySlave;
    }

    public static function getDelimiter(): string
    {
        return static::$delimiter;
    }

    public static function setDelimiter(string $delimiter): void
    {
        static::$delimiter = $delimiter;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function setLogDir(string $logDir): self
    {
        $this->logDir = $logDir;

        return $this;
    }

    public function getStartExecuteDate(): string
    {
        return $this->startExecuteDate;
    }

    public function setStartExecuteDate(string $startExecuteDate): self
    {
        $this->startExecuteDate = $startExecuteDate;

        return $this;
    }

    public function getEndExecuteDate(): string
    {
        return $this->endExecuteDate;
    }

    public function setEndExecuteDate(string $endExecuteDate): self
    {
        $this->endExecuteDate = $endExecuteDate;

        return $this;
    }

    public function isCloseTheTimeoutProcess(): bool
    {
        return $this->closeTheTimeoutProcess;
    }

    public function setCloseTheTimeoutProcess(bool $closeTheTimeoutProcess): self
    {
        $this->closeTheTimeoutProcess = $closeTheTimeoutProcess;

        return $this;
    }

    public function getProcessMaxRunningTime(): int
    {
        return $this->processMaxRunningTime;
    }

    public function setProcessMaxRunningTime(int $processMaxRunningTime): self
    {
        $this->processMaxRunningTime = $processMaxRunningTime;

        return $this;
    }

    public function getSleepMicroseconds(): int
    {
        return $this->sleepMicroseconds;
    }

    public function setSleepMicroseconds(int $sleepMicroseconds): self
    {
        $this->sleepMicroseconds = $sleepMicroseconds;

        return $this;
    }

    public function getSlaveCommand(): string
    {
        return $this->slaveCommand;
    }

    public function setSlaveCommand(string $slaveCommand): self
    {
        $this->slaveCommand = $slaveCommand;

        return $this;
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

    /**
     * @return Process[]
     */
    public function getProcessList(): array
    {
        return $this->processList;
    }

    /**
     * @param Process[] $processList
     */
    public function setProcessList(array $processList): self
    {
        $this->processList = $processList;

        return $this;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    public function getCliInterpreter(): string
    {
        return $this->cliInterpreter;
    }

    public function setCliInterpreter(string $cliInterpreter): self
    {
        $this->cliInterpreter = $cliInterpreter;

        return $this;
    }

    public function isForked(): bool
    {
        return $this->forked;
    }

    public function setForked(bool $forked): self
    {
        $this->forked = $forked;

        return $this;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 16:40
     * @return int
     */
    protected function getProcessCount(): int
    {
        return count($this->getProcessList());
    }

    /**
     * 添加进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:43
     * @param Process $process
     * @return $this
     */
    protected function addProcess(Process $process): self
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
    protected function removeProcess($idx): self
    {
        unset($this->processList[$idx]);

        return $this;
    }

    /**
     * 添加/连接进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:43
     * @param Process $process
     * @return $this
     */
    protected function attachProcess(Process $process): self
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
    protected function detachProcess($idx): self
    {
        return $this->removeProcess($idx);
    }

    /**
     * 移除(分离)所有进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:59
     * @return $this
     */
    protected function detachAllProcess(): self
    {
        $this->processList = [];

        return $this;
    }

    /**
     * 获取存储文件信息
     * @return array ['目录','文件名']
     */
    protected function getStorageFileInfo(): array
    {
        $path_list = explode(DIRECTORY_SEPARATOR, str_replace('\\', DIRECTORY_SEPARATOR, static::class ?? pathinfo(__FILE__, PATHINFO_FILENAME)));
        $filename  = end($path_list);
        $dir       = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->logDir . DIRECTORY_SEPARATOR;

        return [$dir, $filename];
    }

    /**
     * 断言在时间范围内
     * User: Sweeper
     * Time: 2023/4/12 10:11
     * @param bool $throwException
     * @return bool
     */
    protected function assertWithInTheTimeRange(bool $throwException = true): bool
    {
        $currentDate    = date('Y-m-d H:i:s');
        $timestamp      = strtotime($currentDate);
        $startDate      = $this->getStartExecuteDate();
        $endDate        = $this->getEndExecuteDate();
        $startTimestamp = strtotime($startDate);// 格式化为时间戳，只指定了时间，会默认加上当前日期，如：8:00,会转换为今天八点时间戳
        $endTimestamp   = strtotime($endDate);  // 格式化为时间戳，只指定了时间，会默认加上当前日期，如：8:00,会转换为今天八点时间戳
        $_currentTime   = (int)date('His');
        $_startTime     = (int)date('His', $startTimestamp);
        $_endTime       = (int)date('His', $endTimestamp);
        if ($_startTime > $_endTime && ($_currentTime >= $_startTime || $_currentTime < $_endTime)) { //例如：180000 80000 在这个区间可执行
            return true;
        }
        if ($_startTime < $_endTime && $_currentTime >= $_startTime && $_currentTime < $_endTime) { //例如：80000 180000 在这个区间可执行
            return true;
        }
        if ($startTimestamp > $endTimestamp) {// 结束时间小， +1 day
            $endTimestamp = strtotime('+1day', $endTimestamp);
        }
        if ($startDate && $endDate) {
            $startDate        = date('Y-m-d H:i:s', $startTimestamp);
            $endDate          = date('Y-m-d H:i:s', $endTimestamp);
            $inTimestampRange = $timestamp >= $startTimestamp && $timestamp <= $endTimestamp;
            if (!$inTimestampRange) {
                $message = "超出允许执行的时间范围，当前时间({$currentDate})不在允许的时间范围内[{$startDate} ~ {$endDate}]";
                $this->notice($message);
                if (!$throwException) {
                    return false;
                }
                throw new \OutOfRangeException($message);
            }
        }

        return true;
    }

    /**
     * master 关闭之前事件
     * @return bool
     */
    protected function onMasterShutdownBefore(): bool
    {
        return true;
    }

    /**
     * master 关闭之后事件
     * @return bool
     */
    protected function onMasterShutdownAfter(): bool
    {
        return true;
    }

    /**
     * slave 关闭之前事件
     * @param \Sweeper\HelperPhp\Process\Process|null $process
     * @return bool
     */
    protected function onSlaveShutdownBefore(?Process $process = null): bool
    {
        return true;
    }

    /**
     * slave 关闭之后事件
     * @param \Sweeper\HelperPhp\Process\Process|null $process
     * @return bool
     */
    protected function onSlaveShutdownAfter(?Process $process = null): bool
    {
        return true;
    }

    /**
     * 创建子进程失败事件
     * @param Exception  $exception
     * @param            $cmd
     */
    protected function onCreateProcessFail(\Throwable $ex, $cmd): void
    {
        $args = $this->parseArgs();//解析  -x "xx" 格式的命令
        $this->debug("onCreateProcessFail($cmd)：{$ex->getFile()}#{$ex->getLine()} ({$ex->getMessage()})", $args);
    }

    /**
     * 生成子进程命令
     * @return string
     */
    private function _buildSlaveProcessCommand(): string
    {
        return $this->addSubprocessIdentify($this->getCliInterpreter() . ' ' . $_SERVER['SCRIPT_FILENAME']);
    }

    /**
     * 杀死所有忙碌的子进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/10 19:00
     * @return void
     */
    private function _killAllBusySlaveProcess(): void
    {
        foreach ($this->getProcessList() as $taskId => $process) {
            $this->warning('The child process is closed.' . $this->outputLogContentAboutProcessId($this->getSlaveProcessIdContent($taskId)) . "CMD[{$process->getCommand()}]");
            $this->waitShutdownSlaveProcess($process);
        }
        $this->debug('all busy process closed');
    }

    /**
     * 循环检测子进程
     */
    private function _loopSlaveProcess(): void
    {
        while ($this->_isBusy()) {
            $this->loop();
            if (!$this->_isBusy()) {
                break; //有空闲的子进程数 跳出阻塞等待
            }
            $this->isDebug() && $this->debug("Over the allowed max number of process[{$this->getMaxProcessCount()}],sleep {$this->getSleepMicroseconds()} microseconds");
            usleep($this->getSleepMicroseconds());
        }
    }

    /**
     * 繁忙检测
     * @return bool
     */
    private function _isBusy(): bool
    {
        return $this->getProcessCount() >= $this->getMaxProcessCount(); //当前总进程数 >= 最大进程数
    }

    /**
     * 循环检测子进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/12 0:24
     * @return void
     */
    private function loop(): void
    {
        foreach ($this->getProcessList() as $taskId => $process) {
            $_pid_content   = $this->outputLogContentAboutProcessId($this->getSlaveProcessIdContent($taskId));
            $_process_cmd   = $process->getCommand();
            $hasElapsedTime = microtime(true) - $process->getStartTime();
            if ($process->isFinished()) {
                $this->removeProcess($taskId)->debug($_pid_content . 'The child process is finish.');

                Hooker::fire(static::$EVENT_PROCESS_IS_FINISHED, $taskId, $_process_cmd, $process);

                if ($this->isAutoExecuteTask()) {
                    $this->executeTask();
                }
            } elseif ($process->isRunning()) {
                Hooker::fire(static::$EVENT_PROCESS_IS_RUNNING, $taskId, $_process_cmd, $process);

                /** 子进程超出最大运行时间 */
                if ($this->isCloseTheTimeoutProcess() && $this->getProcessMaxRunningTime() && $hasElapsedTime > $this->getProcessMaxRunningTime()) {
                    $this->warning("$_pid_content The child process More than {$this->getProcessMaxRunningTime()} s is about to be closed,CMD:$_process_cmd");
                    $this->waitShutdownSlaveProcess($process);
                    if ($this->isAutoExecuteTask()) {
                        $this->executeTask();
                    }
                }
            }
        }
        Hooker::fire(static::$EVENT_CHECK_TERMINATE_PROCESS); //检测异步终止的进程
    }

    /**
     * 创建子进程
     * @param string $commandline CMD 命令
     * @return \Sweeper\HelperPhp\Process\Process
     * @throws \Exception
     */
    private function _createSlaveProcess(string $commandline): Process
    {
        Hooker::fire(static::$EVENT_BEFORE_CREATE_PROCESS, $commandline);
        try {
            $process = new Process($commandline, $this->getStdInInput());
        } catch (\Throwable $ex) {
            $this->error($ex->getMessage());
            Hooker::fire(static::$EVENT_CREATE_PROCESS_FAIL, $ex, $commandline);
            $this->shutdownMaster(10000); //创建进程失败 关闭master
            exit(0);
        }
        Hooker::fire(static::$EVENT_AFTER_CREATE_PROCESS, $commandline, $process);
        $taskId = $process->getPid();
        $this->attachProcess($process)->debug('create process success' . $this->outputLogContentAboutProcessId($this->getSlaveProcessIdContent($taskId)) . 'cmd:' . $commandline);
        //创建进程后、检测当前子进程数是否达到最大限制数 $this->max_process_count [达到最大限制需等待子进程结束再返回程序继续执行后续操作] 放在创建之前会导致不能及时检测到进程结束触发相应的事件
        $this->_loopSlaveProcess(); //重试不再循环检测【当前已在循环中】多层检测会导致子进程已结束资源已释放这里还在循环检测
        gc_collect_cycles();        //创建完子进程 -> 强制收集所有现存的垃圾循环周期

        return $process;
    }

    /**
     * 关闭 master 进程(等待子进程结束，执行 master 关闭之前事件)
     * @param int $timeoutMilliseconds 超时毫秒
     * @throws \Exception
     */
    final protected function shutdownMaster(int $timeoutMilliseconds = 0): void
    {
        $this->waitAllSlaveProcessFinish($timeoutMilliseconds);  //所有任务执行完毕
        $this->onMasterShutdownBefore();                         //关闭 master 之前事件
        $this->debug('Exit the current script.');
        die(0);
    }

    /**
     * 等待所有任务(子进程)执行完毕, 可以带一个timeout参数代表超时时间毫秒数, 超过后将强行终止还没完成的任务并返回
     * @param int $timeoutMilliseconds 超时毫秒
     * @return bool
     * @throws \Exception
     */
    final protected function waitAllSlaveProcessFinish(int $timeoutMilliseconds = 0): bool
    {
        $start = microtime(true);
        $this->debug('Wait for all tasks to complete.');
        while (true) {
            $this->loop();
            $sleep_milliseconds = (microtime(true) - $start) * 1000;
            if (!$this->getProcessCount()) {
                break;
            }
            if ($timeoutMilliseconds > 0 && $sleep_milliseconds >= $timeoutMilliseconds) {
                $this->debug("sleep($sleep_milliseconds) >= timeout($timeoutMilliseconds)," . "After more than {$timeoutMilliseconds} milliseconds will be forced to terminate unfinished tasks and return.");
                $this->_killAllBusySlaveProcess();
                break;
            }
            usleep($this->getSleepMicroseconds());
        }
        $this->debug('All the tasks completed.');

        return true;
    }

    /**
     * 获取进程内存信息
     * @param $pid
     * @return string
     */
    final protected function getProcessMemory($pid): string
    {
        if (stripos(PHP_OS_FAMILY, 'win') !== false) {
            exec("tasklist | findstr {$pid}", $outputs);
            $info   = array_values(array_clear_empty(explode(' ', current($outputs))));
            $memory = $info[4] . ' ' . $info[5];
        } else {
            exec("cat /proc/{$pid}/status | grep VmRSS", $outputs);
            $output = trim(current($outputs));
            $memory = trim(explode(':', $output)[1]);
        }

        return $memory;
    }

    /**
     * 杀死进程
     * @param Process $process
     */
    final protected function killProcess(Process $process): void
    {
        $pid = $process->getPid();//不存在的话会导致进程组所有进程都被杀死
        if ($pid && function_exists('posix_kill') && !posix_kill($pid, SIGKILL)) {//存在函数 posix_kill 且关闭进程失败
            $process->close();
        } else {
            $process->close();
        }
    }

    /**
     * 主进程睡眠监控子进程
     * @param int $seconds 睡眠时间（秒）
     * @return bool
     */
    final protected function sleepMasterProcessMonitorSlaveProcess(int $seconds = 1): bool
    {
        $time = time();
        while (true) {
            $this->loop();
            $wait_time = time() - $time;
            if ($wait_time >= $seconds) {
                break;
            }
            usleep($this->getSleepMicroseconds());
        }

        return true;
    }

    /**
     * 等待 $timeout_milliseconds 后再关闭子进程 [仅在 Master 进程调用]
     * @param Process $process             进程资源
     * @param bool    $isAsync             异步关闭
     * @param int     $timeoutMilliseconds 超时毫秒数
     */
    final protected function waitShutdownSlaveProcess(Process $process, bool $isAsync = false, int $timeoutMilliseconds = 1000): void
    {
        $start = microtime(true);
        while ($timeoutMilliseconds && !$process->isFinished()) { //设置了超时时间且进程没完成 等待 $timeout_milliseconds 之后再关闭
            $sleep_milliseconds = (microtime(true) - $start) * 1000;
            if ($sleep_milliseconds >= $timeoutMilliseconds) {
                break;
            }
            usleep(100);
        }
        $this->shutdownSlaveProcess($process, $isAsync);
    }

    /**
     * 关闭 slave 进程 [仅在 Master 进程调用]
     * @param Process $process
     * @param bool    $isAsync 等待进程终止|发送信号通知其终止立即返回继续其他的任务
     * @return bool
     */
    final protected function shutdownSlaveProcess(Process $process, bool $isAsync = false): bool
    {
        $taskId = $process->getPid();
        $this->onSlaveShutdownBefore($process); //关闭 slave 之前事件
        $isAsync ? $process->terminate() : $process->close();
        $this->removeProcess($taskId);
        if ($isAsync) {
            $this->asyncClosingProcessList[$taskId] = $process;
        }
        gc_collect_cycles();                          //关闭进程之后 -> 强制收集所有现存的垃圾循环周期

        return $this->onSlaveShutdownAfter($process); //关闭 slave 之后事件
    }

    /**
     * 关闭 slave 进程 [仅在 slave 进程调用]
     */
    final protected function shutdownSlave(): void
    {
        if ($this->isForked()) {
            $this->onSlaveShutdownBefore(); //关闭 slave 之前事件
            die;
        }
        $this->warning('Only in the slave process calls.');
    }

    /**
     * 创建子进程任务
     * @return Process
     * @throws \Exception
     */
    final protected function createSlaveProcessTask(): Process
    {
        if (!$this->assertWithInTheTimeRange(false)) {
            $this->shutdownMaster(10000); //关闭 master 进程
        }

        return $this->_createSlaveProcess($this->buildCommand($this->getSlaveCommand(), func_get_args()));
    }

    /**
     * 通过 CMD 创建子进程任务
     * @param string $cmd 要执行的外部 CMD 命令
     * @return Process
     * @throws \Exception
     */
    final protected function createSlaveProcessTaskViaCmd(string $cmd): Process
    {
        if (!$this->assertWithInTheTimeRange(false)) {
            $this->shutdownMaster(10000); //关闭 master 进程
        }

        return $this->_createSlaveProcess($this->_customCommandHandler($cmd));
    }

    /**
     * 获取子进程进程 ID 内容
     * @param $taskId
     * @return string
     */
    final protected function getSlaveProcessIdContent($taskId): string
    {
        return '【' . static::getIdentifySlave() . ':' . $taskId . '】';
    }

    /**
     * 输出关于进程ID的日志内容
     * @return string
     */
    final protected function outputLogContentAboutProcessId(): string
    {
        return '【' . ($this->isForked() ? static::getIdentifySlave() : static::getIdentifyMaster()) . ':' . getmypid() . '】' . CommonAbstract::combineMessages(func_get_args());
    }

    /**
     * 添加子进程标识
     * @param $command
     * @return bool
     */
    final protected function addSubprocessIdentify($command)
    {
        return $command . ' -' . static::getProcessTypeKey() . static::getDelimiter() . escapeshellarg(static::getIdentifySlave());
    }

    /**
     * 是子进程
     * @param array|null $opt 只要有传参数选项就用参数选项，不管是否为空【多层嵌套会导致直接执行子进程的子进程 应该是先执行子进程的主进程】
     * @return bool
     */
    final protected function isSubprocess(?array $opt = null): bool
    {
        $opt = $opt ?? $this->parseCommand();//解析  -x="xx" 格式的命令

        return isset($opt[static::getProcessTypeKey()]) && $opt[static::getProcessTypeKey()] === static::getIdentifySlave();
    }

    /**
     * 解析命令->获取参数值【【解析等号连接格式的命令：-x="xx"】】
     * @param array $params
     * @return array
     */
    protected function parseCommand(array $params = []): array
    {
        $params = $params ?: $_SERVER['argv'];
        if (is_file($params[0])) {
            array_shift($params);//去除文件名
        }
        $tmp = [];
        foreach ($params as $val) {
            [$key, $val] = explode(static::getDelimiter(), $val);// --0['xx']='xx';
            $is_array = substr_count($key, '-') > 1;
            $key      = ltrim($key, '-');
            if ($is_array) {
                preg_replace_callback('/(?:\[)(.*)(?:\])/i', function($matches) use (&$tmp, $key, $val) {
                    [$org_val, $match_val] = $matches;// 通常: $matches[0]是完成的匹配 $matches[1]是第一个捕获子组的匹配 以此类推...
                    $index                   = str_replace($org_val, '', $key);
                    $tmp[$index][$match_val] = $val;
                }, $key);
            } else {
                $tmp[$key] = $val;
            }
        }

        return $tmp;
    }

    /**
     * 返回传递给当前脚本的参数的数组【解析空格连接格式的命令：-x "xx"】
     * @param array $params
     * @return array
     */
    protected function parseArgs(array $params = []): array
    {
        $args = [];
        $argv = $params ?: $_SERVER['argv'];
        $max  = count($argv);
        for ($i = 0; $i < $max; $i++) {
            if (strpos($argv[$i], '-') === 0) {
                $args[str_replace('-', '', $argv[$i])] = $argv[$i + 1];
                $i++;//跳过后一个字符串（已作为值处理）
            } else {
                $args[] = $argv[$i];
            }
        }

        return $args;
    }

    /**
     * 构建命令->参数组装
     * @param       $commandline
     * @param array $params
     * @return string
     */
    protected function buildCommand($commandline, array $params = []): string
    {
        foreach ($params as $k => $val) {
            if (is_array($val)) {
                foreach ($val as $i => $vi) {
                    $commandline .= " --{$k}[{$i}]" . static::getDelimiter() . escapeshellarg($vi);
                }
            } else {
                $commandline .= " -{$k}" . static::getDelimiter() . escapeshellarg($val);
            }
        }

        return $commandline;
    }

    /**
     * 检测异步终止的进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/12 0:22
     * @return void
     */
    protected function checkTerminateProcess(): void
    {
        foreach ($this->asyncClosingProcessList as $taskId => $process) {
            if ($process->isFinished()) {
                unset($this->asyncClosingProcessList[$taskId]);
            } elseif ($process->isRunning()) {
                $process->close();
            }
        }
    }

    /**
     * 自定义命令处理程序【检测 CMD 路径】
     * @param string $cmd
     * @return string
     */
    protected function _customCommandHandler(string $cmd): string
    {
        $tmp = explode(' ', $cmd); // php filename params_str
        if (!is_file($tmp[1])) {
            $func      = new ReflectionClass(static::class); //当前操作的类 CMD 中的文件名保持在同一目录下才能保证 CMD 可执行
            $path_list = explode(DIRECTORY_SEPARATOR, str_replace('\\', DIRECTORY_SEPARATOR, $func->getFileName()));
            array_pop($path_list);
            $tmp[1] = implode(DIRECTORY_SEPARATOR, $path_list) . DIRECTORY_SEPARATOR . $tmp[1];

            return implode(' ', $tmp);
        }

        return $this->addSubprocessIdentify($cmd); //添加子进程 KEY 标识
    }

    /**
     * 当用户想要中断进程时，INT 信号被进程的控制终端发送到进程
     */
    public function signalInt(): void {
        $this->debug('Caught SIGINT...');
        $this->shutdownMaster(10000);//关闭 master 进程
    }

    /**
     * 发送到进程的 USR1 信号用于指示用户定义的条件
     */
    public function signalUsr1(): void {
        $this->debug('Caught SIGUSR1...');
        $this->shutdownMaster();//关闭 master 进程
    }

    /**
     * 发送到进程的 USR2 信号用于指示用户定义的条件
     */
    public function signalUsr2(): void {
        $this->debug('Caught SIGUSR2...');
        $this->shutdownMaster(600000);//关闭 master 进程
    }

    /**
     * 信号处理器
     * User: Sweeper
     * Time: 2023/8/21 18:04
     * @param int $signal
     * @param     $signalInfo
     * @return void
     */
    public function signalHandler(int $signal, $signalInfo): void { }

    /**
     * 执行任务
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/3 13:27
     * @return void
     */
    protected function executeTask(): void { }

    /**
     * 初始化参数、注册事件
     * MultiProcessManagerAbstract constructor.
     * @param int  $maxProcessCount 最大子进程数
     * @param bool $debug           DEBUG 模式
     */
    public function __construct(int $maxProcessCount = 1, bool $debug = false)
    {
        $args = $this->parseArgs();                    //解析  -x "xx" 格式的命令
        $argv = $this->parseCommand($_SERVER['argv']); //解析  -x="xx" 格式的命令
        $this->setDebug($debug)
             ->setForked($this->isSubprocess(getopt(static::getProcessTypeKey() . ':')))
             ->setSlaveCommand($this->isForked() ? '' : $this->_buildSlaveProcessCommand());
        // 设置最大子进程数量
        // 如果有提供 --worker_num num | -worker_num=num 则优先这个配置
        $this->setMaxProcessCount((int)min(max($maxProcessCount, (int)$args['worker_num'], (int)$argv['worker_num'], 1), 200));
        // 设置进程最大运行时间
        // 如果有提供 --max_running_time seconds | -max_running_time=seconds | --max_exec_time seconds | -max_exec_time=seconds  则优先这个配置
        $this->setProcessMaxRunningTime(min(max((int)$args['max_running_time'], (int)$argv['max_running_time'], (int)$args['max_exec_time'], (int)$argv['max_exec_time'], 10), 86400));
        // 设置允许执行的时间
        // 如果有提供 --start_time time --end_time time | -start_time=time -end_time=time 则优先这个配置
        $this->setStartExecuteDate(($args['start_time'] ?? $argv['start_time']) ?: $this->getStartExecuteDate());
        $this->setEndExecuteDate(($args['end_time'] ?? $argv['end_time']) ?: $this->getEndExecuteDate());
        // 初始化添加检测子进程事件
        Hooker::add(static::$EVENT_CHECK_SLAVE_PROCESS, function() {
            $this->loop();
        });
        //注册 主进程睡眠监控子进程 事件
        Hooker::add(static::$EVENT_SLEEP_MONITOR_SLAVE, function($seconds) {
            $this->sleepMasterProcessMonitorSlaveProcess($seconds ?: 1);
            gc_collect_cycles();
        });
        //注册创建子进程失败事件
        Hooker::add(static::$EVENT_CREATE_PROCESS_FAIL, function(\Throwable $ex, $command) {
            $this->onCreateProcessFail($ex, $command);
        });
        //注册创建子进程前的事件
        Hooker::add(static::$EVENT_BEFORE_CREATE_PROCESS, function($command) {
            if ($this->isEnableSignalDispatch()) {
                $this->signalDispatch();
            }
        });
        // 注册事件
        $this->isForked() ? register_shutdown_function(function() { return $this->onSlaveShutdownAfter(); }) : register_shutdown_function(function() { return $this->onMasterShutdownAfter(); });
        $this->assertWithInTheTimeRange();
    }

    /**
     * 开始执行任务
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/10 19:04
     * @param array $params
     * @return void
     * @throws \Exception
     */
    final public function run(array $params = []): void
    {
        if (!$this->isForked() && !$this->assertWithInTheTimeRange(false)) {
            $this->shutdownMaster(10000); //关闭 master 进程
        }
        $this->debug($this->outputLogContentAboutProcessId() . 'Program start running.');
        $this->isForked() ? $this->slave($this->parseCommand($params)) : $this->master($this->parseCommand($params));
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/12 0:20
     * @return void
     * @throws \Exception
     */
    final public function start(): void
    {
        for ($i = 0; $i < $this->getMaxProcessCount(); $i++) {
            $this->executeTask();
        }
        $this->setAutoExecuteTask(true)->loop();
    }

    /**
     * 主进程处理程序
     * @param array $params
     */
    abstract public function master(array $params = []);

    /**
     * 子进程处理程序
     * @param array $params
     */
    abstract public function slave(array $params = []);

}

