<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Предоставленный файл недействителен или не существует: {path}',
        'invalid_entity'         => 'Сущность должна реализовывать HasAssetsEntityTrait для добавления активов. Сущность: {entity}',
        'file_name_not_allowed'  => 'Имя файла "{fileName}" не разрешено.',
        'file_too_large'         => 'Размер файла ({fileSize} байт) превышает максимально допустимый размер {maxFileSize} байт.',
        'invalid_file_extension' => 'Расширение файла "{extension}" не разрешено. Разрешенные расширения: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME-тип "{mimeType}" не разрешен. Разрешенные MIME-типы: {allowedMimeTypes}.',
        'file_not_found'         => 'Файл не найден по указанному пути: {path}',
        'cannot_copy_file'       => 'Невозможно скопировать файл из "{source}" в "{destination}".',
        'database_error'         => 'Произошла ошибка при сохранении актива в базе данных: {errors}',
        'page_forbidden'         => 'У вас нет разрешения на доступ к этой странице.',
        'variant_not_found'      => 'Запрошенный вариант "{variantName}" не найден.',
        'token_invalid'          => 'Предоставленный токен недействителен или срок его действия истек.',
    ],
];
