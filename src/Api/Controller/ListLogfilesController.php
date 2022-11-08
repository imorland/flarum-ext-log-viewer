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
use Psr\Http\Message\ServerRequestInterface;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tobscure\JsonApi\Document;

class ListLogfilesController extends AbstractListController
{
    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var Finder
     */
    protected $finder;

    public $serializer = FileListSerializer::class;

    public function __construct(Paths $paths, Finder $finder)
    {
        $this->paths = $paths;
        $this->finder = $finder;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        RequestUtil::getActor($request)->assertCan('readLogfiles');

        $logDir = $this->paths->storage.'/logs';

        $files = new Collection();
        $this->finder->files()->in($logDir);
        foreach ($this->finder as $file) {
            /** @var SplFileInfo $file */
            $logfile = LogFile::build($file);

            $files->add($logfile);
        }

        return $files->sortBy(function ($object) {
            return $object->modified;
        }, SORT_REGULAR, true);
    }
}
