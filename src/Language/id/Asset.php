<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'File yang diberikan tidak valid atau tidak ada: {path}',
        'invalid_entity'         => 'Entitas harus mengimplementasikan HasAssetsEntityTrait untuk menambahkan aset. Entitas: {entity}',
        'file_name_not_allowed'  => 'Nama file "{fileName}" tidak diizinkan.',
        'file_too_large'         => 'Ukuran file ({fileSize} byte) melebihi ukuran maksimum yang diizinkan yaitu {maxFileSize} byte.',
        'invalid_file_extension' => 'Ekstensi file "{extension}" tidak diizinkan. Ekstensi yang diizinkan: {allowedExtensions}.',
        'invalid_mime_type'      => 'Tipe MIME "{mimeType}" tidak diizinkan. Tipe MIME yang diizinkan: {allowedMimeTypes}.',
        'file_not_found'         => 'File tidak ditemukan di jalur yang ditentukan: {path}',
        'cannot_copy_file'       => 'Tidak dapat menyalin file dari "{source}" ke "{destination}".',
        'database_error'         => 'Terjadi kesalahan saat menyimpan aset ke database: {errors}',
        'page_forbidden'         => 'Anda tidak memiliki izin untuk mengakses halaman ini.',
        'variant_not_found'      => 'Varian yang diminta "{variantName}" tidak ditemukan.',
        'token_invalid'          => 'Token yang diberikan tidak valid atau telah kedaluwarsa.',
    ],
];
