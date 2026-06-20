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

    public function testCanResolveEveryDeclaredMimeTypeExtension(): void
    {
        foreach ($this->declaredMimeTypeExtensionMap() as [$mimeType, $extension]) {
            $this->assertSame(
                $extension,
                AssetMimeType::getMimeTypeExtension($mimeType),
                $mimeType->name . ' should resolve to its configured file extension.',
            );
        }
    }

    public function testFallsBackToCodeIgniterMimesForUnmappedExtensionsAndMimeTypes(): void
    {
        $this->assertSame('text/html', AssetMimeType::fromExtension('htm'), 'Unmapped extensions should fall back to CodeIgniter Mimes.');
        $this->assertSame('ics', AssetMimeType::getExtension('text/calendar'), 'Unmapped MIME types should fall back to CodeIgniter Mimes.');
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

    /**
     * @return list<array{AssetMimeType, string}>
     */
    private function declaredMimeTypeExtensionMap(): array
    {
        return [
            [AssetMimeType::IMAGE_JPEG, 'jpg'],
            [AssetMimeType::IMAGE_PNG, 'png'],
            [AssetMimeType::IMAGE_GIF, 'gif'],
            [AssetMimeType::IMAGE_SVG, 'svg'],
            [AssetMimeType::IMAGE_WEBP, 'webp'],
            [AssetMimeType::IMAGE_BMP, 'bmp'],
            [AssetMimeType::IMAGE_TIFF, 'tiff'],
            [AssetMimeType::IMAGE_ICO, 'ico'],
            [AssetMimeType::IMAGE_HEIC, 'heic'],
            [AssetMimeType::IMAGE_AVIF, 'avif'],
            [AssetMimeType::IMAGE_XCF, 'xcf'],
            [AssetMimeType::APPLICATION_PDF, 'pdf'],
            [AssetMimeType::APPLICATION_MSWORD, 'doc'],
            [AssetMimeType::APPLICATION_DOCX, 'docx'],
            [AssetMimeType::APPLICATION_XLS, 'xls'],
            [AssetMimeType::APPLICATION_XLSX, 'xlsx'],
            [AssetMimeType::APPLICATION_PPT, 'ppt'],
            [AssetMimeType::APPLICATION_PPTX, 'pptx'],
            [AssetMimeType::APPLICATION_ODT, 'odt'],
            [AssetMimeType::APPLICATION_ODS, 'ods'],
            [AssetMimeType::APPLICATION_ODP, 'odp'],
            [AssetMimeType::APPLICATION_RTF, 'rtf'],
            [AssetMimeType::TEXT_PLAIN, 'txt'],
            [AssetMimeType::TEXT_CSV, 'csv'],
            [AssetMimeType::TEXT_XML, 'xml'],
            [AssetMimeType::APPLICATION_XML, 'xml'],
            [AssetMimeType::APPLICATION_JSON, 'json'],
            [AssetMimeType::TEXT_MARKDOWN, 'md'],
            [AssetMimeType::VIDEO_MP4, 'mp4'],
            [AssetMimeType::VIDEO_WEBM, 'webm'],
            [AssetMimeType::VIDEO_OGG, 'ogv'],
            [AssetMimeType::VIDEO_AVI, 'avi'],
            [AssetMimeType::VIDEO_QUICKTIME, 'mov'],
            [AssetMimeType::VIDEO_WMV, 'wmv'],
            [AssetMimeType::VIDEO_MKV, 'mkv'],
            [AssetMimeType::VIDEO_FLV, 'flv'],
            [AssetMimeType::VIDEO_M4V, 'm4v'],
            [AssetMimeType::VIDEO_TS, 'ts-video'],
            [AssetMimeType::AUDIO_MP3, 'mp3'],
            [AssetMimeType::AUDIO_WAV, 'wav'],
            [AssetMimeType::AUDIO_OGG, 'ogg'],
            [AssetMimeType::AUDIO_AAC, 'aac'],
            [AssetMimeType::AUDIO_FLAC, 'flac'],
            [AssetMimeType::AUDIO_M4A, 'm4a'],
            [AssetMimeType::AUDIO_WMA, 'wma'],
            [AssetMimeType::AUDIO_MIDI, 'midi'],
            [AssetMimeType::APPLICATION_ZIP, 'zip'],
            [AssetMimeType::APPLICATION_RAR, 'rar'],
            [AssetMimeType::APPLICATION_TAR, 'tar'],
            [AssetMimeType::APPLICATION_GZIP, 'gz'],
            [AssetMimeType::APPLICATION_7Z, '7z'],
            [AssetMimeType::APPLICATION_BZ2, 'bz2'],
            [AssetMimeType::APPLICATION_XZ, 'xz'],
            [AssetMimeType::APPLICATION_ISO, 'iso'],
            [AssetMimeType::TEXT_HTML, 'html'],
            [AssetMimeType::TEXT_CSS, 'css'],
            [AssetMimeType::TEXT_JAVASCRIPT, 'js'],
            [AssetMimeType::APPLICATION_JAVASCRIPT, 'js'],
            [AssetMimeType::APPLICATION_PHP, 'php'],
            [AssetMimeType::TEXT_ASP, 'asp'],
            [AssetMimeType::TEXT_JSP, 'jsp'],
            [AssetMimeType::TEXT_JAVA, 'java'],
            [AssetMimeType::TEXT_PYTHON, 'py'],
            [AssetMimeType::TEXT_CPP, 'cpp'],
            [AssetMimeType::TEXT_C, 'c'],
            [AssetMimeType::TEXT_CSHARP, 'cs'],
            [AssetMimeType::TEXT_GO, 'go'],
            [AssetMimeType::TEXT_RUST, 'rs'],
            [AssetMimeType::TEXT_TYPESCRIPT, 'ts'],
            [AssetMimeType::TEXT_SWIFT, 'swift'],
            [AssetMimeType::TEXT_KOTLIN, 'kt'],
            [AssetMimeType::TEXT_DART, 'dart'],
            [AssetMimeType::TEXT_RUBY, 'rb'],
            [AssetMimeType::FONT_TTF, 'ttf'],
            [AssetMimeType::FONT_OTF, 'otf'],
            [AssetMimeType::FONT_WOFF, 'woff'],
            [AssetMimeType::FONT_WOFF2, 'woff2'],
            [AssetMimeType::FONT_EOT, 'eot'],
            [AssetMimeType::MODEL_OBJ, 'obj'],
            [AssetMimeType::MODEL_STL, 'stl'],
            [AssetMimeType::MODEL_FBX, 'fbx'],
            [AssetMimeType::APPLICATION_BLEND, 'blend'],
            [AssetMimeType::IMAGE_PSD, 'psd'],
            [AssetMimeType::APPLICATION_ILLUSTRATOR, 'ai'],
            [AssetMimeType::APPLICATION_EPS, 'eps'],
            [AssetMimeType::APPLICATION_SKETCH, 'sketch'],
            [AssetMimeType::APPLICATION_XD, 'xd'],
            [AssetMimeType::APPLICATION_FIGMA, 'fig'],
            [AssetMimeType::APPLICATION_CDR, 'cdr'],
            [AssetMimeType::APPLICATION_SQL, 'sql'],
            [AssetMimeType::APPLICATION_DB, 'db'],
            [AssetMimeType::APPLICATION_MDB, 'mdb'],
            [AssetMimeType::APPLICATION_ACCDB, 'accdb'],
            [AssetMimeType::APPLICATION_SQLITE, 'db'],
            [AssetMimeType::APPLICATION_EPUB, 'epub'],
            [AssetMimeType::APPLICATION_MOBI, 'mobi'],
            [AssetMimeType::APPLICATION_AZW, 'azw'],
            [AssetMimeType::APPLICATION_FB2, 'fb2'],
            [AssetMimeType::APPLICATION_LIT, 'lit'],
            [AssetMimeType::APPLICATION_DWG, 'dwg'],
            [AssetMimeType::APPLICATION_DXF, 'dxf'],
            [AssetMimeType::APPLICATION_DGN, 'dgn'],
            [AssetMimeType::APPLICATION_SKP, 'skp'],
            [AssetMimeType::APPLICATION_HDF, 'hdf'],
            [AssetMimeType::APPLICATION_HDF5, 'h5'],
            [AssetMimeType::APPLICATION_FITS, 'fits'],
            [AssetMimeType::APPLICATION_NETCDF, 'nc'],
            [AssetMimeType::APPLICATION_MATLAB, 'mat'],
            [AssetMimeType::TEXT_INI, 'ini'],
            [AssetMimeType::TEXT_YAML, 'yaml'],
            [AssetMimeType::TEXT_TOML, 'toml'],
            [AssetMimeType::TEXT_CONF, 'conf'],
            [AssetMimeType::APPLICATION_EXE, 'exe'],
            [AssetMimeType::APPLICATION_DLL, 'dll'],
            [AssetMimeType::APPLICATION_APK, 'apk'],
            [AssetMimeType::APPLICATION_APP, 'app'],
            [AssetMimeType::APPLICATION_DMG, 'dmg'],
            [AssetMimeType::APPLICATION_MSI, 'msi'],
        ];
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
