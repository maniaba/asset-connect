<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Den angitte filen er ikke gyldig eller eksisterer ikke: {path}',
        'invalid_entity'         => 'Enheten må implementere HasAssetsEntityTrait for å legge til ressurser. Enhet: {entity}',
        'file_name_not_allowed'  => 'Filnavnet "{fileName}" er ikke tillatt.',
        'file_too_large'         => 'Filstørrelsen ({fileSize} bytes) overstiger maksimalt tillatt størrelse på {maxFileSize} bytes.',
        'invalid_file_extension' => 'Filutvidelsen "{extension}" er ikke tillatt. Tillatte utvidelser: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME-typen "{mimeType}" er ikke tillatt. Tillatte MIME-typer: {allowedMimeTypes}.',
        'file_not_found'         => 'Filen ble ikke funnet på den angitte stien: {path}',
        'cannot_copy_file'       => 'Kan ikke kopiere fil fra "{source}" til "{destination}".',
        'database_error'         => 'Det oppstod en feil under lagring av ressursen i databasen: {errors}',
        'page_forbidden'         => 'Du har ikke tillatelse til å få tilgang til denne siden.',
        'variant_not_found'      => 'Den forespurte varianten "{variantName}" ble ikke funnet.',
        'token_invalid'          => 'Det angitte tokenet er ugyldig eller har utløpt.',
    ],
];
