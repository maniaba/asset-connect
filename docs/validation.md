# Validation

This page covers validation of file uploads in your application using the `AssetConnectValidator` class. This class works together with the `ValidationRuleCollector` to generate validation rules based on asset collection definitions and validate data against these rules.

## Overview

The `AssetConnectValidator` class provides a flexible way to validate file uploads by:

1. Generating validation rules from asset collection definitions
2. Supporting validation of multiple fields with different collection definitions
3. Providing methods to validate data against these rules
4. Supporting validation directly from HTTP requests

## Basic Usage

```php
// Get a collection definition
$collectionDefinition = new YourCollectionDefinition();

// Create a validator
$validator = new \Maniaba\FileConnect\Validation\AssetConnectValidator($collectionDefinition);

// Set a field collection definition (required)
$validator->setFieldCollectionDefinition('file', $collectionDefinition);

// Validate data
$data = [
    'file' => $this->request->getFile('upload')
];

if ($validator->validate($data)) {
    // Validation passed
    // Process the file
} else {
    // Validation failed
    $errors = $validator->getErrors();
    // Handle errors
}
```

## Defining Field Names

You must define field names using the `setFieldCollectionDefinition` method:

```php
// Create a validator
$validator = new \Maniaba\FileConnect\Validation\AssetConnectValidator($collectionDefinition);

// Define a field name
$validator->setFieldCollectionDefinition('upload_file', $collectionDefinition);

// Now your data should use this field name
$data = [
    'upload_file' => $this->request->getFile('upload')
];

if ($validator->validate($data)) {
    // Validation passed
}
```

This allows you to have multiple file uploads with different field names.

## Using Different Collection Definitions for Different Fields

You can set different collection definitions for different fields using the `setFieldCollectionDefinition` method:

```php
// Create a validator
$validator = new \Maniaba\FileConnect\Validation\AssetConnectValidator(
    new DefaultCollectionDefinition()
);

// Set collection definitions for fields
$validator->setFieldCollectionDefinition('file1', new DefaultCollectionDefinition());
$validator->setFieldCollectionDefinition('file2', new DefaultCollectionDefinition());
$validator->setFieldCollectionDefinition('testfile', new NewCollectionDefinition());

// Validate data
$data = [
    'file1' => $this->request->getFile('upload1'),
    'file2' => $this->request->getFile('upload2'),
    'testfile' => $this->request->getFile('test')
];

if ($validator->validate($data)) {
    // Validation passed for all fields
    // Process the files
} else {
    // Validation failed
    $errors = $validator->getErrors();
    // Handle errors
}
```

This approach allows you to use a single validator instance for multiple fields, each with its own collection definition.

## Validating Specific Fields

You can validate specific fields instead of all fields:

```php
if ($validator->validateFields($data, 'file')) {
    // Validation passed for the 'file' field
    // Process the file
}
```

## Validating All Defined Fields

After setting up field collection definitions with `setFieldCollectionDefinition`, you can validate all defined fields without having to specify them again:

```php
// Create a validator
$validator = new \Maniaba\FileConnect\Validation\AssetConnectValidator(
    new DefaultCollectionDefinition()
);

// Set collection definitions for fields
$validator->setFieldCollectionDefinition('file1', new DefaultCollectionDefinition());
$validator->setFieldCollectionDefinition('file2', new DefaultCollectionDefinition());
$validator->setFieldCollectionDefinition('testfile', new NewCollectionDefinition());

// Validate all defined fields (file1, file2, testfile)
$data = [
    'file1' => $this->request->getFile('upload1'),
    'file2' => $this->request->getFile('upload2'),
    'testfile' => $this->request->getFile('test')
];

if ($validator->validateDefinedFields($data)) {
    // Validation passed for all defined fields
    // Process the files
} else {
    // Validation failed
    $errors = $validator->getErrors();
    // Handle errors
}
```

You can also get the names of all defined fields:

```php
$fieldNames = $validator->getDefinedFieldNames(); // Returns ['file1', 'file2', 'testfile']
```

And get the validation rules for all defined fields:

```php
$definedFieldRules = $validator->getRulesForDefinedFields();
```

## Validating Fields from Request

You can validate fields directly from the request using CodeIgniter's `withRequest` method:

```php
if ($validator->validateFieldsFromRequest('file1', 'profilePicture')) {
    // Validation passed for 'file1' and 'profilePicture' from the request
    // Process the files
}
```

This method uses CodeIgniter's validation with the request data directly, which simplifies the validation process. It gets the rules for the specified fields and applies them to the request data.

For multiple file uploads, you can still specify the field names as you would with other validation methods:

```php
if ($validator->validateFieldsFromRequest('file1', 'documents')) {
    // Validation passed for 'file1' and 'documents' from the request
    // Process the files
}
```

