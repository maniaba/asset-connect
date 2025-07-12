<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Poskytnutý súbor nie je platný alebo neexistuje: {path}',
        'invalid_entity'         => 'Entita musí implementovať HasAssetsEntityTrait pre pridanie aktív. Entita: {entity}',
        'file_name_not_allowed'  => 'Názov súboru "{fileName}" nie je povolený.',
        'file_too_large'         => 'Veľkosť súboru ({fileSize} bajtov) prekračuje maximálnu povolenú veľkosť {maxFileSize} bajtov.',
        'invalid_file_extension' => 'Prípona súboru "{extension}" nie je povolená. Povolené prípony: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME typ "{mimeType}" nie je povolený. Povolené MIME typy: {allowedMimeTypes}.',
        'file_not_found'         => 'Súbor nebol nájdený na zadanej ceste: {path}',
        'cannot_copy_file'       => 'Nemožno kopírovať súbor z "{source}" do "{destination}".',
        'database_error'         => 'Pri ukladaní aktíva do databázy sa vyskytla chyba: {errors}',
        'page_forbidden'         => 'Nemáte oprávnenie na prístup k tejto stránke.',
        'variant_not_found'      => 'Požadovaný variant "{variantName}" nebol nájdený.',
        'token_invalid'          => 'Poskytnutý token je neplatný alebo vypršal.',
    ],
];
