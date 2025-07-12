<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'வழங்கப்பட்ட கோப்பு செல்லுபடியாகாதது அல்லது இல்லை: {path}',
        'invalid_entity'         => 'சொத்துகளைச் சேர்க்க நிறுவனம் HasAssetsEntityTrait ஐ செயல்படுத்த வேண்டும். நிறுவனம்: {entity}',
        'file_name_not_allowed'  => 'கோப்பு பெயர் "{fileName}" அனுமதிக்கப்படவில்லை.',
        'file_too_large'         => 'கோப்பின் அளவு ({fileSize} பைட்டுகள்) அதிகபட்ச அனுமதிக்கப்பட்ட அளவான {maxFileSize} பைட்டுகளை விஞ்சுகிறது.',
        'invalid_file_extension' => 'கோப்பு நீட்டிப்பு "{extension}" அனுமதிக்கப்படவில்லை. அனுமதிக்கப்பட்ட நீட்டிப்புகள்: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME வகை "{mimeType}" அனுமதிக்கப்படவில்லை. அனுமதிக்கப்பட்ட MIME வகைகள்: {allowedMimeTypes}.',
        'file_not_found'         => 'குறிப்பிட்ட பாதையில் கோப்பு காணப்படவில்லை: {path}',
        'cannot_copy_file'       => '"{source}" இலிருந்து "{destination}" க்கு கோப்பை நகலெடுக்க முடியாது.',
        'database_error'         => 'சொத்தை தரவுத்தளத்தில் சேமிக்கும் போது பிழை ஏற்பட்டது: {errors}',
        'page_forbidden'         => 'இந்தப் பக்கத்தை அணுக உங்களுக்கு அனுமதி இல்லை.',
        'variant_not_found'      => 'கோரப்பட்ட மாறுபாடு "{variantName}" காணப்படவில்லை.',
        'token_invalid'          => 'வழங்கப்பட்ட டோக்கன் செல்லுபடியாகாதது அல்லது காலாவதியானது.',
    ],
];
