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

    /**
     * Encode a relative path as a URL-safe base64 string for use as the resource ID.
     */
    public static function encodeId(string $relativePath): string
    {
        return rtrim(strtr(base64_encode($relativePath), '+/', '-_'), '=');
    }

    /**
     * @param \IanM\LogViewer\Model\LogFile $model
     */
    public function getId($model)
    {
        return self::encodeId($model->relativePath);
    }

    /**
     * @param \IanM\LogViewer\Model\LogFile $model
     */
    protected function getDefaultAttributes($model)
    {
        $attributes = [
            'fileName' => $model->fileName,
            'relativePath' => $model->relativePath,
            'fullPath' => $model->fullPath,
            'size' => $model->size,
            'modified' => $this->formatDate($model->modified),
        ];

        return $attributes;
    }
}
