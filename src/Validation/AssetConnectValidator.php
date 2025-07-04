<?php

declare(strict_types=1);

namespace Maniaba\FileConnect\Validation;

use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Validation\Validation;
use CodeIgniter\Validation\ValidationInterface;
use Config\Services;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory;

/**
 * Class AssetConnectValidator
 *
 * This class generates validation rules based on an asset collection definition
 * and provides methods to validate fields against these rules.
 */
final class AssetConnectValidator
{
    /**
     * @var array<string, list<string>> The validation rules generated from the collection definition
     */
    private array $rules = [];

    /**
     * @var ValidationInterface The CodeIgniter validation instance
     */
    private readonly ValidationInterface $validation;

    /**
     * @var array<string, AssetCollectionDefinitionInterface> Field-specific collection definitions
     */
    private array $fieldCollectionDefinitions = [];

    /**
     * @var array<string, bool> Tracks which fields have had their rules generated
     */
    private array $generatedRules = [];

    public function __construct(
    ) {
        $this->validation = Services::validation();
    }

    /**
     * Set a collection definition for a specific field
     *
     * @param string                                                                              $fieldName            The field name
     * @param AssetCollectionDefinitionInterface|class-string<AssetCollectionDefinitionInterface> $collectionDefinition The collection definition for this field
     *
     * @return $this
     */
    public function setFieldCollectionDefinition(string $fieldName, AssetCollectionDefinitionInterface|string $collectionDefinition): self
    {
        if (is_string($collectionDefinition)) {
            $collectionDefinition = AssetCollectionDefinitionFactory::create($collectionDefinition);
        }

        $this->fieldCollectionDefinitions[$fieldName] = $collectionDefinition;
        $this->generatedRules[$fieldName]             = false; // Mark this field as needing rule generation
        $this->generateRules(); // Generate rules only for the new field

        return $this;
    }

    /**
     * Generate validation rules from the collection definition
     */
    private function generateRules(): void
    {
        // Generate rules only for fields that haven't had their rules generated yet
        foreach ($this->fieldCollectionDefinitions as $fieldName => $collectionDefinition) {
            if (! isset($this->generatedRules[$fieldName]) || $this->generatedRules[$fieldName] === false) {
                $this->generateRulesForField($fieldName, $collectionDefinition);
                $this->generatedRules[$fieldName] = true; // Mark as generated
            }
        }
    }

    /**
     * Generate validation rules for a specific field
     *
     * @param string                             $fieldName            The field name
     * @param AssetCollectionDefinitionInterface $collectionDefinition The collection definition for this field
     */
    private function generateRulesForField(string $fieldName, AssetCollectionDefinitionInterface $collectionDefinition): void
    {
        // Create a rule collector to capture the rules from the collection definition
        $ruleCollector = new ValidationRuleCollector($fieldName);

        // Call the definition method on the collection definition to populate the rules
        $collectionDefinition->definition($ruleCollector);

        // Get the collected rules
        $fieldRules = $ruleCollector->getRules();

        // Add the rules for this field to the rules array
        $this->rules[$fieldName] = $fieldRules;
    }

    /**
     * Get all validation rules
     *
     * @return array<string, list<string>> The validation rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get all defined field names (fields with specific collection definitions)
     *
     * @return list<string> The defined field names
     */
    public function getDefinedFieldNames(): array
    {
        return array_keys($this->fieldCollectionDefinitions);
    }

    /**
     * Get validation rules for all defined fields
     *
     * @return array<string, list<string>> The validation rules for all defined fields
     */
    public function getRulesForDefinedFields(): array
    {
        return $this->getRulesForFields(...$this->getDefinedFieldNames());
    }

    /**
     * Get validation rules for specific fields
     *
     * @param string ...$fields The fields to get rules for
     *
     * @return array<string, list<string>> The validation rules for the specified fields
     */
    public function getRulesForFields(string ...$fields): array
    {
        $fieldRules = [];

        foreach ($fields as $field) {
            if (isset($this->rules[$field])) {
                $fieldRules[$field] = $this->rules[$field];
            }
        }

        return $fieldRules;
    }

    /**
     * Validate data against all rules
     *
     * @param array<string, mixed> $data The data to validate
     *
     * @return bool True if validation passes, false otherwise
     */
    public function validate(array $data): bool
    {
        return $this->validation->setRules($this->rules)->run($data);
    }

    /**
     * Validate specific fields in the data
     *
     * @param array<string, mixed> $data      The data to validate
     * @param string               ...$fields The fields to validate
     *
     * @return bool True if validation passes, false otherwise
     */
    public function validateFields(array $data, string ...$fields): bool
    {
        $fieldRules = $this->getRulesForFields(...$fields);

        return $this->validation->setRules($fieldRules)->run($data);
    }

    /**
     * Get validation errors
     *
     * @return array<string, string> The validation errors
     */
    public function getErrors(): array
    {
        return $this->validation->getErrors();
    }

    /**
     * Validate all defined fields in the data
     *
     * This method validates all fields that have been defined with setFieldCollectionDefinition
     * as well as the default field.
     *
     * @param array<string, mixed> $data The data to validate
     *
     * @return bool True if validation passes, false otherwise
     */
    public function validateDefinedFields(array $data): bool
    {
        $fieldRules = $this->getRulesForDefinedFields();

        return $this->validation->setRules($fieldRules)->run($data);
    }

    /**
     * Validate fields from request
     *
     * This method validates fields directly from the request using CodeIgniter's
     * withRequest method.
     *
     * @param string ...$fields The fields to validate
     *
     * @return bool True if validation passes, false otherwise
     */
    public function validateFieldsFromRequest(string ...$fields): bool
    {
        $request    = Services::request();
        $fieldRules = $this->getRulesForFields(...$fields);

        return $this->validation->setRules($fieldRules)->withRequest($request)->run();
    }

    /**
     * Validate all defined fields from request
     *
     * This method validates all fields that have been defined with setFieldCollectionDefinition
     * as well as the default field, directly from the request using CodeIgniter's withRequest method.
     *
     * @param CLIRequest|IncomingRequest|null $request The request to validate against, defaults to the current request
     *
     * @return bool True if validation passes, false otherwise
     */
    public function validateRequest(CLIRequest|IncomingRequest|null $request = null): bool
    {
        $request ??= Services::request();
        $fieldRules = $this->getRulesForDefinedFields();

        return $this->validation->setRules($fieldRules)->withRequest($request)->run();
    }
}
