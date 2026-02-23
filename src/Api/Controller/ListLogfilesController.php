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
use IanM\LogViewer\LogDirectoryTrait;
use IanM\LogViewer\Model\LogFile;
use Illuminate\Support\Collection;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Finder\Finder;
use Tobscure\JsonApi\Document;

class ListLogfilesController extends AbstractListController
{
    use LogDirectoryTrait;

    /**
     * @var Paths
     */
    protected $paths;

    public $serializer = FileListSerializer::class;

    public function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        RequestUtil::getActor($request)->assertCan('manageLogfiles');

        $logDir = $this->getLogDirectory($this->paths);

        $finder = new Finder();
        $finder->files()->in($logDir);

        $files = new Collection();
        foreach ($finder as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            $files->add(LogFile::build($file));
        }

        return $files->sortBy(function ($object) {
            return $object->modified;
        }, SORT_REGULAR, true);
    }
}
