<?php

namespace Sweeper\HelperPhp\Traits;

!defined('SIGNAL_HANDLING') && define('SIGNAL_HANDLING', \extension_loaded("pcntl"));

if (SIGNAL_HANDLING && function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true); //开启异步信号处理
}

/**
 * 信号特性
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/21 17:46
 * @Path \Sweeper\HelperPhp\Signal\SignalTrait
 */
trait SignalTrait
{

    /**
     * 是 Windows 系统
     * User: Sweeper
     * Time: 2023/8/21 18:01
     * @return bool
     */
    public static function isWindows(): bool
    {
        return stripos(PHP_OS_FAMILY, 'win') !== false;
    }

    /**
     * 注册信号处理程序
     * User: Sweeper
     * Time: 2023/8/21 18:00
     * @param array $signals Linux 信号（信号编号）
     * @return void
     */
    protected function registerSignalHandler(array $signals = []): void
    {
        if (SIGNAL_HANDLING && function_exists('pcntl_signal')) {
            $signals = array_merge([SIGINT, SIGUSR1, SIGUSR2], $signals ?: []);
            foreach ($signals as $signal) {
                pcntl_signal($signal, [$this, 'handleSignal'], false);//信号处理器可以是用户创建的函数或方法的名字，也可以是系统常量 SIG_IGN（译注：忽略信号处理程序）或 SIG_DFL（默认信号处理程序）
            }
        }
    }

    /**
     * 信号调度
     * User: Sweeper
     * Time: 2023/8/21 18:01
     * @return void
     */
    protected function signalDispatch(): void
    {
        if (SIGNAL_HANDLING && function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * 处理信号
     * User: Sweeper
     * Time: 2023/8/21 17:52
     * @param int   $signal
     * @param mixed $signalInfo
     * @return void
     */
    public function handleSignal(int $signal, $signalInfo): void
    {
        switch ($signal) {
            case SIGINT://键盘输入中断命令，一般是CTRL+C 当用户想要中断进程时，INT 信号被进程的控制终端发送到进程 信号值 -> 2
                $this->signalInt();
                break;
            case SIGUSR1://发送到进程的 USR1 信号用于指示用户定义的条件 信号值 -> 10
                $this->signalUsr1();
                break;
            case SIGUSR2://发送到进程的 USR2 信号用于指示用户定义的条件 信号值 -> 12
                $this->signalUsr2();
                break;
            default:
                // 处理所有其他信号
                $this->signalHandler($signal, $signalInfo);
                break;
        }
    }

    /**
     * 当用户想要中断进程时，INT 信号被进程的控制终端发送到进程
     */
    abstract public function signalInt();

    /**
     * 发送到进程的 USR1 信号用于指示用户定义的条件
     */
    abstract public function signalUsr1();

    /**
     * 发送到进程的 USR2 信号用于指示用户定义的条件
     */
    abstract public function signalUsr2();

    /**
     * 信号处理器
     * User: Sweeper
     * Time: 2023/8/21 18:04
     * @return mixed
     */
    abstract public function signalHandler(int $signal, $signalInfo);

}