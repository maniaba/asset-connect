<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending;

use AllowDynamicProperties;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use Config\Services;
use JsonSerializable;
use Maniaba\AssetConnect\Asset\Interfaces\AssetDefinitionInterface;
use Maniaba\AssetConnect\Asset\Traits\AssetFileInfoTrait;
use Maniaba\AssetConnect\Exceptions\FileException;
use Maniaba\AssetConnect\Exceptions\InvalidArgumentException;
use Maniaba\AssetConnect\Exceptions\PendingAssetException;
use Maniaba\AssetConnect\Pending\Interfaces\PendingStorageInterface;
use Override;
use Random\RandomException;
use TypeError;

/**
 * Class PendingAsset
 *
 * Represents an asset pending addition to an entity.
 *
 * @property-read Time                 $created_at
 * @property-read array<string, mixed> $custom_properties
 * @property-read string               $file_name
 * @property-read string               $id
 * @property-read string               $mime_type
 * @property-read string               $name
 * @property-read int                  $order
 * @property-read bool                 $preserve_original
 * @property-read int                  $ttl
 * @property-read Time                 $updated_at
 */
#[AllowDynamicProperties]
final class PendingAsset implements AssetDefinitionInterface, JsonSerializable
{
    use AssetFileInfoTrait;

    private string $id = '';
    private string $name;
    private string $file_name;
    private string $mime_type;
    private int $size;
    private int $ttl;
    private Time $created_at;
    private Time $updated_at;
    private int $order;
    private bool $preserve_original;
    private array $custom_properties;

    #[Override]
    public function usingName(string $name): AssetDefinitionInterface
    {
        $this->name = $name;

        return $this;
    }

    #[Override]
    public function usingFileName(string $fileName): AssetDefinitionInterface
    {
        $this->file_name = $fileName;

        return $this;
    }

    #[Override]
    public function preservingOriginal(bool $preserveOriginal = true): AssetDefinitionInterface
    {
        $this->preserve_original = $preserveOriginal;

        return $this;
    }

    #[Override]
    public function setOrder(int $order): AssetDefinitionInterface
    {
        $this->order = $order;

        return $this;
    }

    #[Override]
    public function withCustomProperties(array $customProperties): AssetDefinitionInterface
    {
        $this->custom_properties = $customProperties;

        return $this;
    }

    #[Override]
    public function withCustomProperty(string $key, mixed $value): AssetDefinitionInterface
    {
        $this->custom_properties[$key] = $value;

        return $this;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'file_name'           => $this->file_name,
            'mime_type'           => $this->mime_type,
            'size'                => $this->size,
            'size_human_readable' => $this->getHumanReadableSize(),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
            'order'               => $this->order,
            'preserve_original'   => $this->preserve_original,
            'ttl'                 => $this->ttl,
            'custom_properties'   => $this->custom_properties,
        ];
    }

    private function __construct(public readonly File|UploadedFile $file, array $attributes = [])
    {
        $fileName = $this->file instanceof UploadedFile ? $this->file->getClientName() : $this->file->getBasename();

        $this->file_name         = $fileName;
        $this->name              = pathinfo($fileName, PATHINFO_FILENAME);
        $this->mime_type         = $this->file->getMimeType();
        $this->size              = $this->file->getSize() ?? 0;
        $this->preserve_original = false;
        $this->custom_properties = [];
        $this->order             = 0;
        $this->created_at        = Time::createFromTimestamp($this->file->getCTime() ?? 0);
        $this->updated_at        = Time::createFromTimestamp($this->file->getMTime() ?? 0);
        $this->ttl               = 0;

        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                // try to set the property
                try {
                    $this->{$key} = $value;
                } catch (TypeError) {
                    // ignore type errors - can occur with typed properties when value doesn't match expected type
                }
            }
        }
    }

    public function __get(string $name): mixed
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return null;
    }

    /**
     * @param File|string|UploadedFile $file       The file to be associated with the pending asset.
     * @param array|string             $attributes Optional attributes for the pending asset, either as an associative array or a JSON string.
     */
    public static function createFromFile(File|string|UploadedFile $file, array|string $attributes = []): self
    {
        if (is_string($file)) {
            $file = new File($file);
        }

        // check if the file exists
        if (! $file->isFile()) {
            throw FileException::forInvalidFile($file->getRealPath());
        }

        if (is_string($attributes)) {
            $attributes = json_decode($attributes, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $attributes = [];
            }
        }

        return new self($file, $attributes);
    }

    public static function createFromBase64(string $base64data, array|string $attributes = []): self
    {
        $data = base64_decode($base64data, true);

        if ($data === false) {
            throw FileException::forInvalidFile('base64data');
        }

        return self::createFromString($data, $attributes);
    }

    /**
     * Create PendingAsset(s) from request file(s)
     *
     * @param string ...$keyNames The name of the file input field(s) in the request
     *
     * @return array<string, list<self>> An associative array where keys are field names and values are arrays of PendingAsset instances
     *
     * @throws FileException
     */
    public static function createFromRequest(string ...$keyNames): array
    {
        if ($keyNames === []) {
            throw new InvalidArgumentException('At least one key name must be provided.');
        }

        $request = Services::request();

        $pendingAssets = [];

        foreach ($request->getFiles() as $field => $files) {
            if (! in_array($field, $keyNames, true)) {
                continue;
            }
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    throw FileException::forInvalidFile($field);
                }

                if (! isset($pendingAssets[$field])) {
                    $pendingAssets[$field] = [];
                }

                $pendingAssets[$field][] = self::createFromFile($file);
            }
        }

        return $pendingAssets;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setTTL(int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public static function createFromString(string $string, array|string $attributes = []): self
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'pending_asset_');
        if ($tempFilePath === false) {
            throw FileException::forInvalidFile('tempfile_creation_failed');
        }

        file_put_contents($tempFilePath, $string);
        $file = new File($tempFilePath);

        return self::createFromFile($file, $attributes);
    }

    /**
     * @throws PendingAssetException|RandomException
     */
    public function store(?PendingStorageInterface $storage = null, ?int $ttlSeconds = null): void
    {
        $ttlSeconds ??= $this->ttl;
        $manager = PendingAssetManager::make($storage);

        $manager->store($this, $ttlSeconds);
    }
}