### Validating All Defined Fields from Request

Similar to `validateDefinedFields`, you can validate all defined fields directly from the request without having to specify them again:

```php
if ($validator->validateDefinedFieldsFromRequest()) {
    // Validation passed for all defined fields from the request
    // Process the files
} else {
    // Validation failed
    $errors = $validator->getErrors();
    // Handle errors
}
```

This is particularly useful when you've already defined your fields with `setFieldCollectionDefinition` and don't want to repeat the field names when validating.

## Getting Validation Rules and Errors

You can get all validation rules or rules for specific fields:

```php
// Get all rules
$allRules = $validator->getRules();

// Get rules for specific fields
$fileRules = $validator->getRulesForFields('file');

// Get rules for all defined fields
$definedFieldRules = $validator->getRulesForDefinedFields();

// Get validation errors
$errors = $validator->getErrors();
```

The `getRules()` method returns CodeIgniter 4 compatible validation rules for the current validator instance. This is particularly useful when you want to combine AssetConnectValidator rules with additional validation rules for other fields in your form and continue validation using CodeIgniter 4's validator.

### Example: Combining with CodeIgniter 4 Validator

```php
// Create AssetConnectValidator and set up rules for file uploads
$fileValidator = new \Maniaba\FileConnect\Validation\AssetConnectValidator();
$fileValidator->setFieldCollectionDefinition('profile_image', new ProfileImageCollection());

// Get the rules generated by AssetConnectValidator
$fileRules = $fileValidator->getRules();

// Create a standard CodeIgniter validator with additional rules
$validation = \Config\Services::validation();
$validation->setRules([
    // Add file upload rules from AssetConnectValidator
    ...$fileRules,

    // Add additional rules for other form fields
    'username' => 'required|min_length[5]|max_length[50]',
    'email' => 'required|valid_email',
    'age' => 'required|integer|greater_than[17]'
]);

// Run validation on all fields together
if ($validation->withRequest($this->request)->run()) {
    // All validation passed (both files and other fields)
    // Process the form data
} else {
    // Validation failed
    $errors = $validation->getErrors();
    // Handle errors
}
```

This approach allows you to seamlessly integrate file validation with other form field validations in your application.

## Example with a Collection Definition

Here's an example of how validation rules are generated from a collection definition:

```php
class ImagesCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions(AssetExtension::JPG, AssetExtension::PNG, AssetExtension::GIF)
            ->allowedMimeTypes(AssetMimeType::IMAGE_JPEG, AssetMimeType::IMAGE_PNG, AssetMimeType::IMAGE_GIF)
            ->setMaxFileSize(2 * 1024 * 1024) // 2MB
            ->singleFileCollection();
    }

    // ... other methods
}
```

When you set this collection definition for a field, the generated validation rules would be:

```php
// For field name 'image'
[
    'image' => 'uploaded[image]|ext_in[image,jpg,png,gif]|mime_in[image,image/jpeg,image/png,image/gif]|max_size[image,2048]|max_file_count[1]'
]
```

## Validating Multiple Fields with Different Collection Definitions

As shown in the previous section, you can use the `CollectionValidator` class to validate multiple fields with different collection definitions:

```php
// Create a validator
$validator = new \Maniaba\FileConnect\Validation\AssetConnectValidator(
    new ImagesCollection()
);

// Set collection definitions for fields
$validator->setFieldCollectionDefinition('file1', new ImagesCollection());
$validator->setFieldCollectionDefinition('multipleFileCollection', new DocumentsCollection());
$validator->setFieldCollectionDefinition('profilePicture', new AvatarCollection());

// Validate data
$data = [
    'file1' => $this->request->getFile('upload1'),
    'multipleFileCollection' => $this->request->getFileMultiple('documents'),
    'profilePicture' => $this->request->getFile('avatar')
];

if ($validator->validate($data)) {
    // Validation passed for all fields
    // Process the files
} else {
    // Validation failed
    $errors = $validator->getErrors();
    // Handle errors
}
```

You can also validate specific fields:

```php
if ($validator->validateFields($data, 'file1', 'profilePicture')) {
    // Validation passed for 'file1' and 'profilePicture'
    // Process these files
}
```

And get rules for specific fields:

```php
$file1Rules = $validator->getRulesForFields('file1');
```

### Legacy Approach: MultiFieldCollectionValidator

For backward compatibility, the `MultiFieldCollectionValidator` class is still available, but using the enhanced `CollectionValidator` class as shown above is the recommended approach:

