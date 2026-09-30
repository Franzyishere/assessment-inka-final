import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import assert from 'node:assert/strict';
const require = createRequire(import.meta.url);
const { chromium } = require(join(tmpdir(), 'inka-editor-ui-tools/node_modules/playwright-core'));
const source = readFileSync('resources/views/pages/participant/simulations/material.blade.php', 'utf8');
// Exercise the actual Blade controls/grid, with lightweight stateful pane contents.
const section = source.slice(source.indexOf('<section x-show="currentPage'), source.indexOf('<div id="assessment-material-'))
    .replaceAll('{{ $currentNumber }}', '1');
const html = `<main x-data="{currentPage:1}">${section}<div id="material" x-show="!materialMinimized">Materi</div><form id="answer" x-show="!answerMinimized"><textarea>Jawaban tersimpan</textarea></form></div></section></main>`;
const browser = await chromium.launch({ channel: 'msedge', headless: true });
try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await page.setContent(html);
    const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
    for (const item of Object.values(manifest).filter(item => item.file.endsWith('.css'))) {
        await page.addStyleTag({ path: 'public/build/' + item.file });
    }
    await page.addScriptTag({ path: 'node_modules/alpinejs/dist/cdn.min.js' });
    const answer = page.locator('#answer');
    const initial = (await answer.boundingBox()).width;
    await page.getByRole('button', { name: 'Minimalkan materi', exact: true }).click();
    await page.locator('#material').waitFor({ state: 'hidden' });
    assert.equal(await page.locator('#material').isVisible(), false);
    assert.ok((await answer.boundingBox()).width > initial * 1.8);
    await page.getByRole('button', { name: 'Minimalkan jawaban', exact: true }).click();
    await answer.waitFor({ state: 'hidden' });
    assert.equal(await answer.isVisible(), false);
    assert.equal(await page.locator('#material').isVisible(), true);
    await page.getByRole('button', { name: 'Tampilkan jawaban', exact: true }).click();
    await answer.waitFor({ state: 'visible' });
    assert.equal(await page.locator('textarea').inputValue(), 'Jawaban tersimpan');
    assert.ok(Math.abs((await answer.boundingBox()).width - initial) < 2);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.getByRole('button', { name: 'Minimalkan materi', exact: true }).click();
    await page.locator('#material').waitFor({ state: 'hidden' });
    assert.equal(await answer.isVisible(), true);
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    console.log('PASS: material/answer minimize, full-width answer, mutual visibility, retained input, mobile layout');
} finally { await browser.close(); }
