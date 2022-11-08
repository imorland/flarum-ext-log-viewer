<?php

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
