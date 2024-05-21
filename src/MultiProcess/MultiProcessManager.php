<?php

namespace Sweeper\HelperPhp\MultiProcess;

use Sweeper\HelperPhp\Traits\LogTrait;

use function Sweeper\HelperPhp\Func\var_export_min;

/**
 * 多进程控制类库
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/21 18:33
 * @Path \Sweeper\HelperPhp\MultiProcess\MultiProcessManager
 */
class MultiProcessManager
{

    use LogTrait;

    public const DEFAULT_INTERPRETER = 'php';

    /** @var int 标准输入流 */
    public const STDIN = 0;

    /** @var int 标准输出流 */
    public const STDOUT = 1;

    /** @var int 标准错误流 */
    public const STDERR = 2;

    /** @var string 标记读取 */
    public const FLAG_READ = 'r';

    /** @var string 标记读取 */
    public const FLAG_READ_WIN = 'rb';

    /** @var string 标记写 */
    public const FLAG_WRITE       = 'w';

    public const PROCESS_TYPE_KEY = 'm';

    /** @var string 主进程 */
    public const PROCESS_MASTER = 'master';

    /** @var string 子进程 */
    public const PROCESS_SLAVE = 'slave';

    public const CALLBACK_KEY  = 'callback';

    public const PROCESS_KEY   = 'process';

    public const PIPES_KEY     = 'pipes';

    public const STATUS_KEY    = 'status';

    public const PID_KEY       = 'pid';

    /** @var string 多进程目录 */
    public const MULTI_PROCESS_DIR = 'multi_process_log';

    /** @var array 进程列表 */
    private $_processes = [];

    /** @var bool 是否为子进程 */
    private $_isForked = false;

    private $_cmd;

    private $_name;

    private $_limit    = 2;

    private $_busy     = 0;

    /** @var callable 主进程回调 */
    private $_masterHandler = null;

    /** @var callable 子进程回调 */
    private $_slaveHandler = null;

    /** @var null $_prefix */
    private $_prefix = null;

    /**
     * 检测是否windows系统，因为windows系统默认编码为GBK
     * @return bool
     */
    protected function isWin(): bool
    {
        return stripos(PHP_OS_FAMILY, 'win') !== false;
    }

    /**
     * 构造函数
     * @param int    $limit 子进程数
     * @param string $name  进程名
     * @param null   $prefix
     */
    public function __construct(int $limit = 2, string $name = 'MultiProcess', $prefix = null)
    {
        $this->_name   = $name;
        $this->_limit  = $limit;
        $this->_prefix = $prefix;
        $this->logger  = $this->getDefaultLogger();
        $opt           = getopt(static::PROCESS_TYPE_KEY . ':');
        isset($opt[static::PROCESS_TYPE_KEY]) && $opt[static::PROCESS_TYPE_KEY] === static::PROCESS_SLAVE ? $this->_isForked = true : $this->_isForked = false;
    }

    /**
     * 合并消息
     * @param $messages
     * @return string
     */
    public static function combineMessages($messages): string
    {
        foreach ($messages as $k => $msg) {
            $messages[$k] = is_scalar($msg) ? $msg : var_export_min($msg, true);
        }

        return implode(' ', $messages);
    }

    /**
     * 获取日志内容
     * @param      $str
     * @return string
     */
    public function getLogContent($str): string
    {
        $args = func_get_args();
        $line = count($args) > 1 ? static::combineMessages($args) : $str;

        return '[' . ($this->_isForked ? static::PROCESS_SLAVE : static::PROCESS_MASTER) . ':' . getmypid() . ']' . $line;
    }

    /**
     * 主进程
     * @param callable $masterHandler
     * @return $this
     */
    public function master(callable $masterHandler): self
    {
        if (!$this->_isForked) {
            $this->_masterHandler = $masterHandler;
            $this->createMaster($this->_limit);
        }

        return $this;
    }

