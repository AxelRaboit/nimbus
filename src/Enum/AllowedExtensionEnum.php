<?php

declare(strict_types=1);

namespace App\Enum;

enum AllowedExtensionEnum: string
{
    // Images
    case Jpg = '.jpg';
    case Jpeg = '.jpeg';
    case Png = '.png';
    case Gif = '.gif';
    case Webp = '.webp';

    // PDF
    case Pdf = '.pdf';

    // Word
    case Doc = '.doc';
    case Docx = '.docx';

    // Excel
    case Xls = '.xls';
    case Xlsx = '.xlsx';

    // PowerPoint
    case Ppt = '.ppt';
    case Pptx = '.pptx';

    // Texte
    case Txt = '.txt';
    case Csv = '.csv';
    case Md = '.md';

    // Vidéo
    case Mp4 = '.mp4';
    case Mov = '.mov';
    case Avi = '.avi';
    case Mkv = '.mkv';
    case Webm = '.webm';

    // Audio
    case Mp3 = '.mp3';
    case Wav = '.wav';
    case Ogg = '.ogg';
    case M4a = '.m4a';
    case Flac = '.flac';
    case Aac = '.aac';

    // Archive
    case Zip = '.zip';

    public function getGroup(): string
    {
        return match ($this) {
            self::Jpg, self::Jpeg, self::Png, self::Gif, self::Webp => 'images',
            self::Pdf, self::Doc, self::Docx, self::Xls, self::Xlsx, self::Ppt, self::Pptx => 'documents',
            self::Txt, self::Csv, self::Md => 'text',
            self::Mp4, self::Mov, self::Avi, self::Mkv, self::Webm => 'video',
            self::Mp3, self::Wav, self::Ogg, self::M4a, self::Flac, self::Aac => 'audio',
            self::Zip => 'archive',
        };
    }

    /**
     * @return array<string, string[]>
     */
    public static function groupedValues(): array
    {
        $groups = [];
        foreach (self::cases() as $case) {
            $groups[$case->getGroup()][] = $case->value;
        }

        return $groups;
    }

    public static function accept(): string
    {
        return implode(',', array_column(self::cases(), 'value'));
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
