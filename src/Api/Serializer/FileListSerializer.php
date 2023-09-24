<?php

/*
 * This file is part of ianm/log-viewer.
 *
 * Copyright (c) 2022 IanM.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\LogViewer\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;

class FileListSerializer extends AbstractSerializer
{
    protected $type = 'log';

    protected function getDefaultAttributes($model)
    {
        $attributes = [
            'fileName' => $model->fileName,
            'fullPath' => $model->fullPath,
            'size' =>$model->size,
            'formattedSize' => $this->formatBytes($model->size),
            'modified' => $this->formatDate($model->modified),
        ];

        return $attributes;
    }

    protected function formatBytes(int $bytes, int $decimals = 2): string
    {
        if ($bytes === 0) {
            return '0 Byte';
        }

        $k = 1024;
        $dm = $decimals < 0 ? 0 : $decimals;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $i = floor(log($bytes, $k));

        return number_format($bytes / pow($k, $i), $dm).' '.$sizes[$i];
    }
}
