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

class LogFileSerializer extends FileListSerializer
{
    protected function getDefaultAttributes($model)
    {
        $attributes = parent::getDefaultAttributes($model);

        $attributes['content'] = $model->content;

        return $attributes;
    }
}
