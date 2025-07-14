<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'પ્રદાન કરેલી ફાઇલ માન્ય નથી અથવા અસ્તિત્વમાં નથી: {path}',
        'invalid_entity'         => 'એસેટ્સ ઉમેરવા માટે એન્ટિટીએ HasAssetsEntityTrait લાગુ કરવું આવશ્યક છે. એન્ટિટી: {entity}',
        'file_name_not_allowed'  => 'ફાઇલ નામ "{fileName}" મંજૂર નથી.',
        'file_too_large'         => 'ફાઇલનું કદ ({fileSize} બાઇટ્સ) મહત્તમ માન્ય કદ {maxFileSize} બાઇટ્સથી વધી જાય છે.',
        'invalid_file_extension' => 'ફાઇલ એક્સ્ટેન્શન "{extension}" મંજૂર નથી. માન્ય એક્સ્ટેન્શન: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME પ્રકાર "{mimeType}" મંજૂર નથી. માન્ય MIME પ્રકારો: {allowedMimeTypes}.',
        'file_not_found'         => 'નિર્દિષ્ટ પાથ પર ફાઇલ મળી નથી: {path}',
        'cannot_copy_file'       => '"{source}" થી "{destination}" પર ફાઇલ કૉપિ કરી શકાતી નથી.',
        'database_error'         => 'ડેટાબેઝમાં એસેટ સાચવતી વખતે એક ભૂલ આવી: {errors}',
        'page_forbidden'         => 'તમને આ પૃષ્ઠને ઍક્સેસ કરવાની પરવાનગી નથી.',
        'variant_not_found'      => 'વિનંતી કરેલ વેરિઅન્ટ "{variantName}" મળ્યું નથી.',
        'token_invalid'          => 'પ્રદાન કરેલ ટોકન અમાન્ય છે અથવા સમાપ્ત થઈ ગયું છે.',
    ],
];
