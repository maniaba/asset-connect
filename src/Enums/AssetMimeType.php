<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Enums;

use Config\Mimes;

enum AssetMimeType: string
{
    // Images
    case IMAGE_JPEG = 'image/jpeg';
    case IMAGE_PNG  = 'image/png';
    case IMAGE_GIF  = 'image/gif';
    case IMAGE_SVG  = 'image/svg+xml';
    case IMAGE_WEBP = 'image/webp';
    case IMAGE_BMP  = 'image/bmp';
    case IMAGE_TIFF = 'image/tiff';
    case IMAGE_ICO  = 'image/x-icon';
    case IMAGE_HEIC = 'image/heic';
    case IMAGE_AVIF = 'image/avif';
    case IMAGE_XCF  = 'image/x-xcf';

    // Documents
    case APPLICATION_PDF    = 'application/pdf';
    case APPLICATION_MSWORD = 'application/msword';
    case APPLICATION_DOCX   = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    case APPLICATION_XLS    = 'application/vnd.ms-excel';
    case APPLICATION_XLSX   = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    case APPLICATION_PPT    = 'application/vnd.ms-powerpoint';
    case APPLICATION_PPTX   = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
    case APPLICATION_ODT    = 'application/vnd.oasis.opendocument.text';
    case APPLICATION_ODS    = 'application/vnd.oasis.opendocument.spreadsheet';
    case APPLICATION_ODP    = 'application/vnd.oasis.opendocument.presentation';
    case APPLICATION_RTF    = 'application/rtf';
    case TEXT_PLAIN         = 'text/plain';
    case TEXT_CSV           = 'text/csv';
    case TEXT_XML           = 'text/xml';
    case APPLICATION_XML    = 'application/xml';
    case APPLICATION_JSON   = 'application/json';
    case TEXT_MARKDOWN      = 'text/markdown';

    // Videos
    case VIDEO_MP4       = 'video/mp4';
    case VIDEO_WEBM      = 'video/webm';
    case VIDEO_OGG       = 'video/ogg';
    case VIDEO_AVI       = 'video/x-msvideo';
    case VIDEO_QUICKTIME = 'video/quicktime';
    case VIDEO_WMV       = 'video/x-ms-wmv';
    case VIDEO_MKV       = 'video/x-matroska';
    case VIDEO_FLV       = 'video/x-flv';
    case VIDEO_M4V       = 'video/x-m4v';
    case VIDEO_TS        = 'video/mp2t';

    // Audio
    case AUDIO_MP3  = 'audio/mpeg';
    case AUDIO_WAV  = 'audio/wav';
    case AUDIO_OGG  = 'audio/ogg';
    case AUDIO_AAC  = 'audio/aac';
    case AUDIO_FLAC = 'audio/flac';
    case AUDIO_M4A  = 'audio/x-m4a';
    case AUDIO_WMA  = 'audio/x-ms-wma';
    case AUDIO_MIDI = 'audio/midi';

    // Archives
    case APPLICATION_ZIP  = 'application/zip';
    case APPLICATION_RAR  = 'application/vnd.rar';
    case APPLICATION_TAR  = 'application/x-tar';
    case APPLICATION_GZIP = 'application/gzip';
    case APPLICATION_7Z   = 'application/x-7z-compressed';
    case APPLICATION_BZ2  = 'application/x-bzip2';
    case APPLICATION_XZ   = 'application/x-xz';
    case APPLICATION_ISO  = 'application/x-iso9660-image';

    // Web
    case TEXT_HTML              = 'text/html';
    case TEXT_CSS               = 'text/css';
    case TEXT_JAVASCRIPT        = 'text/javascript';
    case APPLICATION_JAVASCRIPT = 'application/javascript';
    case APPLICATION_PHP        = 'application/x-httpd-php';
    case TEXT_ASP               = 'text/asp';
    case TEXT_JSP               = 'text/jsp';

