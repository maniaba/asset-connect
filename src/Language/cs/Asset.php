<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Poskytnutý soubor není platný nebo neexistuje: {path}',
        'invalid_entity'         => 'Entita musí implementovat HasAssetsEntityTrait pro přidání aktiv. Entita: {entity}',
        'file_name_not_allowed'  => 'Název souboru "{fileName}" není povolen.',
        'file_too_large'         => 'Velikost souboru ({fileSize} bajtů) překračuje maximální povolenou velikost {maxFileSize} bajtů.',
        'invalid_file_extension' => 'Přípona souboru "{extension}" není povolena. Povolené přípony: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME typ "{mimeType}" není povolen. Povolené MIME typy: {allowedMimeTypes}.',
        'file_not_found'         => 'Soubor nebyl nalezen na zadané cestě: {path}',
        'cannot_copy_file'       => 'Nelze zkopírovat soubor z "{source}" do "{destination}".',
        'database_error'         => 'Při ukládání aktiva do databáze došlo k chybě: {errors}',
        'page_forbidden'         => 'Nemáte oprávnění k přístupu na tuto stránku.',
        'variant_not_found'      => 'Požadovaná varianta "{variantName}" nebyla nalezena.',
        'token_invalid'          => 'Poskytnutý token je neplatný nebo vypršel.',
    ],
];
