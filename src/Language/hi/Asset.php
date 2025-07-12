<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'प्रदान की गई फ़ाइल अमान्य है या मौजूद नहीं है: {path}',
        'invalid_entity'         => 'एसेट्स जोड़ने के लिए एंटिटी को HasAssetsEntityTrait को लागू करना चाहिए। एंटिटी: {entity}',
        'file_name_not_allowed'  => 'फ़ाइल नाम "{fileName}" की अनुमति नहीं है।',
        'file_too_large'         => 'फ़ाइल का आकार ({fileSize} बाइट्स) अधिकतम अनुमत आकार {maxFileSize} बाइट्स से अधिक है।',
        'invalid_file_extension' => 'फ़ाइल एक्सटेंशन "{extension}" की अनुमति नहीं है। अनुमत एक्सटेंशन: {allowedExtensions}।',
        'invalid_mime_type'      => 'MIME प्रकार "{mimeType}" की अनुमति नहीं है। अनुमत MIME प्रकार: {allowedMimeTypes}।',
        'file_not_found'         => 'निर्दिष्ट पथ पर फ़ाइल नहीं मिली: {path}',
        'cannot_copy_file'       => '"{source}" से "{destination}" तक फ़ाइल कॉपी नहीं कर सकते।',
        'database_error'         => 'डेटाबेस में एसेट सहेजते समय एक त्रुटि हुई: {errors}',
        'page_forbidden'         => 'आपको इस पृष्ठ तक पहुंचने की अनुमति नहीं है।',
        'variant_not_found'      => 'अनुरोधित वेरिएंट "{variantName}" नहीं मिला।',
        'token_invalid'          => 'प्रदान किया गया टोकन अमान्य है या समाप्त हो गया है।',
    ],
];
