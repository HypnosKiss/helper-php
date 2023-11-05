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

    /**
     * @param string      $cmd        程序运行命令行
     * @param string|null $stdInInput 标准输入
     */
    public function __construct(string $cmd, string $stdInInput = null)
    {
        $this->command = $cmd;
        $descriptors   = [
            self::STDIN  => ['pipe', self::FLAG_READ],
            self::STDOUT => ['pipe', self::FLAG_WRITE],
            self::STDERR => ['pipe', self::FLAG_WRITE],
        ];
        $pipes         = [];
        $this->process = proc_open($this->command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);

        //set no blocking for IO
        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);

        $this->pid = getmypid();
        if ($this->process === false || $this->process === null) {
            throw new \RunTimeException("Cannot create new process: $this->command");
        }
        [$stdin, $this->stdout, $this->stderr] = $pipes;
        if ($stdInInput) {
            fwrite($stdin, $stdInInput);
        }
        fclose($stdin);
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
            return proc_get_status($this->process)['pid'];
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
        $status = proc_get_status($this->process);
        if ($status['running']) {
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
        $status = proc_get_status($this->process);
        if ($status['running']) {
            return false;
        }

        if ($this->status_code === null) {
            $this->status_code = (int)$status['exitcode'];
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
    public function setStderr($stderr): void
    {
        $this->stderr = $stderr;
    }

    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    public function setErrorOutput(string $error_output): void
    {
        $this->error_output = $error_output;
    }

    public function setStatusCode(int $status_code): void
    {
        $this->status_code = $status_code;
    }

}