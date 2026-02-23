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

    public function __construct(protected SettingsRepositoryInterface $settings, protected Filesystem $filesystem, protected Paths $paths)
    {
        parent::__construct();
    }

    public function handle(): void
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

    protected function splitFile(\Symfony\Component\Finder\SplFileInfo $file, int $maxFileSize): void
    {
        $originalFilePath = $file->getRealPath();
        $baseNameWithoutExtension = pathinfo($file->getBasename(), PATHINFO_FILENAME);
        $extension = pathinfo($file->getBasename(), PATHINFO_EXTENSION);

        // Determine the starting part number and strip any existing -partN suffix
        // so that re-splitting an already-split file doesn't produce compounded names
        // like "flarum-part3-part6-part9...".
        $existingParts = preg_match('/-part(\\d+)$/', $baseNameWithoutExtension, $matches);
        $partNumber = $existingParts ? (int) $matches[1] + 1 : 1;
        $baseNameWithoutExtension = $existingParts
            ? substr($baseNameWithoutExtension, 0, -strlen($matches[0]))
            : $baseNameWithoutExtension;

        // Open the original file for reading
        $handle = fopen($originalFilePath, 'rb');
        if (! $handle) {
            $this->error('Error opening file: '.$originalFilePath);

            return;
        }

        $partsWritten = 0;
        while (! feof($handle)) {
            $chunk = fread($handle, $maxFileSize);

            // fread returns an empty string at EOF on some systems; skip it.
            if ($chunk === '' || $chunk === false) {
                break;
            }

            $filename = $baseNameWithoutExtension.'-part'.$partNumber.'.'.$extension;
            $filePath = $file->getPath().DIRECTORY_SEPARATOR.$filename;

            if (file_put_contents($filePath, $chunk) === false) {
                $this->error('Error writing to file: '.$filePath);
                fclose($handle);

                return;
            }

            $partNumber++;
            $partsWritten++;
        }

        // Close the original file handle
        fclose($handle);

        if ($partsWritten > 1) {
            // All chunks were written to new part files; delete the original.
            unlink($originalFilePath);
        } else {
            // Only one chunk written — file was at or below the limit (e.g. exactly
            // maxFileSize bytes). Remove the single part file and leave original intact.
            $singlePartPath = $file->getPath().DIRECTORY_SEPARATOR.$baseNameWithoutExtension.'-part'.($partNumber - 1).'.'.$extension;
            @unlink($singlePartPath);
        }
    }
}