    /**
     * 子进程
     * @param callable $slaveHandler
     * @return $this
     */
    public function slave(callable $slaveHandler): self
    {
        if ($this->_isForked) {
            $this->_slaveHandler = $slaveHandler;
            $this->createSlave();
        }

        return $this;
    }

    /**
     * 创建任务
     * @param array|null    $data
     * @param callable|null $callback
     */
    public function createTask(array $params = [], callable $callback = null): void
    {
        if (!$this->_isForked) {
            $this->logger->debug('createTask');
            $process                       = &$this->getAvailableProcess();
            $process[static::CALLBACK_KEY] = $callback;
            $data                          = json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $length                        = strlen($data);
            $length                        = str_pad($length . '', 8, ' ', STR_PAD_RIGHT);
            $size                          = fwrite($process[static::PIPES_KEY][0], $length . $data);
            $this->logger->debug('createTask success size:' . $size);
            //测试是否可以接收到输出
            // var_dump($process[static::PIPES_KEY]);
            // $stream_get_contents = stream_get_contents($process[static::PIPES_KEY][1]);
            // $this->logger->debug('stream_get_contents:'.$stream_get_contents);
        }
    }

    /**
     * 循环检测
     * @param int $sleep
     * @return boolean
     */
    public function loop(int $sleep = 0): bool
    {
        if (!$this->_isForked) {
            if ($sleep > 0) {
                usleep($sleep * 1000);
            }
            $this->check();

            return true;
        }

        return false;
    }

    /**
     * 等待所有任务执行完毕, 可以带一个timeout参数代表超时时间毫秒数, 超过后将强行终止还没完成的任务并返回
     * @param int $timeout
     * @throws \Exception
     */
    public function wait(int $timeout = 0): void
    {
        $start = microtime(true);
        while (true) {
            $this->check();
            $interval = (microtime(true) - $start) * 1000;
            if ($this->_busy === 0) {
                return;
            }
            // timeout
            if ($timeout > 0 && $interval >= $timeout) {
                $this->logger->debug("Wait for all tasks to complete.interval($interval) >= timeout($timeout)" . ($interval >= $timeout) ? ",After more than {$timeout} ms will be forced to terminate unfinished tasks and return" : '.');
                $this->killAllBusyProcess();

                return;
            }
            usleep(10000);
        }
    }

    /**
     * 默认日志输出
     * @param $str
     */
    public function error_log($str): void
    {
        $args = func_get_args();
        $line = count($args) > 1 ? sprintf('%s', ...$args) : $str;
        $line = date('Y-m-d H:i:s') . ' [' . ($this->_isForked ? static::PROCESS_SLAVE : static::PROCESS_MASTER) . ':' . getmypid() . '] ' . $line;
        error_log($line . "\n", 3, $this->_isForked ? 'php://stderr' : 'php://stdout');
    }

    /**
     * create master handlers 创建主处理程序
     * @param int $limit
     */
    private function createMaster(int $limit): void
    {
        $this->_cmd = $this->buildCommand();
        $this->logger->debug("createMaster:CMD({$this->_cmd})");
        for ($i = 0; $i < $limit; $i++) {
            $this->_processes[] = $this->createProcess();//创建子进程
        }
        @cli_set_process_title($this->_name . ':' . static::PROCESS_MASTER);//设置进程标题
        if (!empty($this->_masterHandler)) {
            call_user_func($this->_masterHandler, $this);
        }
    }

