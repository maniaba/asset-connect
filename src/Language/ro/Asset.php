<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Fișierul furnizat nu este valid sau nu există: {path}',
        'invalid_entity'         => 'Entitatea trebuie să implementeze HasAssetsEntityTrait pentru a adăuga active. Entitate: {entity}',
        'file_name_not_allowed'  => 'Numele fișierului "{fileName}" nu este permis.',
        'file_too_large'         => 'Dimensiunea fișierului ({fileSize} bytes) depășește dimensiunea maximă permisă de {maxFileSize} bytes.',
        'invalid_file_extension' => 'Extensia fișierului "{extension}" nu este permisă. Extensii permise: {allowedExtensions}.',
        'invalid_mime_type'      => 'Tipul MIME "{mimeType}" nu este permis. Tipuri MIME permise: {allowedMimeTypes}.',
        'file_not_found'         => 'Fișierul nu a fost găsit la calea specificată: {path}',
        'cannot_copy_file'       => 'Nu se poate copia fișierul de la "{source}" la "{destination}".',
        'database_error'         => 'A apărut o eroare la salvarea activului în baza de date: {errors}',
        'page_forbidden'         => 'Nu aveți permisiunea de a accesa această pagină.',
        'variant_not_found'      => 'Varianta solicitată "{variantName}" nu a fost găsită.',
        'token_invalid'          => 'Tokenul furnizat este invalid sau a expirat.',
    ],
];
