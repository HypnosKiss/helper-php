<?php

namespace Sweeper\HelperPhp\Tool;

use ZipArchive;

/**
 * 目录压缩
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/27 23:03
 * @Path \Sweeper\HelperPhp\Tool\DirToZip
 */
class DirToZip
{

    /**
     * Zip a folder (including itself).
     * Usage:
     * Folder path that should be zipped.
     * @param $sourcePath string Relative path of directory to be zipped.
     * @param $outZipPath string Path of output zip file.
     * @return bool
     */
    public static function zipDir(string $sourcePath, string $outZipPath): bool
    {
        $pathInfo   = pathinfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName    = $pathInfo['basename'];

        $z = new ZipArchive();
        $z->open($outZipPath, ZipArchive::CREATE);
        $z->addEmptyDir($dirName);
        if ($sourcePath === $dirName) {
            static::dirToZip($sourcePath, $z, 0);
        } else {
            static::dirToZip($sourcePath, $z, strlen("$parentPath/"));
        }
        $z->close();

        return true;
    }

    /**
     * Add files and sub-directories in a folder to zip file.
     * @param $folder          string Folder path that should be zipped.
     * @param $zipFile         ZipArchive Zip file where files end up.
     * @param $exclusiveLength int Number of text to be excluded from the file path.
     */
    private static function dirToZip(string $folder, ZipArchive &$zipFile, int $exclusiveLength): void
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            // Check for local/parent path or zipping file itself and skip
            if ($f !== '.' && $f !== '..' && $f !== basename(__FILE__)) {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory
                    $zipFile->addEmptyDir($localPath);
                    static::dirToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

}

