<?php

/*
 * This file is part of ianm/log-viewer.
 *
 * Copyright (c) 2022 IanM.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\LogViewer\Console;

use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use IanM\LogViewer\LogDirectoryTrait;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CleanupLogfilesCommand extends Command
{
    use LogDirectoryTrait;

    protected $signature = 'logfiles:cleanup';
    protected $description = 'Deletes log files older than x days.';

    protected $settings;
    protected $filesystem;
    protected $paths;

    public function __construct(SettingsRepositoryInterface $settings, Filesystem $filesystem, Paths $paths)
    {
        parent::__construct();

        $this->settings = $settings;
        $this->filesystem = $filesystem;
        $this->paths = $paths;
    }

    public function handle()
    {
        $purgeDays = $this->getPurgeDays();

        if ($purgeDays <= 0) {
            $this->info('Log file cleanup is disabled. To enable, set a positive value for ianm-log-viewer.purge-days.');

            return;
        }

        $logFiles = $this->getOldLogFiles($purgeDays);

        if (! $logFiles->count()) {
            $this->info('No old log files found.');

            return;
        }

        $this->deleteLogFiles($logFiles, $purgeDays);
    }

    protected function getPurgeDays(): int
    {
        $purgeDays = $this->settings->get('ianm-log-viewer.purge-days');

        if (! is_numeric($purgeDays)) {
            return 90;  // Default for non-numeric values
        }

        if ((int) $purgeDays < 0) {
            return -1;  // Indicate disabled cleanup for negative values
        }

        return (int) $purgeDays;
    }

    protected function getOldLogFiles(int $purgeDays): Finder
    {
        $finder = new Finder();
        $finder->files()
            ->in($this->getLogDirectory($this->paths))
            ->date('< now - '.$purgeDays.' days');

        return $finder;
    }

    protected function deleteLogFiles(Finder $logFiles, int $purgeDays): void
    {
        $count = $logFiles->count();

        foreach ($logFiles as $file) {
            $this->filesystem->delete($file->getRealPath());
        }

        $this->info($count.' log files older than '.$purgeDays.' days have been deleted.');
    }
}
