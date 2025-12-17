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
                ['group_id' => 4, 'permission' => 'readLogfiles'],
            ]
        ]);

        // Delete any existing log files before starting
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        // check the folder exists, if not, create it
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
        $logFileName = Arr::get($data[0], 'attributes.fileName');

        $response = $this->send(
            $this->request('GET', "/api/logs/$logFileName", [
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
    public function authorized_user_can_get_logfile_with_malformed_utf8()
    {
        // Create a log file with malformed UTF-8 characters
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $testLogFile = $logDir.'/test-malformed-utf8.log';

        // Write content with invalid UTF-8 byte sequences
        // \xFF is invalid in UTF-8, as are other sequences like \x80-\xBF without proper leading bytes
        $malformedContent = "Valid UTF-8 line\n";
        $malformedContent .= "Line with invalid UTF-8: \xFF\xFE\x80\x81\n";
        $malformedContent .= "Another valid line\n";
        $malformedContent .= "Mixed content: Hello \xC0\xAF World\n";

        file_put_contents($testLogFile, $malformedContent);

        // List log files to get the file name
        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');

        // Find our test log file
        $testLog = null;
        foreach ($data as $log) {
            if (Arr::get($log, 'attributes.fileName') === 'test-malformed-utf8.log') {
                $testLog = $log;
                break;
            }
        }

        $this->assertNotNull($testLog, 'Test log file should be found in the list');

        // Now fetch the log file content - this should NOT throw a JSON encoding exception
        $logFileName = Arr::get($testLog, 'attributes.fileName');
        $response = $this->send(
            $this->request('GET', "/api/logs/$logFileName", [
                'authenticatedAs' => 3,
            ])
        );

        // Should successfully return 200, not 500 with JSON encoding error
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');

        // Verify we got the content back
        $this->assertIsArray($json['data']);
        $this->assertEquals('logs', Arr::get($data, 'type'));

        $content = $data['attributes']['content'];

        // The content should contain the valid parts
        $this->assertStringContainsString('Valid UTF-8 line', $content);
        $this->assertStringContainsString('Another valid line', $content);

        // The malformed UTF-8 sequences should be replaced with replacement characters
        // and the response should be valid JSON (which we've already verified by decoding it)
        $this->assertNotEmpty($content);

        // Clean up
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
        $logFileName = Arr::get($data[0], 'attributes.fileName');

        // Test download endpoint
        $response = $this->send(
            $this->request('GET', '/api/logs/download/'.$logFileName, [
                'authenticatedAs' => 3,
            ])->withQueryParams(['file' => $logFileName])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/octet-stream', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment', $response->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString($logFileName, $response->getHeaderLine('Content-Disposition'));

        // Verify content is downloadable
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
        $logFileName = Arr::get($data[0], 'attributes.fileName');

        // Try to download as unauthorized user
        $response = $this->send(
            $this->request('GET', '/api/logs/download/'.$logFileName, [
                'authenticatedAs' => 2,
            ])->withQueryParams(['file' => $logFileName])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function download_nonexistent_file_returns_404()
    {
        $response = $this->send(
            $this->request('GET', '/api/logs/download/nonexistent.log', [
                'authenticatedAs' => 3,
            ])->withQueryParams(['file' => 'nonexistent.log'])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function authorized_user_can_delete_logfile()
    {
        // Create a test log file
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $testLogFile = $logDir.'/test-delete.log';
        file_put_contents($testLogFile, 'This file will be deleted');

        $this->assertTrue(file_exists($testLogFile));

        // Delete the file
        $response = $this->send(
            $this->request('DELETE', '/api/logs/test-delete.log', [
                'authenticatedAs' => 3,
            ])->withQueryParams(['file' => 'test-delete.log'])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertFalse(file_exists($testLogFile));
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_delete_logfile()
    {
        // Create a test log file
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $testLogFile = $logDir.'/test-delete-unauthorized.log';
        file_put_contents($testLogFile, 'This file should not be deleted');

        // Try to delete as unauthorized user
        $response = $this->send(
            $this->request('DELETE', '/api/logs/test-delete-unauthorized.log', [
                'authenticatedAs' => 2,
            ])->withQueryParams(['file' => 'test-delete-unauthorized.log'])
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertTrue(file_exists($testLogFile));

        // Clean up
        unlink($testLogFile);
    }

    /**
     * @test
     */
    public function delete_nonexistent_file_returns_404()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/logs/nonexistent.log', [
                'authenticatedAs' => 3,
            ])->withQueryParams(['file' => 'nonexistent.log'])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cannot_delete_file_outside_log_directory()
    {
        // Try path traversal attack
        $response = $this->send(
            $this->request('DELETE', '/api/logs/../../../etc/passwd', [
                'authenticatedAs' => 3,
            ])->withQueryParams(['file' => '../../../etc/passwd'])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cannot_download_file_outside_log_directory()
    {
        // Try path traversal attack
        $response = $this->send(
            $this->request('GET', '/api/logs/download/../../../etc/passwd', [
                'authenticatedAs' => 3,
            ])->withQueryParams(['file' => '../../../etc/passwd'])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }
}
