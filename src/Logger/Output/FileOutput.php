<?php

namespace Sweeper\HelperPhp\Logger\Output;

/**
 * File output
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/10/21 23:22
 * @Package \Sweeper\HelperPhp\Logger\Output\FileOutput
 */
class FileOutput extends CommonAbstract
{

    private $file;

    private $separatorBetweenContext;

    private $fileFp;

    private $format = '%Y-%m-%d %H:%i:%s {id} [ {level} ] {message}';

    /**
     * constructor options
     * @param string|null $logFile                 log file name, default using logger tmp file
     * @param bool        $separatorBetweenContext insert blank line after each context
     */
    public function __construct(string $logFile = null, bool $separatorBetweenContext = true, $format = '')
    {
        $logFile                       = $logFile ?: sys_get_temp_dir() . '/logger.' . date('Ymd') . '.log';
        $this->separatorBetweenContext = $separatorBetweenContext;
        $this->setFile($logFile);
        $this->setFormat($format ?: $this->format);
    }

    /**
     * insert file separator after context
     */
    public function __destruct()
    {
        if ($this->fileFp && $this->separatorBetweenContext) {
            fwrite($this->fileFp, PHP_EOL);
            fclose($this->fileFp);
            $this->fileFp = null;
        }
    }

    /**
     * set log file
     * @param string $logFile log file path
     * @return $this
     */
    public function setFile(string $logFile): self
    {
        if (is_callable($logFile)) {
            $logFile = $logFile();
        }
        $dir = dirname($logFile);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        $this->file = $logFile;

        return $this;
    }

    /**
     * set log format
     * @param $format
     * @return $this
     */
    public function setFormat($format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * do log
     * @param array  $messages
     * @param string $level
     * @param string $loggerId
     * @param array  $traceInfo
     */
    public function output(array $messages, string $level, string $loggerId, array $traceInfo)
    {
        $str = str_replace(['{id}', '{level}', '{message}'], [$loggerId, $level, static::combineMessages($messages)], $this->format);
        $str = preg_replace_callback('/(%\w)/', function($matches) { return date(str_replace('%', '', $matches[1])); }, $str);
        if (!$this->fileFp) {
            $this->fileFp = fopen($this->file, 'ab+');
        }
        fwrite($this->fileFp, $str . PHP_EOL);
    }

}
