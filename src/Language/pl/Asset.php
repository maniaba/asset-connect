<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Podany plik jest nieprawidłowy lub nie istnieje: {path}',
        'invalid_entity'         => 'Encja musi implementować HasAssetsEntityTrait, aby dodawać zasoby. Encja: {entity}',
        'file_name_not_allowed'  => 'Nazwa pliku "{fileName}" jest niedozwolona.',
        'file_too_large'         => 'Rozmiar pliku ({fileSize} bajtów) przekracza maksymalny dozwolony rozmiar {maxFileSize} bajtów.',
        'invalid_file_extension' => 'Rozszerzenie pliku "{extension}" jest niedozwolone. Dozwolone rozszerzenia: {allowedExtensions}.',
        'invalid_mime_type'      => 'Typ MIME "{mimeType}" jest niedozwolony. Dozwolone typy MIME: {allowedMimeTypes}.',
        'file_not_found'         => 'Plik nie został znaleziony pod określoną ścieżką: {path}',
        'cannot_copy_file'       => 'Nie można skopiować pliku z "{source}" do "{destination}".',
        'database_error'         => 'Wystąpił błąd podczas zapisywania zasobu do bazy danych: {errors}',
        'page_forbidden'         => 'Nie masz uprawnień do dostępu do tej strony.',
        'variant_not_found'      => 'Żądany wariant "{variantName}" nie został znaleziony.',
        'token_invalid'          => 'Podany token jest nieprawidłowy lub wygasł.',
    ],
];
