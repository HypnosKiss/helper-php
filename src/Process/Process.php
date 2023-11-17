<?php

namespace Sweeper\HelperPhp\Process;

/**
 * 进程类
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/25 22:29
 * @Package \Sweeper\HelperPhp\Process\Process
 */
class Process
{

    public const STDIN      = 0;

    public const STDOUT     = 1;

    public const STDERR     = 2;

    public const FLAG_READ  = 'r';

    public const FLAG_WRITE = 'w';

    /** @var string $command 待执行的命令 */
    protected $command;

    /** @var resource */
    protected $process;

    /** @var int current process id */
    private $pid;

    /** @var resource */
    protected $stdout;

    /** @var resource */
    protected $stderr;

    /** @var string */
    private $output;

    /** @var string */
    private $error_output;

    /** @var int */
    private $status_code;

    /** @var array */
    private $processInfo;

    /** @var float 进程开始时间 */
    private $startTime;

    /** @var int 超时时间 */
    private $timeout;

    /** @var array */
    private $options = ['suppress_errors' => false, 'bypass_shell' => true];

    /**
     * @param string      $command    程序运行命令行
     * @param string|null $stdInInput 标准输入
     */
    public function __construct(string $command, string $cwd = null, array $env = null, string $stdInInput = null, float $timeout = 0)
    {
        if (!\function_exists('proc_open')) {
            throw new \LogicException('The Process class relies on proc_open, which is not available on your PHP installation.');
        }
        $this->setStartTime(microtime(true))->setTimeout($timeout)->setCommand($command);
        $descriptors   = [
            self::STDIN  => ['pipe', self::FLAG_READ],
            self::STDOUT => ['pipe', self::FLAG_WRITE],
            self::STDERR => ['pipe', self::FLAG_WRITE],
        ];
        $pipes         = [];
        $this->process = proc_open($this->command, $descriptors, $pipes, $cwd, $env, $this->getOptions());

        //set no blocking for IO
        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);

