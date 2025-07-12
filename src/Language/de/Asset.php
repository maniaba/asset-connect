<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Die angegebene Datei ist ungültig oder existiert nicht: {path}',
        'invalid_entity'         => 'Die Entität muss HasAssetsEntityTrait implementieren, um Assets hinzuzufügen. Entität: {entity}',
        'file_name_not_allowed'  => 'Der Dateiname "{fileName}" ist nicht erlaubt.',
        'file_too_large'         => 'Die Dateigröße ({fileSize} Bytes) überschreitet die maximal zulässige Größe von {maxFileSize} Bytes.',
        'invalid_file_extension' => 'Die Dateierweiterung "{extension}" ist nicht erlaubt. Erlaubte Erweiterungen: {allowedExtensions}.',
        'invalid_mime_type'      => 'Der MIME-Typ "{mimeType}" ist nicht erlaubt. Erlaubte MIME-Typen: {allowedMimeTypes}.',
        'file_not_found'         => 'Die Datei wurde unter dem angegebenen Pfad nicht gefunden: {path}',
        'cannot_copy_file'       => 'Datei kann nicht von "{source}" nach "{destination}" kopiert werden.',
        'database_error'         => 'Beim Speichern des Assets in der Datenbank ist ein Fehler aufgetreten: {errors}',
        'page_forbidden'         => 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.',
        'variant_not_found'      => 'Die angeforderte Variante "{variantName}" wurde nicht gefunden.',
        'token_invalid'          => 'Das bereitgestellte Token ist ungültig oder abgelaufen.',
    ],
];
