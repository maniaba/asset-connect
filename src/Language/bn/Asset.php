<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'প্রদত্ত ফাইলটি বৈধ নয় বা বিদ্যমান নেই: {path}',
        'invalid_entity'         => 'সম্পদ যোগ করতে এনটিটিকে HasAssetsEntityTrait বাস্তবায়ন করতে হবে। এনটিটি: {entity}',
        'file_name_not_allowed'  => 'ফাইলের নাম "{fileName}" অনুমোদিত নয়।',
        'file_too_large'         => 'ফাইলের আকার ({fileSize} বাইট) সর্বাধিক অনুমোদিত আকার {maxFileSize} বাইট অতিক্রম করেছে।',
        'invalid_file_extension' => 'ফাইল এক্সটেনশন "{extension}" অনুমোদিত নয়। অনুমোদিত এক্সটেনশন: {allowedExtensions}।',
        'invalid_mime_type'      => 'MIME টাইপ "{mimeType}" অনুমোদিত নয়। অনুমোদিত MIME টাইপ: {allowedMimeTypes}।',
        'file_not_found'         => 'নির্দিষ্ট পাথে ফাইল পাওয়া যায়নি: {path}',
        'cannot_copy_file'       => '"{source}" থেকে "{destination}" এ ফাইল কপি করা যাচ্ছে না।',
        'database_error'         => 'ডাটাবেসে সম্পদ সংরক্ষণ করার সময় একটি ত্রুটি ঘটেছে: {errors}',
        'page_forbidden'         => 'আপনার এই পৃষ্ঠা অ্যাক্সেস করার অনুমতি নেই।',
        'variant_not_found'      => 'অনুরোধকৃত ভেরিয়েন্ট "{variantName}" পাওয়া যায়নি।',
        'token_invalid'          => 'প্রদত্ত টোকেন অবৈধ বা মেয়াদ শেষ হয়ে গেছে।',
    ],
];
