<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Den angivna filen är inte giltig eller existerar inte: {path}',
        'invalid_entity'         => 'Entiteten måste implementera HasAssetsEntityTrait för att lägga till tillgångar. Entitet: {entity}',
        'file_name_not_allowed'  => 'Filnamnet "{fileName}" är inte tillåtet.',
        'file_too_large'         => 'Filstorleken ({fileSize} byte) överstiger den maximalt tillåtna storleken på {maxFileSize} byte.',
        'invalid_file_extension' => 'Filtillägget "{extension}" är inte tillåtet. Tillåtna tillägg: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME-typen "{mimeType}" är inte tillåten. Tillåtna MIME-typer: {allowedMimeTypes}.',
        'file_not_found'         => 'Filen hittades inte på den angivna sökvägen: {path}',
        'cannot_copy_file'       => 'Kan inte kopiera fil från "{source}" till "{destination}".',
        'database_error'         => 'Ett fel inträffade när tillgången skulle sparas i databasen: {errors}',
        'page_forbidden'         => 'Du har inte behörighet att komma åt denna sida.',
        'variant_not_found'      => 'Den begärda varianten "{variantName}" hittades inte.',
        'token_invalid'          => 'Den angivna token är ogiltig eller har löpt ut.',
    ],
];
