const STORAGE_KEY = "nimbus-pending-transfer";

export function useTransferDraft() {
    function saveDraft(context) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(context));
        } catch {}
    }

    function getDraft() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    }

    function clearDraft() {
        localStorage.removeItem(STORAGE_KEY);
    }

    function clearTusFingerprints() {
        Object.keys(localStorage).forEach((key) => {
            if (key.startsWith("tus::")) {
                localStorage.removeItem(key);
            }
        });
    }

    return { saveDraft, getDraft, clearDraft, clearTusFingerprints };
}
