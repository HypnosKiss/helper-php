<?php

namespace Sweeper\HelperPhp\Tool;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * 文件监视器
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/27 23:43
 * @Path \Sweeper\HelperPhp\Tool\FileMonitor
 */
trait FileMonitor
{

    /**
     * 检测文件是否更改
     * @param string   $monitorDir 监控目录
     * @param string[] $extensions 需要检测的扩展名
     */
    public function checkFilesChange(string $monitorDir, array $extensions = ['php']): void
    {
        global $last_mtime;
        $last_mtime = $last_mtime ?? time();
        // recursive traversal directory
        $dirIterator = new RecursiveDirectoryIterator($monitorDir);
        $iterator    = new RecursiveIteratorIterator($dirIterator);
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            // only check $extensions files
            if (!in_array(pathinfo($file, PATHINFO_EXTENSION), $extensions, true)) {
                continue;
            }
            // check mtime
            if ($last_mtime < $file->getMTime()) {
                $this->onAfterChangeFile($file);
                $last_mtime = $file->getMTime();
                break;
            }
        }
    }

    /**
     * 更改文件后事件
     * @param $file
     */
    public function onAfterChangeFile($file): void
    {
        echo $file . " update and reload...\n";
        // send SIGUSR1 signal to master process for reload
        posix_kill(posix_getppid(), SIGUSR1);
    }

}