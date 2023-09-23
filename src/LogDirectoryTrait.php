<?php

/*
 * This file is part of ianm/log-viewer.
 *
 * Copyright (c) 2022 IanM.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\LogViewer;

use Flarum\Foundation\Paths;

trait LogDirectoryTrait
{
    public function getLogDirectory(Paths $paths): string
    {
        return $paths->storage.DIRECTORY_SEPARATOR.'logs';
    }
}
