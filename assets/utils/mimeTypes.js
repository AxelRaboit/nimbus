export const IMAGE_MIME_TYPES = [
    "image/jpeg",
    "image/png",
    "image/gif",
    "image/webp",
    "image/svg+xml",
    "image/avif",
];

export const PDF_MIME_TYPE = "application/pdf";

export function isImage(mimeType) {
    return IMAGE_MIME_TYPES.includes(mimeType);
}

export function isPdf(mimeType) {
    return mimeType === PDF_MIME_TYPE;
}

export function isPreviewable(mimeType) {
    return isImage(mimeType) || isPdf(mimeType);
}
