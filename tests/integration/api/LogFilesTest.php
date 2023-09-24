<?php

/*
 * This file is part of ianm/log-viewer.
 *
 * Copyright (c) 2022 IanM.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\LogViewer\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class LogFilesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('ianm-log-viewer');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1, 'last_seen_at' => Carbon::now()->subSecond()],
            ],
            'group_user' => [
                ['group_id' => 4, 'user_id' => 3],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'readLogfiles'],
                ['group_id' => 4, 'permission' => 'deleteLogfiles']
            ]
        ]);

        // Delete any existing log files before starting
        $this->clearLogFiles();
    }

    private function clearLogFiles()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage . '/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($logDir);
        foreach ($finder as $file) {
            unlink($file->getRealPath());
        }
    }

    private function createLogEntryAndGetFileName(string $content): string
    {
        $this->logInfoContent($content);

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3,
            ])
        );

        $json = json_decode($response->getBody()->getContents(), true);
        return Arr::get($json, 'data.0.attributes.fileName');
    }

    private function logInfoContent(string $string): void
    {
        $this->app()->getContainer()->make('log')->info($string);
    }

    private function getContents(ResponseInterface $response): mixed
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @test
     */
    public function authorized_user_can_list_logfiles()
    {
        $this->logInfoContent('hello, testing');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = $this->getContents($response);
        $data = Arr::get($json, 'data');
        $this->assertIsArray($json['data']);
        $this->assertEquals(1, count($data));
        $this->assertEquals('log', Arr::get($data[0], 'type'));
    }

    /**
     * @test
     */
    public function normal_user_cannot_list_logfiles()
    {
        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_user_cannot_list_logfiles()
    {
        $response = $this->send(
            $this->request('GET', '/api/logs')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function authorized_user_can_get_logfile()
    {
        $this->logInfoContent('my !!!content');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = $this->getContents($response);
        $data = Arr::get($json, 'data');
        $logFileName = Arr::get($data[0], 'attributes.fileName');

        $response = $this->send(
            $this->request('GET', "/api/logs/$logFileName", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $json = $this->getContents($response);
        $data = Arr::get($json, 'data');

        $this->assertIsArray($json['data']);
        $this->assertEquals('log', Arr::get($data, 'type'));
        $this->assertStringContainsString('my !!!content', $data['attributes']['content']);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_get_logfile()
    {
        $this->logInfoContent('my !!!content');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = $this->getContents($response);
        $data = Arr::get($json, 'data');
        $logFileName = Arr::get($data[0], 'id');

        $response = $this->send(
            $this->request('GET', "/api/logs/$logFileName", [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_get_logfile_not_existing()
    {
        $response = $this->send(
            $this->request('GET', '/api/logs/idontexist.log', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function authorized_user_can_download_logfile()
    {
        $logFileName = $this->createLogEntryAndGetFileName('content for download');

        $response = $this->send(
            $this->request('GET', "/api/logs/download/$logFileName", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('content for download', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function authorized_user_can_delete_logfile()
    {
        $logFileName = $this->createLogEntryAndGetFileName('content for delete');

        $response = $this->send(
            $this->request('DELETE', "/api/logs/$logFileName", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        // Check the file is indeed deleted
        $response = $this->send(
            $this->request('GET', "/api/logs/$logFileName", [
                'authenticatedAs' => 3,
            ])
        );
        $this->assertEquals(404, $response->getStatusCode()); // Not found
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_download_logfile()
    {
        $logFileName = $this->createLogEntryAndGetFileName('content');

        $response = $this->send(
            $this->request('GET', "/api/logs/download/$logFileName", [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_delete_logfile()
    {
        $logFileName = $this->createLogEntryAndGetFileName('content');

        $response = $this->send(
            $this->request('DELETE', "/api/logs/$logFileName", [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cannot_download_nonexistent_logfile()
    {
        $response = $this->send(
            $this->request('GET', "/api/logs/download/idontexist.log", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cannot_delete_nonexistent_logfile()
    {
        $response = $this->send(
            $this->request('DELETE', "/api/logs/idontexist.log", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }
}