    // Programming
    case TEXT_JAVA       = 'text/x-java';
    case TEXT_PYTHON     = 'text/x-python';
    case TEXT_CPP        = 'text/x-c++src';
    case TEXT_C          = 'text/x-c';
    case TEXT_CSHARP     = 'text/x-csharp';
    case TEXT_GO         = 'text/x-go';
    case TEXT_RUST       = 'text/x-rust';
    case TEXT_TYPESCRIPT = 'text/typescript';
    case TEXT_SWIFT      = 'text/x-swift';
    case TEXT_KOTLIN     = 'text/x-kotlin';
    case TEXT_DART       = 'text/x-dart';
    case TEXT_RUBY       = 'text/x-ruby';

    // Fonts
    case FONT_TTF   = 'font/ttf';
    case FONT_OTF   = 'font/otf';
    case FONT_WOFF  = 'font/woff';
    case FONT_WOFF2 = 'font/woff2';
    case FONT_EOT   = 'application/vnd.ms-fontobject';

    // 3D and Design
    case MODEL_OBJ               = 'model/obj';
    case MODEL_STL               = 'model/stl';
    case MODEL_FBX               = 'application/octet-stream';
    case APPLICATION_BLEND       = 'application/x-blender';
    case IMAGE_PSD               = 'image/vnd.adobe.photoshop';
    case APPLICATION_ILLUSTRATOR = 'application/illustrator';
    case APPLICATION_EPS         = 'application/postscript';
    case APPLICATION_SKETCH      = 'application/sketch';
    case APPLICATION_XD          = 'application/vnd.adobe.xd';
    case APPLICATION_FIGMA       = 'application/figma';
    case APPLICATION_CDR         = 'application/cdr';

    // Database
    case APPLICATION_SQL    = 'application/sql';
    case APPLICATION_DB     = 'application/x-sqlite3-db';
    case APPLICATION_MDB    = 'application/x-msaccess';
    case APPLICATION_ACCDB  = 'application/msaccess';
    case APPLICATION_SQLITE = 'application/x-sqlite3';

    // E-books
    case APPLICATION_EPUB = 'application/epub+zip';
    case APPLICATION_MOBI = 'application/x-mobipocket-ebook';
    case APPLICATION_AZW  = 'application/vnd.amazon.ebook';
    case APPLICATION_FB2  = 'application/x-fictionbook+xml';
    case APPLICATION_LIT  = 'application/x-ms-reader';

    // CAD
    case APPLICATION_DWG = 'application/acad';
    case APPLICATION_DXF = 'application/dxf';
    case APPLICATION_DGN = 'application/x-dgn';
    case APPLICATION_SKP = 'application/vnd.sketchup.skp';

    // Scientific/Data
    case APPLICATION_HDF    = 'application/x-hdf';
    case APPLICATION_HDF5   = 'application/x-hdf5';
    case APPLICATION_FITS   = 'application/fits';
    case APPLICATION_NETCDF = 'application/x-netcdf';
    case APPLICATION_MATLAB = 'application/x-matlab-data';

    // Configuration
    case TEXT_INI  = 'text/x-ini';
    case TEXT_YAML = 'text/yaml';
    case TEXT_TOML = 'text/toml';
    case TEXT_CONF = 'text/x-conf';

    // Executable
    case APPLICATION_EXE = 'application/x-msdownload';
    case APPLICATION_DLL = 'application/x-msdownload-dll';
    case APPLICATION_APK = 'application/vnd.android.package-archive';
    case APPLICATION_APP = 'application/x-executable';
    case APPLICATION_DMG = 'application/x-apple-diskimage';
    case APPLICATION_MSI = 'application/x-msi';

