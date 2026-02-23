<?php

/*
 * This file is part of ianm/log-viewer.
 *
 * Copyright (c) 2022 IanM.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\LogViewer\Tests\integration\console;

use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Support\Str;

class SplitLargeLogfilesCommandTest extends ConsoleTestCase
{
    protected $maxFileSize = 1;  // In MB
    protected $largeLogFileName = 'largeTest.log';

    public function setUp(): void
    {
        parent::setUp();
        $this->extension('ianm-log-viewer');
        $maxFileSize = $this->maxFileSize;
        $this->prepareDatabase([
            'settings' => [
                ['key' => 'ianm-log-viewer.max-file-size', 'value' => $maxFileSize],
            ]
        ]);
    }

    public function tearDown(): void
    {
        $this->cleanupLogFiles();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_log_file_is_split_when_exceeding_limit()
    {
        $this->prepareLargeLogFile();
        $input = [
            'command' => 'logfiles:split-large'
        ];
        $this->runCommand($input);

        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        // Original file should be deleted after splitting
        $this->assertFileDoesNotExist($logDir.'/'.$this->largeLogFileName);
        $this->assertFileExists($logDir.'/'.'largeTest-part1.log');
        $this->assertFileExists($logDir.'/'.'largeTest-part2.log');
        $this->assertFileExists($logDir.'/'.'largeTest-part3.log');

        $this->cleanupLogFiles();
    }

    protected function prepareLargeLogFile()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        // Create a large log file
        $largeContent = Str::random(($this->maxFileSize * 1024 * 1024) * 2.5);  // 2.5 times the max file size (in bytes) for testing
        file_put_contents($logDir.'/'.$this->largeLogFileName, $largeContent);
    }

    protected function cleanupLogFiles()
    {
        $paths = $this->app->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        $filesToDelete = [
            $this->largeLogFileName,
            'largeTest-part1.log',
            'largeTest-part2.log',
            'largeTest-part3.log',
        ];

        foreach ($filesToDelete as $filename) {
            $filePath = $logDir.'/'.$filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * @test
     */
    public function test_file_splitting_is_disabled_when_max_size_is_zero()
    {
        // Set max file size to 0 (disabled)
        $this->updateSetting('ianm-log-viewer.max-file-size', 0);

        $this->prepareLargeLogFile();
        $input = ['command' => 'logfiles:split-large'];
        $output = $this->runCommand($input);

        $this->assertStringContainsString('File splitting is disabled. No action taken.', $output);
        $this->cleanupLogFiles();
    }

    /**
     * @test
     */
    public function test_no_files_are_split_when_there_are_no_large_files()
    {
        // Create a small log file
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $smallContent = Str::random(1024);  // 1KB
        file_put_contents($logDir.'/'.$this->largeLogFileName, $smallContent);

        $input = ['command' => 'logfiles:split-large'];
        $output = $this->runCommand($input);

        $this->assertStringContainsString('No large log files found.', $output);
        $this->cleanupLogFiles();
    }

    /**
     * @test
     */
    public function test_invalid_or_negative_max_file_size_defaults_to_1MB()
    {
        // Set max file size to -5 (invalid)
        $this->updateSetting('ianm-log-viewer.max-file-size', -5);

        $this->prepareLargeLogFile();
        $input = ['command' => 'logfiles:split-large'];
        $this->runCommand($input);

        // Assert the file was split using the default 1MB size
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $this->assertFileExists($logDir.'/'.'largeTest-part1.log');
        $this->assertFileExists($logDir.'/'.'largeTest-part2.log');
        $this->assertFileExists($logDir.'/'.'largeTest-part3.log');

        $this->cleanupLogFiles();
    }

    /**
     * @test
     */
    public function test_resplitting_an_already_split_file_does_not_compound_part_names()
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        // Simulate an already-split file that is still too large
        $largeContent = Str::random(($this->maxFileSize * 1024 * 1024) * 2.5);
        file_put_contents($logDir.'/largeTest-part1.log', $largeContent);

        $input = ['command' => 'logfiles:split-large'];
        $this->runCommand($input);

        // Resulting files must be largeTest-part1.log, largeTest-part2.log, largeTest-part3.log
        // NOT largeTest-part1-part1.log etc.
        $this->assertFileExists($logDir.'/largeTest-part1.log');
        $this->assertFileExists($logDir.'/largeTest-part2.log');
        $this->assertFileExists($logDir.'/largeTest-part3.log');
        $this->assertFileDoesNotExist($logDir.'/largeTest-part1-part1.log');
        $this->assertFileDoesNotExist($logDir.'/largeTest-part1-part2.log');

        $this->cleanupLogFiles();
    }

    protected function updateSetting($key, $value)
    {
        $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    $key => $value,
                ],
            ])
        );
    }
}
