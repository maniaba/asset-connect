<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => '提供されたファイルは無効であるか、存在しません: {path}',
        'invalid_entity'         => 'アセットを追加するには、エンティティはHasAssetsEntityTraitを実装する必要があります。エンティティ: {entity}',
        'file_name_not_allowed'  => 'ファイル名"{fileName}"は許可されていません。',
        'file_too_large'         => 'ファイルサイズ（{fileSize}バイト）が最大許容サイズ{maxFileSize}バイトを超えています。',
        'invalid_file_extension' => 'ファイル拡張子"{extension}"は許可されていません。許可される拡張子: {allowedExtensions}。',
        'invalid_mime_type'      => 'MIMEタイプ"{mimeType}"は許可されていません。許可されるMIMEタイプ: {allowedMimeTypes}。',
        'file_not_found'         => '指定されたパスにファイルが見つかりませんでした: {path}',
        'cannot_copy_file'       => '"{source}"から"{destination}"にファイルをコピーできません。',
        'database_error'         => 'アセットをデータベースに保存中にエラーが発生しました: {errors}',
        'page_forbidden'         => 'このページにアクセスする権限がありません。',
        'variant_not_found'      => '要求されたバリアント"{variantName}"が見つかりませんでした。',
        'token_invalid'          => '提供されたトークンは無効であるか、期限切れです。',
    ],
];
