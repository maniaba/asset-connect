<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'O arquivo fornecido não é válido ou não existe: {path}',
        'invalid_entity'         => 'A entidade deve implementar o HasAssetsEntityTrait para adicionar ativos. Entidade: {entity}',
        'file_name_not_allowed'  => 'O nome do arquivo "{fileName}" não é permitido.',
        'file_too_large'         => 'O tamanho do arquivo ({fileSize} bytes) excede o tamanho máximo permitido de {maxFileSize} bytes.',
        'invalid_file_extension' => 'A extensão do arquivo "{extension}" não é permitida. Extensões permitidas: {allowedExtensions}.',
        'invalid_mime_type'      => 'O tipo MIME "{mimeType}" não é permitido. Tipos MIME permitidos: {allowedMimeTypes}.',
        'file_not_found'         => 'O arquivo não foi encontrado no caminho especificado: {path}',
        'cannot_copy_file'       => 'Não é possível copiar o arquivo de "{source}" para "{destination}".',
        'database_error'         => 'Ocorreu um erro ao salvar o ativo no banco de dados: {errors}',
        'page_forbidden'         => 'Você não tem permissão para acessar esta página.',
        'variant_not_found'      => 'A variante solicitada "{variantName}" não foi encontrada.',
        'token_invalid'          => 'O token fornecido é inválido ou expirou.',
    ],
];
