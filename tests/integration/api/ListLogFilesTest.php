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
            ]
        ]);
    }

    /**
     * @test
     */
    public function authorized_user_can_list_logfiles()
    {
        $this->app()->getContainer()->make('log')->info('hello, testing');

        $response = $this->send(
            $this->request('GET', '/api/logs', [
                'authenticatedAs' => 1,
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
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $data = Arr::get($json, 'data');
        $logFileName = Arr::get($data[0], 'id');

        $response = $this->send(
            $this->request('GET', "/api/logs/$logFileName", [
                'authenticatedAs' => 1,
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
                'authenticatedAs' => 1,
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
}
