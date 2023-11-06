<?php

namespace Sweeper\HelperPhp\MultiProcess;

use Sweeper\HelperPhp\Process\Process;
use Sweeper\HelperPhp\Traits\LogTrait;

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/6 19:30
 * @Package \Sweeper\HelperPhp\MultiProcess\MultiProcessAbstract
 */
abstract class MultiProcessAbstract
{

    use LogTrait;

    /** @var int 子线程最大个数 */
    public $max_process_count = 1;

    /** @var int 检查子线程休眠时间(S) */
    public $check_children_process_sleep_second = 5;

    /** @var int 检查子线程个数休眠时间(s) */
    private $interval_second = 1;

    /** @var int 子线程未结束检查次数 */
    public $process_max_check_number = 20;

    /** @var Process[] $process_list 正在执行线程列表 */
    public $process_list = [];

    /** @var array 未正常结束的子进程检查线程列表 */
    private $process_check_list = [];

    /** @var int 脚本最早开始时间(24H) */
    private $start_hour = 21;

    /** @var int 脚本最晚开始时间(24H) */
    private $end_hour = 8;

    /** @var bool 是否开启debug */
    private $debug = false;

    /**
     * ProcessMaster constructor.
     * @param int $max_process_count
     */
    public function __construct($max_process_count = 1)
    {
        $this->max_process_count = $max_process_count;
    }

    /**
     * 执行入口
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/2 23:19
     * @return void
     */
    public function run(): void
    {
        for ($i = 0; $i < $this->max_process_count; $i++) {
            $this->executeTask();
        }
        $this->loop();
    }

    /**
     * 检查时间
     * @return bool
     */
    public function checkHour(): bool
    {
        $hour = date('H');
        if ($hour > $this->end_hour && $hour < $this->start_hour && !$this->debug) {
            $this->notice("Beyond execution time");

            return false;
        }

        return true;
    }

    /**
     * 生成子线程命令
     * @param string $cmd
     * @param array  $param
     * @return string
     */
    public function buildCommand(string $cmd, array $param = []): string
    {
        return "php " . $cmd . " " . http_build_query($param, null, ' ');
    }

    /**
     * 循环检查子线程执行
     */
    public function loop(): void
    {
        while (count($this->process_list) > 0) {
            foreach ($this->process_list as $index => $process) {
                if ($process->isFinished()) {
                    unset($this->process_list[$index], $this->process_check_list[$index]);
                    $this->executeTask();
                } elseif ($process->isRunning()) {
                    $check_number                     = $this->process_check_list[$index] ?? 0;
                    $this->process_check_list[$index] = ++$check_number;
                }
                if (isset($this->process_check_list[$index]) && $this->process_check_list[$index] > $this->process_max_check_number) {
                    sleep($this->check_children_process_sleep_second);
                    unset($this->process_list[$index], $this->process_check_list[$index]);
                    $this->error('The child process is about to be closed,Task Id:' . $index . ' CMD:' . $process->getCommand());
                    $process->close();
                    $this->executeTask();
                }
            }
            sleep($this->interval_second);
        }
    }

    /**
     * 执行任务
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/2 23:17
     * @return mixed
     */
    abstract public function executeTask();

}