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
    public string $id;

    public string $fileName;

    public string $fullPath;

    public int $size;

    public Carbon $modified;

    public ?string $content = null;

    public static function build(SplFileInfo $file, bool $withContent = false): self
    {
        $logFile = new self();

        $logFile->id = Str::slug($file->getFilename());
        $logFile->fileName = $file->getFilename();
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

        throw new \RuntimeException('Log file not found.');
    }
}
