<?php

/*
 * This file is part of ianm/log-viewer.
 *
 * Copyright (c) 2022 IanM.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\LogViewer\Api\Controller;

use Flarum\Foundation\Paths;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\RequestUtil;
use IanM\LogViewer\LogDirectoryTrait;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DownloadLogFileController implements RequestHandlerInterface
{
    use LogDirectoryTrait;

    /**
     * @var Paths
     */
    protected $paths;

    public function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertCan('readLogfiles');

        $fileName = Arr::get($request->getQueryParams(), 'file');

        if (! $fileName) {
            throw new RouteNotFoundException();
        }

        // Sanitize the filename to prevent directory traversal
        $fileName = basename($fileName);

        $logDir = $this->getLogDirectory($this->paths);
        $filePath = $logDir.DIRECTORY_SEPARATOR.$fileName;

        if (! file_exists($filePath) || ! is_file($filePath)) {
            throw new RouteNotFoundException();
        }

        // Security check: ensure the file is within the log directory
        $realLogDir = realpath($logDir);
        $realFilePath = realpath($filePath);

        if (! $realFilePath || strpos($realFilePath, $realLogDir) !== 0) {
            throw new RouteNotFoundException();
        }

        $stream = new Stream($filePath, 'r');
        $fileSize = filesize($filePath);

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.basename($fileName).'"',
        ];

        if ($fileSize !== false) {
            $headers['Content-Length'] = (string) $fileSize;
        }

        return new Response($stream, 200, $headers);
    }
}
