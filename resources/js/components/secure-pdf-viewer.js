import { createPdfHighlights } from './pdf-highlights';
import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

let securityGuardsInstalled = false;

function installGlobalSecurityGuards() {
    if (securityGuardsInstalled) return;
    securityGuardsInstalled = true;

    // 1. Prevent context menu (right-click) on secure PDF viewers
    document.addEventListener('contextmenu', (event) => {
        if (event.target.closest('[data-secure-pdf-viewer]')) {
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
    }, true);

    // 2. Prevent common keyboard shortcuts for saving, printing, or inspecting
    document.addEventListener('keydown', (event) => {
        const key = event.key ? event.key.toLowerCase() : '';
        const isCtrlOrMeta = event.ctrlKey || event.metaKey;

        // Block Ctrl+S (Save), Ctrl+P (Print), Ctrl+U (View Source)
        if (isCtrlOrMeta && (key === 's' || key === 'p' || key === 'u')) {
            event.preventDefault();
            event.stopPropagation();
            return false;
        }

        // Block DevTools shortcuts: F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C
        if (event.key === 'F12' || (isCtrlOrMeta && event.shiftKey && (key === 'i' || key === 'j' || key === 'c'))) {
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
    }, true);

    // 3. Inject print-blocking CSS style
    const style = document.createElement('style');
    style.id = 'secure-pdf-print-blocker';
    style.textContent = `
        @media print {
            body { display: none !important; }
            html { display: none !important; }
        }
        [data-secure-pdf-viewer] {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }
        [data-secure-pdf-viewer] canvas {
            pointer-events: none;
            user-select: none;
            -webkit-user-drag: none;
        }
    `;
    if (!document.getElementById('secure-pdf-print-blocker')) {
        document.head.appendChild(style);
    }
}

export function initializeSecurePdfViewers() {
    installGlobalSecurityGuards();

    const viewerElements = document.querySelectorAll('[data-secure-pdf-viewer]:not([data-initialized="true"])');

    viewerElements.forEach((element) => {
        element.setAttribute('data-initialized', 'true');
        createSecurePdfViewer(element);
    });
}

function createSecurePdfViewer(rootElement) {
    const pdfUrl = rootElement.getAttribute('data-pdf-url');
    if (!pdfUrl) return;

    const watermarkText = rootElement.getAttribute('data-watermark') || 'PT INKA (PERSERO) HUMAN CAPITAL';
    let pdfDoc = null;
    let currentScale = 1.15;
    let rendering = false;
    let renderPending = false;

    // Build the UI scaffolding
    rootElement.innerHTML = `
        <div class="flex flex-col h-full w-full bg-gray-100 dark:bg-gray-900 select-none overflow-hidden" oncontextmenu="return false;" ondragstart="return false;">
            <!-- Top Control Bar -->
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-xs dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200 sm:px-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300">
                        <svg class="size-3.5 text-brand-600 dark:text-brand-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Hanya Baca</span>
                    </span>
                    <span class="text-gray-400 dark:text-gray-500">|</span>
                    <span class="pdf-page-indicator font-medium text-gray-600 dark:text-gray-300">Memuat...</span>
                </div>
                <div class="flex items-center gap-1 sm:gap-2">
                    <button type="button" class="pdf-zoom-out inline-flex size-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600" title="Perkecil">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" /></svg>
                    </button>
                    <label class="flex items-center gap-1"><input type="number" min="25" max="300" step="1" aria-label="Zoom materi dalam persen" class="pdf-zoom-level w-16 rounded border border-gray-300 bg-white px-1 py-1 text-center text-xs" value="100"><span>%</span></label>
                    <button type="button" class="pdf-zoom-in inline-flex size-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600" title="Perbesar">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <button type="button" class="pdf-fit-width hidden rounded-lg border border-gray-200 bg-white px-2 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 sm:inline-flex" title="Sesuaikan Lebar">
                        Pas Lebar
                    </button>
                    <button type="button" class="pdf-fit-page rounded-lg border border-gray-200 bg-white px-2 py-1 text-[11px] text-gray-700">Pas Halaman</button>
                </div>
            </div>

            <div class="pdf-annotation-tools flex flex-wrap items-center gap-3 bg-white px-3 py-1 text-xs" hidden><button type="button" aria-pressed="false" class="rounded border border-gray-300 px-3 py-1">Stabilo</button><span role="status" aria-live="polite"></span></div>
            <!-- Scrollable Pages Viewport -->
            <div class="pdf-pages-scroll-container relative flex-1 overflow-y-auto overflow-x-auto p-1 flex flex-col gap-2">
                <!-- Loading State -->
                <div class="pdf-loading-state flex flex-col items-center justify-center py-16 text-gray-500">
                    <svg class="size-8 animate-spin text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-3 text-xs font-medium text-gray-600 dark:text-gray-300">Memuat materi PDF yang dilindungi...</p>
                </div>

                <!-- Rendered Canvas Pages Container -->
                <div class="pdf-rendered-pages flex flex-col gap-3 min-w-full w-max"></div>
            </div>
        </div>
    `;

    const scrollContainer = rootElement.querySelector('.pdf-pages-scroll-container');
    const renderedPagesContainer = rootElement.querySelector('.pdf-rendered-pages');
    const loadingState = rootElement.querySelector('.pdf-loading-state');
    const pageIndicator = rootElement.querySelector('.pdf-page-indicator');
    const zoomLevelEl = rootElement.querySelector('.pdf-zoom-level');
    const zoomInBtn = rootElement.querySelector('.pdf-zoom-in');
    const zoomOutBtn = rootElement.querySelector('.pdf-zoom-out');
    const fitWidthBtn = rootElement.querySelector('.pdf-fit-width');

    const highlights = createPdfHighlights(rootElement);
    let hasRenderedWhenVisible = false;
    let observedWidth = 0;
    let containerResizeTimer;
    let basePageWidth = 620;
    let basePageHeight = 880;
    let fitMode = 'width';

    // Load the PDF Document
    const loadingTask = pdfjsLib.getDocument({
        url: pdfUrl,
        withCredentials: true,
    });

    loadingTask.promise.then(async (doc) => {
        pdfDoc = doc;
        const firstViewport = (await doc.getPage(1)).getViewport({ scale: 1 });
        basePageWidth = firstViewport.width;
        basePageHeight = firstViewport.height;
        loadingState.classList.add('hidden');
        pageIndicator.textContent = `${pdfDoc.numPages} Halaman`;
        
        // If container is currently visible, render immediately
        if (scrollContainer.clientWidth > 50) {
            hasRenderedWhenVisible = true;
            calculateInitialScale();
            renderAllPages();
        }
    }).catch((error) => {
        console.error('Error loading PDF:', error);
        loadingState.innerHTML = `
            <div class="rounded-xl border border-error-200 bg-error-50 p-4 text-center text-xs text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                <p class="font-semibold">Materi PDF gagal dimuat.</p>
                <p class="mt-1">Silakan muat ulang halaman atau hubungi administrator.</p>
            </div>
        `;
    });

    if (window.ResizeObserver) {
        const observer = new ResizeObserver((entries) => {
            for (const entry of entries) {
                if (entry.contentRect.width > 50) {
                    if (pdfDoc && (!hasRenderedWhenVisible || Math.abs(observedWidth - entry.contentRect.width) > 2)) {
                        observedWidth = entry.contentRect.width;
                        hasRenderedWhenVisible = true;
                        clearTimeout(containerResizeTimer);
                        containerResizeTimer = setTimeout(() => {
                            calculateInitialScale();
                            renderAllPages();
                        }, 180);
                    }
                }
            }
        });
        observer.observe(rootElement);
    }

    function calculateInitialScale() {
        if (!pdfDoc || fitMode === 'manual') return;
        const availableWidth = scrollContainer.clientWidth - 12; // padding
        currentScale = Math.max(0.1, fitMode === 'page'
            ? Math.min(availableWidth / basePageWidth, (scrollContainer.clientHeight - 40) / basePageHeight)
            : availableWidth / basePageWidth);
        updateZoomLabel();
    }

    function updateZoomLabel() {
        if (zoomLevelEl) {
            zoomLevelEl.value = Math.round(currentScale * 100);
        }
    }

    async function renderAllPages() {
        if (!pdfDoc) return;
        if (rendering) {
            renderPending = true;
            return;
        }
        rendering = true;
        updateZoomLabel();

        const renderScale = currentScale;
        const fragment = document.createDocumentFragment();
        const dpr = window.devicePixelRatio || 1;

        for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
            if (renderScale !== currentScale) break;
            try {
                const page = await pdfDoc.getPage(pageNum);
                const viewport = page.getViewport({ scale: renderScale * dpr });
                const displayWidth = Math.floor(viewport.width / dpr);
                const displayHeight = Math.floor(viewport.height / dpr);

                // Page Card Wrapper
                const pageWrapper = document.createElement('div');
                pageWrapper.dataset.page = pageNum;
                pageWrapper.dataset.renderScale = renderScale;
                pageWrapper.className = 'pdf-page shrink-0 mx-auto relative flex justify-center items-center rounded-lg shadow-sm border border-gray-200/80 bg-white overflow-hidden select-none dark:border-gray-700';
                pageWrapper.style.width = `${displayWidth}px`;
                pageWrapper.style.height = `${displayHeight}px`;

                // Canvas element
                const canvas = document.createElement('canvas');
                canvas.width = Math.floor(viewport.width);
                canvas.height = Math.floor(viewport.height);
                canvas.style.width = `${displayWidth}px`;
                canvas.style.height = `${displayHeight}px`;
                canvas.className = 'block pointer-events-none select-none';

                const ctx = canvas.getContext('2d', { alpha: false });
                await page.render({
                    canvasContext: ctx,
                    viewport: viewport,
                }).promise;

                // Protective Transparent Overlay with subtle watermark
                const overlay = document.createElement('div');
                overlay.className = 'pointer-events-none absolute inset-0 select-none overflow-hidden flex flex-col justify-around';
                overlay.style.userSelect = 'none';

                // Create repeating watermark stripes
                overlay.innerHTML = `
                    <div class="w-full h-full flex flex-col justify-around py-6 opacity-[0.08] pointer-events-none select-none -rotate-12 transform scale-110">
                        <div class="text-center font-bold tracking-widest text-gray-900 text-sm sm:text-base whitespace-nowrap">${escapeHtml(watermarkText)}</div>
                        <div class="text-center font-bold tracking-widest text-gray-900 text-sm sm:text-base whitespace-nowrap">${escapeHtml(watermarkText)}</div>
                        <div class="text-center font-bold tracking-widest text-gray-900 text-sm sm:text-base whitespace-nowrap">${escapeHtml(watermarkText)}</div>
                    </div>
                `;

                // Page Number Tag
                const pageTag = document.createElement('div');
                pageTag.className = 'pointer-events-none absolute bottom-2 right-2 rounded bg-black/60 px-2 py-0.5 text-[10px] font-medium text-white/90 backdrop-blur-xs';
                pageTag.textContent = `${pageNum} / ${pdfDoc.numPages}`;

                pageWrapper.appendChild(canvas);
                pageWrapper.appendChild(overlay);
                pageWrapper.appendChild(pageTag);
                const textLayer = document.createElement('div');
                textLayer.className = 'textLayer';
                textLayer.style.setProperty('--scale-factor', renderScale);
                textLayer.style.setProperty('--total-scale-factor', renderScale);
                textLayer.style.setProperty('--scale-round-x', '1px');
                textLayer.style.setProperty('--scale-round-y', '1px');
                const displayViewport = page.getViewport({ scale: renderScale });
                const layer = new pdfjsLib.TextLayer({ textContentSource: await page.getTextContent(), container: textLayer, viewport: displayViewport });
                await layer.render();
                const highlightLayer = document.createElement('div');
                highlightLayer.className = 'pdf-highlights';
                pageWrapper.append(highlightLayer, textLayer);
                fragment.append(pageWrapper);
            } catch (err) {
                console.error(`Error rendering page ${pageNum}:`, err);
            }
        }

        if (renderScale === currentScale) {
            const top = scrollContainer.scrollTop, left = scrollContainer.scrollLeft;
            renderedPagesContainer.replaceChildren(fragment);
            highlights?.paint();
            // Preview already applied the zoom. Preserve its current location.
            scrollContainer.scrollTop = top;
            scrollContainer.scrollLeft = left;
        }
        rendering = false;
        if (renderPending || renderScale !== currentScale) {
            renderPending = false;
            renderAllPages();
        }
    }

    let zoomTimer;
    const setZoom = (value, clientX, clientY) => {
        if (!Number.isFinite(value)) { updateZoomLabel(); return; }
        fitMode = 'manual';
        const oldScale = currentScale;
        currentScale = Math.min(3, Math.max(.25, value));
        updateZoomLabel();
        const bounds = scrollContainer.getBoundingClientRect();
        const x = clientX == null ? scrollContainer.clientWidth / 2 : clientX - bounds.left;
        const y = clientY == null ? scrollContainer.clientHeight / 2 : clientY - bounds.top;
        const top = (scrollContainer.scrollTop + y) * currentScale / oldScale - y;
        const left = (scrollContainer.scrollLeft + x) * currentScale / oldScale - x;
        // Respond immediately using existing pixels/text/highlights; replace
        // with sharp canvases only after the gesture settles.
        renderedPagesContainer.querySelectorAll('.pdf-page').forEach(page => {
            page.style.zoom = currentScale / Number(page.dataset.renderScale || oldScale);
        });
        scrollContainer.scrollTop = top;
        scrollContainer.scrollLeft = left;
        clearTimeout(zoomTimer);
        zoomTimer = setTimeout(renderAllPages, 180);
    };
    zoomInBtn?.addEventListener('click', () => setZoom(currentScale + .15));
    zoomOutBtn?.addEventListener('click', () => setZoom(currentScale - .15));
    zoomLevelEl.addEventListener('change', () => setZoom(Number(zoomLevelEl.value) / 100));
    zoomLevelEl.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); setZoom(Number(zoomLevelEl.value) / 100); }
    });
    scrollContainer.addEventListener('wheel', event => {
        if (!event.ctrlKey) return;
        event.preventDefault();
        setZoom(currentScale * Math.exp(-event.deltaY * .008), event.clientX, event.clientY);
    }, { passive: false });

    fitWidthBtn?.addEventListener('click', () => {
        fitMode = 'width';
        calculateInitialScale();
        renderAllPages();
    });
    rootElement.querySelector('.pdf-fit-page')?.addEventListener('click', () => {
        fitMode = 'page';
        calculateInitialScale();
        renderAllPages();
    });

    // Resize handler with debounce
    let resizeTimeout = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            calculateInitialScale();
            renderAllPages();
        }, 300);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
