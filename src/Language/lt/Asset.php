<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Pateiktas failas yra neteisingas arba neegzistuoja: {path}',
        'invalid_entity'         => 'Objektas turi įgyvendinti HasAssetsEntityTrait, kad galėtų pridėti išteklius. Objektas: {entity}',
        'file_name_not_allowed'  => 'Failo pavadinimas "{fileName}" neleidžiamas.',
        'file_too_large'         => 'Failo dydis ({fileSize} baitų) viršija maksimalų leistiną dydį {maxFileSize} baitų.',
        'invalid_file_extension' => 'Failo plėtinys "{extension}" neleidžiamas. Leidžiami plėtiniai: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME tipas "{mimeType}" neleidžiamas. Leidžiami MIME tipai: {allowedMimeTypes}.',
        'file_not_found'         => 'Failas nerastas nurodytame kelyje: {path}',
        'cannot_copy_file'       => 'Nepavyksta nukopijuoti failo iš "{source}" į "{destination}".',
        'database_error'         => 'Įvyko klaida išsaugant išteklių duomenų bazėje: {errors}',
        'page_forbidden'         => 'Jūs neturite leidimo pasiekti šį puslapį.',
        'variant_not_found'      => 'Prašomas variantas "{variantName}" nerastas.',
        'token_invalid'          => 'Pateiktas žetonas yra neteisingas arba baigėsi jo galiojimo laikas.',
    ],
];
