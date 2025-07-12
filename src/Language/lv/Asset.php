<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Norādītais fails nav derīgs vai neeksistē: {path}',
        'invalid_entity'         => 'Entītijai jāimplementē HasAssetsEntityTrait, lai pievienotu aktīvus. Entītija: {entity}',
        'file_name_not_allowed'  => 'Faila nosaukums "{fileName}" nav atļauts.',
        'file_too_large'         => 'Faila izmērs ({fileSize} baiti) pārsniedz maksimāli atļauto izmēru {maxFileSize} baiti.',
        'invalid_file_extension' => 'Faila paplašinājums "{extension}" nav atļauts. Atļautie paplašinājumi: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME tips "{mimeType}" nav atļauts. Atļautie MIME tipi: {allowedMimeTypes}.',
        'file_not_found'         => 'Fails norādītajā ceļā netika atrasts: {path}',
        'cannot_copy_file'       => 'Nevar kopēt failu no "{source}" uz "{destination}".',
        'database_error'         => 'Saglabājot aktīvu datubāzē, radās kļūda: {errors}',
        'page_forbidden'         => 'Jums nav atļaujas piekļūt šai lapai.',
        'variant_not_found'      => 'Pieprasītais variants "{variantName}" netika atrasts.',
        'token_invalid'          => 'Norādītais tokens nav derīgs vai ir beidzies tā derīguma termiņš.',
    ],
];
