<?php

declare(strict_types=1);

return [
    'exception' => [
        'invalid_file'           => 'Το παρεχόμενο αρχείο δεν είναι έγκυρο ή δεν υπάρχει: {path}',
        'invalid_entity'         => 'Η οντότητα πρέπει να υλοποιεί το HasAssetsEntityTrait για να προσθέσει στοιχεία. Οντότητα: {entity}',
        'file_name_not_allowed'  => 'Το όνομα αρχείου "{fileName}" δεν επιτρέπεται.',
        'file_too_large'         => 'Το μέγεθος του αρχείου ({fileSize} bytes) υπερβαίνει το μέγιστο επιτρεπόμενο μέγεθος των {maxFileSize} bytes.',
        'invalid_file_extension' => 'Η επέκταση αρχείου "{extension}" δεν επιτρέπεται. Επιτρεπόμενες επεκτάσεις: {allowedExtensions}.',
        'invalid_mime_type'      => 'Ο τύπος MIME "{mimeType}" δεν επιτρέπεται. Επιτρεπόμενοι τύποι MIME: {allowedMimeTypes}.',
        'file_not_found'         => 'Το αρχείο δεν βρέθηκε στη συγκεκριμένη διαδρομή: {path}',
        'cannot_copy_file'       => 'Δεν είναι δυνατή η αντιγραφή αρχείου από "{source}" σε "{destination}".',
        'database_error'         => 'Παρουσιάστηκε σφάλμα κατά την αποθήκευση του στοιχείου στη βάση δεδομένων: {errors}',
        'page_forbidden'         => 'Δεν έχετε άδεια πρόσβασης σε αυτήν τη σελίδα.',
        'variant_not_found'      => 'Η ζητούμενη παραλλαγή "{variantName}" δεν βρέθηκε.',
        'token_invalid'          => 'Το παρεχόμενο διακριτικό δεν είναι έγκυρο ή έχει λήξει.',
    ],
];
