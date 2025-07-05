<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Enums;

enum AssetExtension: string
{
    // Images
    case JPG  = 'jpg';
    case JPEG = 'jpeg';
    case PNG  = 'png';
    case GIF  = 'gif';
    case SVG  = 'svg';
    case WEBP = 'webp';
    case BMP  = 'bmp';
    case TIFF = 'tiff';
    case ICO  = 'ico';
    case HEIC = 'heic';
    case AVIF = 'avif';
    case TIF  = 'tif';
    case XCF  = 'xcf';

    // Documents
    case PDF      = 'pdf';
    case DOC      = 'doc';
    case DOCX     = 'docx';
    case XLS      = 'xls';
    case XLSX     = 'xlsx';
    case PPT      = 'ppt';
    case PPTX     = 'pptx';
    case ODT      = 'odt';
    case ODS      = 'ods';
    case ODP      = 'odp';
    case RTF      = 'rtf';
    case TXT      = 'txt';
    case CSV      = 'csv';
    case XML      = 'xml';
    case JSON     = 'json';
    case MARKDOWN = 'md';

    // Videos
    case MP4      = 'mp4';
    case WEBM     = 'webm';
    case OGV      = 'ogv';
    case AVI      = 'avi';
    case MOV      = 'mov';
    case WMV      = 'wmv';
    case MKV      = 'mkv';
    case FLV      = 'flv';
    case M4V      = 'm4v';
    case TS_VIDEO = 'ts-video';

    // Audio
    case MP3  = 'mp3';
    case WAV  = 'wav';
    case OGG  = 'ogg';
    case AAC  = 'aac';
    case FLAC = 'flac';
    case M4A  = 'm4a';
    case WMA  = 'wma';
    case MIDI = 'midi';

    // Archives
    case ZIP     = 'zip';
    case RAR     = 'rar';
    case TAR     = 'tar';
    case GZ      = 'gz';
    case SEVEN_Z = '7z';
    case BZ2     = 'bz2';
    case XZ      = 'xz';
    case ISO     = 'iso';

    // Web
    case HTML = 'html';
    case HTM  = 'htm';
    case CSS  = 'css';
    case JS   = 'js';
    case PHP  = 'php';
    case ASP  = 'asp';
    case JSP  = 'jsp';
    case ASPX = 'aspx';

    // Programming
    case JAVA       = 'java';
    case PY         = 'py';
    case CPP        = 'cpp';
    case C          = 'c';
    case CS         = 'cs';
    case GO         = 'go';
    case RS         = 'rs';
    case TYPESCRIPT = 'ts';
    case SWIFT      = 'swift';
    case KOTLIN     = 'kt';
    case DART       = 'dart';
    case RB         = 'rb';

    // Fonts
    case TTF   = 'ttf';
    case OTF   = 'otf';
    case WOFF  = 'woff';
    case WOFF2 = 'woff2';
    case EOT   = 'eot';

    // 3D and Design
    case OBJ    = 'obj';
    case STL    = 'stl';
    case FBX    = 'fbx';
    case BLEND  = 'blend';
    case PSD    = 'psd';
    case AI     = 'ai';
    case EPS    = 'eps';
    case SKETCH = 'sketch';
    case XD     = 'xd';
    case FIG    = 'fig';
    case CDR    = 'cdr';

    // Database
    case SQL     = 'sql';
    case DB      = 'db';
    case MDB     = 'mdb';
    case ACCDB   = 'accdb';
    case SQLITE  = 'sqlite';
    case SQLITE3 = 'sqlite3';

    // E-books
    case EPUB = 'epub';
    case MOBI = 'mobi';
    case AZW  = 'azw';
    case AZW3 = 'azw3';
    case FB2  = 'fb2';
    case LIT  = 'lit';

    // CAD
    case DWG = 'dwg';
    case DXF = 'dxf';
    case DGN = 'dgn';
    case SKP = 'skp';

    // Scientific/Data
    case HDF    = 'hdf';
    case HDF5   = 'h5';
    case FITS   = 'fits';
    case NETCDF = 'nc';
    case MAT    = 'mat';

    // Configuration
    case INI  = 'ini';
    case YAML = 'yaml';
    case YML  = 'yml';
    case TOML = 'toml';
    case CONF = 'conf';

    // Executable
    case EXE = 'exe';
    case DLL = 'dll';
    case APK = 'apk';
    case APP = 'app';
    case DMG = 'dmg';
    case MSI = 'msi';

    /**
     * Get all image extensions
     *
     * @return list<self>
     */
    public static function images(): array
    {
        return [
            self::JPG,
            self::JPEG,
            self::PNG,
            self::GIF,
            self::SVG,
            self::WEBP,
            self::BMP,
            self::TIFF,
            self::ICO,
            self::HEIC,
            self::AVIF,
        ];
    }

    /**
     * Get all document extensions
     *
     * @return list<self>
     */
    public static function documents(): array
    {
        return [
            self::PDF,
            self::DOC,
            self::DOCX,
            self::XLS,
            self::XLSX,
            self::PPT,
            self::PPTX,
            self::ODT,
            self::ODS,
            self::ODP,
            self::RTF,
            self::TXT,
            self::CSV,
            self::XML,
            self::JSON,
            self::MARKDOWN,
        ];
    }

