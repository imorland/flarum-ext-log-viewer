<?php

namespace IanM\LogViewer\Api\Controller;

use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;

trait LogFileDirectory
{
    protected function getLogDirectoryOrThrow(ServerRequestInterface $request, Paths $paths): string
    {
        RequestUtil::getActor($request)->assertCan('readLogfiles');

        $logDir = $this->getLogDirectory($paths);

        // Ensure the resulting path is still within the desired directory
        if (strpos(realpath($logDir), realpath($paths->base)) !== 0) {
            throw new \RuntimeException("Invalid log directory path");
        }

        return $logDir;
    }

    private function getLogDirectory(Paths $paths): string
    {
        return $paths->storage.DIRECTORY_SEPARATOR.'logs';
    }
}
