<?php

namespace IanM\LogViewer\Tests\unit;

use Flarum\Testing\unit\TestCase;
use IanM\LogViewer\Model\LogFile;

class ModelTest extends TestCase
{
    public $filename = 'myFileName.txt';
    public $path = __DIR__;
    public $content = 'my_test content --here';
    
    public function setUp(): void
    {
        parent::setUp();
        
        file_put_contents("$this->path/$this->filename", $this->content);
    }

    public function tearDown(): void
    {
        unlink("$this->path/$this->filename");
    }

    /**
     * @test
     */
    public function model_is_created_correctly_with_content()
    {
        $model = LogFile::build($this->filename, $this->path, true);

        $this->assertEquals($this->filename, $model->id);
        $this->assertEquals($this->filename, $model->fileName);
        $this->assertEquals("$this->path/$this->filename", $model->fullPath);
        $this->assertEquals(22, $model->size);
        $this->assertNotNull($model->modified);
        $this->assertEquals($this->content, $model->content);
    }

    /**
     * @test
     */
    public function model_is_created_correctly_without_content()
    {
        $model = LogFile::build($this->filename, $this->path);

        $this->assertEquals($this->filename, $model->id);
        $this->assertEquals($this->filename, $model->fileName);
        $this->assertEquals("$this->path/$this->filename", $model->fullPath);
        $this->assertEquals(22, $model->size);
        $this->assertNotNull($model->modified);
        $this->assertNull($model->content);
    }
}
