<?php

namespace IanM\LogViewer\Tests\integration\api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

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

        $this->assertEquals(404, $response->getStatusCode());
    }

    
}
