<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'فایل ارائه شده معتبر نیست یا وجود ندارد: {path}',
        'invalid_entity'         => 'موجودیت باید HasAssetsEntityTrait را برای افزودن دارایی‌ها پیاده‌سازی کند. موجودیت: {entity}',
        'file_name_not_allowed'  => 'نام فایل "{fileName}" مجاز نیست.',
        'file_too_large'         => 'اندازه فایل ({fileSize} بایت) از حداکثر اندازه مجاز {maxFileSize} بایت بیشتر است.',
        'invalid_file_extension' => 'پسوند فایل "{extension}" مجاز نیست. پسوندهای مجاز: {allowedExtensions}.',
        'invalid_mime_type'      => 'نوع MIME "{mimeType}" مجاز نیست. انواع MIME مجاز: {allowedMimeTypes}.',
        'file_not_found'         => 'فایل در مسیر مشخص شده یافت نشد: {path}',
        'cannot_copy_file'       => 'نمی‌توان فایل را از "{source}" به "{destination}" کپی کرد.',
        'database_error'         => 'هنگام ذخیره دارایی در پایگاه داده خطایی رخ داد: {errors}',
        'page_forbidden'         => 'شما اجازه دسترسی به این صفحه را ندارید.',
        'variant_not_found'      => 'نسخه درخواستی "{variantName}" یافت نشد.',
        'token_invalid'          => 'توکن ارائه شده نامعتبر است یا منقضی شده است.',
    ],
];
