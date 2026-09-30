import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import assert from 'node:assert/strict';
const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || join(tmpdir(), 'inka-editor-ui-tools/node_modules/playwright-core'));
const html = execFileSync('php', ['-r', `
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
echo Illuminate\\Support\\Facades\\Blade::render('<main class="assessment-workspace"><div data-assessment-session-header style="position:sticky;top:12px;height:80px;background:white;z-index:30">Timer</div><form><x-forms.rich-text-editor name="response" :document="true" /></form></main>');
`], { encoding: 'utf8', env: { ...process.env, APP_ENV: 'testing', CACHE_STORE: 'array', SESSION_DRIVER: 'array' } });
const browser = await chromium.launch({ channel: 'msedge', headless: true });
try {
    const page = await browser.newPage({ viewport: { width: 1100, height: 900 } });
    const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
    await page.route('http://toolbar.test/**', route => {
        const path = new URL(route.request().url()).pathname;
        if (path === '/') return route.fulfill({ contentType: 'text/html', body: html });
        if (!/^\/public\/build\/assets\/[\w.-]+\.js$/.test(path)) return route.fulfill({ status: 404, body: '' });
        return route.fulfill({ contentType: 'text/javascript', body: readFileSync('.' + path) });
    });
    await page.goto('http://toolbar.test/');
    for (const entry of Object.values(manifest).filter(item => item.file.endsWith('.css'))) await page.addStyleTag({ path: 'public/build/' + entry.file });
    await page.evaluate(() => {
        document.querySelector('[data-editor-input]').value = '<p>Jawaban peserta yang panjang untuk pengujian toolbar.</p>'.repeat(100);
    });
    const editorEntry = Object.values(manifest).find(item => item.name === 'rich-text-editor').file;
    await page.evaluate(async entry => {
        const module = await import('/public/build/' + entry);
        module.initializeRichTextEditors();
        window.scrollTo(0, 900);
    }, editorEntry);
    const toolbar = page.locator('[data-answer-toolbar]');
    const assertFlush = async () => {
        const header = await page.locator('[data-assessment-session-header]').boundingBox();
        assert.ok(Math.abs((await toolbar.boundingBox()).y - header.y - header.height) < 1);
    };
    await assertFlush();
    await page.getByText('Tabel', { exact: false }).first().click();
    await page.locator('[data-editor-command="insertTable"]').waitFor({ state: 'visible' });
    await page.setViewportSize({ width: 390, height: 844 });
    await assertFlush();
    assert.ok((await toolbar.boundingBox()).width <= 390);
    const expandedHeight = (await toolbar.boundingBox()).height;
    await page.locator('.answer-toolbar-toggle').click();
    assert.ok((await toolbar.boundingBox()).height < expandedHeight);
    await assertFlush();
    assert.equal(await page.getByRole('button', { name: 'Fishbone', exact: true }).isVisible(), false);
    assert.equal(await page.locator('[data-editor-surface]').isVisible(), true);
    await page.locator('.answer-toolbar-toggle').click();
    assert.equal(await page.getByRole('button', { name: 'Fishbone', exact: true }).isVisible(), true);
    await page.locator('[data-assessment-session-header]').evaluate(node => node.style.height = '120px');
    await page.waitForFunction(() => document.querySelector('[data-rich-text-editor]').style.getPropertyValue('--answer-toolbar-top') === '132px');
    await assertFlush();
    console.log('PASS: actual Blade toolbar stays visible on desktop/mobile; table menu remains accessible');
} finally { await browser.close(); }
