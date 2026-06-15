<?php

declare(strict_types=1);

namespace Tests\Asset\Properties;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Properties\InternalProperty;

/**
 * @internal
 */
final class InternalPropertyTest extends CIUnitTestCase
{
    public function testGetNameReturnsCorrectName(): void
    {
        $this->assertSame('internal', InternalProperty::getName());
    }

    public function testCreateReturnsInstanceOfInternalProperty(): void
    {
        $properties = ['internal' => ['processing_job_id' => 'job-123']];

        $result = InternalProperty::create($properties);

        $this->assertSame('job-123', $result->get('processing_job_id'));
    }

    public function testJsonSerializeReturnsPropertiesWithCorrectNameAsKey(): void
    {
        $properties       = ['s3_etag' => 'etag-value', 'processor' => ['version' => 2]];
        $internalProperty = new InternalProperty($properties);

        $json = $internalProperty->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertArrayHasKey('internal', $json);
        $this->assertSame($properties, $json['internal']);
    }
}
