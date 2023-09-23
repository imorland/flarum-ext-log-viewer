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

use Carbon\Carbon;
use Flarum\Testing\integration\ConsoleTestCase;

class CleanupLogfilesCommandTest extends ConsoleTestCase
{
    protected $purgeDays = 30;  // Default to 30 days for testing

    public function setUp(): void
    {
        parent::setUp();
        $this->extension('ianm-log-viewer');
    }

    public function tearDown(): void
    {
        $this->cleanupLogFiles();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_old_log_files_are_deleted()
    {
        // Set purge days to our test value
        $this->updateSetting('ianm-log-viewer.purge-days', $this->purgeDays);

        // Create an old log file
        $this->createLogFile('test.log', 40);  // 40 days ago

        $input = ['command' => 'logfiles:cleanup'];
        $output = $this->runCommand($input);

        $this->assertStringContainsString('1 log files older than 30 days have been deleted.', $output);
    }

    /**
     * @test
     */
    public function test_new_log_files_are_retained()
    {
        // Set purge days to our test value
        $this->updateSetting('ianm-log-viewer.purge-days', $this->purgeDays);

        // Create a new log file
        $this->createLogFile('new.log', 10);  // 10 days ago

        $input = ['command' => 'logfiles:cleanup'];
        $output = $this->runCommand($input);

        $this->assertStringContainsString('No old log files found.', $output);
    }

    /**
     * @test
     */
    public function test_cleanup_is_disabled_with_non_positive_purge_days()
    {
        // Set purge days to 0 (disabled)
        $this->updateSetting('ianm-log-viewer.purge-days', 0);

        $input = ['command' => 'logfiles:cleanup'];
        $output = $this->runCommand($input);

        $this->assertStringContainsString('Log file cleanup is disabled.', $output);
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

    protected function createLogFile(string $fileName, int $daysAgo)
    {
        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';
        $logFilePath = $logDir.'/'.$fileName;

        // Create the log file
        file_put_contents($logFilePath, Carbon::now()->addDays($daysAgo)->toDateString());

        // Set the file's modification time
        $timestamp = Carbon::now()->subDays($daysAgo)->timestamp;
        touch($logFilePath, $timestamp);
    }

    protected function cleanupLogFiles()
    {
        $paths = $this->app->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($logDir);
        foreach ($finder as $file) {
            unlink($file->getRealPath());
        }
    }

    /**
     * @test
     */
    public function test_correct_files_are_deleted_among_multiple_files()
    {
        $this->updateSetting('ianm-log-viewer.purge-days', $this->purgeDays);

        // Create an old log file and a new log file
        $this->createLogFile('oldFile.log', 40);  // 40 days ago
        $this->createLogFile('newFile.log', 10);  // 10 days ago

        $input = ['command' => 'logfiles:cleanup'];
        $this->runCommand($input);

        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        $this->assertFileDoesNotExist($logDir.'/oldFile.log');
        $this->assertFileExists($logDir.'/newFile.log');
    }

    /**
     * @test
     */
    public function test_file_on_the_cusp_is_retained()
    {
        $this->updateSetting('ianm-log-viewer.purge-days', $this->purgeDays);

        // Create a log file exactly 30 days old
        $this->createLogFile('cuspFile.log', 30);

        $input = ['command' => 'logfiles:cleanup'];
        $this->runCommand($input);

        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        $this->assertFileExists($logDir.'/cuspFile.log');
    }

    /**
     * @test
     */
    public function test_default_cleanup_value_when_setting_not_present()
    {
        // Don't set any value for 'ianm-log-viewer.purge-days'
        // Create a log file 91 days old (older than default)
        $this->createLogFile('defaultOldFile.log', 91);

        $input = ['command' => 'logfiles:cleanup'];
        $this->runCommand($input);

        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        $this->assertFileDoesNotExist($logDir.'/defaultOldFile.log');
    }

    /**
     * @test
     */
    public function test_negative_purge_days_value_disables_cleanup()
    {
        $this->updateSetting('ianm-log-viewer.purge-days', -30);

        // Create an old log file
        $this->createLogFile('negativeDaysFile.log', 40);  // 40 days ago

        $input = ['command' => 'logfiles:cleanup'];
        $output = $this->runCommand($input);

        $this->assertStringContainsString('Log file cleanup is disabled.', $output);
    }

    /**
     * @test
     */
    public function test_non_numeric_purge_days_value_uses_default()
    {
        $this->updateSetting('ianm-log-viewer.purge-days', 'abc');  // invalid value

        // Create a log file 91 days old (older than default)
        $this->createLogFile('nonNumericDaysFile.log', 91);

        $input = ['command' => 'logfiles:cleanup'];
        $this->runCommand($input);

        $paths = $this->app()->getContainer()->make('flarum.paths');
        $logDir = $paths->storage.'/logs';

        $this->assertFileDoesNotExist($logDir.'/nonNumericDaysFile.log');
    }
}
