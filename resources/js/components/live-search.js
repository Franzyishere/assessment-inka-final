let activeController = null;
let searchTimer = null;

async function replacePageContent(url, { pushState = true, focusSearch = true } = {}) {
    activeController?.abort();
    activeController = new AbortController();
    const controller = activeController;
    const currentContent = document.querySelector('[data-page-content]');
    if (!currentContent) return;
    currentContent.classList.add('is-live-search-loading');

    try {
        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' }, signal: controller.signal });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = new DOMParser().parseFromString(await response.text(), 'text/html');
        if (controller.signal.aborted || controller !== activeController) return;
        const nextContent = result.querySelector('[data-page-content]');
        if (!nextContent) throw new Error('Konten halaman tidak ditemukan.');

        window.Alpine?.destroyTree(currentContent);
        currentContent.innerHTML = nextContent.innerHTML;
        window.Alpine?.initTree(currentContent);
        initializeLiveSearch(currentContent);
        if (pushState) window.history.replaceState({}, '', url);

        if (focusSearch) {
            const input = currentContent.querySelector('[data-live-search] input[type="search"]');
            if (input) {
                input.focus({ preventScroll: true });
                input.setSelectionRange(input.value.length, input.value.length);
            }
        }
    } catch (error) {
        if (error.name !== 'AbortError' && !controller.signal.aborted) window.location.assign(url);
    } finally {
        if (controller === activeController) currentContent.classList.remove('is-live-search-loading');
    }
}

function formUrl(form) {
    const url = new URL(form.action || window.location.href, window.location.origin);
    url.search = '';
    for (const [key, value] of new FormData(form).entries()) {
        if (String(value).trim() !== '') url.searchParams.append(key, value);
    }
    return url.toString();
}

export function initializeLiveSearch(root = document) {
    root.querySelectorAll('[data-live-search]').forEach(form => {
        if (form.dataset.liveSearchInitialized) return;
        form.dataset.liveSearchInitialized = 'true';
        const input = form.querySelector('input[type="search"]');
        if (!input) return;

        input.addEventListener('input', () => {
            activeController?.abort();
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => replacePageContent(formUrl(form)), 180);
        });
        form.addEventListener('submit', event => {
            event.preventDefault();
            window.clearTimeout(searchTimer);
            replacePageContent(formUrl(form));
        });
    });
}

document.addEventListener('click', event => {
    const link = event.target.closest('[data-page-content] a[href*="page="]');
    if (!link || !document.querySelector('[data-live-search]')) return;
    event.preventDefault();
    replacePageContent(link.href, { focusSearch: false });
});

window.addEventListener('popstate', () => replacePageContent(window.location.href, { pushState: false, focusSearch: false }));
