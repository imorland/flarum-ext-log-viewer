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
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class LogFile
{
    public $id;

    public $fileName;

    public $fullPath;

    public $size;

    public $modified;

    public $content;

    public static function build(SplFileInfo $file, bool $withContent = false): self
    {
        $logFile = new self();

        $logFile->id = Str::slug($file->getFilename());
        $logFile->fileName = $file->getFilename();
        $logFile->fullPath = $file->getRealPath();
        $logFile->size = $file->getSize();
        $logFile->modified = Carbon::parse($file->getMTime());

        if ($withContent) {
            $logFile->content = $file->getContents();
        }

        return $logFile;
    }

    public static function find(string $fileName, string $path, bool $withContent = false): self
    {
        /** @var Finder $finder */
        $finder = resolve(Finder::class);
        $finder->files()
            ->in($path)
            ->name($fileName);

        if (! $finder->hasResults()) {
            throw new \RuntimeException('Log file not found.');
        }

        foreach ($finder as $file) {
            return self::build($file, $withContent);
        }
    }
}
