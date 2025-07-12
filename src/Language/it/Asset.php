<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Il file fornito non è valido o non esiste: {path}',
        'invalid_entity'         => 'L\'entità deve implementare HasAssetsEntityTrait per aggiungere asset. Entità: {entity}',
        'file_name_not_allowed'  => 'Il nome del file "{fileName}" non è consentito.',
        'file_too_large'         => 'La dimensione del file ({fileSize} byte) supera la dimensione massima consentita di {maxFileSize} byte.',
        'invalid_file_extension' => 'L\'estensione del file "{extension}" non è consentita. Estensioni consentite: {allowedExtensions}.',
        'invalid_mime_type'      => 'Il tipo MIME "{mimeType}" non è consentito. Tipi MIME consentiti: {allowedMimeTypes}.',
        'file_not_found'         => 'Il file non è stato trovato nel percorso specificato: {path}',
        'cannot_copy_file'       => 'Impossibile copiare il file da "{source}" a "{destination}".',
        'database_error'         => 'Si è verificato un errore durante il salvataggio dell\'asset nel database: {errors}',
        'page_forbidden'         => 'Non hai il permesso di accedere a questa pagina.',
        'variant_not_found'      => 'La variante richiesta "{variantName}" non è stata trovata.',
        'token_invalid'          => 'Il token fornito non è valido o è scaduto.',
    ],
];
