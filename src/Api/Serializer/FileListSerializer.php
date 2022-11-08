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
    protected $type = 'logs';

    protected function getDefaultAttributes($model)
    {
        $attributes = [
            'fileName' => $model->fileName,
            'fullPath' => $model->fullPath,
            'size' => $model->size,
            'modified' => $this->formatDate($model->modified),
        ];

        return $attributes;
    }
}
