<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => '提供的文件无效或不存在：{path}',
        'invalid_entity'         => '实体必须实现 HasAssetsEntityTrait 才能添加资产。实体：{entity}',
        'file_name_not_allowed'  => '文件名 "{fileName}" 不允许。',
        'file_too_large'         => '文件大小（{fileSize} 字节）超过了最大允许大小 {maxFileSize} 字节。',
        'invalid_file_extension' => '文件扩展名 "{extension}" 不允许。允许的扩展名：{allowedExtensions}。',
        'invalid_mime_type'      => 'MIME 类型 "{mimeType}" 不允许。允许的 MIME 类型：{allowedMimeTypes}。',
        'file_not_found'         => '在指定路径找不到文件：{path}',
        'cannot_copy_file'       => '无法将文件从 "{source}" 复制到 "{destination}"。',
        'database_error'         => '将资产保存到数据库时发生错误：{errors}',
        'page_forbidden'         => '您无权访问此页面。',
        'variant_not_found'      => '未找到请求的变体 "{variantName}"。',
        'token_invalid'          => '提供的令牌无效或已过期。',
    ],
];
