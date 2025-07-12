<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'الملف المقدم غير صالح أو غير موجود: {path}',
        'invalid_entity'         => 'يجب أن يقوم الكيان بتنفيذ HasAssetsEntityTrait لإضافة الأصول. الكيان: {entity}',
        'file_name_not_allowed'  => 'اسم الملف "{fileName}" غير مسموح به.',
        'file_too_large'         => 'حجم الملف ({fileSize} بايت) يتجاوز الحجم الأقصى المسموح به وهو {maxFileSize} بايت.',
        'invalid_file_extension' => 'امتداد الملف "{extension}" غير مسموح به. الامتدادات المسموح بها: {allowedExtensions}.',
        'invalid_mime_type'      => 'نوع MIME "{mimeType}" غير مسموح به. أنواع MIME المسموح بها: {allowedMimeTypes}.',
        'file_not_found'         => 'لم يتم العثور على الملف في المسار المحدد: {path}',
        'cannot_copy_file'       => 'لا يمكن نسخ الملف من "{source}" إلى "{destination}".',
        'database_error'         => 'حدث خطأ أثناء حفظ الأصل في قاعدة البيانات: {errors}',
        'page_forbidden'         => 'ليس لديك إذن للوصول إلى هذه الصفحة.',
        'variant_not_found'      => 'لم يتم العثور على المتغير المطلوب "{variantName}".',
        'token_invalid'          => 'الرمز المقدم غير صالح أو انتهت صلاحيته.',
    ],
];
