<?php

namespace IanM\LogViewer\Model;

use Carbon\Carbon;
use Flarum\Formatter\Formatter;

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
        $file->fullPath = $path . DIRECTORY_SEPARATOR . $fileName;
        $file->size = filesize($file->fullPath);
        $file->modified = Carbon::parse(filemtime($file->fullPath));
        
        if ($withContent) {
            /** @var Formatter $formatter */
            $formatter = resolve(Formatter::class);
            $file->content = $formatter->parse(file_get_contents($file->fullPath));
        }

        return $file;
    }
}
