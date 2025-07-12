<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Предоставеният файл не е валиден или не съществува: {path}',
        'invalid_entity'         => 'Обектът трябва да имплементира HasAssetsEntityTrait, за да добавя активи. Обект: {entity}',
        'file_name_not_allowed'  => 'Името на файла "{fileName}" не е разрешено.',
        'file_too_large'         => 'Размерът на файла ({fileSize} байта) надвишава максимално допустимия размер от {maxFileSize} байта.',
        'invalid_file_extension' => 'Разширението на файла "{extension}" не е разрешено. Разрешени разширения: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME типът "{mimeType}" не е разрешен. Разрешени MIME типове: {allowedMimeTypes}.',
        'file_not_found'         => 'Файлът не беше намерен на посочения път: {path}',
        'cannot_copy_file'       => 'Не може да се копира файл от "{source}" до "{destination}".',
        'database_error'         => 'Възникна грешка при запазване на актива в базата данни: {errors}',
        'page_forbidden'         => 'Нямате разрешение за достъп до тази страница.',
        'variant_not_found'      => 'Заявеният вариант "{variantName}" не беше намерен.',
        'token_invalid'          => 'Предоставеният токен е невалиден или е изтекъл.',
    ],
];
