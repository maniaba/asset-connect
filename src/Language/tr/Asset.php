<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Sağlanan dosya geçerli değil veya mevcut değil: {path}',
        'invalid_entity'         => 'Varlık, varlıklar eklemek için HasAssetsEntityTrait\'i uygulamalıdır. Varlık: {entity}',
        'file_name_not_allowed'  => '"{fileName}" dosya adına izin verilmiyor.',
        'file_too_large'         => 'Dosya boyutu ({fileSize} bayt) izin verilen maksimum boyut olan {maxFileSize} baytı aşıyor.',
        'invalid_file_extension' => '"{extension}" dosya uzantısına izin verilmiyor. İzin verilen uzantılar: {allowedExtensions}.',
        'invalid_mime_type'      => '"{mimeType}" MIME türüne izin verilmiyor. İzin verilen MIME türleri: {allowedMimeTypes}.',
        'file_not_found'         => 'Dosya belirtilen yolda bulunamadı: {path}',
        'cannot_copy_file'       => 'Dosya "{source}" konumundan "{destination}" konumuna kopyalanamıyor.',
        'database_error'         => 'Varlık veritabanına kaydedilirken bir hata oluştu: {errors}',
        'page_forbidden'         => 'Bu sayfaya erişim izniniz yok.',
        'variant_not_found'      => 'İstenen varyant "{variantName}" bulunamadı.',
        'token_invalid'          => 'Sağlanan token geçersiz veya süresi dolmuş.',
    ],
];
