<?php

/*
 * This file is part of ianm/log-viewer.
 *
 * Copyright (c) 2022 IanM.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\LogViewer\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Resource\Contracts\Deletable;
use Flarum\Api\Resource\Contracts\Findable;
use Flarum\Api\Resource\Contracts\Listable;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\Paths;
use Flarum\Http\Exception\RouteNotFoundException;
use IanM\LogViewer\LogDirectoryTrait;
use IanM\LogViewer\Model\LogFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Symfony\Component\Finder\Finder;

/**
 * @extends Resource\AbstractResource<LogFile>
 */
class LogFileResource extends Resource\AbstractResource implements Findable, Listable, Deletable
{
    use LogDirectoryTrait;

    public function __construct(
        protected Paths $paths
    ) {
    }

    public function type(): string
    {
        return 'logs';
    }

    public function getId(object $model, \Tobyz\JsonApiServer\Context $context): string
    {
        // Use the actual filename as the ID for API compatibility
        return $model->fileName;
    }

    public function find(string $id, \Tobyz\JsonApiServer\Context $context): ?object
    {
        /** @var Context $context */
        $context->getActor()->assertCan('readLogfiles');

        $logDir = $this->getLogDirectory($this->paths);

        // The ID is the actual filename
        // Load with content for show endpoint
        try {
            return LogFile::find($id, $logDir, true);
        } catch (\RuntimeException) {
            return null;
        }
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->can('readLogfiles'),

            Endpoint\Show::make()
                ->authenticated()
                ->before(function (Context $context) {
                    $context->getActor()->assertCan('readLogfiles');
                }),

            Endpoint\Delete::make()
                ->authenticated()
                ->before(function (Context $context) {
                    $context->getActor()->assertCan('readLogfiles');
                }),

            // Custom download endpoint matching old route pattern
            Endpoint\Endpoint::make('download')
                ->route('GET', '/download/{id}')
                ->authenticated()
                ->before(function (Context $context) {
                    $context->getActor()->assertCan('readLogfiles');
                })
                ->action(function (Context $context) {
                    // Extract filename from the route path - it's in the 'id' part of the route
                    $fileName = $this->id($context);

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

                    return ['fileName' => $fileName, 'filePath' => $filePath];
                })
                ->response(function (Context $context, array $data) {
                    $stream = new Stream($data['filePath'], 'r');
                    $fileSize = filesize($data['filePath']);

                    $headers = [
                        'Content-Type' => 'application/octet-stream',
                        'Content-Disposition' => 'attachment; filename="'.basename($data['fileName']).'"',
                    ];

                    if ($fileSize !== false) {
                        $headers['Content-Length'] = (string) $fileSize;
                    }

                    return new Response($stream, 200, $headers);
                }),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('fileName')
                ->get(fn (LogFile $logFile) => $logFile->fileName),

            Schema\Str::make('fullPath')
                ->get(fn (LogFile $logFile) => $logFile->fullPath),

            Schema\Integer::make('size')
                ->get(fn (LogFile $logFile) => $logFile->size),

            Schema\DateTime::make('modified')
                ->get(fn (LogFile $logFile) => $logFile->modified),

            Schema\Str::make('content')
                ->get(fn (LogFile $logFile) => $logFile->content),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('modified')
                ->descendingAlias('newest')
                ->ascendingAlias('oldest'),
            SortColumn::make('fileName'),
            SortColumn::make('size'),
        ];
    }

    /**
     * Create a query object for the current request.
     */
    public function query(\Tobyz\JsonApiServer\Context $context): object
    {
        /** @var Context $context */
        $context->getActor()->assertCan('readLogfiles');

        // Return a simple object containing the query parameters
        return (object) [
            'context' => $context,
            'logDir' => $this->getLogDirectory($this->paths),
        ];
    }

    /**
     * Get results from the given query.
     */
    public function results(object $query, \Tobyz\JsonApiServer\Context $context): iterable
    {
        /** @var Context $context */
        $logDir = $query->logDir;

        $files = new Collection();
        $finder = new Finder();
        $finder->files()->in($logDir);

        foreach ($finder as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            $logFile = LogFile::build($file, false);
            $files->add($logFile);
        }

        // Apply sorting
        $queryParams = $context->request->getQueryParams();
        $sort = Arr::get($queryParams, 'sort', '-modified');
        $sortField = ltrim($sort, '-');
        $sortDirection = str_starts_with($sort, '-') ? 'desc' : 'asc';

        return $files->sortBy(function ($logFile) use ($sortField) {
            return match ($sortField) {
                'fileName' => $logFile->fileName,
                'size' => $logFile->size,
                'modified', 'newest', 'oldest' => $logFile->modified->timestamp,
                default => $logFile->modified->timestamp,
            };
        }, SORT_REGULAR, $sortDirection === 'desc');
    }

    /**
     * Filters that can be applied to the resource list.
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Resolve the sorts for this resource.
     */
    public function resolveSorts(): array
    {
        return $this->sorts();
    }

    /**
     * For Delete endpoint.
     */
    public function delete(object $model, \Tobyz\JsonApiServer\Context $context): void
    {
        /** @var Context $context */
        $context->getActor()->assertCan('readLogfiles');

        $fileName = basename($model->fileName);
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

        // Delete the file
        unlink($filePath);
    }
}
