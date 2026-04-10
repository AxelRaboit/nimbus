<?php

declare(strict_types=1);

namespace App\Enum;

enum AllowedMimeTypeEnum: string
{
    // Images
    case Jpeg = 'image/jpeg';
    case Png = 'image/png';
    case Gif = 'image/gif';
    case Webp = 'image/webp';

    // PDF
    case Pdf = 'application/pdf';

    // Word
    case Doc = 'application/msword';
    case Docx = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    // Excel
    case Xls = 'application/vnd.ms-excel';
    case Xlsx = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    // PowerPoint
    case Ppt = 'application/vnd.ms-powerpoint';
    case Pptx = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

    // Texte
    case Txt = 'text/plain';
    case Csv = 'text/csv';
    case Markdown = 'text/markdown';

    // Vidéo
    case Mp4 = 'video/mp4';
    case Mov = 'video/quicktime';
    case Avi = 'video/x-msvideo';
    case Mkv = 'video/x-matroska';
    case Webm = 'video/webm';

    // Audio
    case Mp3 = 'audio/mpeg';
    case Wav = 'audio/wav';
    case WavX = 'audio/x-wav';
    case Ogg = 'audio/ogg';
    case M4a = 'audio/mp4';
    case Flac = 'audio/flac';
    case FlacX = 'audio/x-flac';
    case Aac = 'audio/aac';

    // Archive
    case Zip = 'application/zip';
    case ZipCompressed = 'application/x-zip-compressed';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
