export const Route = Object.freeze({
    Home: "home",
    Dashboard: "transfers",
    Plan: "plan",
    Profile: "profile",
    Dev: "dev",
});

/** @param {string} ownerToken */
export function manageUrl(ownerToken) {
    return `/manage/${ownerToken}`;
}

/** @param {string} token @param {string} filename */
export function fileDownloadUrl(token, filename) {
    return `/t/${token}/download/${filename}`;
}

/** @param {string} token @param {string} filename */
export function filePreviewUrl(token, filename) {
    return `/t/${token}/preview/${filename}`;
}