    /**
     * Get the extension for a MIME type
     *
     * @param string $mimeType The MIME type
     *
     * @return string|null The extension or null if not found
     */
    public static function getExtension(string $mimeType): ?string
    {
        foreach (self::cases() as $case) {
            if ($case->value === $mimeType) {
                return match ($case) {
                    self::IMAGE_JPEG         => 'jpg',
                    self::IMAGE_PNG          => 'png',
                    self::IMAGE_GIF          => 'gif',
                    self::IMAGE_SVG          => 'svg',
                    self::IMAGE_WEBP         => 'webp',
                    self::IMAGE_BMP          => 'bmp',
                    self::IMAGE_TIFF         => 'tiff',
                    self::IMAGE_ICO          => 'ico',
                    self::IMAGE_HEIC         => 'heic',
                    self::IMAGE_AVIF         => 'avif',
                    self::IMAGE_XCF          => 'xcf',
                    self::APPLICATION_PDF    => 'pdf',
                    self::APPLICATION_MSWORD => 'doc',
                    self::APPLICATION_DOCX   => 'docx',
                    self::APPLICATION_XLS    => 'xls',
                    self::APPLICATION_XLSX   => 'xlsx',
                    self::APPLICATION_PPT    => 'ppt',
                    self::APPLICATION_PPTX   => 'pptx',
                    self::APPLICATION_ODT    => 'odt',
                    self::APPLICATION_ODS    => 'ods',
                    self::APPLICATION_ODP    => 'odp',
                    self::APPLICATION_RTF    => 'rtf',
                    self::TEXT_PLAIN         => 'txt',
                    self::TEXT_CSV           => 'csv',
                    self::TEXT_XML, self::APPLICATION_XML => 'xml',
                    self::APPLICATION_JSON => 'json',
                    self::TEXT_MARKDOWN    => 'md',
                    self::VIDEO_MP4        => 'mp4',
                    self::VIDEO_WEBM       => 'webm',
                    self::VIDEO_OGG        => 'ogv',
                    self::VIDEO_AVI        => 'avi',
                    self::VIDEO_QUICKTIME  => 'mov',
                    self::VIDEO_WMV        => 'wmv',
                    self::VIDEO_MKV        => 'mkv',
                    self::VIDEO_FLV        => 'flv',
                    self::VIDEO_M4V        => 'm4v',
                    self::VIDEO_TS         => 'ts-video',
                    self::AUDIO_MP3        => 'mp3',
                    self::AUDIO_WAV        => 'wav',
                    self::AUDIO_OGG        => 'ogg',
                    self::AUDIO_AAC        => 'aac',
                    self::AUDIO_FLAC       => 'flac',
                    self::AUDIO_M4A        => 'm4a',
                    self::AUDIO_WMA        => 'wma',
                    self::AUDIO_MIDI       => 'midi',
                    self::APPLICATION_ZIP  => 'zip',
                    self::APPLICATION_RAR  => 'rar',
                    self::APPLICATION_TAR  => 'tar',
                    self::APPLICATION_GZIP => 'gz',
                    self::APPLICATION_7Z   => '7z',
                    self::APPLICATION_BZ2  => 'bz2',
                    self::APPLICATION_XZ   => 'xz',
                    self::APPLICATION_ISO  => 'iso',
                    self::TEXT_HTML        => 'html',
                    self::TEXT_CSS         => 'css',
                    self::TEXT_JAVASCRIPT, self::APPLICATION_JAVASCRIPT => 'js',
                    self::APPLICATION_PHP         => 'php',
                    self::TEXT_ASP                => 'asp',
                    self::TEXT_JSP                => 'jsp',
                    self::TEXT_JAVA               => 'java',
                    self::TEXT_PYTHON             => 'py',
                    self::TEXT_CPP                => 'cpp',
                    self::TEXT_C                  => 'c',
                    self::TEXT_CSHARP             => 'cs',
                    self::TEXT_GO                 => 'go',
                    self::TEXT_RUST               => 'rs',
                    self::TEXT_TYPESCRIPT         => 'ts',
                    self::TEXT_SWIFT              => 'swift',
                    self::TEXT_KOTLIN             => 'kt',
                    self::TEXT_DART               => 'dart',
                    self::TEXT_RUBY               => 'rb',
                    self::FONT_TTF                => 'ttf',
                    self::FONT_OTF                => 'otf',
                    self::FONT_WOFF               => 'woff',
                    self::FONT_WOFF2              => 'woff2',
                    self::FONT_EOT                => 'eot',
                    self::MODEL_OBJ               => 'obj',
                    self::MODEL_STL               => 'stl',
                    self::MODEL_FBX               => 'fbx',
                    self::APPLICATION_BLEND       => 'blend',
                    self::IMAGE_PSD               => 'psd',
                    self::APPLICATION_ILLUSTRATOR => 'ai',
                    self::APPLICATION_EPS         => 'eps',
                    self::APPLICATION_SKETCH      => 'sketch',
                    self::APPLICATION_XD          => 'xd',
                    self::APPLICATION_FIGMA       => 'fig',
                    self::APPLICATION_CDR         => 'cdr',
                    self::APPLICATION_SQL         => 'sql',
                    self::APPLICATION_DB, self::APPLICATION_SQLITE => 'db',
                    self::APPLICATION_MDB    => 'mdb',
                    self::APPLICATION_ACCDB  => 'accdb',
                    self::APPLICATION_EPUB   => 'epub',
                    self::APPLICATION_MOBI   => 'mobi',
                    self::APPLICATION_AZW    => 'azw',
                    self::APPLICATION_FB2    => 'fb2',
                    self::APPLICATION_LIT    => 'lit',
                    self::APPLICATION_DWG    => 'dwg',
                    self::APPLICATION_DXF    => 'dxf',
                    self::APPLICATION_DGN    => 'dgn',
                    self::APPLICATION_SKP    => 'skp',
                    self::APPLICATION_HDF    => 'hdf',
                    self::APPLICATION_HDF5   => 'h5',
                    self::APPLICATION_FITS   => 'fits',
                    self::APPLICATION_NETCDF => 'nc',
                    self::APPLICATION_MATLAB => 'mat',
                    self::TEXT_INI           => 'ini',
                    self::TEXT_YAML          => 'yaml',
                    self::TEXT_TOML          => 'toml',
                    self::TEXT_CONF          => 'conf',
                    self::APPLICATION_EXE    => 'exe',
                    self::APPLICATION_DLL    => 'dll',
                    self::APPLICATION_APK    => 'apk',
                    self::APPLICATION_APP    => 'app',
                    self::APPLICATION_DMG    => 'dmg',
                    self::APPLICATION_MSI    => 'msi',
                };
            }
        }

        // Fall back to CodeIgniter's Mimes class
        return Mimes::guessExtensionFromType($mimeType);
    }

