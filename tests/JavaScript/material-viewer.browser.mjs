import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { resolve, extname, join, sep } from 'node:path';
import { tmpdir } from 'node:os';
import { createRequire } from 'node:module';
import assert from 'node:assert/strict';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || join(tmpdir(), 'inka-editor-ui-tools/node_modules/playwright-core'));
const browser = await chromium.launch({ channel: 'msedge', headless: true });
const producer = await browser.newPage();
await producer.setContent('<html><body><p style="font:24px Arial">Materi assessment untuk pengujian stabilo.</p></body></html>');
const pdf = await producer.pdf({ format: 'A4' }); await producer.close();
const root = process.cwd();
const manifest = JSON.parse(await readFile('public/build/manifest.json', 'utf8'));
const entry = Object.values(manifest).find(e => e.name === 'secure-pdf-viewer').file;
const css = Object.values(manifest).filter(e => e.file.endsWith('.css')).map(e => `<link rel="stylesheet" href="/public/build/${e.file}">`).join('');
let highlights = [];
const server = createServer(async (req, res) => {
    if (req.url === '/favicon.ico') { res.writeHead(204); res.end(); return; }
    if (req.url === '/sample.pdf') { res.setHeader('Content-Type', 'application/pdf'); res.end(pdf); return; }
    if (req.url === '/highlights') {
        if (req.method === 'PUT') {
            let body = ''; for await (const chunk of req) body += chunk;
            highlights = JSON.parse(body).highlights;
        }
        res.setHeader('Content-Type', 'application/json'); res.end(JSON.stringify({ highlights, saved: true })); return;
    }
    if (req.url === '/') {
        res.setHeader('Content-Type', 'text/html');
        res.end(`<html><head>${css}</head><body><div data-secure-pdf-viewer data-pdf-url="/sample.pdf" data-highlights-url="/highlights" style="width:800px;height:700px"></div><script type="module">import {initializeSecurePdfViewers} from '/public/build/${entry}'; initializeSecurePdfViewers();</script></body></html>`); return;
    }
    const path = resolve(root, '.' + (req.url.startsWith('/build/') ? '/public' : '') + req.url.split('?')[0]);
    if (!path.startsWith(root + sep)) { res.writeHead(403); res.end(); return; }
    try { res.setHeader('Content-Type', ['.js', '.mjs'].includes(extname(path)) ? 'text/javascript' : extname(path) === '.css' ? 'text/css' : 'application/octet-stream'); res.end(await readFile(path)); }
    catch { res.writeHead(404); res.end(); }
});
await new Promise(done => server.listen(0, '127.0.0.1', done));
try {
    const page = await browser.newPage({ viewport: { width: 1200, height: 900 } });
    const errors = []; page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => { if (message.type() === 'error') console.error(message.text()); });
    await page.goto(`http://127.0.0.1:${server.address().port}/`);
    await page.locator('.textLayer span').first().waitFor({ state: 'attached' });
    await page.getByRole('button', { name: 'Stabilo', exact: true }).click();
    const span = page.locator('.textLayer span').first();
    const bounds = await span.boundingBox();
    assert.ok(bounds.width > 100 && bounds.height > 10, 'PDF selectable text must match visible text');
    await page.mouse.move(bounds.x + bounds.width * .2, bounds.y + bounds.height / 2);
    await page.mouse.down();
    await page.mouse.move(bounds.x + bounds.width * .5, bounds.y + bounds.height / 2, { steps: 10 });
    await page.mouse.up();
    await page.waitForFunction(() => document.body.textContent.includes('Highlight tersimpan'));
    assert.ok(highlights.length > 0);
    const pageWidth = (await page.locator('.pdf-page').boundingBox()).width;
    assert.ok(highlights.every(mark => mark.width * pageWidth < bounds.width * .5), 'Partial selection must not highlight the entire line');
    const mark = { ...highlights[0] };
    await page.reload();
    await page.locator('.pdf-highlight').first().waitFor({ state: 'attached' });
    const zoom = page.getByRole('spinbutton', { name: 'Zoom materi dalam persen' });
    await zoom.fill('150'); await zoom.press('Enter');
    await page.waitForFunction(() => document.querySelector('.textLayer').style.getPropertyValue('--total-scale-factor') === '1.5');
    assert.deepEqual(highlights[0], mark);
    const instantZoom = await page.locator('.pdf-pages-scroll-container').evaluate(node => {
        const pdfPage = node.querySelector('.pdf-page');
        const before = pdfPage.getBoundingClientRect().width;
        node.dispatchEvent(new WheelEvent('wheel', { ctrlKey: true, deltaY: -30, clientX: 400, clientY: 300, bubbles: true, cancelable: true }));
        return pdfPage.getBoundingClientRect().width > before;
    });
    assert.equal(instantZoom, true, 'Zoom must resize immediately, before canvas rerender');
    await page.waitForFunction(() => Number(document.querySelector('.pdf-zoom-level').value) > 150);
    await page.getByRole('button', { name: 'Penghapus', exact: true }).click();
    // Zoom may rerender asynchronously; erase the current highlight after rendering.
    await page.waitForTimeout(400);
    await page.locator('.pdf-highlight').first().scrollIntoViewIfNeeded();
    const eraseBox = await page.locator('.pdf-highlight').first().boundingBox();
    await page.mouse.move(Math.max(20, eraseBox.x + 3), eraseBox.y + eraseBox.height / 2);
    await page.mouse.down();
    await page.mouse.move(eraseBox.x + eraseBox.width + 10, eraseBox.y + eraseBox.height / 2, { steps: 8 });
    await page.mouse.up();
    await page.waitForFunction(() => document.body.textContent.includes('Highlight tersimpan'));
    assert.equal(highlights.length, 0);
    // Exercise area highlighting for image-only pages with no text layer.
    await page.getByRole('button', { name: 'Stabilo', exact: true }).click();
    await page.locator('.pdf-pages-scroll-container').evaluate(node => node.scrollTo(0, 0));
    await page.locator('.textLayer').evaluate(node => node.replaceChildren());
    const scan = await page.locator('.pdf-page').boundingBox();
    await page.mouse.move(scan.x + 40, scan.y + 100); await page.mouse.down();
    await page.mouse.move(scan.x + 230, scan.y + 135, { steps: 5 }); await page.mouse.up();
    await page.waitForFunction(() => document.body.textContent.includes('Highlight tersimpan'));
    assert.equal(highlights.length, 1);
    assert.ok(highlights[0].width > .1);
    const materialView = await readFile('resources/views/pages/participant/simulations/material.blade.php', 'utf8');
    const overlay = materialView.match(/<template x-teleport="body">[\s\S]*?<\/template>/)[0];
    await page.evaluate(overlay => {
        const host = document.createElement('main');
        host.style.cssText = 'transform:translateX(20px);height:2000px';
        host.setAttribute('x-data', "{ secureMode: false, violations: 0, securityMessage: 'Test', fullscreenError: '', enableFullscreen() { this.secureMode = true } }");
        host.innerHTML = overlay + '<section :inert="!secureMode"><input id="blocked-input"></section>';
        document.body.append(host);
    }, overlay);
    await page.addScriptTag({ path: 'node_modules/alpinejs/dist/cdn.min.js' });
    await page.locator('.assessment-lock').waitFor();
    const lock = await page.locator('.assessment-lock').boundingBox();
    assert.equal(lock.x, 0); assert.equal(lock.y, 0);
    assert.equal(lock.width, 1200); assert.equal(lock.height, 900);
    assert.equal(await page.locator('#blocked-input').evaluate(node => node.closest('section').inert), true);
    assert.equal(await page.evaluate(() => getComputedStyle(document.documentElement).overflow), 'hidden');
    await page.getByRole('button', { name: 'Kembali ke Fullscreen & Lanjutkan' }).click();
    await page.locator('.assessment-lock').waitFor({ state: 'hidden' });
    assert.equal(await page.locator('#blocked-input').evaluate(node => node.closest('section').inert), false);
    assert.deepEqual(errors, []);
    console.log('PASS: PDF highlights/save/reload/zoom/delete; full-viewport lock, inert content and unlock');
} finally { await browser.close(); server.close(); }
