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

/**
 * @TODO: Remove this in favor of one of the API resource classes that were added.
 *      Or extend an existing API Resource to add this to.
 *      Or use a vanilla RequestHandlerInterface controller.
 *      @link https://docs.flarum.org/2.x/extend/api#endpoints
 */
class FileListSerializer extends AbstractSerializer
{
    protected $type = 'logs';

    /**
     * @param \IanM\LogViewer\Model\LogFile $model
     */
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
