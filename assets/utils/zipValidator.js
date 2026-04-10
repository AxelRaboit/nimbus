const ZIP_SIGNATURE_EOCD = 0x06054b50;
const ZIP_SIGNATURE_CD = 0x02014b50;
const ZIP_EOCD_MIN_SIZE = 22;
const ZIP_EOCD_MAX_COMMENT = 65535;
const ZIP_CD_ENTRY_HEADER_SIZE = 46;

export async function getDisallowedZipFiles(file, allowedExtensions) {
    const buffer = await file.arrayBuffer();
    const names = listZipEntries(buffer);

    return names.filter((name) => {
        if (name.endsWith("/")) return false;
        const dotIndex = name.lastIndexOf(".");
        const ext = dotIndex !== -1 ? name.slice(dotIndex).toLowerCase() : "";
        return !allowedExtensions.includes(ext);
    });
}

function listZipEntries(buffer) {
    const view = new DataView(buffer);
    const len = buffer.byteLength;

    const eocdOffset = findEocdOffset(view, len);
    if (eocdOffset === -1) return [];

    const cdSize = view.getUint32(eocdOffset + 12, true);
    const cdOffset = view.getUint32(eocdOffset + 16, true);

    return parseCentralDirectory(buffer, view, cdOffset, cdSize);
}

function findEocdOffset(view, len) {
    for (
        let i = len - ZIP_EOCD_MIN_SIZE;
        i >= Math.max(0, len - ZIP_EOCD_MIN_SIZE - ZIP_EOCD_MAX_COMMENT);
        i--
    ) {
        if (view.getUint32(i, true) === ZIP_SIGNATURE_EOCD) return i;
    }
    return -1;
}

function parseCentralDirectory(buffer, view, cdOffset, cdSize) {
    const decoder = new TextDecoder();
    const names = [];
    let offset = cdOffset;

    while (offset < cdOffset + cdSize) {
        if (view.getUint32(offset, true) !== ZIP_SIGNATURE_CD) break;
        const fileNameLength = view.getUint16(offset + 28, true);
        const extraFieldLength = view.getUint16(offset + 30, true);
        const commentLength = view.getUint16(offset + 32, true);
        names.push(
            decoder.decode(
                new Uint8Array(
                    buffer,
                    offset + ZIP_CD_ENTRY_HEADER_SIZE,
                    fileNameLength,
                ),
            ),
        );
        offset +=
            ZIP_CD_ENTRY_HEADER_SIZE +
            fileNameLength +
            extraFieldLength +
            commentLength;
    }

    return names;
}
