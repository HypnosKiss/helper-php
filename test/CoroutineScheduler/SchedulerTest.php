<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2024/1/10 14:32
 */

namespace Sweeper\HelperPhp\Test\CoroutineScheduler;

use Sweeper\HelperPhp\CoroutineScheduler\Scheduler;
use PHPUnit\Framework\TestCase;

class SchedulerTest extends TestCase
{

    public function testCoroutineScheduler(): void
    {
        $scheduler = new Scheduler();
        $data      = [];
        $scheduler->newTask((static function() use (&$data) {
            var_dump('===== 任务1  =====' . time());
            foreach (range(1, 10) as $k) {
                $random                     = random_int(1, 3);
                $data["range-1-$k-$random"] = date('Y-m-d H:i:s');
                var_dump("range-1-$k-$random = " . time() . " ===== sleep($random)");
                sleep($random);
                yield;
            }
            yield;
        })());
        $scheduler->newTask((static function() use (&$data) {
            var_dump('===== 任务2  =====' . time());
            foreach (range(1, 6) as $m) {
                $random                     = random_int(1, 3);
                $data["range-2-$m-$random"] = date('Y-m-d H:i:s');
                var_dump("range-2-$m-$random = " . time() . " ===== sleep($random)");
                sleep($random);
                yield;
            }
            yield;
        })());

        $scheduler->run();

        dump($data);

        static::assertIsArray($data);
    }

}
