<?php
/*
 *                                                     __----~~~~~~~~~~~------___
 *                                    .  .   ~~//====......          __--~ ~~
 *                    -.            \_|//     |||\\  ~~~~~~::::... /~
 *                 ___-==_       _-~o~  \/    |||  \\            _/~~-
 *         __---~~~.==~||\=_    -_--~/_-~|-   |\\   \\        _/~
 *     _-~~     .=~    |  \\-_    '-~7  /-   /  ||    \      /
 *   .~       .~       |   \\ -_    /  /-   /   ||      \   /
 *  /  ____  /         |     \\ ~-_/  /|- _/   .||       \ /
 *  |~~    ~~|--~~~~--_ \     ~==-/   | \~--===~~        .\
 *           '         ~-|      /|    |-~\~~       __--~~
 *                       |-~~-_/ |    |   ~\_   _-~            /\
 *                            /  \     \__   \/~                \__
 *                        _--~ _/ | .-~~____--~-/                  ~~==.
 *                       ((->/~   '.|||' -_|    ~~-/ ,              . _||
 *                                  -_     ~\      ~~---l__i__i__i--~~_/
 *                                  _-~-__   ~)  \--______________--~~
 *                                //.-~~~-~_--~- |-------~~~~~~~~
 *                                       //.-~~~--\
 *                       ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 *
 *                               神兽保佑            永无BUG
 *
 * @Author       : Sweeper <wili.lixiang@gmail.com>
 * @Date         : 2023-09-17 10:00:49
 * @LastEditors  : Sweeper <wili.lixiang@gmail.com>
 * @LastEditTime : 2023-10-07 21:55:51
 * @FilePath     : \php\sweeper\helper-php\test\LogTest.php
 * @Description  : 日志测试
 * @AuthorEmail  : wili.lixiang@gmail.com
 * Copyright (c) 2023 by ${git_name} email: ${git_email}, All Rights Reserved.
 */

use Sweeper\HelperPhp\Traits\LogTrait;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class Log
{
    use LogTrait;


    /**
     * 测试日志记录
     * @return void
     */
    public function testLog()
    {
        $this->setLogPath(__DIR__ . '/log')->setFilename('test');
        $this->info('test log message', ['todo' => 'some message']);
    }
}

$log = new Log();
$log->testLog();
