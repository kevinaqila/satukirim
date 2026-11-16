import { ref } from 'vue';

export async function copyToClipboard(text: string): Promise<boolean> {
    if (!text) return false;
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch {
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    let copied = false;
    try {
        copied = document.execCommand('copy');
    } finally {
        document.body.removeChild(textarea);
    }

    return copied;
}

export function useCopyClipboard(resetMs = 1800) {
    const copiedKey = ref<string | number | null>(null);
    let resetTimer: ReturnType<typeof setTimeout> | null = null;

    const copy = async (key: string | number, text: string): Promise<boolean> => {
        const ok = await copyToClipboard(text);
        if (!ok) return false;

        copiedKey.value = key;
        if (resetTimer) clearTimeout(resetTimer);
        resetTimer = setTimeout(() => {
            if (copiedKey.value === key) {
                copiedKey.value = null;
            }
        }, resetMs);

        return true;
    };

    const isCopied = (key: string | number): boolean => copiedKey.value === key;

    return {
        copiedKey,
        copy,
        isCopied,
    };
}

