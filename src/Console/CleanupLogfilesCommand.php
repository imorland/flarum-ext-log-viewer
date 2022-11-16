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

use Carbon\Carbon;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class CleanupLogfilesCommand extends Command
{
    protected $signature = 'logfiles:cleanup';
    protected $description = 'Deletes logfiles older than {configured|90} days';

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(Paths $paths, Finder $finder, SettingsRepositoryInterface $settings)
    {
        parent::__construct();

        $this->paths = $paths;
        $this->finder = $finder;
        $this->settings = $settings;
    }

    public function handle(): void
    {
        $logDir = $this->paths->storage.'/logs';

        $days = $this->settings->get('ianm-log-viewer.purge-days');

        if (! is_numeric($days)) {
            $days = 90;
        }

        $searchDate = Carbon::now()->subDays($days);

        $this->finder->files()->in($logDir)->date("< {$searchDate->toDateTimeString()}");

        $this->info("Detected {$this->finder->count()} files in {$logDir} older than {$searchDate->toDateTimeString()}");

        if ($this->finder->count() === 0) {
            return;
        }

        $this->output->progressStart($this->finder->count());

        $deleted = [];

        foreach ($this->finder as $file) {
            /** @var SplFileInfo $file */
            $path = $file->getRealPath();
            $deleted[] = $path;

            unlink($path);

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        foreach ($deleted as $path) {
            $this->info("Deleted {$path}");
        }
    }
}
