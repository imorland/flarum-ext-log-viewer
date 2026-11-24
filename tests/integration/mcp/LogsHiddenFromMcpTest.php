<?php

/*
 * This file is part of ianm/log-viewer.
 *
 * Copyright (c) 2022 IanM.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\LogViewer\Tests\integration\mcp;

use Flarum\Mcp\Extend\Mcp;
use Flarum\Mcp\Testing\McpTestCase;
use PHPUnit\Framework\Attributes\Test;

class LogsHiddenFromMcpTest extends McpTestCase
{
    private const string LOG_FILE = 'log-viewer-mcp-test.log';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('ianm-log-viewer');
    }

    /**
     * Boots the app, so a test's own extenders must be registered before calling it.
     */
    private function writeLog(): void
    {
        $logDir = dirname($this->logPath());

        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents($this->logPath(), "secret log line\n");
    }

    protected function tearDown(): void
    {
        if (isset($this->app)) {
            @unlink($this->logPath());
        }

        parent::tearDown();
    }

    private function logPath(): string
    {
        return $this->app()->getContainer()->make('flarum.paths')->storage.'/logs/'.self::LOG_FILE;
    }

    /**
     * The hidden type is left out of the tool's `type` enum, so the call is refused as invalid params.
     */
    private function assertTypeRefused(array $body): void
    {
        $this->assertSame(-32602, $body['error']['code'] ?? null, json_encode($body));
        $this->assertStringContainsString("Property '/type'", $body['error']['message']);
    }

    private function logId(): string
    {
        return rtrim(strtr(base64_encode(self::LOG_FILE), '+/', '-_'), '=');
    }

    private function adminBearer(): array
    {
        return $this->bearer($this->developerToken(1));
    }

    #[Test]
    public function describe_api_does_not_offer_logs(): void
    {
        $this->writeLog();

        $types = array_column($this->toolOutput($this->callTool('describe_api', [], $this->adminBearer()))['types'], 'type');

        $this->assertNotEmpty($types);
        $this->assertNotContains('logs', $types);
    }

    #[Test]
    public function list_resources_refuses_logs(): void
    {
        $this->writeLog();

        $body = $this->mcpJson($this->callTool('list_resources', ['type' => 'logs'], $this->adminBearer()));

        $this->assertStringNotContainsString('secret log line', json_encode($body));
        $this->assertStringNotContainsString(self::LOG_FILE, json_encode($body));
        $this->assertTypeRefused($body);
    }

    #[Test]
    public function get_resource_refuses_logs(): void
    {
        $this->writeLog();

        $body = $this->mcpJson($this->callTool('get_resource', ['type' => 'logs', 'id' => $this->logId()], $this->adminBearer()));

        $this->assertStringNotContainsString('secret log line', json_encode($body));
        $this->assertTypeRefused($body);
    }

    #[Test]
    public function delete_resource_refuses_logs_even_when_writes_are_allowed(): void
    {
        // Writes are off by default, so opt in to show that hiding wins over it.
        $this->extend((new Mcp())->allowWrites('logs', ['delete']));
        $this->writeLog();

        $body = $this->mcpJson($this->callTool('delete_resource', ['type' => 'logs', 'id' => $this->logId()], $this->adminBearer()));

        $this->assertTypeRefused($body);
        $this->assertFileExists($this->logPath());
    }

    #[Test]
    public function the_rest_api_still_serves_logs_to_admins(): void
    {
        $this->writeLog();

        $response = $this->send($this->request('GET', '/api/logs/'.$this->logId(), ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("secret log line\n", json_decode((string) $response->getBody(), true)['data']['attributes']['content']);
    }
}
