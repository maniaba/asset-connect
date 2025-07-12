<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'සපයා ඇති ගොනුව වලංගු නොවේ හෝ නොපවතී: {path}',
        'invalid_entity'         => 'වත්කම් එකතු කිරීමට අස්තිත්වය HasAssetsEntityTrait ක්‍රියාත්මක කළ යුතුය. අස්තිත්වය: {entity}',
        'file_name_not_allowed'  => 'ගොනු නාමය "{fileName}" අවසර නැත.',
        'file_too_large'         => 'ගොනු ප්‍රමාණය ({fileSize} බයිට) උපරිම අවසර ප්‍රමාණය {maxFileSize} බයිට ඉක්මවා යයි.',
        'invalid_file_extension' => 'ගොනු දිගුව "{extension}" අවසර නැත. අවසර දිගු: {allowedExtensions}.',
        'invalid_mime_type'      => 'MIME වර්ගය "{mimeType}" අවසර නැත. අවසර MIME වර්ග: {allowedMimeTypes}.',
        'file_not_found'         => 'නියමිත මාර්ගයේ ගොනුව හමු නොවීය: {path}',
        'cannot_copy_file'       => '"{source}" සිට "{destination}" දක්වා ගොනුව පිටපත් කළ නොහැක.',
        'database_error'         => 'දත්ත සමුදායට වත්කම සුරැකීමේදී දෝෂයක් ඇති විය: {errors}',
        'page_forbidden'         => 'ඔබට මෙම පිටුවට ප්‍රවේශ වීමට අවසර නැත.',
        'variant_not_found'      => 'ඉල්ලූ විකල්පය "{variantName}" හමු නොවීය.',
        'token_invalid'          => 'සපයා ඇති ටෝකනය වලංගු නොවේ හෝ කල් ඉකුත් වී ඇත.',
    ],
];
