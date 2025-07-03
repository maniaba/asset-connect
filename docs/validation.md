# Validation with CollectionValidator

The `CollectionValidator` class allows you to generate validation rules based on an asset collection definition and validate data against these rules. It also supports validating multiple fields with different collection definitions.

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

## Getting Validation Rules

You can get all validation rules or rules for specific fields:

```php
// Get all rules
$allRules = $validator->getRules();

// Get rules for specific fields
$fileRules = $validator->getRulesForFields('file');
```

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

## Additional Validation Rules

The `ValidationRuleCollector` class provides several methods for adding validation rules to your collection definition:

### Basic File Validation

- `allowedExtensions(AssetExtension|string ...$extensions)`: Validates that the file extension is one of the specified extensions.
- `allowedMimeTypes(AssetMimeType|string ...$mimeTypes)`: Validates that the file's MIME type is one of the specified MIME types.
- `setMaxFileSize(float|int $maxFileSize)`: Validates that the file size does not exceed the specified maximum size in bytes.
- `singleFileCollection()`: Validates that only a single file is uploaded.
- `onlyKeepLatest(int $maximumNumberOfItemsInCollection)`: Validates that the number of files does not exceed the specified maximum.

### Image Validation

- `setMaxImageDimensions(int $width, int $height)`: Validates that the image dimensions do not exceed the specified maximum width and height.
- `setMinImageDimensions(int $width, int $height)`: Validates that the image dimensions meet the specified minimum width and height.
- `requireImage()`: Validates that the file is an image.

### Example

```php
class ImagesCollection implements AssetCollectionDefinitionInterface, AssetVariantsInterface
{
    public function definition(AssetCollectionSetterInterface $definition): void
    {
        $definition
            ->allowedExtensions(AssetExtension::JPG, AssetExtension::PNG, AssetExtension::GIF)
            ->allowedMimeTypes(AssetMimeType::IMAGE_JPEG, AssetMimeType::IMAGE_PNG, AssetMimeType::IMAGE_GIF)
            ->setMaxFileSize(2 * 1024 * 1024) // 2MB
            ->setMaxImageDimensions(1920, 1080) // Maximum dimensions: 1920x1080 pixels
            ->setMinImageDimensions(100, 100) // Minimum dimensions: 100x100 pixels
            ->requireImage() // Must be an image
            ->singleFileCollection();
    }

    // ... other methods
}
```

## Custom Validation Rules

The AssetConnectValidator includes the following custom validation rules:

- `max_file_count[n]`: Validates that the number of files does not exceed the specified maximum. This rule is used by the `singleFileCollection()` and `onlyKeepLatest(int $maximumNumberOfItemsInCollection)` methods.
- `uploaded[field_name]`: Validates that the name of the parameter matches the name of an uploaded file. This rule is automatically added by the `allowedExtensions()` method.
- `is_image[field_name]`: Validates that the file is an image based on the mime type. This rule is added by the `requireImage()` method.
- `mime_in[field_name,mime1,mime2,...]`: Validates that the file's mime type is one of those listed in the parameters. This rule is added by the `allowedMimeTypes()` method.
- `ext_in[field_name,ext1,ext2,...]`: Validates that the file's extension is one of those listed in the parameters. This rule is added by the `allowedExtensions()` method.
- `max_size[field_name,size]`: Validates that the file size does not exceed the specified maximum size in kilobytes. This rule is added by the `setMaxFileSize()` method.
- `max_dims[field_name,width,height]`: Validates that the image dimensions do not exceed the specified maximum width and height. This rule is added by the `setMaxImageDimensions()` method.
- `min_dims[field_name,width,height]`: Validates that the image dimensions meet the specified minimum width and height. This rule is added by the `setMinImageDimensions()` method.

These rules follow the CodeIgniter 4 validation rule format and are implemented according to the CodeIgniter 4 validation system.

You can also extend the `ValidationRuleCollector` class to add your own custom validation rules for your specific needs.
