<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'A megadott fájl érvénytelen vagy nem létezik: {path}',
        'invalid_entity'         => 'Az entitásnak implementálnia kell a HasAssetsEntityTrait-et az eszközök hozzáadásához. Entitás: {entity}',
        'file_name_not_allowed'  => 'A(z) "{fileName}" fájlnév nem engedélyezett.',
        'file_too_large'         => 'A fájl mérete ({fileSize} bájt) meghaladja a maximálisan megengedett {maxFileSize} bájtot.',
        'invalid_file_extension' => 'A(z) "{extension}" fájlkiterjesztés nem engedélyezett. Engedélyezett kiterjesztések: {allowedExtensions}.',
        'invalid_mime_type'      => 'A(z) "{mimeType}" MIME típus nem engedélyezett. Engedélyezett MIME típusok: {allowedMimeTypes}.',
        'file_not_found'         => 'A fájl nem található a megadott útvonalon: {path}',
        'cannot_copy_file'       => 'Nem lehet másolni a fájlt a(z) "{source}" helyről a(z) "{destination}" helyre.',
        'database_error'         => 'Hiba történt az eszköz adatbázisba mentése során: {errors}',
        'page_forbidden'         => 'Nincs jogosultsága az oldal megtekintéséhez.',
        'variant_not_found'      => 'A kért "{variantName}" változat nem található.',
        'token_invalid'          => 'A megadott token érvénytelen vagy lejárt.',
    ],
];
