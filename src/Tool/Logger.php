<?php

namespace Sweeper\HelperPhp\Tool;

use Sweeper\DesignPattern\Traits\MultiPattern;
use Sweeper\HelperPhp\Traits\LogTrait;

/**
 * Monolog Logger
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/19 15:20
 * @Package \Sweeper\HelperPhp\Tool\Logger
 */
class Logger
{

    use LogTrait, MultiPattern;

    /** @var string 日志目录 */
    public const LOG_DIR = '/webroot/logs/php';

    /**
     * 获取日志目录
     * Author: Sweeper <wili.lixiang@gmail.com>
     * Time: 2024/4/29 13:51:00
     * @return string
     */
    public static function getLogDirPath(): string
    {
        return static::LOG_DIR . DIRECTORY_SEPARATOR . date('Y-m-d');// 日志目录，以日期存储(按天分目录)
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @return string|null
     */
    public function getLogPath(): ?string
    {
        return $this->logPath ?? static::getLogDirPath();
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * Time: 2024/5/24 10:41:21
     * @return \Monolog\Logger
     */
    public function getLogger(): \Monolog\Logger
    {
        if (!($this->logger instanceof \Monolog\Logger) || $this->getLogPath() !== static::getLogDirPath()) {
            $this->setLogPath(static::getLogDirPath())->getDefaultLogger($this->getLoggerName(), $this->getFilename(), $this->getLogPath(), $this->isRegisterErrorHandler());
        }

        return $this->logger;
    }

}
