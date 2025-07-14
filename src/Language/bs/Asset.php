<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Navedena datoteka nije važeća ili ne postoji: {path}',
        'invalid_entity'         => 'Entitet mora implementirati HasAssetsEntityTrait za dodavanje sredstava. Entitet: {entity}',
        'file_name_not_allowed'  => 'Naziv datoteke "{fileName}" nije dozvoljen.',
        'file_too_large'         => 'Veličina datoteke ({fileSize} bajtova) prelazi maksimalno dozvoljenu veličinu od {maxFileSize} bajtova.',
        'invalid_file_extension' => 'Ekstenzija datoteke "{extension}" nije dozvoljena. Dozvoljene ekstenzije: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME tip "{mimeType}" nije dozvoljen. Dozvoljeni MIME tipovi: {allowedMimeTypes}.',
        'file_not_found'         => 'Datoteka nije pronađena na navedenoj putanji: {path}',
        'cannot_copy_file'       => 'Nije moguće kopirati datoteku iz "{source}" u "{destination}".',
        'database_error'         => 'Došlo je do greške prilikom spremanja sredstva u bazu podataka: {errors}',
        'page_forbidden'         => 'Nemate dozvolu za pristup ovoj stranici.',
        'variant_not_found'      => 'Tražena varijanta "{variantName}" nije pronađena.',
        'token_invalid'          => 'Navedeni token nije važeći ili je istekao.',
    ],
];
