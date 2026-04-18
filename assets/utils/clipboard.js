/**
 * Copy text to clipboard and toggle a flag ref for 2 seconds.
 * @param {string} text
 * @param {import('vue').Ref<boolean>} flag
 * @param {number} [duration=2000]
 */
export async function copyToClipboard(text, flag, duration = 2000) {
    try {
        await navigator.clipboard.writeText(text);
        flag.value = true;
        setTimeout(() => (flag.value = false), duration);
    } catch {}
}
