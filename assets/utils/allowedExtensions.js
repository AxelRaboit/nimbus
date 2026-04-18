// Miroir de src/Enum/AllowedExtensionEnum.php
export const ALLOWED_EXTENSIONS = [
    // Images
    ".jpg",
    ".jpeg",
    ".png",
    ".gif",
    ".webp",
    // PDF
    ".pdf",
    // Word
    ".doc",
    ".docx",
    // Excel
    ".xls",
    ".xlsx",
    // PowerPoint
    ".ppt",
    ".pptx",
    // Texte
    ".txt",
    ".csv",
    ".md",
    // Vidéo
    ".mp4",
    ".mov",
    ".avi",
    ".mkv",
    ".webm",
    // Audio
    ".mp3",
    ".wav",
    ".ogg",
    ".m4a",
    ".flac",
    ".aac",
    // Archive
    ".zip",
];

export const ALLOWED_EXTENSIONS_ACCEPT = ALLOWED_EXTENSIONS.join(",");

/**
 * Check if a filename has an allowed extension.
 * @param {string} name
 * @returns {{ ext: string, allowed: boolean }}
 */
export function isAllowedExt(name) {
    const dotIndex = name.lastIndexOf(".");
    const ext = dotIndex !== -1 ? name.slice(dotIndex).toLowerCase() : "";
    return { ext, allowed: ALLOWED_EXTENSIONS.includes(ext) };
}
