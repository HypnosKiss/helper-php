<?php
/**
 * Created by Administrator PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>${CARET}
 * Time: 2025/5/28 16:51:11
 */

namespace Sweeper\HelperPhp\Test;

use PHPUnit\Framework\TestCase;

use function Sweeper\HelperPhp\distribute_data_evenly;
use function Sweeper\HelperPhp\weighted_distribute;

class HelperTest extends TestCase
{

    /**
     * 测试多维数据平均分配算法
     * Author: Sweeper <wili.lixiang@gmail.com>
     * Time: 2025/1/15 10:20:00
     * @return void
     */
    public function testDistributeAlgorithm(): void
    {
        // 测试数据
        $testData = [
            ['id' => 1, 'name' => 'Product A', 'category' => 'Electronics'],
            ['id' => 2, 'name' => 'Product B', 'category' => 'Clothing'],
            ['id' => 3, 'name' => 'Product C', 'category' => 'Books'],
            ['id' => 4, 'name' => 'Product D', 'category' => 'Electronics'],
            ['id' => 5, 'name' => 'Product E', 'category' => 'Sports'],
            ['id' => 6, 'name' => 'Product F', 'category' => 'Home'],
            ['id' => 7, 'name' => 'Product G', 'category' => 'Electronics'],
            ['id' => 8, 'name' => 'Product H', 'category' => 'Clothing'],
            ['id' => 9, 'name' => 'Product I', 'category' => 'Books'],
            ['id' => 10, 'name' => 'Product J', 'category' => 'Sports'],
        ];

        $groups = 3; // 分成3组

        // 测试轮询分配
        echo "=== 轮询分配策略 ===\n";
        $roundRobinResult = distribute_data_evenly($testData, $groups, 'round_robin');
        foreach ($roundRobinResult as $index => $group) {
            echo "Group $index (" . count($group) . ' items): ';
            echo implode(', ', array_column($group, 'name')) . "\n";
        }

        // 测试分块分配
        echo "\n=== 分块分配策略 ===\n";
        $chunkResult = distribute_data_evenly($testData, $groups, 'chunk');
        foreach ($chunkResult as $index => $group) {
            echo "Group $index (" . count($group) . ' items): ';
            echo implode(', ', array_column($group, 'name')) . "\n";
        }

        // 测试加权分配
        echo "\n=== 加权分配策略 ===\n";
        $weights        = [3, 2, 1]; // 组0:组1:组2 = 3:2:1
        $weightedResult = weighted_distribute($testData, $groups, $weights);
        foreach ($weightedResult as $index => $group) {
            echo "Group $index (" . count($group) . " items, weight: {$weights[$index]}): ";
            echo implode(', ', array_column($group, 'name')) . "\n";
        }

        $this->assertNotEmpty($testData, '测试数据不能为空');
    }

}