    /**
     * create slave handlers 创建从处理程序
     */
    private function createSlave(): void
    {
        @cli_set_process_title($this->_name . ':' . static::PROCESS_SLAVE);
        file_put_contents('php://stdout', str_pad(getmypid(), 5, ' ', STR_PAD_LEFT));
        while (true) {
            $fp   = @fopen('php://stdin', static::FLAG_READ_WIN);
            $recv = @fread($fp, 8);
            $size = (int)rtrim($recv);
            $data = @fread($fp, $size);
            @fclose($fp);
            if (!empty($data) && !empty($this->_slaveHandler)) {
                $data = json_decode($data, true);
                $resp = call_user_func($this->_slaveHandler, $data, $this);
                echo json_encode($resp, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            } else {
                usleep(100000);
            }
        }
    }

    /**
     * 创建进程
     * @return array
     */
    private function createProcess(): array
    {
        $descriptors   = [
            static::STDIN  => ['pipe', static::FLAG_READ],
            static::STDOUT => ['pipe', static::FLAG_WRITE],
            static::STDERR => ['pipe', static::FLAG_WRITE],
        ];
        $cwd           = realpath('./');//getcwd()
        $env           = $_SERVER;      //WINDOWS环境：必须传递 $_SERVER给子进程，否则子进程内数据库连接可能出错 ？？
        $other_options = null;
        $pipes         = [];
        $_process      = proc_open($this->_cmd, $descriptors, $pipes, $cwd, $env, $other_options);
        if ($_process === false || $_process === null || !is_resource($_process)) {
            throw new \RuntimeException("Cannot create new process: {$this->_cmd}");
        }
        /** $pipes 现在看起来是这样的：0 => 可以向子进程标准输入写入的句柄 1 => 可以从子进程标准输出读取的句柄 错误输出将被追加到文件 /tmp/error-output.txt */
        $pid     = $this->getPid($_process);
        $process = [
            static::PROCESS_KEY  => $_process,
            static::PIPES_KEY    => $pipes,
            static::STATUS_KEY   => true,
            static::PID_KEY      => $pid,
            static::CALLBACK_KEY => null,
        ];
        //为 stream 设置阻塞或者阻塞模。
        // stream_set_blocking(STDIN, false);
        // stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $this->logger->debug("createProcess success" . $this->getLogContent($pid));

        return $process;
    }

    /**
     * @param $process
     * @return bool
     */
    private function printErrorMsg($process): bool
    {
        $errormsg = stream_get_contents($this->isWin() ? STDERR : $process[static::PIPES_KEY][2]);//windows stream_get_contents 获取$process[static::PIPES_KEY][2]会阻塞住
        if ($errormsg) {
            $this->logger->error('errormsg:' . $errormsg);
        }

        return true;
    }

    /**
     * 检测进程
     * @return int|string
     */
    private function check()
    {
        $index = -1;
        foreach ($this->_processes as $task_id => &$process) {
            $this->checkProcessAlive($process);
            if (!$process[static::STATUS_KEY]) {
                $this->printErrorMsg($process);
                $stdout = stream_get_contents($this->isWin() ? STDOUT : $process[static::PIPES_KEY][1]);
                if (!empty($stdout) || $this->isFinished($process[static::PROCESS_KEY])) {
                    $process[static::STATUS_KEY] = true;
                    $this->_busy--;
                    if (!empty($process[static::CALLBACK_KEY])) {
                        $process[static::CALLBACK_KEY](json_decode($stdout, true));
                    }
                }
            }
            if ($process[static::STATUS_KEY] && $index < 0) {
                $index = $task_id;
            }
        }

        return $index;
    }

    /**
     * 检查进程是否有效
     * @param $process
     */
    private function checkProcessAlive(&$process): void
    {
        $status = $this->getProcessStatus($process[static::PROCESS_KEY]);
        if (!$status['running']) {
            $this->printErrorMsg($process);
            $this->killProcess($process);
            $this->logger->debug('process is not running, close ' . $this->getLogContent($process[static::PID_KEY]));
            if (!$process[static::STATUS_KEY]) {
                $this->_busy--;
            }
            $process = $this->createProcess();
        }
    }

    /**
     * 杀死进程
     * @param $process
     */
    private function killProcess($process): void
    {
        if (function_exists('posix_kill')) {
            posix_kill($process[static::PID_KEY], 9);
        } else {
            // @proc_terminate($process[static::PROCESS_KEY]);
            @proc_close($process[static::PROCESS_KEY]);
        }
    }

    /**
     * 杀死所有忙碌的进程
     * @throws \Exception
     */
    private function killAllBusyProcess(): void
    {
        foreach ($this->_processes as &$process) {
            if (!$process[static::STATUS_KEY]) {
                $this->killProcess($process);
                $process = $this->createProcess();
                $this->logger->debug('kill all busy process close ' . $this->getLogContent($process[static::PID_KEY]));
                $this->_busy--;
            }
        }
    }

    /**
     * 获取可用进程
     * @return mixed
     */
    private function &getAvailableProcess()
    {
        $available = null;
        while (true) {
            $index = $this->check();
            if (isset($this->_processes[$index])) {
                $this->logger->debug('available process TASK ID:' . $index);
                $this->_processes[$index][static::STATUS_KEY] = false;
                $this->_busy++;

                return $this->_processes[$index];
            }
            $this->logger->debug('temporarily no idle process,sleep:' . 10000);
            usleep(10000);// sleep 50 msec
        }
    }

    /**
     * 生成命令
     * @return string
     */
    private function buildCommand(): string
    {
        $prefix = empty($this->_prefix) ? ($_SERVER['_'] ?? static::DEFAULT_INTERPRETER) : $this->_prefix;

        return "{$prefix} " . $_SERVER['PHP_SELF'] . ' -' . static::PROCESS_TYPE_KEY . static::PROCESS_SLAVE;
    }

    /**
     * 获取进程状态
     * @param $process
     *  command  string 传入 proc_open() 函数的命令行字符串。
     *  pid      int    进程 ID
     *  running  bool   TRUE 表示进程还在运行中， FALSE 表示进程已经终止
     *  signaled bool   TRUE 表示子进程被未捕获的信号所终止。 在 Windows 平台永远为 FALSE。
     *  stopped  bool   TRUE 表示子进程被信号停止。 在 Windows 平台永远为 FALSE。
     *  exitcode int    进程的退出码（仅在 running 为 FALSE 时有意义）。 仅在第一次调用此函数时会返回实际的值， 后续的调用将返回 -1。
     *  termsig  int    导致子进程终止执行的信号值 （仅在 signaled 为 TRUE 时有意义）。
     *  stopsig  int    导致子进程停止执行的信号值 （仅在 stopped 为 TRUE 时有意义）。
     * @return array|bool
     */
    public function getProcessStatus($process)
    {
        return proc_get_status($process);
    }

    /**
     * 获取进程ID
     * @param $process
     * @return mixed
     */
    public function getPid($process)
    {
        if ($process) {
            return $this->getProcessStatus($process)[static::PID_KEY];
        }

        return null;
    }

    /**
     * 进程是否正在运行
     * @param $process
     * @return bool
     */
    public function isRunning($process): bool
    {
        return (bool)proc_get_status($process)['running'];
    }

    /**
     * 监测进程是否已经结束
     * @param $process
     * @return bool
     */
    public function isFinished($process): bool
    {
        return !proc_get_status($process)['running'];
    }

    /**
     * 获取进程输出结果（仅在进程结束后才允许获取）
     * @param $process
     * @return bool|string
     * @throws \Exception
     */
    public function getOutput($process)
    {
        if (!$this->isFinished($process)) {
            throw new \RuntimeException("Cannot get output for running process");
        }

        return stream_get_contents($process[static::PIPES_KEY][1]);
    }

    /**
     * 获取进程错误输出结果（仅在进程结束后才允许获取）
     * @param $process
     * @return bool|string
     * @throws \Exception
     */
    public function getErrorOutput($process)
    {
        if (!$this->isFinished($process)) {
            throw new \RuntimeException("Cannot get error output for running process");
        }

        return stream_get_contents($process[static::PIPES_KEY][2]) ?: 'no error output';
    }

}
