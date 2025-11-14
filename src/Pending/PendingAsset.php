<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending;

use CodeIgniter\Entity\Entity;

/**
 * // need to hide file path on storage
 * $data = [
 * 'id'                  => $this->id,
 * 'entity_id'           => $this->entity_id,
 * 'name'                => $this->name,
 * 'file_name'           => $this->file_name,
 * 'mime_type'           => $this->mime_type,
 * 'size'                => $this->size,
 * 'size_human_readable' => $this->getHumanReadableSize(),
 * 'created_at'          => $this->created_at,
 * 'updated_at'          => $this->updated_at,
 * 'deleted_at'          => $this->deleted_at,
 * 'order'               => $this->order,
 * 'custom_properties'   => $this->getCustomProperties(),
 * 'url'                 => $this->getUrl(),
 * 'url_relative'        => $this->getUrlRelative(),
 * 'variants'            => [],
 * ];
 */
class PendingAsset extends Entity
{
}
