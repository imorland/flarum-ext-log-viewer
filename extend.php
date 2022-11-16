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
        ->get('/logs/{file}', 'logs.show', Api\Controller\ShowLogFileController::class),

    (new Extend\Settings())
        ->default('ianm-log-viewer.purge-days', 90),

    (new Extend\Console())
        ->command(Console\CleanupLogfilesCommand::class)
        ->schedule(Console\CleanupLogfilesCommand::class, function (Event $schedule) {
            $schedule->daily();
        }),
];
