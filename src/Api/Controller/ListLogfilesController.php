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

use Flarum\Api\Controller\AbstractListController;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use IanM\LogViewer\Api\Serializer\FileListSerializer;
use IanM\LogViewer\Model\LogFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListLogfilesController extends AbstractListController
{
    public $serializer = FileListSerializer::class;

    public function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        RequestUtil::getActor($request)->assertPermission('readLogfiles');

        $logDir = $this->paths->storage.'/logs';

        $files = new Collection();
        if ($handle = opendir($logDir)) {
            while (false !== ($fileName = readdir($handle))) {
                if (Str::endsWith($fileName, '.log')) {
                    $file = LogFile::build($fileName, $logDir);

                    $files->add($file);
                }
            }
        }

        return $files->sortBy(function ($object) {
            return $object->modified;
        }, SORTDATE, true);
    }
}