        $this->pid = getmypid();
        if ($this->process === false || $this->process === null) {
            throw new \RunTimeException("Cannot create new process: $this->command");
        }
        if (!\is_resource($this->process)) {
            throw new \RuntimeException('Unable to launch a new process.');
        }
        [$stdin, $this->stdout, $this->stderr] = $pipes;
        if ($stdInInput) {
            fwrite($stdin, $stdInInput);
        }
        fclose($stdin);
        $this->updateStatus()->checkTimeout();
    }

    /**
     * 获取进程ID
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/25 22:37
     * @return mixed|null
     */
    public function getProcessPid()
    {
        if ($this->process) {
            $this->updateStatus();

            return $this->getProcessSpecifyInfo('pid');
        }

        return null;
    }

    /**
     * 关闭进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/25 22:43
     * @return void
     */
    public function close(): void
    {
        if (!$this->isFinished()) {
            proc_close($this->process);
        }
    }

    /**
     * 终结进程
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/25 22:43
     * @return void
     */
    public function terminate(): void
    {
        if (!$this->isFinished()) {
            proc_terminate($this->process);
        }
    }

    /**
     * 进程是否正在运行
     * @return bool
     */
    public function isRunning(): bool
    {
        if ($this->status_code !== null) {
            return false;
        }
        $this->updateStatus();
        if ($this->getProcessSpecifyInfo('running')) {
            return true;
        }

        return false;
    }

    /**
     * 监测进程是否已经结束
     * @return bool
     */
    public function isFinished(): bool
    {
        if ($this->status_code !== null) {
            return true;
        }
        $this->updateStatus();
        if ($this->processInfo['running']) {
            return false;
        }

        if ($this->status_code === null) {
            $this->status_code = (int)$this->processInfo['exitcode'];
        }

        // Process outputs
        $this->output = stream_get_contents($this->stdout);
        fclose($this->stdout);
        $this->error_output = stream_get_contents($this->stderr);
        fclose($this->stderr);
        $statusCode = proc_close($this->process);
        if ($this->status_code === null) {
            $this->status_code = $statusCode;
        }
        $this->process = null;

        return true;
    }

    /**
     * 阻塞等待子进程结束
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/25 22:45
     * @return void
     */
    public function waitForFinish(): void
    {
        while (!$this->isFinished()) {
            usleep(100);
        }
    }

    /**
     * 获取进程输出结果（仅在进程结束后才允许获取）
     * @return string
     * @throws \RunTimeException
     */
    public function getOutput(): string
    {
        if (!$this->isFinished()) {
            throw new \RunTimeException("Cannot get output for running process");
        }

        return $this->output;
    }

    /**
     * 获取进程错误输出结果（仅在进程结束后才允许获取）
     * @return string
     * @throws \RunTimeException
     */
    public function getErrorOutput(): string
    {
        if (!$this->isFinished()) {
            throw new \RunTimeException("Cannot get error output for running process");
        }

        return $this->error_output ?: 'no error output';
    }

    /**
     * 获取进程状态码
     * @return int
     * @throws \RunTimeException
     */
    public function getStatusCode(): int
    {
        if (!$this->isFinished()) {
            throw new \RunTimeException("Cannot get status code for running process");
        }

        return $this->status_code;
    }

    /**
     * @return bool
     */
    public function isFail(): bool
    {
        return $this->getStatusCode() === 1;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @return false|resource
     */
    public function getProcess(): bool
    {
        return $this->process;
    }

    /**
     * @param false|resource $process
     */
    public function setProcess(bool $process): self
    {
        $this->process = $process;

        return $this;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setPid(int $pid): self
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * @return mixed|resource
     */
    public function getStdout()
    {
        return $this->stdout;
    }

    /**
     * @param mixed|resource $stdout
     */
    public function setStdout($stdout): self
    {
        $this->stdout = $stdout;

        return $this;
    }

    /**
     * @return mixed|resource
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     * @param mixed|resource $stderr
     */
    public function setStderr($stderr): self
    {
        $this->stderr = $stderr;

        return $this;
    }

    public function setOutput(string $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function setErrorOutput(string $error_output): self
    {
        $this->error_output = $error_output;

        return $this;
    }

    public function setStatusCode(int $status_code): self
    {
        $this->status_code = $status_code;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 17:06
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options): self
    {
        if ($this->isRunning()) {
            throw new \RuntimeException('Setting options while the process is running is not possible.');
        }

        $defaultOptions  = $this->options;
        $existingOptions = ['blocking_pipes', 'create_process_group', 'create_new_console'];

        foreach ($options as $key => $value) {
            if (!\in_array($key, $existingOptions, true)) {
                $this->options = $defaultOptions;
                throw new \LogicException(sprintf('Invalid option "%s" passed to "%s()". Supported options are "%s".', $key, __METHOD__, implode('", "', $existingOptions)));
            }
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 17:06
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 17:05
     * @param int $timeout
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 17:05
     * @return float
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 17:06
     * @param float $startTime
     * @return $this
     */
    public function setStartTime(float $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * 获取进程指定信息
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 17:42
     * @param string $key
     * @return mixed|null
     */
    public function getProcessSpecifyInfo(string $key)
    {
        return $this->processInfo[$key] ?? null;
    }

    /**
     * Updates the status of the process
     * @doc https://www.php.net/manual/zh/function.proc-get-status.php
     * proc_get_status
     * command    string 传入 proc_open() 函数的命令行字符串。
     * pid        int    进程 ID
     * running    bool   true 表示进程还在运行中， false 表示进程已经终止
     * signaled   bool   true 表示子进程被未捕获的信号所终止。 在 Windows 平台永远为 false。
     * stopped    bool   true 表示子进程被信号停止。 在 Windows 平台永远为 false。
     * exitcode   int    进程的退出码（仅在 running 为 false 时有意义）。 仅在第一次调用此函数时会返回实际的值， 后续的调用将返回 -1。
     * termsig    int    导致子进程终止执行的信号值 （仅在 signaled 为 true 时有意义）。
     * stopsig    int    导致子进程停止执行的信号值 （仅在 stopped 为 true 时有意义）。
     */
    protected function updateStatus(): self
    {
        $this->processInfo = proc_get_status($this->process);

        return $this;
    }

    /**
     * Waits for the process to terminate.
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 18:02
     * @return int
     */
    public function wait(): int
    {
        $this->updateStatus();
        do {
            $this->checkTimeout();
            $running = $this->isRunning();
        } while ($running);

        while ($this->isRunning()) {
            $this->checkTimeout();
            usleep(1000);
        }

        $this->status_code = (int)$this->processInfo['exitcode'];

        return $this->status_code;
    }

    /**
     * Waits for the process to terminate.
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 18:06
     * @return bool
     */
    public function waitUntil(): bool
    {
        $this->updateStatus();
        while (true) {
            $this->checkTimeout();
            $running = $this->isRunning();
            if (!$running) {
                return false;
            }
            usleep(1000);
        }
    }

    /**
     * 超时检测
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 17:56
     * @return void
     */
    public function checkTimeout(): void
    {
        if ($this->getTimeout() && $this->getTimeout() < (microtime(true) - $this->getStartTime())) {
            $this->stop(0);

            throw new \RuntimeException(sprintf('The process "%s" exceeded the timeout of %s seconds.', $this->getCommand(), $this->getTimeout()));
        }
    }

    /**
     * Stops the process.
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/11 18:14
     * @param int|float $timeout The timeout in seconds
     * @return int|null
     */
    public function stop(float $timeout = 10): ?int
    {
        $timeoutMicro = microtime(true) + $timeout;
        if ($this->isRunning()) {
            do {
                usleep(1000);
            } while ($this->isRunning() && microtime(true) < $timeoutMicro);

            if ($this->isRunning()) {
                $this->terminate();
            }
        }

        if ($this->isRunning()) {
            if ($this->getProcessSpecifyInfo('pid')) {
                return $this->stop(0);
            }
            $this->close();
        }

        $this->status_code = (int)$this->processInfo['exitcode'];

        return $this->status_code;
    }

}