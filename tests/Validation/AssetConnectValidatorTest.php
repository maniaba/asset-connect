<?php

declare(strict_types=1);

namespace Tests\Validation;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Validation\Validation;
use Config\Services;
use Maniaba\FileConnect\Asset\Interfaces\AssetCollectionDefinitionInterface;
use Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory;
use Maniaba\FileConnect\Validation\AssetConnectValidator;
use Maniaba\FileConnect\Validation\ValidationRuleCollector;
use Override;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class AssetConnectValidatorTest extends CIUnitTestCase
{
    private AssetConnectValidator $validator;
    private MockObject $mockValidation;
    private MockObject $mockCollectionDefinition;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the validation service
        $this->mockValidation = $this->createMock(Validation::class);
        Services::injectMock('validation', $this->mockValidation);

        // Create a mock collection definition
        $this->mockCollectionDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);

        // Create the validator
        $this->validator = new AssetConnectValidator();

        // Setup global function mocks
        $this->setupGlobalFunctionMocks();
    }

    /**
     * Setup global function mocks
     */
    private function setupGlobalFunctionMocks(): void
    {
        // Mock AssetCollectionDefinitionFactory::validateStringClass
        global $mockFunctions;
        $mockFunctions['Maniaba\FileConnect\AssetCollection\AssetCollectionDefinitionFactory::validateStringClass'] = static fn () => null;
    }

    /**
     * Test setFieldCollectionDefinition with an interface instance
     */
    public function testSetFieldCollectionDefinitionWithInterfaceInstance(): void
    {
        // Arrange
        $fieldName = 'testField';

        // Setup the mock to expect the definition method to be called
        $this->mockCollectionDefinition->expects($this->once())
            ->method('definition')
            ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                $collector->allowedExtensions('jpg', 'png')
                    ->requireImage();

                return $collector;
            });

        // Act
        $result = $this->validator->setFieldCollectionDefinition($fieldName, $this->mockCollectionDefinition);

        // Assert
        $this->assertSame($this->validator, $result);
        $this->assertContains($fieldName, $this->validator->getDefinedFieldNames());
    }

    /**
     * Test getRules method
     */
    public function testGetRules(): void
    {
        // Arrange
        $fieldName = 'testField';

        // Setup the mock to expect the definition method to be called
        $this->mockCollectionDefinition->expects($this->once())
            ->method('definition')
            ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                $collector->allowedExtensions('jpg', 'png')
                    ->requireImage();

                return $collector;
            });

        $this->validator->setFieldCollectionDefinition($fieldName, $this->mockCollectionDefinition);

        // Act
        $rules = $this->validator->getRules();

        // Assert
        $this->assertIsArray($rules);
        $this->assertArrayHasKey($fieldName, $rules);
        $this->assertIsArray($rules[$fieldName]);
        $this->assertContains('uploaded[' . $fieldName . ']', $rules[$fieldName]);
        $this->assertContains('ext_in[' . $fieldName . ',jpg,png]', $rules[$fieldName]);
        $this->assertContains('is_image[' . $fieldName . ']', $rules[$fieldName]);
    }

    /**
     * Test getDefinedFieldNames method
     */
    public function testGetDefinedFieldNames(): void
    {
        // Arrange
        $fieldNames = ['field1', 'field2', 'field3'];

        foreach ($fieldNames as $fieldName) {
            // Setup the mock to expect the definition method to be called
            $mockDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
            $mockDefinition->expects($this->once())
                ->method('definition')
                ->willReturnCallback(static fn (ValidationRuleCollector $collector) => $collector);

            $this->validator->setFieldCollectionDefinition($fieldName, $mockDefinition);
        }

        // Act
        $definedFieldNames = $this->validator->getDefinedFieldNames();

        // Assert
        $this->assertIsArray($definedFieldNames);
        $this->assertCount(count($fieldNames), $definedFieldNames);

        foreach ($fieldNames as $fieldName) {
            $this->assertContains($fieldName, $definedFieldNames);
        }
    }

    /**
     * Test getRulesForDefinedFields method
     */
    public function testGetRulesForDefinedFields(): void
    {
        // Arrange
        $fieldNames = ['field1', 'field2'];

        foreach ($fieldNames as $fieldName) {
            // Setup the mock to expect the definition method to be called
            $mockDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
            $mockDefinition->expects($this->once())
                ->method('definition')
                ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                    $collector->allowedExtensions('jpg', 'png');

                    return $collector;
                });

            $this->validator->setFieldCollectionDefinition($fieldName, $mockDefinition);
        }

        // Act
        $rules = $this->validator->getRulesForDefinedFields();

        // Assert
        $this->assertIsArray($rules);
        $this->assertCount(count($fieldNames), $rules);

        foreach ($fieldNames as $fieldName) {
            $this->assertArrayHasKey($fieldName, $rules);
            $this->assertIsArray($rules[$fieldName]);
            $this->assertContains('uploaded[' . $fieldName . ']', $rules[$fieldName]);
            $this->assertContains('ext_in[' . $fieldName . ',jpg,png]', $rules[$fieldName]);
        }
    }

    /**
     * Test getRulesForFields method
     */
    public function testGetRulesForFields(): void
    {
        // Arrange
        $allFieldNames       = ['field1', 'field2', 'field3'];
        $requestedFieldNames = ['field1', 'field3'];

        foreach ($allFieldNames as $fieldName) {
            // Setup the mock to expect the definition method to be called
            $mockDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
            $mockDefinition->expects($this->once())
                ->method('definition')
                ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                    $collector->allowedExtensions('jpg', 'png');

                    return $collector;
                });

            $this->validator->setFieldCollectionDefinition($fieldName, $mockDefinition);
        }

        // Act
        $rules = $this->validator->getRulesForFields(...$requestedFieldNames);

        // Assert
        $this->assertIsArray($rules);
        $this->assertCount(count($requestedFieldNames), $rules);

        foreach ($requestedFieldNames as $fieldName) {
            $this->assertArrayHasKey($fieldName, $rules);
            $this->assertIsArray($rules[$fieldName]);
            $this->assertContains('uploaded[' . $fieldName . ']', $rules[$fieldName]);
            $this->assertContains('ext_in[' . $fieldName . ',jpg,png]', $rules[$fieldName]);
        }
        $this->assertArrayNotHasKey('field2', $rules);
    }

    /**
     * Test validate method
     */
    public function testValidate(): void
    {
        // Arrange
        $fieldName = 'testField';
        $data      = ['testField' => 'test.jpg'];

        // Setup the mock to expect the definition method to be called
        $this->mockCollectionDefinition->expects($this->once())
            ->method('definition')
            ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                $collector->allowedExtensions('jpg', 'png');

                return $collector;
            });

        $this->validator->setFieldCollectionDefinition($fieldName, $this->mockCollectionDefinition);

        // Setup the validation mock
        $this->mockValidation->expects($this->once())
            ->method('setRules')
            ->with($this->validator->getRules())
            ->willReturnSelf();

        $this->mockValidation->expects($this->once())
            ->method('run')
            ->with($data)
            ->willReturn(true);

        // Act
        $result = $this->validator->validate($data);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test validateFields method
     */
    public function testValidateFields(): void
    {
        // Arrange
        $fieldNames          = ['field1', 'field2', 'field3'];
        $requestedFieldNames = ['field1', 'field3'];
        $data                = ['field1' => 'test1.jpg', 'field3' => 'test3.jpg'];

        foreach ($fieldNames as $fieldName) {
            // Setup the mock to expect the definition method to be called
            $mockDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
            $mockDefinition->expects($this->once())
                ->method('definition')
                ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                    $collector->allowedExtensions('jpg', 'png');

                    return $collector;
                });

            $this->validator->setFieldCollectionDefinition($fieldName, $mockDefinition);
        }

        // Setup the validation mock
        $this->mockValidation->expects($this->once())
            ->method('setRules')
            ->with($this->validator->getRulesForFields(...$requestedFieldNames))
            ->willReturnSelf();

        $this->mockValidation->expects($this->once())
            ->method('run')
            ->with($data)
            ->willReturn(true);

        // Act
        $result = $this->validator->validateFields($data, ...$requestedFieldNames);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test getErrors method
     */
    public function testGetErrors(): void
    {
        // Arrange
        $errors = ['field1' => 'Error message for field1'];

        // Setup the validation mock
        $this->mockValidation->expects($this->once())
            ->method('getErrors')
            ->willReturn($errors);

        // Act
        $result = $this->validator->getErrors();

        // Assert
        $this->assertSame($errors, $result);
    }

    /**
     * Test validateDefinedFields method
     */
    public function testValidateDefinedFields(): void
    {
        // Arrange
        $fieldNames = ['field1', 'field2'];
        $data       = ['field1' => 'test1.jpg', 'field2' => 'test2.jpg'];

        foreach ($fieldNames as $fieldName) {
            // Setup the mock to expect the definition method to be called
            $mockDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
            $mockDefinition->expects($this->once())
                ->method('definition')
                ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                    $collector->allowedExtensions('jpg', 'png');

                    return $collector;
                });

            $this->validator->setFieldCollectionDefinition($fieldName, $mockDefinition);
        }

        // Setup the validation mock
        $this->mockValidation->expects($this->once())
            ->method('setRules')
            ->with($this->validator->getRulesForDefinedFields())
            ->willReturnSelf();

        $this->mockValidation->expects($this->once())
            ->method('run')
            ->with($data)
            ->willReturn(true);

        // Act
        $result = $this->validator->validateDefinedFields($data);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test validateFieldsFromRequest method
     */
    public function testValidateFieldsFromRequest(): void
    {
        // Arrange
        $fieldNames          = ['field1', 'field2', 'field3'];
        $requestedFieldNames = ['field1', 'field3'];

        foreach ($fieldNames as $fieldName) {
            // Setup the mock to expect the definition method to be called
            $mockDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
            $mockDefinition->expects($this->once())
                ->method('definition')
                ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                    $collector->allowedExtensions('jpg', 'png');

                    return $collector;
                });

            $this->validator->setFieldCollectionDefinition($fieldName, $mockDefinition);
        }

        // Create a mock request
        $mockRequest = $this->createMock(IncomingRequest::class);
        Services::injectMock('request', $mockRequest);

        // Setup the validation mock
        $this->mockValidation->expects($this->once())
            ->method('setRules')
            ->with($this->validator->getRulesForFields(...$requestedFieldNames))
            ->willReturnSelf();

        $this->mockValidation->expects($this->once())
            ->method('withRequest')
            ->with($mockRequest)
            ->willReturnSelf();

        $this->mockValidation->expects($this->once())
            ->method('run')
            ->willReturn(true);

        // Act
        $result = $this->validator->validateFieldsFromRequest(...$requestedFieldNames);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test validateRequest method
     */
    public function testValidateRequest(): void
    {
        // Arrange
        $fieldNames = ['field1', 'field2'];

        foreach ($fieldNames as $fieldName) {
            // Setup the mock to expect the definition method to be called
            $mockDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
            $mockDefinition->expects($this->once())
                ->method('definition')
                ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                    $collector->allowedExtensions('jpg', 'png');

                    return $collector;
                });

            $this->validator->setFieldCollectionDefinition($fieldName, $mockDefinition);
        }

        // Create a mock request
        $mockRequest = $this->createMock(IncomingRequest::class);

        // Setup the validation mock
        $this->mockValidation->expects($this->once())
            ->method('setRules')
            ->with($this->validator->getRulesForDefinedFields())
            ->willReturnSelf();

        $this->mockValidation->expects($this->once())
            ->method('withRequest')
            ->with($mockRequest)
            ->willReturnSelf();

        $this->mockValidation->expects($this->once())
            ->method('run')
            ->willReturn(true);

        // Act
        $result = $this->validator->validateRequest($mockRequest);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test validateRequest method with default request
     */
    public function testValidateRequestWithDefaultRequest(): void
    {
        // Arrange
        $fieldNames = ['field1', 'field2'];

        foreach ($fieldNames as $fieldName) {
            // Setup the mock to expect the definition method to be called
            $mockDefinition = $this->createMock(AssetCollectionDefinitionInterface::class);
            $mockDefinition->expects($this->once())
                ->method('definition')
                ->willReturnCallback(static function (ValidationRuleCollector $collector) {
                    $collector->allowedExtensions('jpg', 'png');

                    return $collector;
                });

            $this->validator->setFieldCollectionDefinition($fieldName, $mockDefinition);
        }

        // Create a mock request
        $mockRequest = $this->createMock(IncomingRequest::class);
        Services::injectMock('request', $mockRequest);

        // Setup the validation mock
        $this->mockValidation->expects($this->once())
            ->method('setRules')
            ->with($this->validator->getRulesForDefinedFields())
            ->willReturnSelf();

        $this->mockValidation->expects($this->once())
            ->method('withRequest')
            ->with($mockRequest)
            ->willReturnSelf();

        $this->mockValidation->expects($this->once())
            ->method('run')
            ->willReturn(true);

        // Act
        $result = $this->validator->validateRequest();

        // Assert
        $this->assertTrue($result);
    }
}