    /**
     * Get the extension for a MIME type enum case
     *
     * @param self $mimeType The MIME type enum case
     *
     * @return string|null The extension or null if not found
     */
    public static function getMimeTypeExtension(self $mimeType): ?string
    {
        return self::getExtension($mimeType->value);
    }

    /**
     * Check if a mime type is an image
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is an image, false otherwise
     */
    public static function isImage(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/') || in_array($mimeType, [
            self::IMAGE_JPEG->value,
            self::IMAGE_PNG->value,
            self::IMAGE_GIF->value,
            self::IMAGE_SVG->value,
            self::IMAGE_WEBP->value,
            self::IMAGE_BMP->value,
            self::IMAGE_TIFF->value,
            self::IMAGE_ICO->value,
            self::IMAGE_HEIC->value,
            self::IMAGE_AVIF->value,
            self::IMAGE_XCF->value,
            self::IMAGE_PSD->value,
        ], true);
    }

    /**
     * Check if a mime type is a document
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a document, false otherwise
     */
    public static function isDocument(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::APPLICATION_PDF->value,
            self::APPLICATION_MSWORD->value,
            self::APPLICATION_DOCX->value,
            self::APPLICATION_XLS->value,
            self::APPLICATION_XLSX->value,
            self::APPLICATION_PPT->value,
            self::APPLICATION_PPTX->value,
            self::APPLICATION_ODT->value,
            self::APPLICATION_ODS->value,
            self::APPLICATION_ODP->value,
            self::APPLICATION_RTF->value,
            self::TEXT_PLAIN->value,
            self::TEXT_CSV->value,
            self::TEXT_XML->value,
            self::APPLICATION_XML->value,
            self::APPLICATION_JSON->value,
            self::TEXT_MARKDOWN->value,
        ], true);
    }

    /**
     * Check if a mime type is a video
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a video, false otherwise
     */
    public static function isVideo(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/') || in_array($mimeType, [
            self::VIDEO_MP4->value,
            self::VIDEO_WEBM->value,
            self::VIDEO_OGG->value,
            self::VIDEO_AVI->value,
            self::VIDEO_QUICKTIME->value,
            self::VIDEO_WMV->value,
            self::VIDEO_MKV->value,
            self::VIDEO_FLV->value,
            self::VIDEO_M4V->value,
            self::VIDEO_TS->value,
        ], true);
    }

    /**
     * Check if a mime type is an audio
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is an audio, false otherwise
     */
    public static function isAudio(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'audio/') || in_array($mimeType, [
            self::AUDIO_MP3->value,
            self::AUDIO_WAV->value,
            self::AUDIO_OGG->value,
            self::AUDIO_AAC->value,
            self::AUDIO_FLAC->value,
            self::AUDIO_M4A->value,
            self::AUDIO_WMA->value,
            self::AUDIO_MIDI->value,
        ], true);
    }

    /**
     * Check if a mime type is an archive
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is an archive, false otherwise
     */
    public static function isArchive(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::APPLICATION_ZIP->value,
            self::APPLICATION_RAR->value,
            self::APPLICATION_TAR->value,
            self::APPLICATION_GZIP->value,
            self::APPLICATION_7Z->value,
            self::APPLICATION_BZ2->value,
            self::APPLICATION_XZ->value,
            self::APPLICATION_ISO->value,
        ], true);
    }

    /**
     * Check if a mime type is a text
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a text, false otherwise
     */
    public static function isText(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'text/') || in_array($mimeType, [
            self::TEXT_PLAIN->value,
            self::TEXT_HTML->value,
            self::TEXT_CSS->value,
            self::TEXT_JAVASCRIPT->value,
            self::APPLICATION_JAVASCRIPT->value,
            self::TEXT_XML->value,
            self::APPLICATION_XML->value,
            self::APPLICATION_JSON->value,
            self::TEXT_MARKDOWN->value,
        ], true);
    }

    /**
     * Check if a mime type is a web file
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a web file, false otherwise
     */
    public static function isWeb(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::TEXT_HTML->value,
            self::TEXT_CSS->value,
            self::TEXT_JAVASCRIPT->value,
            self::APPLICATION_JAVASCRIPT->value,
            self::APPLICATION_PHP->value,
            self::TEXT_ASP->value,
            self::TEXT_JSP->value,
        ], true);
    }

    /**
     * Check if a mime type is a programming file
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a programming file, false otherwise
     */
    public static function isProgramming(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::TEXT_JAVA->value,
            self::TEXT_PYTHON->value,
            self::TEXT_CPP->value,
            self::TEXT_C->value,
            self::TEXT_CSHARP->value,
            self::TEXT_GO->value,
            self::TEXT_RUST->value,
            self::TEXT_TYPESCRIPT->value,
            self::TEXT_SWIFT->value,
            self::TEXT_KOTLIN->value,
            self::TEXT_DART->value,
            self::TEXT_RUBY->value,
        ], true);
    }

    /**
     * Check if a mime type is a font
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a font, false otherwise
     */
    public static function isFont(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'font/') || in_array($mimeType, [
            self::FONT_TTF->value,
            self::FONT_OTF->value,
            self::FONT_WOFF->value,
            self::FONT_WOFF2->value,
            self::FONT_EOT->value,
        ], true);
    }

    /**
     * Check if a mime type is a design file
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a design file, false otherwise
     */
    public static function isDesign(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::MODEL_OBJ->value,
            self::MODEL_STL->value,
            self::MODEL_FBX->value,
            self::APPLICATION_BLEND->value,
            self::IMAGE_PSD->value,
            self::APPLICATION_ILLUSTRATOR->value,
            self::APPLICATION_EPS->value,
            self::APPLICATION_SKETCH->value,
            self::APPLICATION_XD->value,
            self::APPLICATION_FIGMA->value,
            self::APPLICATION_CDR->value,
        ], true);
    }

    /**
     * Check if a mime type is a database file
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a database file, false otherwise
     */
    public static function isDatabase(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::APPLICATION_SQL->value,
            self::APPLICATION_DB->value,
            self::APPLICATION_MDB->value,
            self::APPLICATION_ACCDB->value,
            self::APPLICATION_SQLITE->value,
        ], true);
    }

    /**
     * Check if a mime type is an e-book
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is an e-book, false otherwise
     */
    public static function isEbook(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::APPLICATION_EPUB->value,
            self::APPLICATION_MOBI->value,
            self::APPLICATION_AZW->value,
            self::APPLICATION_FB2->value,
            self::APPLICATION_LIT->value,
            self::APPLICATION_PDF->value,
        ], true);
    }

    /**
     * Check if a mime type is a CAD file
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a CAD file, false otherwise
     */
    public static function isCad(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::APPLICATION_DWG->value,
            self::APPLICATION_DXF->value,
            self::APPLICATION_DGN->value,
            self::APPLICATION_SKP->value,
            self::MODEL_OBJ->value,
            self::MODEL_STL->value,
        ], true);
    }

    /**
     * Check if a mime type is a scientific/data file
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a scientific/data file, false otherwise
     */
    public static function isScientific(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::APPLICATION_HDF->value,
            self::APPLICATION_HDF5->value,
            self::APPLICATION_FITS->value,
            self::APPLICATION_NETCDF->value,
            self::APPLICATION_MATLAB->value,
            self::TEXT_CSV->value,
        ], true);
    }

    /**
     * Check if a mime type is a configuration file
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a configuration file, false otherwise
     */
    public static function isConfiguration(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::TEXT_INI->value,
            self::TEXT_YAML->value,
            self::TEXT_TOML->value,
            self::TEXT_CONF->value,
            self::APPLICATION_JSON->value,
            self::TEXT_XML->value,
            self::APPLICATION_XML->value,
        ], true);
    }

    /**
     * Check if a mime type is an executable file
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is an executable file, false otherwise
     */
    public static function isExecutable(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::APPLICATION_EXE->value,
            self::APPLICATION_DLL->value,
            self::APPLICATION_APK->value,
            self::APPLICATION_APP->value,
            self::APPLICATION_DMG->value,
            self::APPLICATION_MSI->value,
        ], true);
    }

    /**
     * Check if a mime type is a vector graphic
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a vector graphic, false otherwise
     */
    public static function isVectorGraphic(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::IMAGE_SVG->value,
            self::APPLICATION_ILLUSTRATOR->value,
            self::APPLICATION_EPS->value,
            self::APPLICATION_CDR->value,
        ], true);
    }

    /**
     * Check if a mime type is a raster graphic
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a raster graphic, false otherwise
     */
    public static function isRasterGraphic(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::IMAGE_JPEG->value,
            self::IMAGE_PNG->value,
            self::IMAGE_GIF->value,
            self::IMAGE_BMP->value,
            self::IMAGE_TIFF->value,
            self::IMAGE_XCF->value,
            self::IMAGE_PSD->value,
            self::IMAGE_WEBP->value,
            self::IMAGE_HEIC->value,
            self::IMAGE_AVIF->value,
        ], true);
    }

    /**
     * Check if a mime type is a spreadsheet
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a spreadsheet, false otherwise
     */
    public static function isSpreadsheet(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::APPLICATION_XLS->value,
            self::APPLICATION_XLSX->value,
            self::APPLICATION_ODS->value,
            self::TEXT_CSV->value,
        ], true);
    }

    /**
     * Check if a mime type is a presentation
     *
     * @param string $mimeType The mime type
     *
     * @return bool True if the mime type is a presentation, false otherwise
     */
    public static function isPresentation(string $mimeType): bool
    {
        return in_array($mimeType, [
            self::APPLICATION_PPT->value,
            self::APPLICATION_PPTX->value,
            self::APPLICATION_ODP->value,
        ], true);
    }

    /**
     * Get the mime type from a file extension
     *
     * @param string $extension The file extension
     *
     * @return string|null The mime type or null if not found
     */
    public static function fromExtension(string $extension): ?string
    {
        $extension = strtolower($extension);

        foreach (self::cases() as $case) {
            if (self::getMimeTypeExtension($case) === $extension) {
                return $case->value;
            }
        }

        // Fall back to CodeIgniter's Mimes class
        return Mimes::guessTypeFromExtension($extension);
    }

    /**
     * Get the mime type from an AssetExtension enum
     *
     * @param AssetExtension $extension The AssetExtension enum
     *
     * @return string|null The mime type or null if not found
     */
    public static function fromAssetExtension(AssetExtension $extension): ?string
    {
        return self::fromExtension($extension->value);
    }
}
