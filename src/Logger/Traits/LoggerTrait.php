<?php

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/16 18:46
 */

namespace Sweeper\HelperPhp\Logger\Traits;

use ReflectionClass;
use Sweeper\HelperPhp\Logger\Logger;
use Sweeper\HelperPhp\Logger\LoggerException;
use Sweeper\HelperPhp\Logger\LoggerLevel;
use Sweeper\HelperPhp\Logger\Output\ConsoleOutput;
use Sweeper\HelperPhp\Logger\Output\FileOutput;

!defined('WWW_PATH') && define('WWW_PATH', str_replace('＼＼', '/', dirname(__DIR__, 5) . '/'));  // 定义站点目录
!defined('APP_PATH') && define('APP_PATH', $_SERVER['DOCUMENT_ROOT'] ?: WWW_PATH);              // 定义应用目录

/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/21 23:43
 * @Package \Sweeper\HelperPhp\Logger\Traits\LoggerTrait
 * @mixin  \Sweeper\HelperPhp\Logger\Logger
 */
trait LoggerTrait
{

    /** @var \Sweeper\Logger\Logger */
    private $logger;

    /** @var string 日志记录器名字(ID) */
    private $loggerName;

    /** @var string 日志路径 */
    private $logPath;

    /** @var string 文件名 */
    private $filename;

    /** @var string 日志文件(包含路径) */
    private $logFile;

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @return string|null
     */
    public function getLoggerName(): ?string
    {
        return $this->loggerName;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:22
     * @param string $loggerName
     * @return $this
     */
    public function setLoggerName(string $loggerName): self
    {
        $this->loggerName = $loggerName;

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @return string|null
     */
    public function getLogPath(): ?string
    {
        return $this->logPath;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @param string $logPath
     * @return $this
     */
    public function setLogPath(string $logPath): self
    {
        $this->logPath = $logPath;

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * User: Sweeper
     * Time: 2023/8/16 19:23
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/15 22:21
     * @return string|null
     */
    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/15 22:21
     * @param string $logFile
     * @return $this
     */
    public function setLogFile(string $logFile): self
    {
        $this->logFile = $logFile;

        return $this;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/15 22:14
     * @return \Sweeper\Logger\Logger
     */
    public function getLogger(): Logger
    {
        if (!($this->logger instanceof Logger)) {
            $this->getDefaultLogger($this->getLoggerName(), $this->getFilename(), $this->getLogPath(), $this->getLogFile());
        }

        return $this->logger;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/15 22:16
     * @param \Sweeper\Logger\Logger $logger
     * @return $this
     */
    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * 默认日志记录器
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/15 22:18
     * @param string|null $name
     * @param string|null $filename
     * @param string|null $logPath
     * @param string|null $logFile
     * @return \Sweeper\HelperPhp\Logger\Logger
     */
    public function getDefaultLogger(string $name = null, string $filename = null, string $logPath = null, string $logFile = null): Logger
    {
        return $this->setLogger(static::getSpecificLogger($name ?? $this->getLoggerName(), $filename ?? $this->getFilename(), $logPath ?? $this->getLogPath(), $logFile ?? $this->getLogFile()))->getLogger();
    }

    /**
     * 获取指定 Logger
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/15 22:18
     * @param string|null $name
     * @param string|null $filename
     * @param string|null $logPath
     * @param string|null $logFile
     * @return \Sweeper\Logger\Logger
     */
    public static function getSpecificLogger(string $name = null, string $filename = null, string $logPath = null, string $logFile = null): Logger
    {
        $class    = new ReflectionClass(static::class);
        $name     = $name ?: $class->getName();
        $filename = $filename ?: $class->getShortName();
        $logPath  = $logPath ?: trim(APP_PATH, DIRECTORY_SEPARATOR) . implode(DIRECTORY_SEPARATOR, ['runtime', 'log']);
        $logFile  = $logFile ?: implode(DIRECTORY_SEPARATOR, [$logPath, date('Ymd'), "$filename.log"]);
        $logId    = $name;

        // 初始化日志记录器
        return Logger::instance($logId)
                     ->register(new ConsoleOutput(), LoggerLevel::DEBUG, false, md5("{$logId}-Console-Output"))
                     ->register(new FileOutput($logFile, true), LoggerLevel::INFO, false, md5("{$logId}-File-Output"));
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/15 22:12
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        // 优先调用自己方法
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }
        // 调用 Logger 方法
        $levelMethod = strtoupper($name);
        if (defined(LoggerLevel::class . "::$levelMethod")) {
            $level = constant(LoggerLevel::class . "::$levelMethod");

            return $this->getLogger()->trigger($level, ...$arguments);
        }
        // 调用父类
        if (is_callable([$this, $name])) {
            return parent::__call($name, $arguments);
        }

        throw new LoggerException('Method no exists:' . $name);
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/10/21 23:46
     * @param $level
     * @param ...$arguments
     * @return mixed
     */
    protected function log($level, ...$arguments)
    {
        return $this->getLogger()->{$level}(...$arguments);
    }

}
