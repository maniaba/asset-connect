<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'O ficheiro fornecido não é válido ou não existe: {path}',
        'invalid_entity'         => 'A entidade deve implementar o HasAssetsEntityTrait para adicionar ativos. Entidade: {entity}',
        'file_name_not_allowed'  => 'O nome do ficheiro "{fileName}" não é permitido.',
        'file_too_large'         => 'O tamanho do ficheiro ({fileSize} bytes) excede o tamanho máximo permitido de {maxFileSize} bytes.',
        'invalid_file_extension' => 'A extensão do ficheiro "{extension}" não é permitida. Extensões permitidas: {allowedExtensions}.',
        'invalid_mime_type'      => 'O tipo MIME "{mimeType}" não é permitido. Tipos MIME permitidos: {allowedMimeTypes}.',
        'file_not_found'         => 'O ficheiro não foi encontrado no caminho especificado: {path}',
        'cannot_copy_file'       => 'Não é possível copiar o ficheiro de "{source}" para "{destination}".',
        'database_error'         => 'Ocorreu um erro ao guardar o ativo na base de dados: {errors}',
        'page_forbidden'         => 'Não tem permissão para aceder a esta página.',
        'variant_not_found'      => 'A variante solicitada "{variantName}" não foi encontrada.',
        'token_invalid'          => 'O token fornecido é inválido ou expirou.',
    ],
];
