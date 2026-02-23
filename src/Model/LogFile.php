<?php

/*
 * This file is part of ianm/log-viewer.
 *
 * Copyright (c) 2022 IanM.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\LogViewer\Model;

use Carbon\Carbon;
use Symfony\Component\Finder\SplFileInfo;

class LogFile
{
    public $fileName;

    public $relativePath;

    public $fullPath;

    public $size;

    public $modified;

    public $content;

    public static function build(SplFileInfo $file, bool $withContent = false): self
    {
        $logFile = new self();

        $logFile->fileName = $file->getFilename();
        $logFile->relativePath = $file->getRelativePathname();
        $logFile->fullPath = $file->getRealPath();
        $logFile->size = $file->getSize();
        $logFile->modified = Carbon::createFromTimestamp($file->getMTime());

        if ($withContent) {
            $content = $file->getContents();
            // Ensure content is valid UTF-8 for JSON encoding
            // Replace invalid UTF-8 sequences with the Unicode replacement character
            $logFile->content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        }

        return $logFile;
    }

    public static function find(string $relativePath, string $logDir, bool $withContent = false): self
    {
        $realLogDir = realpath($logDir);

        if (! $realLogDir) {
            throw new \RuntimeException('Log directory not found.');
        }

        $fullPath = realpath($realLogDir.DIRECTORY_SEPARATOR.$relativePath);

        // Security check: ensure the file is within the log directory
        if (! $fullPath || strpos($fullPath, $realLogDir.DIRECTORY_SEPARATOR) !== 0) {
            throw new \RuntimeException('Log file not found.');
        }

        if (! is_file($fullPath)) {
            throw new \RuntimeException('Log file not found.');
        }

        $relDir = ltrim(str_replace($realLogDir, '', dirname($fullPath)), DIRECTORY_SEPARATOR);
        $file = new SplFileInfo($fullPath, $relDir, $relativePath);

        return self::build($file, $withContent);
    }
}
