<?php

namespace IanM\LogViewer\Api\Controller;

use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DownloadLogFileController implements RequestHandlerInterface
{
    use LogFileDirectory;

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
        
        // Sanitize the filename to prevent directory traversal
        $fileName = basename($fileName);

        $logDir = $this->getLogDirectoryOrThrow($request, $this->paths);
        $absoluteFilePath = $logDir.DIRECTORY_SEPARATOR.$fileName;

        // Ensure the resulting path is still within the log directory
        if (strpos($absoluteFilePath, $logDir) !== 0) {
            throw new \RuntimeException("Invalid file path");
        }

        if (! file_exists($absoluteFilePath)) {
            throw new ModelNotFoundException();
        }

        $fileStream = new Stream($absoluteFilePath, 'r');

        return (new Response())
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->withBody($fileStream);
    }
}
