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
        RequestUtil::getActor($request)->assertCan('readLogfiles');

        $logDir = $this->paths->storage.'/logs';

        if (! file_exists($logDir.DIRECTORY_SEPARATOR.$fileName)) {
            throw new RouteNotFoundException();
        }

        return LogFile::find($fileName, $logDir, true);
    }
}
