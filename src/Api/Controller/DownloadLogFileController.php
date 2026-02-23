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
        RequestUtil::getActor($request)->assertCan('manageLogfiles');

        $encodedId = Arr::get($request->getQueryParams(), 'file');

        if (! $encodedId) {
            throw new RouteNotFoundException();
        }

        $relativePath = base64_decode(strtr($encodedId, '-_', '+/'));

        if ($relativePath === '') {
            throw new RouteNotFoundException();
        }

        $logDir = $this->getLogDirectory($this->paths);
        $realLogDir = realpath($logDir);

        if (! $realLogDir) {
            throw new RouteNotFoundException();
        }

        $realFilePath = realpath($realLogDir.DIRECTORY_SEPARATOR.$relativePath);

        // Security check: ensure the file is within the log directory
        if (! $realFilePath || strpos($realFilePath, $realLogDir.DIRECTORY_SEPARATOR) !== 0) {
            throw new RouteNotFoundException();
        }

        if (! is_file($realFilePath)) {
            throw new RouteNotFoundException();
        }

        $stream = new Stream($realFilePath, 'r');
        $fileSize = filesize($realFilePath);

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.basename($realFilePath).'"',
        ];

        if ($fileSize !== false) {
            $headers['Content-Length'] = (string) $fileSize;
        }

        return new Response($stream, 200, $headers);
    }
}
