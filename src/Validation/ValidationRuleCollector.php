<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Validation;

use Config\Services;
use Maniaba\AssetConnect\Asset\Interfaces\AssetCollectionSetterInterface;
use Maniaba\AssetConnect\Enums\AssetExtension;
use Maniaba\AssetConnect\Enums\AssetMimeType;
use Maniaba\AssetConnect\PathGenerator\Interfaces\PathGeneratorInterface;
use Override;

/**
 * Class ValidationRuleCollector
 *
 * This class implements AssetCollectionSetterInterface to collect validation rules
 * from an asset collection definition.
 */
final class ValidationRuleCollector implements AssetCollectionSetterInterface
{
    /**
     * @var non-empty-array<string, list<string>|non-falsy-string> The collected validation rules
     */
    private array $rules;

    public function __construct(
        private readonly string $currentField,
    ) {
        $this->rules['uploaded'] = 'uploaded[' . $this->currentField . ']';
    }

    /**
     * Set allowed file extensions
     *
     * @param AssetExtension|string ...$extensions The allowed file extensions
     *
     * @return $this
     */
    #[Override]
    public function allowedExtensions(AssetExtension|string ...$extensions): static
    {
        $extensionList = [];

        foreach ($extensions as $extension) {
            $extensionList[] = $extension instanceof AssetExtension ? $extension->value : $extension;
        }

        $this->rules['ext_in'] = 'ext_in[' . $this->currentField . ',' . implode(',', $extensionList) . ']';

        return $this;
    }

    /**
     * Set allowed MIME types
     *
     * @param AssetMimeType|string ...$mimeTypes The allowed MIME types
     *
     * @return $this
     */
    #[Override]
    public function allowedMimeTypes(AssetMimeType|string ...$mimeTypes): static
    {
        $mimeTypeList = [];

        foreach ($mimeTypes as $mimeType) {
            $mimeTypeList[] = $mimeType instanceof AssetMimeType ? $mimeType->value : $mimeType;
        }

        $this->rules['mime_in'] = 'mime_in[' . $this->currentField . ',' . implode(',', $mimeTypeList) . ']';

        return $this;
    }

    /**
     * Set maximum number of items in the collection
     *
     * @param int $maximumNumberOfItemsInCollection The maximum number of items
     *
     * @return $this
     */
    #[Override]
    public function onlyKeepLatest(int $maximumNumberOfItemsInCollection): static
    {
        // Use a custom rule name that will be registered in the validator
        $name = $this->currentField;

        $this->rules['max_file_count'] = static fn ($value, ?array $data, ?string &$error, string $field): bool => self::maxFileCountValidationRule($value, "{$name},{$maximumNumberOfItemsInCollection}", $data, $error, $field);

        return $this;
    }

    public static function maxFileCountValidationRule($value, string $params, ?array $data, ?string &$error, string $field): bool
    {
        // Extract the maximum number of files from the parameters
        $params   = explode(',', $params);
        $name     = array_shift($params);
        $maxFiles = (int) array_shift($params);

        $request = Services::request();
        $files   = $request->getFileMultiple($name);
        if ($files === null) {
            $files = [$request->getFile($name)];
        }

        // For multiple file uploads, check the count
        if (is_array($files) && count($files) > 1) {
            if (count($files) > $maxFiles) {
                $error = "The field '{$field}' cannot contain more than {$maxFiles} files.";

                return false;
            }
        } elseif ($maxFiles === 0 && $files[0] !== null && $files[0]->getError() !== UPLOAD_ERR_NO_FILE) {
            // If max files is 0, no files should be uploaded
            $error = "The field '{$field}' cannot contain any files.";

            return false;
        }

        return true;
    }

    /**
     * Set maximum file size
     *
     * @param float|int $maxFileSize The maximum file size in bytes
     *
     * @return $this
     */
    #[Override]
    public function setMaxFileSize(float|int $maxFileSize): static
    {
        // Convert to kilobytes for the max_size rule
        $maxSizeKB = (int) ($maxFileSize / 1024);

        $this->rules['max_size'] = 'max_size[' . $this->currentField . ',' . $maxSizeKB . ']';

        return $this;
    }

    /**
     * Set the collection to only allow a single file
     *
     * @return $this
     */
    #[Override]
    public function singleFileCollection(): static
    {
        // Use a custom rule name that will be registered in the validator
        $name = $this->currentField;

        $this->rules['max_file_count'] = static fn ($value, ?array $data, ?string &$error, string $field): bool => self::maxFileCountValidationRule($value, "{$name},1", $data, $error, $field);

        return $this;
    }

    /**
     * Set the path generator
     *
     * @param PathGeneratorInterface $pathGenerator The path generator
     *
     * @return $this
     */
    #[Override]
    public function setPathGenerator(PathGeneratorInterface $pathGenerator): static
    {
        // This doesn't translate to a validation rule
        return $this;
    }

    /**
     * Set maximum image dimensions
     *
     * @param int $width  The maximum width in pixels
     * @param int $height The maximum height in pixels
     *
     * @return $this
     */
    public function setMaxImageDimensions(int $width, int $height): static
    {
        $this->rules['max_dims'] = 'max_dims[' . $this->currentField . ',' . $width . ',' . $height . ']';

        return $this;
    }

    /**
     * Set minimum image dimensions
     *
     * @param int $width  The minimum width in pixels
     * @param int $height The minimum height in pixels
     *
     * @return $this
     */
    public function setMinImageDimensions(int $width, int $height): static
    {
        $this->rules['min_dims'] = 'min_dims[' . $this->currentField . ',' . $width . ',' . $height . ']';

        return $this;
    }

    /**
     * Require that the file is an image
     *
     * @return $this
     */
    public function requireImage(): static
    {
        $this->rules['is_image'] = 'is_image[' . $this->currentField . ']';

        return $this;
    }

    /**
     * Get the collected validation rules
     *
     * @return list<string> The validation rules
     */
    public function getRules(): array
    {
        return array_values($this->rules);
    }
}
