<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\PathGenerator;

use Maniaba\FileConnect\AssetCollection\AssetCollection;
use Maniaba\FileConnect\Exceptions\FileException;

final readonly class PathGenerator
{
    private readonly PathGeneratorHelper $helper;

    public function __construct(
        public PathGeneratorInterface $pathGenerator,
        private AssetCollection $collection,
    ) {
        $this->helper = new PathGeneratorHelper();
    }

    public function getPath(): string
    {
        $path = $this->pathGenerator->getPath($this->helper, $this->collection);
        // Ensure the path ends with a DIRECTORY_SEPARATOR
        if (substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        $this->ensurePathExists($path);

        return $path;
    }

    public function getPathForVariants(): string
    {
        $path = $this->pathGenerator->getPathForVariants($this->helper, $this->collection);
        // Ensure the path ends with a DIRECTORY_SEPARATOR
        if (substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        $this->ensurePathExists($path);

        return $path;
    }

    /**
     * Ensure the path is valid and exists or create it if necessary.
     */
    private function ensurePathExists(string $path): void
    {
        if (! is_dir($path)) {
            if (! mkdir($path, 0755, true) && ! is_dir($path)) {
                $error = sprintf('Directory "%s" was not created', $path);

                throw new FileException($error, $error, 500);
            }
            // Ensure the directory is writable
            if (! is_writable($path)) {
                $error = sprintf('Directory "%s" is not writable', $path);

                throw new FileException($error, $error, 500);
            }

            // Ensure the directory is readable
            if (! is_readable($path)) {
                $error = sprintf('Directory "%s" is not readable', $path);

                throw new FileException($error, $error, 500);
            }

            // Recrusively create empty index.html file to prevent directory listing
            $indexFile = $path . DIRECTORY_SEPARATOR . 'index.html';

            if (! file_exists($indexFile)) {
                if (false === file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>Index</title></head><body></body></html>')) {
                    $error = sprintf('Failed to create index.html in "%s"', $path);

                    throw new FileException($error, $error, 500);
                }
            }

            // create .htaccess file to prevent directory listing
            $htaccessFile = $path . DIRECTORY_SEPARATOR . '.htaccess';
            if (! file_exists($htaccessFile)) {
                $htaccessContent = "Options -Indexes\n";
                if (false === file_put_contents($htaccessFile, $htaccessContent)) {
                    $error = sprintf('Failed to create .htaccess in "%s"', $path);

                    throw new FileException($error, $error, 500);
                }
            }
        }
    }
}
