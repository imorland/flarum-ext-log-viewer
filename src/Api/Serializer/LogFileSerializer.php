<?php

namespace IanM\LogViewer\Api\Serializer;

class LogFileSerializer extends FileListSerializer
{
    protected function getDefaultAttributes($model)
    {
        $attributes = parent::getDefaultAttributes($model);

        $attributes['content'] = $model->content;

        return $attributes;
    }
}