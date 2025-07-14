<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Наданий файл недійсний або не існує: {path}',
        'invalid_entity'         => 'Сутність повинна реалізувати HasAssetsEntityTrait для додавання активів. Сутність: {entity}',
        'file_name_not_allowed'  => 'Ім\'я файлу "{fileName}" не дозволено.',
        'file_too_large'         => 'Розмір файлу ({fileSize} байт) перевищує максимально допустимий розмір {maxFileSize} байт.',
        'invalid_file_extension' => 'Розширення файлу "{extension}" не дозволено. Дозволені розширення: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME-тип "{mimeType}" не дозволено. Дозволені MIME-типи: {allowedMimeTypes}.',
        'file_not_found'         => 'Файл не знайдено за вказаним шляхом: {path}',
        'cannot_copy_file'       => 'Неможливо скопіювати файл з "{source}" до "{destination}".',
        'database_error'         => 'Сталася помилка під час збереження активу в базі даних: {errors}',
        'page_forbidden'         => 'У вас немає дозволу на доступ до цієї сторінки.',
        'variant_not_found'      => 'Запитаний варіант "{variantName}" не знайдено.',
        'token_invalid'          => 'Наданий токен недійсний або термін його дії закінчився.',
    ],
];
