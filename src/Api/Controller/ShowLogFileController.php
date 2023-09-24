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

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Foundation\Paths;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\RequestUtil;
use IanM\LogViewer\Api\Serializer\LogFileSerializer;
use IanM\LogViewer\Model\LogFile;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowLogFileController extends AbstractShowController
{
    use LogFileDirectory;

    public $serializer = LogFileSerializer::class;

    /**
     * @var Paths
     */
    protected $paths;

    public function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $fileName = Arr::get($request->getQueryParams(), 'file');

        // Sanitize the filename to prevent directory traversal
        $fileName = basename($fileName);

        RequestUtil::getActor($request)->assertCan('readLogfiles');

        $logDir = $this->getLogDirectoryOrThrow($request, $this->paths);
        $absoluteFilePath = $logDir.DIRECTORY_SEPARATOR.$fileName;

        // Ensure the resulting path is still within the log directory
        if (strpos($absoluteFilePath, $logDir) !== 0) {
            throw new \RuntimeException('Invalid file path');
        }

        if (! file_exists($absoluteFilePath)) {
            throw new RouteNotFoundException();
        }

        return LogFile::find($fileName, $logDir, true);
    }
}
