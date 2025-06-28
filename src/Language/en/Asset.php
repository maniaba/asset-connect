<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'The provided file is not valid or does not exist: {path}',
        'invalid_entity'         => 'The entity must implement the HasAssetsEntityTrait to add assets. Entity: {entity}',
        'file_name_not_allowed'  => 'The file name "{fileName}" is not allowed.',
        'file_too_large'         => 'The file size ({fileSize} bytes) exceeds the maximum allowed size of {maxFileSize} bytes.',
        'invalid_file_extension' => 'The file extension "{extension}" is not allowed. Allowed extensions: {allowedExtensions}.',
        'invalid_mime_type'      => 'The MIME type "{mimeType}" is not allowed. Allowed MIME types: {allowedMimeTypes}.',
        'file_not_found'         => 'The file was not found at the specified path: {path}',
        'cannot_copy_file'       => 'Cannot copy file from "{source}" to "{destination}".',
    ],
];
