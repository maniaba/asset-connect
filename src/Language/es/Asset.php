<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'El archivo proporcionado no es válido o no existe: {path}',
        'invalid_entity'         => 'La entidad debe implementar HasAssetsEntityTrait para agregar activos. Entidad: {entity}',
        'file_name_not_allowed'  => 'El nombre de archivo "{fileName}" no está permitido.',
        'file_too_large'         => 'El tamaño del archivo ({fileSize} bytes) excede el tamaño máximo permitido de {maxFileSize} bytes.',
        'invalid_file_extension' => 'La extensión de archivo "{extension}" no está permitida. Extensiones permitidas: {allowedExtensions}.',
        'invalid_mime_type'      => 'El tipo MIME "{mimeType}" no está permitido. Tipos MIME permitidos: {allowedMimeTypes}.',
        'file_not_found'         => 'No se encontró el archivo en la ruta especificada: {path}',
        'cannot_copy_file'       => 'No se puede copiar el archivo de "{source}" a "{destination}".',
        'database_error'         => 'Ocurrió un error al guardar el activo en la base de datos: {errors}',
        'page_forbidden'         => 'No tienes permiso para acceder a esta página.',
        'variant_not_found'      => 'No se encontró la variante solicitada "{variantName}".',
        'token_invalid'          => 'El token proporcionado no es válido o ha caducado.',
    ],
];
