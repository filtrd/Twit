let tiktokScriptPromise = null;
let instagramScriptPromise = null;

function loadScript(id, src) {
    const existing = document.getElementById(id);
    if (existing) return Promise.resolve();

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.id = id;
        script.src = src;
        script.async = true;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

async function processTikTokEmbeds(root) {
    const embeds = root.querySelectorAll?.('blockquote.tiktok-embed') ?? [];
    if (!embeds.length) return;

    try {
        tiktokScriptPromise ??= loadScript('tiktok-embed-script', 'https://www.tiktok.com/embed.js');
        await tiktokScriptPromise;
        if (window.tiktokEmbed?.lib?.render) {
            window.tiktokEmbed.lib.render(embeds);
        }
    } catch (_) {
        // Leave the original link available if the provider cannot be loaded.
    }
}

async function processInstagramEmbeds(root) {
    const embeds = root.querySelectorAll?.('blockquote.instagram-media') ?? [];
    if (!embeds.length) return;

    try {
        instagramScriptPromise ??= loadScript('instagram-embed-script', 'https://www.instagram.com/embed.js');
        await instagramScriptPromise;
        window.instgrm?.Embeds?.process();
    } catch (_) {
        // Leave the original link available if the provider cannot be loaded.
    }
}

export function initVideoEmbeds(root = document) {
    processTikTokEmbeds(root);
    processInstagramEmbeds(root);
}
