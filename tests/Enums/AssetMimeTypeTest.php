<?php

declare(strict_types=1);

namespace Tests\Enums;

use CodeIgniter\Test\CIUnitTestCase;
use Maniaba\AssetConnect\Asset\Traits\AssetMimeTypeTrait;
use Maniaba\AssetConnect\Enums\AssetExtension;
use Maniaba\AssetConnect\Enums\AssetMimeType;

/**
 * @internal
 */
final class AssetMimeTypeTest extends CIUnitTestCase
{
    public function testCanResolveExtensionsAndMimeTypes(): void
    {
        $this->assertSame('jpg', AssetMimeType::getExtension(AssetMimeType::IMAGE_JPEG->value));
        $this->assertSame('txt', AssetMimeType::getMimeTypeExtension(AssetMimeType::TEXT_PLAIN));
        $this->assertSame(AssetMimeType::TEXT_PLAIN->value, AssetMimeType::fromExtension('TXT'));
        $this->assertSame(AssetMimeType::APPLICATION_PDF->value, AssetMimeType::fromAssetExtension(AssetExtension::PDF));
        $this->assertNull(AssetMimeType::getExtension('application/x-asset-connect-unknown'));
    }

    public function testClassifiesKnownMimeTypes(): void
    {
        $this->assertTrue(AssetMimeType::isImage(AssetMimeType::IMAGE_PNG->value));
        $this->assertTrue(AssetMimeType::isDocument(AssetMimeType::APPLICATION_PDF->value));
        $this->assertTrue(AssetMimeType::isVideo(AssetMimeType::VIDEO_MP4->value));
        $this->assertTrue(AssetMimeType::isAudio(AssetMimeType::AUDIO_MP3->value));
        $this->assertTrue(AssetMimeType::isArchive(AssetMimeType::APPLICATION_ZIP->value));
        $this->assertTrue(AssetMimeType::isText(AssetMimeType::TEXT_PLAIN->value));
        $this->assertTrue(AssetMimeType::isWeb(AssetMimeType::TEXT_HTML->value));
        $this->assertTrue(AssetMimeType::isProgramming(AssetMimeType::TEXT_PYTHON->value));
        $this->assertTrue(AssetMimeType::isFont(AssetMimeType::FONT_TTF->value));
        $this->assertTrue(AssetMimeType::isDesign(AssetMimeType::APPLICATION_FIGMA->value));
        $this->assertTrue(AssetMimeType::isDatabase(AssetMimeType::APPLICATION_SQL->value));
        $this->assertTrue(AssetMimeType::isEbook(AssetMimeType::APPLICATION_EPUB->value));
        $this->assertTrue(AssetMimeType::isCad(AssetMimeType::APPLICATION_DXF->value));
        $this->assertTrue(AssetMimeType::isScientific(AssetMimeType::APPLICATION_FITS->value));
        $this->assertTrue(AssetMimeType::isConfiguration(AssetMimeType::TEXT_YAML->value));
        $this->assertTrue(AssetMimeType::isExecutable(AssetMimeType::APPLICATION_EXE->value));
        $this->assertTrue(AssetMimeType::isVectorGraphic(AssetMimeType::IMAGE_SVG->value));
        $this->assertTrue(AssetMimeType::isRasterGraphic(AssetMimeType::IMAGE_WEBP->value));
        $this->assertTrue(AssetMimeType::isSpreadsheet(AssetMimeType::APPLICATION_XLS->value));
        $this->assertTrue(AssetMimeType::isPresentation(AssetMimeType::APPLICATION_PPT->value));
    }

    public function testTraitDelegatesToMimeTypeClassifier(): void
    {
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::IMAGE_PNG->value))->isImage());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_PDF->value))->isDocument());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::VIDEO_MP4->value))->isVideo());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::AUDIO_MP3->value))->isAudio());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_ZIP->value))->isArchive());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::TEXT_PLAIN->value))->isText());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::TEXT_HTML->value))->isWeb());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::TEXT_PYTHON->value))->isProgramming());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::FONT_TTF->value))->isFont());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_FIGMA->value))->isDesign());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_SQL->value))->isDatabase());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_EPUB->value))->isEbook());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_DXF->value))->isCad());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_FITS->value))->isScientific());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::TEXT_YAML->value))->isConfiguration());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_EXE->value))->isExecutable());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::IMAGE_SVG->value))->isVectorGraphic());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::IMAGE_WEBP->value))->isRasterGraphic());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_XLS->value))->isSpreadsheet());
        $this->assertTrue((new AssetMimeTypeProbe(AssetMimeType::APPLICATION_PPT->value))->isPresentation());
    }
}

final readonly class AssetMimeTypeProbe
{
    use AssetMimeTypeTrait;

    public function __construct(private string $mimeType)
    {
    }

    protected function mimeTypeValue(): string
    {
        return $this->mimeType;
    }
}
