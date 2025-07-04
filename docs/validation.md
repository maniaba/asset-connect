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

> For more detailed information about the AssetConnectValidator class and its usage with ValidationRuleCollector, see the [AssetConnectValidator](asset-connect-validator.md) documentation.

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

