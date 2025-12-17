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

use Flarum\Extend;
use Illuminate\Console\Scheduling\Event;

return [

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('api'))
        ->get('/logs', 'logs.index', Api\Controller\ListLogfilesController::class)
        ->get('/logs/{file}', 'logs.show', Api\Controller\ShowLogFileController::class)
        ->get('/logs/download/{file}', 'logs.download', Api\Controller\DownloadLogFileController::class)
        ->delete('/logs/{file}', 'logs.delete', Api\Controller\DeleteLogFileController::class),

    (new Extend\Settings())
        ->default('ianm-log-viewer.purge-days', 90)
        ->default('ianm-log-viewer.max-file-size', 1),

    (new Extend\Console())
        ->command(Console\CleanupLogfilesCommand::class)
        ->command(Console\SplitLargeLogfilesCommand::class)
        ->schedule(Console\CleanupLogfilesCommand::class, function (Event $schedule) {
            $schedule->daily();
        })
        ->schedule(Console\SplitLargeLogfilesCommand::class, function (Event $schedule) {
            $schedule->daily();
        }),
];
