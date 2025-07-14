<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Het opgegeven bestand is niet geldig of bestaat niet: {path}',
        'invalid_entity'         => 'De entiteit moet HasAssetsEntityTrait implementeren om assets toe te voegen. Entiteit: {entity}',
        'file_name_not_allowed'  => 'De bestandsnaam "{fileName}" is niet toegestaan.',
        'file_too_large'         => 'De bestandsgrootte ({fileSize} bytes) overschrijdt de maximaal toegestane grootte van {maxFileSize} bytes.',
        'invalid_file_extension' => 'De bestandsextensie "{extension}" is niet toegestaan. Toegestane extensies: {allowedExtensions}.',
        'invalid_mime_type'      => 'Het MIME-type "{mimeType}" is niet toegestaan. Toegestane MIME-types: {allowedMimeTypes}.',
        'file_not_found'         => 'Het bestand is niet gevonden op het opgegeven pad: {path}',
        'cannot_copy_file'       => 'Kan bestand niet kopiëren van "{source}" naar "{destination}".',
        'database_error'         => 'Er is een fout opgetreden bij het opslaan van de asset in de database: {errors}',
        'page_forbidden'         => 'U heeft geen toestemming om deze pagina te bekijken.',
        'variant_not_found'      => 'De gevraagde variant "{variantName}" is niet gevonden.',
        'token_invalid'          => 'De opgegeven token is ongeldig of verlopen.',
    ],
];
