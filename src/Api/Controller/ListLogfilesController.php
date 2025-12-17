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

/**
 * @TODO: Remove this in favor of one of the API resource classes that were added.
 *      Or extend an existing API Resource to add this to.
 *      Or use a vanilla RequestHandlerInterface controller.
 *      @link https://docs.flarum.org/2.x/extend/api#endpoints
 */
class ListLogfilesController extends AbstractListController
{
    use LogDirectoryTrait;

    public $serializer = FileListSerializer::class;

    public function __construct(protected Paths $paths, protected Finder $finder)
    {
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        RequestUtil::getActor($request)->assertCan('readLogfiles');

        $logDir = $this->getLogDirectory($this->paths);

        $files = new Collection();
        $this->finder->files()->in($logDir);
        foreach ($this->finder as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            $logfile = LogFile::build($file);

            $files->add($logfile);
        }

        return $files->sortBy(function ($object) {
            return $object->modified;
        }, SORT_REGULAR, true);
    }
}