    /**
     * Get all video extensions
     *
     * @return list<self>
     */
    public static function videos(): array
    {
        return [
            self::MP4,
            self::WEBM,
            self::OGV,
            self::AVI,
            self::MOV,
            self::WMV,
            self::MKV,
            self::FLV,
            self::M4V,
            self::TS_VIDEO,
        ];
    }

    /**
     * Get all audio extensions
     *
     * @return list<self>
     */
    public static function audio(): array
    {
        return [
            self::MP3,
            self::WAV,
            self::OGG,
            self::AAC,
            self::FLAC,
            self::M4A,
            self::WMA,
            self::MIDI,
        ];
    }

    /**
     * Get all archive extensions
     *
     * @return list<self>
     */
    public static function archives(): array
    {
        return [
            self::ZIP,
            self::RAR,
            self::TAR,
            self::GZ,
            self::SEVEN_Z,
            self::BZ2,
            self::XZ,
            self::ISO,
        ];
    }

    /**
     * Get all web extensions
     *
     * @return list<self>
     */
    public static function web(): array
    {
        return [
            self::HTML,
            self::HTM,
            self::CSS,
            self::JS,
            self::PHP,
            self::ASP,
            self::JSP,
            self::ASPX,
        ];
    }

    /**
     * Get all programming extensions
     *
     * @return list<self>
     */
    public static function programming(): array
    {
        return [
            self::JAVA,
            self::PY,
            self::CPP,
            self::C,
            self::CS,
            self::GO,
            self::RS,
            self::TYPESCRIPT,
            self::SWIFT,
            self::KOTLIN,
            self::DART,
            self::RB,
        ];
    }

    /**
     * Get all font extensions
     *
     * @return list<self>
     */
    public static function fonts(): array
    {
        return [
            self::TTF,
            self::OTF,
            self::WOFF,
            self::WOFF2,
            self::EOT,
        ];
    }

    /**
     * Get all 3D and design extensions
     *
     * @return list<self>
     */
    public static function design(): array
    {
        return [
            self::OBJ,
            self::STL,
            self::FBX,
            self::BLEND,
            self::PSD,
            self::AI,
            self::EPS,
            self::SKETCH,
            self::XD,
            self::FIG,
        ];
    }

    /**
     * Get all text extensions
     *
     * @return list<self>
     */
    public static function text(): array
    {
        return [
            self::TXT,
            self::HTML,
            self::HTM,
            self::CSS,
            self::JS,
            self::XML,
            self::JSON,
            self::MARKDOWN,
        ];
    }

    /**
     * Get all database extensions
     *
     * @return list<self>
     */
    public static function database(): array
    {
        return [
            self::SQL,
            self::DB,
            self::MDB,
            self::ACCDB,
            self::SQLITE,
            self::SQLITE3,
        ];
    }

    /**
     * Get all e-book extensions
     *
     * @return list<self>
     */
    public static function ebooks(): array
    {
        return [
            self::EPUB,
            self::MOBI,
            self::AZW,
            self::AZW3,
            self::FB2,
            self::LIT,
            self::PDF,
        ];
    }

    /**
     * Get all CAD extensions
     *
     * @return list<self>
     */
    public static function cad(): array
    {
        return [
            self::DWG,
            self::DXF,
            self::DGN,
            self::SKP,
            self::OBJ,
            self::STL,
        ];
    }

    /**
     * Get all scientific/data extensions
     *
     * @return list<self>
     */
    public static function scientific(): array
    {
        return [
            self::HDF,
            self::HDF5,
            self::FITS,
            self::NETCDF,
            self::MAT,
            self::CSV,
        ];
    }

    /**
     * Get all configuration extensions
     *
     * @return list<self>
     */
    public static function configuration(): array
    {
        return [
            self::INI,
            self::YAML,
            self::YML,
            self::TOML,
            self::CONF,
            self::JSON,
            self::XML,
        ];
    }

    /**
     * Get all executable extensions
     *
     * @return list<self>
     */
    public static function executable(): array
    {
        return [
            self::EXE,
            self::DLL,
            self::APK,
            self::APP,
            self::DMG,
            self::MSI,
        ];
    }

    /**
     * Get all vector graphic extensions
     *
     * @return list<self>
     */
    public static function vectorGraphics(): array
    {
        return [
            self::SVG,
            self::AI,
            self::EPS,
            self::CDR,
        ];
    }

    /**
     * Get all raster graphic extensions
     *
     * @return list<self>
     */
    public static function rasterGraphics(): array
    {
        return [
            self::JPG,
            self::JPEG,
            self::PNG,
            self::GIF,
            self::BMP,
            self::TIFF,
            self::TIF,
            self::PSD,
            self::XCF,
            self::WEBP,
            self::HEIC,
            self::AVIF,
        ];
    }

    /**
     * Get all spreadsheet extensions
     *
     * @return list<self>
     */
    public static function spreadsheets(): array
    {
        return [
            self::XLS,
            self::XLSX,
            self::ODS,
            self::CSV,
        ];
    }

    /**
     * Get all presentation extensions
     *
     * @return list<self>
     */
    public static function presentations(): array
    {
        return [
            self::PPT,
            self::PPTX,
            self::ODP,
        ];
    }
}
