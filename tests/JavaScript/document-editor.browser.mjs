// Run after npm run build. PLAYWRIGHT_MODULE can point to an external playwright-core install.
import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { resolve, extname, join, sep } from 'node:path';
import { tmpdir } from 'node:os';
import { createRequire } from 'node:module';
import assert from 'node:assert/strict';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || join(tmpdir(), 'inka-editor-ui-tools/node_modules/playwright-core'));
const root = process.cwd();
const manifest = JSON.parse(await readFile('public/build/manifest.json', 'utf8'));
const entry = Object.values(manifest).find(e => e.name === 'rich-text-editor').file;
const css = Object.values(manifest).filter(e => e.file?.endsWith('.css')).map(e => `<link rel="stylesheet" href="/public/build/${e.file}">`).join('');
const server = createServer(async (req, res) => {
    if (req.url === '/' || req.url === '/?draft') {
        res.setHeader('Content-Type', 'text/html');
        res.end(`<!doctype html><html><head>${css}</head><body><form><div data-rich-text-editor data-required="true" style="width:900px;margin:20px auto"><textarea data-editor-input name="response" hidden><p>Analisis awal</p></textarea><textarea data-legacy-diagram hidden>[]</textarea><button type="button" data-insert-shape="fishbone">Fishbone</button><button type="button" data-insert-shape="text">Textbox</button><button type="button" data-editor-command="insertTable">Tabel</button><div data-shape-tools></div><button type="button" data-editor-command="undo">Undo</button><button type="button" data-editor-command="redo">Redo</button><div data-editor-surface></div><p data-editor-error hidden></p></div><button type="button" id="save">Simpan</button></form><script type="module">import { initializeRichTextEditors } from '/public/build/${entry}'; initializeRichTextEditors();window.ready=true;</script></body></html>`.replace('<form>', req.url === '/?draft' ? '<form action="/draft-endpoint" method="post">' : '<form>').replace('data-required="true"', req.url === '/?draft' ? 'data-required="true" data-draft-key="browser-test-draft"' : 'data-required="true"'));
        return;
    }
    const path = resolve(root, '.' + decodeURIComponent(req.url.split('?')[0]));
    if (!path.startsWith(root + sep)) { res.writeHead(403); res.end(); return; }
    try { res.setHeader('Content-Type', extname(path) === '.js' ? 'text/javascript' : extname(path) === '.css' ? 'text/css' : 'text/plain'); res.end(await readFile(path)); }
    catch { res.writeHead(404); res.end(); }
});
await new Promise(r => server.listen(0, '127.0.0.1', r));
const browser = await chromium.launch({ channel: process.env.BROWSER_CHANNEL || 'msedge', headless: true });
try {
    const page = await browser.newPage({ viewport: { width: 1280, height: 1000 } });
    const errors = []; page.on('pageerror', e => errors.push(e.message));
    await page.goto(`http://127.0.0.1:${server.address().port}`);
    await page.waitForFunction(() => window.ready);
    await page.locator('.ProseMirror p').first().click();
    await page.keyboard.press('End');
    for (let i = 0; i < 6; i++) {
        await page.keyboard.press('Enter');
        await page.keyboard.insertText('Paragraf jawaban peserta sebelum diagram ditambahkan.');
    }
    await page.locator('[data-insert-shape="fishbone"]').click();
    assert.equal(await page.locator('.document-shapes').count(), 1);
    assert.equal(await page.locator('.document-shapes text').allTextContents().then(values => values.join('')), '');
    assert.equal(await page.locator('.document-shapes').evaluate(node => getComputedStyle(node).position), 'absolute');
    assert.equal(await page.locator('.document-shapes .document-shapes-tools').count(), 0);
    const readScene = () => page.locator('[data-editor-input]').evaluate(input => {
        const doc = new DOMParser().parseFromString(input.value, 'text/html');
        return JSON.parse(doc.querySelector('[data-answer-scene]').dataset.answerScene);
    });
    const textBottom = await page.locator('.ProseMirror p').evaluateAll(nodes => Math.max(...nodes.filter(node => node.textContent.trim()).map(node => node.getBoundingClientRect().bottom)));
    const shapesTop = await page.locator('.document-shapes [data-shape]').evaluateAll(nodes => Math.min(...nodes.map(node => node.getBoundingClientRect().top)));
    assert.ok(shapesTop > textBottom, `Fishbone top ${shapesTop} must start below existing answer text ${textBottom}`);
    const beforeGroup = await readScene();
    await page.locator('[data-tool="group"]').click();
    await page.locator('.document-shapes [data-shape="0"]').scrollIntoViewIfNeeded();
    const groupLine = await page.locator('.document-shapes [data-shape="0"]').boundingBox();
    await page.mouse.move(groupLine.x + 80, groupLine.y + groupLine.height / 2);
    await page.mouse.down(); await page.mouse.move(groupLine.x + 70, groupLine.y + groupLine.height / 2 + 40, { steps: 4 }); await page.mouse.up();
    const afterGroup = await readScene();
    const delta = afterGroup[0].points[0].map((value, i) => value - beforeGroup[0].points[0][i]);
    assert.ok(delta[1] > 0);
    afterGroup.forEach((shape, i) => shape.points.forEach((point, j) => {
        assert.deepEqual(point.map((value, k) => value - beforeGroup[i].points[j][k]), delta);
    }));
    await page.locator('[data-tool="select"]').click();
    const box = page.locator('.document-shapes [data-shape="1"]');
    const initialHeight = Number(await box.locator('rect').first().getAttribute('height'));
    await box.dblclick();
    await page.locator('.document-shapes textarea').fill('Kalimat panjang untuk menjelaskan akar permasalahan produksi. '.repeat(8));
    await page.locator('#save').click();
    assert.equal(await box.locator('text').getAttribute('font-size'), '18');
    assert.ok(Number(await box.locator('rect').first().getAttribute('height')) > initialHeight);
    await box.dblclick();
    await page.locator('.document-shapes textarea').fill('Masalah produksi');
    await page.locator('#save').click();
    let html = await page.locator('[data-editor-input]').inputValue();
    assert.ok(html.includes('Masalah produksi') && html.includes('data-answer-scene'));
    assert.ok(html.includes('Analisis awal'));
    const shape = await box.boundingBox();
    await page.mouse.move(shape.x + 15, shape.y + 15); await page.mouse.down(); await page.mouse.move(shape.x - 30, shape.y + 35, { steps: 4 }); await page.mouse.up();
    const moved = await page.locator('[data-editor-input]').inputValue();
    assert.notEqual(moved, html);
    await page.locator('[data-editor-command="undo"]').click();
    await page.locator('[data-editor-command="redo"]').click();
    assert.equal(await page.locator('[data-editor-input]').inputValue(), moved);
    const handle = page.locator('[data-resize]').first();
    // Select text again after undo/redo, then resize it.
    await box.click();
    const hb = await handle.boundingBox();
    await page.mouse.move(hb.x + 4, hb.y + 4); await page.mouse.down(); await page.mouse.move(hb.x - 25, hb.y + 25, { steps: 4 }); await page.mouse.up();
    assert.notEqual(await page.locator('[data-editor-input]').inputValue(), moved);

    await page.locator('.ProseMirror p').first().click();
    await page.keyboard.press('End');
    await page.keyboard.type(' dan kesimpulan');
    assert.ok((await page.locator('[data-editor-input]').inputValue()).includes('dan kesimpulan'));
    // Paste before the fishbone must move the entire in-flow block down.
    const first = page.locator('.document-flow-shapes').first();
    const beforePaste = await first.evaluate(node => node.getBoundingClientRect().top + window.scrollY);
    await page.locator('.ProseMirror p').first().click();
    await page.keyboard.press('End');
    await page.locator('.ProseMirror').evaluate(node => {
        const data = new DataTransfer(); data.setData('text/plain', 'Teks tambahan sebelum diagram. '.repeat(100));
        node.dispatchEvent(new ClipboardEvent('paste', { clipboardData: data, bubbles: true, cancelable: true }));
    });
    const afterPaste = await first.evaluate(node => node.getBoundingClientRect().top + window.scrollY);
    assert.ok(afterPaste > beforePaste + 100, 'Pasted text must push the diagram down');
    await page.locator('.ProseMirror > p').last().click();
    await page.keyboard.type('Teks setelah fishbone');
    const afterText = await page.locator('.ProseMirror > p').last().boundingBox();
    const flowBox = await first.boundingBox();
    assert.ok(afterText.y >= flowBox.y + flowBox.height, 'Text after fishbone must not overlap');
    await page.locator('[data-editor-command="insertTable"]').click();
    assert.ok((await page.locator('.ProseMirror table').boundingBox()).y > (await first.boundingBox()).y + (await first.boundingBox()).height);
    // Select directly by dragging blank area, then delete via keyboard.
    await first.scrollIntoViewIfNeeded();
    await page.locator('[data-tool="select"]').click();
    await first.scrollIntoViewIfNeeded();
    const area = await page.locator('.document-shapes [data-shape="1"]').boundingBox();
    await page.mouse.move(area.x - 8, area.y - 8); await page.mouse.down();
    await page.mouse.move(area.x + area.width + 8, area.y + area.height + 8, { steps: 5 }); await page.mouse.up();
    assert.ok(await page.locator('.document-shapes [data-shape][stroke="#b91c1c"]').count() > 0);
    const count = (await readScene()).length;
    await page.keyboard.press('Delete');
    assert.ok((await readScene()).length < count);
    // Keyboard undo keeps the viewport steady.
    const scrollBefore = await page.evaluate(() => window.scrollY);
    await page.keyboard.press('Control+z');
    await page.waitForTimeout(100);
    assert.equal((await readScene()).length, count, 'Undo must restore deleted objects');
    assert.ok(Math.abs(await page.evaluate(() => window.scrollY) - scrollBefore) < 2);
    await page.locator('.ProseMirror p').first().click();
    assert.equal(await page.locator('.document-shapes [data-shape][stroke="#b91c1c"]').count(), 0);
    const saved = await page.locator('[data-editor-input]').inputValue();
    assert.ok(saved.includes('data-answer-flow="true"'));
    const previewEntry = Object.values(manifest).find(e => e.name === 'document-shapes').file;
    await page.evaluate(async ({ saved, previewEntry }) => {
        const preview = document.createElement('article'); preview.id = 'preview'; preview.innerHTML = saved;
        document.body.append(preview);
        const module = await import('/public/build/' + previewEntry); module.initializeDocumentPreviews();
    }, { saved, previewEntry });
    assert.equal(await page.locator('#preview svg').count(), 1);
    assert.deepEqual(errors, []);
    const draftPage = await browser.newPage();
    let offline = true, draftPayload = '';
    await draftPage.route('**/draft-endpoint', route => {
        draftPayload = route.request().postData() || '';
        return route.fulfill({ status: offline ? 503 : 200, contentType: 'application/json', body: JSON.stringify(offline ? { message: 'offline' } : { draft_saved: true }) });
    });
    await draftPage.goto(`http://127.0.0.1:${server.address().port}/?draft`);
    await draftPage.waitForFunction(() => window.ready);
    await draftPage.locator('.ProseMirror').fill('Draft tetap ada setelah refresh');
    await draftPage.reload();
    await draftPage.waitForFunction(() => window.ready);
    assert.ok((await draftPage.locator('[data-editor-input]').inputValue()).includes('Draft tetap ada setelah refresh'));
    await draftPage.waitForFunction(() => document.body.textContent.includes('Draft belum tersimpan di server'));
    offline = false;
    await draftPage.evaluate(() => window.dispatchEvent(new Event('online')));
    await draftPage.waitForFunction(() => document.body.textContent.includes('Draft tersimpan otomatis.'));
    assert.ok(draftPayload.includes('draft_only') && draftPayload.includes('Draft tetap ada setelah refresh'));
    await draftPage.close();
    console.log('PASS: embedded fishbone, inline textbox, move, resize, undo/redo, reload and readonly preview; no browser errors.');
} finally { await browser.close(); server.close(); }
