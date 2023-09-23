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

class SplitLargeLogfilesCommand extends Command
{
    use LogDirectoryTrait;

    protected $signature = 'logfiles:split-large';
    protected $description = 'Splits log files larger than a configured size.';

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
        $maxFileSize = $this->getMaxFileSize();

        // Check if file splitting is disabled
        if ($maxFileSize === 0) {
            $this->info('File splitting is disabled. No action taken.');
            return;
        }

        $logFiles = $this->getLargeLogFiles($maxFileSize);

        if (!$logFiles->count()) {
            $this->info('No large log files found.');
            return;
        }

        $this->splitLargeFiles($logFiles, $maxFileSize);
    }

    protected function getMaxFileSize(): int
    {
        $sizeMb = (int) $this->settings->get('ianm-log-viewer.max-file-size');

        // Ensure the value is non-negative
        if ($sizeMb < 0) {
            $sizeMb = 1;  // Default to 1MB for negative values
        }

        // No need to default to 1MB for zero value, as it indicates disabling

        // Set a reasonable upper limit (e.g., 150MB).
        if ($sizeMb > 150) {
            $sizeMb = 150;
        }

        return $sizeMb * 1024 * 1024;
    }


    protected function getLargeLogFiles(int $maxFileSize): Finder
    {
        $finder = new Finder();
        $finder->files()
            ->in($this->getLogDirectory($this->paths))
            ->size('>' . $maxFileSize);

        return $finder;
    }

    protected function splitLargeFiles(Finder $logFiles, int $maxFileSize): void
    {
        foreach ($logFiles as $file) {
            $this->splitFile($file, $maxFileSize);
        }

        $this->info('Large log files split successfully.');
    }


    protected function splitFile($file, $maxFileSize): void
    {
        $originalFilePath = $file->getRealPath();
        $baseNameWithoutExtension = pathinfo($file->getBasename(), PATHINFO_FILENAME);
        $extension = pathinfo($file->getBasename(), PATHINFO_EXTENSION);

        // Determine the starting part number
        $existingParts = preg_match('/-part(\\d+)$/', $baseNameWithoutExtension, $matches);
        $partNumber = $existingParts ? (int)$matches[1] + 1 : 1;

        // Open the original file for reading
        $handle = fopen($originalFilePath, 'rb');
        if (!$handle) {
            $this->error('Error opening file: ' . $originalFilePath);
            return;
        }

        $partFiles = [];
        while (!feof($handle)) {
            $filename = $baseNameWithoutExtension . '-part' . $partNumber . '.' . $extension;
            $filePath = $file->getPath() . DIRECTORY_SEPARATOR . $filename;

            // Read a chunk of the file
            $chunk = fread($handle, $maxFileSize);

            // Write the chunk to a new part file
            if (file_put_contents($filePath, $chunk) === false) {
                $this->error('Error writing to file: ' . $filePath);
                fclose($handle);
                return;
            }

            $partFiles[] = $filePath;
            $partNumber++;
        }

        // Close the original file handle
        fclose($handle);

        // Rename the original file to become the last part (if there are multiple parts)
        if (count($partFiles) > 1) {
            $lastPartFileName = $baseNameWithoutExtension . '-part' . ($partNumber - 1) . '.' . $extension;
            $lastPartFilePath = $file->getPath() . DIRECTORY_SEPARATOR . $lastPartFileName;
            rename($originalFilePath, $lastPartFilePath);
        } else {
            // If there's only one part, delete the split file and keep the original
            unlink($partFiles[0]);
        }
    }
}
