<?php

namespace App\Media;

use App\Services\Archivos\ArchivosService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class IcuPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->base($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->base($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->base($media).'/responsive/';
    }

    protected function base(Media $media): string
    {
        $prefijo = app(ArchivosService::class)->prefijo() ?: 'icu';
        $modelo = class_basename((string) $media->model_type);

        return "{$prefijo}/{$media->collection_name}/{$modelo}/{$media->model_id}/{$media->getKey()}";
    }
}
