<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => '提供的檔案無效或不存在：{path}',
        'invalid_entity'         => '實體必須實現 HasAssetsEntityTrait 才能添加資產。實體：{entity}',
        'file_name_not_allowed'  => '檔案名稱 "{fileName}" 不允許。',
        'file_too_large'         => '檔案大小（{fileSize} 位元組）超過了最大允許大小 {maxFileSize} 位元組。',
        'invalid_file_extension' => '檔案擴展名 "{extension}" 不允許。允許的擴展名：{allowedExtensions}。',
        'invalid_mime_type'      => 'MIME 類型 "{mimeType}" 不允許。允許的 MIME 類型：{allowedMimeTypes}。',
        'file_not_found'         => '在指定路徑找不到檔案：{path}',
        'cannot_copy_file'       => '無法將檔案從 "{source}" 複製到 "{destination}"。',
        'database_error'         => '將資產保存到資料庫時發生錯誤：{errors}',
        'page_forbidden'         => '您無權訪問此頁面。',
        'variant_not_found'      => '未找到請求的變體 "{variantName}"。',
        'token_invalid'          => '提供的權杖無效或已過期。',
    ],
];
