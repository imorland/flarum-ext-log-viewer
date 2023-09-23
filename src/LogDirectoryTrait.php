<?php

namespace IanM\LogViewer;

use Flarum\Foundation\Paths;

trait LogDirectoryTrait
{
    public function getLogDirectory(Paths $paths): string
    {
        return $paths->storage . DIRECTORY_SEPARATOR . 'logs';
    }
}
