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
        // Encode the relative path as base64url (RFC 4648 §5) so that paths
        // containing '/' are safe to embed as a single URL path segment.
        return rtrim(strtr(base64_encode($model->relativePath), '+/', '-_'), '=');
    }

    /** Decode a base64url ID back to a relative path. */
    private function decodeId(string $id): string
    {
        return base64_decode(strtr($id, '-_', '+/'));
    }

    public function find(string $id, \Tobyz\JsonApiServer\Context $context): ?object
    {
        /** @var Context $context */
        $context->getActor()->assertCan('manageLogfiles');

        $logDir = $this->getLogDirectory($this->paths);

        try {
            return LogFile::find($this->decodeId($id), $logDir, true);
        } catch (\RuntimeException) {
            return null;
        }
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->can('manageLogfiles'),

            Endpoint\Show::make()
                ->authenticated()
                ->before(function (Context $context) {
                    $context->getActor()->assertCan('manageLogfiles');
                }),

            Endpoint\Delete::make()
                ->authenticated()
                ->before(function (Context $context) {
                    $context->getActor()->assertCan('manageLogfiles');
                }),

            // Custom download endpoint matching old route pattern
            Endpoint\Endpoint::make('download')
                ->route('GET', '/download/{id}')
                ->authenticated()
                ->before(function (Context $context) {
                    $context->getActor()->assertCan('manageLogfiles');
                })
                ->action(function (Context $context) {
                    $encodedId = $this->id($context);

                    if (! $encodedId) {
                        throw new RouteNotFoundException();
                    }

                    $relativePath = $this->decodeId($encodedId);
                    $logDir = $this->getLogDirectory($this->paths);
                    $realLogDir = realpath($logDir);
                    $realFilePath = realpath($logDir.DIRECTORY_SEPARATOR.$relativePath);

                    if (! $realFilePath || ! is_file($realFilePath)) {
                        throw new RouteNotFoundException();
                    }

                    // Security check: ensure the file is within the log directory
                    if (! $realLogDir || ! str_starts_with($realFilePath, $realLogDir.DIRECTORY_SEPARATOR)) {
                        throw new RouteNotFoundException();
                    }

                    return ['fileName' => basename($realFilePath), 'filePath' => $realFilePath];
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

            Schema\Str::make('relativePath')
                ->get(fn (LogFile $logFile) => $logFile->relativePath),

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
        $context->getActor()->assertCan('manageLogfiles');

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

        $realLogDir = realpath($logDir) ?: $logDir;

        foreach ($finder as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            $logFile = LogFile::build($file, false, $realLogDir);
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
        $context->getActor()->assertCan('manageLogfiles');

        $logDir = $this->getLogDirectory($this->paths);
        $realLogDir = realpath($logDir);
        $realFilePath = realpath($logDir.DIRECTORY_SEPARATOR.$model->relativePath);

        if (! $realFilePath || ! is_file($realFilePath)) {
            throw new RouteNotFoundException();
        }

        // Security check: ensure the file is within the log directory
        if (! $realLogDir || ! str_starts_with($realFilePath, $realLogDir.DIRECTORY_SEPARATOR)) {
            throw new RouteNotFoundException();
        }

        unlink($realFilePath);
    }
}
