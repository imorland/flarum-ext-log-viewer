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
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class LogFile
{
    public $id;

    public $fileName;

    public $fullPath;

    public $size;

    public $modified;

    public $content;

    public static function build(SplFileInfo $file, bool $withContent = false): LogFile
    {
        $logfile = new static();

        $logfile->id = $file->getFilename();
        $logfile->fileName = $file->getFilename();
        $logfile->fullPath = $file->getRealPath();
        $logfile->size = $file->getSize();
        $logfile->modified = Carbon::parse($file->getMTime());

        if ($withContent) {
            $logfile->content = file_get_contents($logfile->fullPath);
        }

        return $logfile;
    }

    public static function find(string $fileName, string $path, bool $withContent = false): LogFile
    {
        /** @var Finder $finder */
        $finder = resolve(Finder::class);
        $finder->files()->in($path)->name($fileName);

        foreach ($finder as $file) {
            return self::build($file, $withContent);
        }
    }
}