```php
// Create a multi-field validator
$multiValidator = new \Maniaba\FileConnect\Validation\MultiFieldCollectionValidator();

// Add fields with their respective collection definitions
$multiValidator->addField('file1', new ImagesCollection());
$multiValidator->addField('multipleFileCollection', new DocumentsCollection());
$multiValidator->addField('profilePicture', new AvatarCollection());

// Validate data
$data = [
    'file1' => $this->request->getFile('upload1'),
    'multipleFileCollection' => $this->request->getFileMultiple('documents'),
    'profilePicture' => $this->request->getFile('avatar')
];

if ($multiValidator->validate($data)) {
    // Validation passed for all fields
    // Process the files
} else {
    // Validation failed
    $errors = $multiValidator->getErrors();
    // Handle errors
}
```

## Working with ValidationRuleCollector

The `ValidationRuleCollector` class is used internally by `AssetConnectValidator` to collect validation rules from asset collection definitions. It implements the `AssetCollectionSetterInterface` and provides methods to set various validation constraints.

### How They Work Together

1. `AssetConnectValidator` creates a `ValidationRuleCollector` for each field
2. The collection definition's `definition()` method is called with the rule collector
3. The rule collector collects validation rules based on the methods called on it
4. `AssetConnectValidator` retrieves the collected rules and uses them for validation

### ValidationRuleCollector Methods

The `ValidationRuleCollector` provides the following methods for setting validation constraints:

#### File Validation

- `allowedExtensions(AssetExtension|string ...$extensions)`: Validates file extensions
- `allowedMimeTypes(AssetMimeType|string ...$mimeTypes)`: Validates MIME types
- `setMaxFileSize(float|int $maxFileSize)`: Validates maximum file size
- `singleFileCollection()`: Ensures only a single file is uploaded
- `onlyKeepLatest(int $maximumNumberOfItemsInCollection)`: Limits the number of files

#### Image Validation

- `setMaxImageDimensions(int $width, int $height)`: Validates maximum image dimensions
- `setMinImageDimensions(int $width, int $height)`: Validates minimum image dimensions
- `requireImage()`: Ensures the file is an image

## Advanced Usage

### Validating Multiple File Uploads

For multiple file uploads, you can use the `onlyKeepLatest` method to limit the number of files:

```php
class MultipleImagesCollection implements AssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions(AssetExtension::JPG, AssetExtension::PNG)
            ->allowedMimeTypes(AssetMimeType::IMAGE_JPEG, AssetMimeType::IMAGE_PNG)
            ->setMaxFileSize(2 * 1024 * 1024) // 2MB
            ->requireImage()
            ->onlyKeepLatest(5); // Allow up to 5 images
    }
}
```

Then validate as usual:

```php
$validator->setFieldCollectionDefinition('gallery_images', new MultipleImagesCollection());

if ($validator->validateFieldsFromRequest('gallery_images')) {
    $images = $this->request->getFileMultiple('gallery_images');
    // Process the images
}
```

## Complete Example

Here's a complete example showing how to use `AssetConnectValidator` with multiple fields and different collection definitions:

```php
// Define collection definitions
class ProfileImageCollection implements AssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions(AssetExtension::JPG, AssetExtension::PNG)
            ->allowedMimeTypes(AssetMimeType::IMAGE_JPEG, AssetMimeType::IMAGE_PNG)
            ->setMaxFileSize(1 * 1024 * 1024) // 1MB
            ->setMaxImageDimensions(500, 500)
            ->requireImage()
            ->singleFileCollection();
    }
}

class DocumentsCollection implements AssetCollectionDefinitionInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions(AssetExtension::PDF, AssetExtension::DOC, AssetExtension::DOCX)
            ->allowedMimeTypes(
                AssetMimeType::APPLICATION_PDF,
                AssetMimeType::APPLICATION_MSWORD,
                AssetMimeType::APPLICATION_DOCX
            )
            ->setMaxFileSize(5 * 1024 * 1024) // 5MB
            ->onlyKeepLatest(3); // Allow up to 3 documents
    }
}

// In your controller
public function upload()
{
    // Create a validator
    $validator = new \Maniaba\FileConnect\Validation\AssetConnectValidator();

    // Set field collection definitions
    $validator->setFieldCollectionDefinition('profile_image', new ProfileImageCollection());
    $validator->setFieldCollectionDefinition('documents', new DocumentsCollection());

    // Validate from request
    if ($validator->validateRequest()) {
        // Validation passed

        // Get the files
        $profileImage = $this->request->getFile('profile_image');
        $documents = $this->request->getFileMultiple('documents');

        // Process the files
        // ...

        return redirect()->to('/success')->with('message', 'Files uploaded successfully');
    } else {
        // Validation failed
        $errors = $validator->getErrors();

        return redirect()->back()->withInput()->with('errors', $errors);
    }
}
```
