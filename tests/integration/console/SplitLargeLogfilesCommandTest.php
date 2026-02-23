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
use PHPUnit\Framework\Attributes\Test;

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

        // Ensure the log directory exists
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    public function tearDown(): void
    {
        $this->cleanupLogFiles();
        parent::tearDown();
    }

    #[Test]
    public function test_log_file_is_split_when_exceeding_limit()
    {
        $this->prepareLargeLogFile();
        $input = [
            'command' => 'logfiles:split-large'
        ];
        $this->runCommand($input);

        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

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
            'largeTest-part1.log',
            'largeTest-part2.log',
            'largeTest-part3.log'
        ];

        foreach ($filesToDelete as $filename) {
            $filePath = $logDir.'/'.$filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function test_resplitting_an_already_split_file_does_not_compound_part_names()
    {
        // Simulate a file that was previously split: largeTest-part3.log is still too large.
        // The command must produce largeTest-part3.log → largeTest-part4.log, largeTest-part5.log, …
        // NOT largeTest-part3-part4.log, largeTest-part3-part4-part5.log, …
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        $largeContent = Str::random(($this->maxFileSize * 1024 * 1024) * 2.5);
        file_put_contents($logDir.'/largeTest-part3.log', $largeContent);

        $input = ['command' => 'logfiles:split-large'];
        $this->runCommand($input);

        // Correct output: sequential part numbers, no compounding
        $this->assertFileExists($logDir.'/largeTest-part4.log');
        $this->assertFileExists($logDir.'/largeTest-part5.log');
        $this->assertFileExists($logDir.'/largeTest-part6.log');

        // Incorrect output must NOT exist
        $this->assertFileDoesNotExist($logDir.'/largeTest-part3-part4.log');
        $this->assertFileDoesNotExist($logDir.'/largeTest-part3-part4-part5.log');

        // Cleanup
        foreach (['largeTest-part4.log', 'largeTest-part5.log', 'largeTest-part6.log'] as $f) {
            @unlink($logDir.'/'.$f);
        }
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
