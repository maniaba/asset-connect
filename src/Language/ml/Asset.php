<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'നൽകിയ ഫയൽ സാധുവല്ല അല്ലെങ്കിൽ നിലവിലില്ല: {path}',
        'invalid_entity'         => 'ആസ്തികൾ ചേർക്കുന്നതിന് എന്റിറ്റി HasAssetsEntityTrait നടപ്പിലാക്കണം. എന്റിറ്റി: {entity}',
        'file_name_not_allowed'  => 'ഫയൽ നാമം "{fileName}" അനുവദനീയമല്ല.',
        'file_too_large'         => 'ഫയൽ വലുപ്പം ({fileSize} ബൈറ്റുകൾ) പരമാവധി അനുവദനീയമായ വലുപ്പമായ {maxFileSize} ബൈറ്റുകൾ കവിയുന്നു.',
        'invalid_file_extension' => 'ഫയൽ എക്സ്റ്റൻഷൻ "{extension}" അനുവദനീയമല്ല. അനുവദനീയമായ എക്സ്റ്റൻഷനുകൾ: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME തരം "{mimeType}" അനുവദനീയമല്ല. അനുവദനീയമായ MIME തരങ്ങൾ: {allowedMimeTypes}.',
        'file_not_found'         => 'വ്യക്തമാക്കിയ പാതയിൽ ഫയൽ കണ്ടെത്തിയില്ല: {path}',
        'cannot_copy_file'       => '"{source}" എന്നതിൽ നിന്ന് "{destination}" എന്നതിലേക്ക് ഫയൽ പകർത്താൻ കഴിയില്ല.',
        'database_error'         => 'ആസ്തി ഡാറ്റാബേസിൽ സംരക്ഷിക്കുമ്പോൾ ഒരു പിശക് സംഭവിച്ചു: {errors}',
        'page_forbidden'         => 'ഈ പേജ് ആക്സസ് ചെയ്യാൻ നിങ്ങൾക്ക് അനുമതിയില്ല.',
        'variant_not_found'      => 'അഭ്യർത്ഥിച്ച വേരിയന്റ് "{variantName}" കണ്ടെത്തിയില്ല.',
        'token_invalid'          => 'നൽകിയ ടോക്കൺ അസാധുവാണ് അല്ലെങ്കിൽ കാലഹരണപ്പെട്ടു.',
    ],
];
