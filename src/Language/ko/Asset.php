<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => '제공된 파일이 유효하지 않거나 존재하지 않습니다: {path}',
        'invalid_entity'         => '자산을 추가하려면 엔티티가 HasAssetsEntityTrait를 구현해야 합니다. 엔티티: {entity}',
        'file_name_not_allowed'  => '파일 이름 "{fileName}"은(는) 허용되지 않습니다.',
        'file_too_large'         => '파일 크기({fileSize} 바이트)가 최대 허용 크기인 {maxFileSize} 바이트를 초과합니다.',
        'invalid_file_extension' => '파일 확장자 "{extension}"은(는) 허용되지 않습니다. 허용되는 확장자: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME 유형 "{mimeType}"은(는) 허용되지 않습니다. 허용되는 MIME 유형: {allowedMimeTypes}.',
        'file_not_found'         => '지정된 경로에서 파일을 찾을 수 없습니다: {path}',
        'cannot_copy_file'       => '"{source}"에서 "{destination}"(으)로 파일을 복사할 수 없습니다.',
        'database_error'         => '자산을 데이터베이스에 저장하는 동안 오류가 발생했습니다: {errors}',
        'page_forbidden'         => '이 페이지에 접근할 권한이 없습니다.',
        'variant_not_found'      => '요청한 변형 "{variantName}"을(를) 찾을 수 없습니다.',
        'token_invalid'          => '제공된 토큰이 유효하지 않거나 만료되었습니다.',
    ],
];
