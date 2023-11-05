<?php

namespace Sweeper\HelperPhp\Logger\Output;

use Sweeper\HelperPhp\Logger\LoggerLevel;

use function Sweeper\HelperPhp\Func\console_color;

/**
 * console output
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2022/12/19 11:27
 * @Path \app\common\lib\logger\src\output\ConsoleOutput
 */
class ConsoleOutput extends CommonAbstract
{

    private $colorless;

    private const LEVEL_COLORS = [
        LoggerLevel::DEBUG     => ['dark_gray', null],
        LoggerLevel::INFO      => ['white', null],
        LoggerLevel::NOTICE    => ['brown', null],
        LoggerLevel::WARNING   => ['yellow', null],
        LoggerLevel::ERROR     => ['red', null],
        LoggerLevel::CRITICAL  => ['purple', null],
        LoggerLevel::ALERT     => ['light_cyan', null],
        LoggerLevel::EMERGENCY => ['cyan', null],
    ];

    /**
     * ConsoleOutput constructor.
     * @param bool $colorless
     */
    public function __construct(bool $colorless = false)
    {
        $this->colorless = $colorless;
    }

    public function output(array $messages, string $level, string $loggerId, array $traceInfo)
    {
        $lvStr = strtoupper($level);
        if (!$this->colorless) {
            $lvStr = console_color($lvStr, static::LEVEL_COLORS[$level][0], static::LEVEL_COLORS[$level][1]);
        }
        echo static::formatAsText($messages, $lvStr, $loggerId, $traceInfo), PHP_EOL;
    }

}
