<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Tệp được cung cấp không hợp lệ hoặc không tồn tại: {path}',
        'invalid_entity'         => 'Thực thể phải triển khai HasAssetsEntityTrait để thêm tài sản. Thực thể: {entity}',
        'file_name_not_allowed'  => 'Tên tệp "{fileName}" không được phép.',
        'file_too_large'         => 'Kích thước tệp ({fileSize} byte) vượt quá kích thước tối đa cho phép là {maxFileSize} byte.',
        'invalid_file_extension' => 'Phần mở rộng tệp "{extension}" không được phép. Phần mở rộng được phép: {allowedExtensions}.',
        'invalid_mime_type'      => 'Kiểu MIME "{mimeType}" không được phép. Kiểu MIME được phép: {allowedMimeTypes}.',
        'file_not_found'         => 'Không tìm thấy tệp tại đường dẫn đã chỉ định: {path}',
        'cannot_copy_file'       => 'Không thể sao chép tệp từ "{source}" đến "{destination}".',
        'database_error'         => 'Đã xảy ra lỗi khi lưu tài sản vào cơ sở dữ liệu: {errors}',
        'page_forbidden'         => 'Bạn không có quyền truy cập trang này.',
        'variant_not_found'      => 'Không tìm thấy biến thể được yêu cầu "{variantName}".',
        'token_invalid'          => 'Token được cung cấp không hợp lệ hoặc đã hết hạn.',
    ],
];
