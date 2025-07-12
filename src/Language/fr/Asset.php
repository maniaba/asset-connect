<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Le fichier fourni n\'est pas valide ou n\'existe pas : {path}',
        'invalid_entity'         => 'L\'entité doit implémenter HasAssetsEntityTrait pour ajouter des actifs. Entité : {entity}',
        'file_name_not_allowed'  => 'Le nom de fichier "{fileName}" n\'est pas autorisé.',
        'file_too_large'         => 'La taille du fichier ({fileSize} octets) dépasse la taille maximale autorisée de {maxFileSize} octets.',
        'invalid_file_extension' => 'L\'extension de fichier "{extension}" n\'est pas autorisée. Extensions autorisées : {allowedExtensions}.',
        'invalid_mime_type'      => 'Le type MIME "{mimeType}" n\'est pas autorisé. Types MIME autorisés : {allowedMimeTypes}.',
        'file_not_found'         => 'Le fichier n\'a pas été trouvé au chemin spécifié : {path}',
        'cannot_copy_file'       => 'Impossible de copier le fichier de "{source}" vers "{destination}".',
        'database_error'         => 'Une erreur s\'est produite lors de l\'enregistrement de l\'actif dans la base de données : {errors}',
        'page_forbidden'         => 'Vous n\'avez pas la permission d\'accéder à cette page.',
        'variant_not_found'      => 'La variante demandée "{variantName}" n\'a pas été trouvée.',
        'token_invalid'          => 'Le jeton fourni n\'est pas valide ou a expiré.',
    ],
];
