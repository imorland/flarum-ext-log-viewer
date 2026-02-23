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

class ListLogFileTest extends TestCase
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
                ['group_id' => 4, 'permission' => 'manageLogfiles'],
            ]
        ]);

        // Delete any existing log files before starting
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($logDir);
        foreach ($finder as $file) {
            unlink($file->getRealPath());
        }
    }

    /**
     * Encode a relative path as URL-safe base64 (matches FileListSerializer::encodeId).
     */
    protected function encodeId(string $relativePath): string
    {
        return rtrim(strtr(base64_encode($relativePath), '+/', '-_'), '=');
    }

    /**
     * @test
     */
    public function authorized_user_can_list_logfiles()
    {
        $this->app()->getContainer()->make('log')->info('hello, testing');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');
        $this->assertIsArray($json['data']);
        $this->assertEquals(1, count($data));
        $this->assertEquals('logs', Arr::get($data[0], 'type'));
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
        $this->app()->getContainer()->make('log')->info('my !!!content');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');
        $encodedId = Arr::get($data[0], 'id');

        $response = $this->send(
            $this->request('GET', "/api/logs/$encodedId", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');

        $this->assertIsArray($json['data']);
        $this->assertEquals('logs', Arr::get($data, 'type'));
        $this->assertStringContainsString('my !!!content', $data['attributes']['content']);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_get_logfile()
    {
        $this->app()->getContainer()->make('log')->info('my !!!content');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');
        $encodedId = Arr::get($data[0], 'id');

        $response = $this->send(
            $this->request('GET', "/api/logs/$encodedId", [
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
        $encodedId = $this->encodeId('idontexist.log');

        $response = $this->send(
            $this->request('GET', "/api/logs/$encodedId", [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function authorized_user_can_get_logfile_with_malformed_utf8()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $testLogFile = $logDir.'/test-malformed-utf8.log';

        $malformedContent = "Valid UTF-8 line\n";
        $malformedContent .= "Line with invalid UTF-8: \xFF\xFE\x80\x81\n";
        $malformedContent .= "Another valid line\n";
        $malformedContent .= "Mixed content: Hello \xC0\xAF World\n";

        file_put_contents($testLogFile, $malformedContent);

        $encodedId = $this->encodeId('test-malformed-utf8.log');

        $response = $this->send(
            $this->request('GET', "/api/logs/$encodedId", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');

        $this->assertIsArray($json['data']);
        $this->assertEquals('logs', Arr::get($data, 'type'));

        $content = $data['attributes']['content'];
        $this->assertStringContainsString('Valid UTF-8 line', $content);
        $this->assertStringContainsString('Another valid line', $content);
        $this->assertNotEmpty($content);

        unlink($testLogFile);
    }

    /**
     * @test
     */
    public function authorized_user_can_download_logfile()
    {
        $this->app()->getContainer()->make('log')->info('Download test content');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');
        $encodedId = Arr::get($data[0], 'id');
        $logFileName = Arr::get($data[0], 'attributes.fileName');

        $response = $this->send(
            $this->request('GET', '/api/logs/download/'.$encodedId, [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/octet-stream', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment', $response->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString($logFileName, $response->getHeaderLine('Content-Disposition'));

        $body = $response->getBody()->getContents();
        $this->assertStringContainsString('Download test content', $body);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_download_logfile()
    {
        $this->app()->getContainer()->make('log')->info('Test content');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3,
            ])
        );

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');
        $encodedId = Arr::get($data[0], 'id');

        $response = $this->send(
            $this->request('GET', '/api/logs/download/'.$encodedId, [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function download_nonexistent_file_returns_404()
    {
        $encodedId = $this->encodeId('nonexistent.log');

        $response = $this->send(
            $this->request('GET', '/api/logs/download/'.$encodedId, [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function authorized_user_can_delete_logfile()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $testLogFile = $logDir.'/test-delete.log';
        file_put_contents($testLogFile, 'This file will be deleted');

        $this->assertTrue(file_exists($testLogFile));

        $encodedId = $this->encodeId('test-delete.log');

        $response = $this->send(
            $this->request('DELETE', '/api/logs/'.$encodedId, [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertFalse(file_exists($testLogFile));
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_delete_logfile()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $testLogFile = $logDir.'/test-delete-unauthorized.log';
        file_put_contents($testLogFile, 'This file should not be deleted');

        $encodedId = $this->encodeId('test-delete-unauthorized.log');

        $response = $this->send(
            $this->request('DELETE', '/api/logs/'.$encodedId, [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertTrue(file_exists($testLogFile));

        unlink($testLogFile);
    }

    /**
     * @test
     */
    public function delete_nonexistent_file_returns_404()
    {
        $encodedId = $this->encodeId('nonexistent.log');

        $response = $this->send(
            $this->request('DELETE', '/api/logs/'.$encodedId, [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cannot_delete_file_outside_log_directory()
    {
        $encodedId = $this->encodeId('../../../etc/passwd');

        $response = $this->send(
            $this->request('DELETE', '/api/logs/'.$encodedId, [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cannot_download_file_outside_log_directory()
    {
        $encodedId = $this->encodeId('../../../etc/passwd');

        $response = $this->send(
            $this->request('GET', '/api/logs/download/'.$encodedId, [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function authorized_user_can_list_subdirectory_logfiles()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $subDir = $logDir.'/subdir';

        if (! is_dir($subDir)) {
            mkdir($subDir, 0777, true);
        }

        file_put_contents($subDir.'/sub-test.log', 'Subdirectory log content');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');
        $this->assertIsArray($data);

        $relativePaths = array_column(array_column($data, 'attributes'), 'relativePath');
        $this->assertContains('subdir/sub-test.log', $relativePaths);

        unlink($subDir.'/sub-test.log');
        rmdir($subDir);
    }

    /**
     * @test
     */
    public function authorized_user_can_view_subdirectory_logfile()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $subDir = $logDir.'/subdir';

        if (! is_dir($subDir)) {
            mkdir($subDir, 0777, true);
        }

        file_put_contents($subDir.'/sub-view.log', 'Subdirectory view content');

        $encodedId = $this->encodeId('subdir/sub-view.log');

        $response = $this->send(
            $this->request('GET', "/api/logs/$encodedId", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');
        $this->assertStringContainsString('Subdirectory view content', $data['attributes']['content']);

        unlink($subDir.'/sub-view.log');
        rmdir($subDir);
    }

    /**
     * @test
     */
    public function authorized_user_can_download_subdirectory_logfile()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $subDir = $logDir.'/subdir';

        if (! is_dir($subDir)) {
            mkdir($subDir, 0777, true);
        }

        file_put_contents($subDir.'/sub-download.log', 'Subdirectory download content');

        $encodedId = $this->encodeId('subdir/sub-download.log');

        $response = $this->send(
            $this->request('GET', "/api/logs/download/$encodedId", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Subdirectory download content', $response->getBody()->getContents());

        unlink($subDir.'/sub-download.log');
        rmdir($subDir);
    }

    /**
     * @test
     */
    public function authorized_user_can_delete_subdirectory_logfile()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $subDir = $logDir.'/subdir';

        if (! is_dir($subDir)) {
            mkdir($subDir, 0777, true);
        }

        $subFile = $subDir.'/sub-delete.log';
        file_put_contents($subFile, 'Will be deleted');

        $encodedId = $this->encodeId('subdir/sub-delete.log');

        $response = $this->send(
            $this->request('DELETE', "/api/logs/$encodedId", [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertFalse(file_exists($subFile));

        if (is_dir($subDir)) {
            rmdir($subDir);
        }
    }
}
