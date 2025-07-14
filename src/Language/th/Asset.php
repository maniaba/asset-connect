<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'ไฟล์ที่ให้มาไม่ถูกต้องหรือไม่มีอยู่: {path}',
        'invalid_entity'         => 'เอนทิตีต้องใช้ HasAssetsEntityTrait เพื่อเพิ่มสินทรัพย์ เอนทิตี: {entity}',
        'file_name_not_allowed'  => 'ชื่อไฟล์ "{fileName}" ไม่ได้รับอนุญาต',
        'file_too_large'         => 'ขนาดไฟล์ ({fileSize} ไบต์) เกินขนาดสูงสุดที่อนุญาตคือ {maxFileSize} ไบต์',
        'invalid_file_extension' => 'นามสกุลไฟล์ "{extension}" ไม่ได้รับอนุญาต นามสกุลที่อนุญาต: {allowedExtensions}',
        'invalid_mime_type'      => 'ประเภท MIME "{mimeType}" ไม่ได้รับอนุญาต ประเภท MIME ที่อนุญาต: {allowedMimeTypes}',
        'file_not_found'         => 'ไม่พบไฟล์ที่เส้นทางที่ระบุ: {path}',
        'cannot_copy_file'       => 'ไม่สามารถคัดลอกไฟล์จาก "{source}" ไปยัง "{destination}"',
        'database_error'         => 'เกิดข้อผิดพลาดขณะบันทึกสินทรัพย์ลงในฐานข้อมูล: {errors}',
        'page_forbidden'         => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้',
        'variant_not_found'      => 'ไม่พบรูปแบบที่ร้องขอ "{variantName}"',
        'token_invalid'          => 'โทเค็นที่ให้มาไม่ถูกต้องหรือหมดอายุแล้ว',
    ],
];
