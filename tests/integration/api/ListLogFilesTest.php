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

    public $filename = 'myFileName.txt';
    public $path = __DIR__;
    public $content = 'my_test content --here';

    public function setUp(): void
    {
        parent::setUp();

        file_put_contents("$this->path/$this->filename", $this->content);

        $this->extension('ianm-log-viewer');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    public function tearDown(): void
    {
        unlink("$this->path/$this->filename");
    }

    /**
     * @test
     */
    public function authorized_user_can_list_logfiles()
    {
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
}
