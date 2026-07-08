<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * §4.2 — Path objek COS berskop tenant:
 *   tenants/{mosque_id}/records/{tahun}/{record_ulid}/{collection}/{namafail}
 */
class TenantPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->base($media);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->base($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->base($media).'responsive/';
    }

    protected function base(Media $media): string
    {
        $model = $media->model;
        $mosqueId = $model->mosque_id ?? 'unknown';
        $date = $model->record_date ?? $model->created_at ?? null;
        $year = $date ? $date->format('Y') : date('Y');
        $ulid = $model->ulid ?? $media->uuid ?? $media->getKey();

        return "tenants/{$mosqueId}/records/{$year}/{$ulid}/{$media->collection_name}/";
    }
}
