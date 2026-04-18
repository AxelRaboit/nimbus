/**
 * Count recipients who have downloaded the transfer.
 * @param {{ downloaded: boolean }[]} recipients
 * @returns {number}
 */
export function downloadedCount(recipients) {
    return recipients.filter((r) => r.downloaded).length;
}

/**
 * Sum file sizes in bytes.
 * @param {{ size: number }[]} files
 * @returns {number}
 */
export function totalSize(files) {
    return files.reduce((acc, file) => acc + file.size, 0);
}
