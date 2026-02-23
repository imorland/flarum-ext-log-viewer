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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class LogFile
{
    public string $fileName;

    /** Relative path from the log directory root, e.g. "composer/output-2024-11-16.log". */
    public string $relativePath;

    public string $fullPath;

    public int $size;

    public Carbon $modified;

    public ?string $content = null;

    public static function build(SplFileInfo $file, bool $withContent = false, string $logDir = ''): self
    {
        $logFile = new self();

        $logFile->fileName = $file->getFilename();
        $logFile->relativePath = $logDir
            ? ltrim(str_replace($logDir, '', $file->getRealPath()), DIRECTORY_SEPARATOR)
            : $file->getRelativePathname();
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
        // Resolve the full path directly — no need to scan the directory.
        $fullPath = realpath($logDir.DIRECTORY_SEPARATOR.$relativePath);

        if (! $fullPath || ! is_file($fullPath)) {
            throw new \RuntimeException('Log file not found.');
        }

        // Security: ensure the resolved path is still inside the log directory.
        $realLogDir = realpath($logDir);
        if (! $realLogDir || ! str_starts_with($fullPath, $realLogDir.DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('Log file not found.');
        }

        $file = new SplFileInfo($fullPath, dirname($relativePath), $relativePath);

        return self::build($file, $withContent, $realLogDir);
    }
}
