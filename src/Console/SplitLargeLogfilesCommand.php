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

        if (! $logFiles->count()) {
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
            ->size('> '.$maxFileSize);

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

        // Strip any existing -partN suffix to avoid compounding names on re-split
        $hadPartSuffix = false;
        if (preg_match('/^(.*)-part(\d+)$/', $baseNameWithoutExtension, $matches)) {
            $baseNameWithoutExtension = $matches[1];
            $hadPartSuffix = true;
        }

        // If the original filename would collide with chunk 1 (e.g. re-splitting largeTest-part1.log),
        // rename the original to a temporary name first so reads and writes don't interfere.
        // Use dirname($originalFilePath) to get the absolute directory, not $file->getPath() which is relative.
        $readPath = $originalFilePath;
        $tempPath = null;
        if ($hadPartSuffix) {
            $firstChunkPath = dirname($originalFilePath).DIRECTORY_SEPARATOR.$baseNameWithoutExtension.'-part1.'.$extension;
            if ($firstChunkPath === $originalFilePath) {
                $tempPath = $originalFilePath.'.splitting';
                if (! rename($originalFilePath, $tempPath)) {
                    $this->error('Error renaming file for splitting: '.$originalFilePath);

                    return;
                }
                $readPath = $tempPath;
            }
        }

        // Open the file for reading
        $handle = fopen($readPath, 'rb');
        if (! $handle) {
            $this->error('Error opening file: '.$readPath);
            if ($tempPath) {
                rename($tempPath, $originalFilePath);
            }

            return;
        }

        $partNumber = 1;
        $partsWritten = 0;
        while (! feof($handle)) {
            $chunk = fread($handle, $maxFileSize);

            if ($chunk === false || strlen($chunk) === 0) {
                break;
            }

            $filename = $baseNameWithoutExtension.'-part'.$partNumber.'.'.$extension;
            $filePath = dirname($originalFilePath).DIRECTORY_SEPARATOR.$filename;

            // Write the chunk to a new part file
            if (file_put_contents($filePath, $chunk) === false) {
                $this->error('Error writing to file: '.$filePath);
                fclose($handle);

                return;
            }

            $partsWritten++;
            $partNumber++;
        }

        // Close the file handle
        fclose($handle);

        if ($partsWritten > 1) {
            // All chunks were written to numbered part files; delete the source
            unlink($readPath);
        } else {
            // Only one chunk — no real split happened; remove the single part file
            // and restore the original name if we had renamed it
            $singlePartPath = dirname($originalFilePath).DIRECTORY_SEPARATOR.$baseNameWithoutExtension.'-part1.'.$extension;
            if ($tempPath) {
                // Restore original name; singlePartPath may or may not exist
                if (file_exists($singlePartPath) && $singlePartPath !== $originalFilePath) {
                    @unlink($singlePartPath);
                }
                rename($tempPath, $originalFilePath);
            } else {
                @unlink($singlePartPath);
            }
        }
    }
}
