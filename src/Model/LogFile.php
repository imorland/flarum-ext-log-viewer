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

class LogFile
{
    public $id;

    public $fileName;

    public $fullPath;

    public $size;

    public $modified;

    public $content;

    public static function build(string $fileName, string $path, bool $withContent = false): LogFile
    {
        $file = new static();

        $file->id = $fileName;
        $file->fileName = $fileName;
        $file->fullPath = $path.DIRECTORY_SEPARATOR.$fileName;
        $file->size = filesize($file->fullPath);
        $file->modified = Carbon::parse(filemtime($file->fullPath));

        if ($withContent) {
            $file->content = file_get_contents($file->fullPath);
        }

        return $file;
    }
}
