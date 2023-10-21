<?php

namespace Sweeper\HelperPhp\Logger\Output;

/**
 * Class SummaryOutput
 * collect message and then flush them in specified time interval
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2022/12/19 11:34
 * @Path \app\common\lib\logger\src\output\SummaryOutput
 */
class SummaryOutput extends CommonAbstract
{

    /** @var callable */
    private $flusher;

    /** @var string */
    private $tmpFile;

    /** @var string */
    private $startTime;

    /** @var int */
    private $sendInterval;

    private $subject;

    /**
     * constructor options
     * @param callable $flusher
     * @param int      $sendInterval
     * @param string   $tmpFile
     */
    public function __construct(callable $flusher, int $sendInterval = 300, string $tmpFile = '')
    {
        $this->flusher = $flusher;
        if (!$tmpFile) {
            $tmp_fold = sys_get_temp_dir() . '/logger/';
            if (!mkdir($tmp_fold, 0755, true) && !is_dir($tmp_fold)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $tmp_fold));
            }
            $tmpFile = tempnam($tmp_fold, 'logger');
        }
        $this->setTemporalFile($tmpFile);
        $this->sendInterval = $sendInterval;
    }

    /**
     * set temporal file
     * User: Sweeper
     * Time: 2022/12/19 11:31
     * @param string $tmpFile
     * @return $this
     */
    public function setTemporalFile(string $tmpFile): self
    {
        $this->tmpFile = $tmpFile;
        if (is_file($tmpFile)) {
            $this->startTime = filemtime($tmpFile);
        }

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2022/12/19 11:31
     * @param bool $flush
     * @return bool
     */
    private function send(bool $flush = false): bool
    {
        if (!$this->tmpFile || !is_file($this->tmpFile)) {
            return false;
        }
        if (!$flush && filemtime($this->tmpFile) > (time() - $this->sendInterval)) {
            return false;
        }

        $content = file_get_contents($this->tmpFile);
        $content = trim($content);
        if (!$content) {
            unlink($this->tmpFile); //may be trigger by sometime ?

            return false;
        }
        $subject = $this->subject ?: 'Unknown Errors';
        call_user_func($this->flusher, $subject, $content);
        unlink($this->tmpFile);

        return true;
    }

    /**
     * insert file separator after context
     */
    public function __destruct()
    {
        $this->send(true);
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
        if (!$this->startTime) {
            $this->startTime = time();
        }
        $text          = static::formatAsText($messages, $level, $loggerId, $traceInfo);
        $this->subject = '[' . strtoupper($level) . '] ' . static::combineMessages($messages);
        file_put_contents($this->tmpFile, $text . PHP_EOL, FILE_APPEND);
    }

}
