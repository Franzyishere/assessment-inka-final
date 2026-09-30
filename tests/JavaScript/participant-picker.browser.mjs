// Uses in-memory view data only; no database or email access.
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
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
$participants = collect(range(1, 12))->map(fn ($id) => (object) ['id' => $id, 'name' => "Peserta $id", 'email' => "peserta$id@example.test"]);
echo view('pages.admin.assessment-programs._participant-picker', [
'participants' => $participants, 'selectedParticipantCategories' => [], 'selectedParticipantChoices' => [],
'assessmentCategories' => ['golongan' => 'Kenaikan Golongan'], 'simulationThreeOptions' => ['ci' => 'CI Panjang'],
'errors' => new Illuminate\\Support\\ViewErrorBag,
])->render();
`], { encoding: 'utf8', env: { ...process.env, APP_ENV: 'testing', SESSION_DRIVER: 'array', CACHE_STORE: 'array' } });
const browser = await chromium.launch({ channel: 'msedge', headless: true });
try {
    const page = await browser.newPage({ viewport: { width: 1100, height: 900 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setContent(`<form x-data="{ selectedParticipants: [], participantSearch: '', participantMatches(s) { return s.includes(this.participantSearch.toLowerCase()); } }"><input id="search" x-model="participantSearch">${html}</form>`);
    const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
    for (const entry of Object.values(manifest).filter(e => e.file.endsWith('.css'))) {
        await page.addStyleTag({ path: 'public/build/' + entry.file });
    }
    await page.addScriptTag({ path: 'node_modules/alpinejs/dist/cdn.min.js' });
    const row = page.locator('.participant-picker-row').first();
    await page.waitForFunction(() => document.querySelector('.participant-picker-row').style.display !== 'none');
    await page.locator('#participant-1').check();
    await page.locator('#participant-category-1').selectOption('golongan');
    await page.locator('#simulation-choice-1').selectOption('ci');
    await page.locator('#participant-2').check();
    await page.waitForFunction(() => document.querySelector('#participant-options-2').dataset.expanded === 'true');
    assert.equal(await page.locator('#participant-options-1').getAttribute('data-expanded'), 'false');
    await page.locator('#participant-name-1').click();
    await page.waitForFunction(() => document.querySelector('#participant-options-1').dataset.expanded === 'true');
    assert.equal(await page.locator('#participant-1').isChecked(), true, 'Clicking name must not deselect participant');
    await row.getByRole('button', { name: /Tutup/ }).click();
    await row.click({ position: { x: 3, y: 3 } });
    await page.waitForFunction(() => document.querySelector('#participant-options-1').dataset.expanded === 'true');
    assert.equal(await page.locator('#participant-1').isChecked(), true, 'Clicking card padding must only open details');
    await page.locator('#participant-1').uncheck();
    await page.waitForFunction(() => document.querySelector('#participant-options-1').dataset.expanded === 'false');
    assert.equal(await page.locator('#participant-1').isChecked(), false, 'Checkbox still allows deselection');
    await page.locator('#participant-1').check();
    assert.equal(await page.locator('#participant-1').isChecked(), true);
    assert.equal(await page.locator('#participant-category-1').inputValue(), 'golongan');
    assert.equal(await page.locator('#simulation-choice-1').inputValue(), 'ci');
    await row.getByRole('button', { name: /Tutup/ }).click();
    await row.getByRole('button', { name: /Atur/ }).click();
    await page.waitForFunction(() => document.querySelector('#participant-options-1').dataset.expanded === 'true');
    assert.equal(await page.locator('#participant-options-2').getAttribute('data-expanded'), 'false');
    await page.locator('#participant-2').uncheck();
    assert.equal(await page.locator('#participant-options-1').getAttribute('data-expanded'), 'true');
    assert.match(await row.innerText(), /Kenaikan Golongan · CI Panjang/);
    await row.getByRole('button', { name: /Tutup/ }).click();
    assert.equal(await page.locator('#participant-options-1').getAttribute('data-expanded'), 'false');
    await page.getByRole('button', { name: 'Berikutnya' }).click();
    await row.waitFor({ state: 'hidden' });
    assert.equal(await row.isVisible(), false);
    await page.locator('#participant-11').check();
    const data = await page.locator('form').evaluate(form => [...new FormData(form).entries()]);
    assert(data.some(([key, value]) => key === 'participant_ids[]' && value === '1'));
    assert(data.some(([key, value]) => key === 'participant_categories[1]' && value === 'golongan'));
    await page.getByRole('button', { name: /Belum lengkap/ }).click();
    await page.waitForFunction(() => [...document.querySelectorAll('.participant-picker-row')].filter(el => el.getClientRects().length).length === 1);
    assert.equal(await page.locator('.participant-picker-row:visible').count(), 1);
    await page.getByRole('button', { name: /Dipilih/ }).click();
    await page.waitForFunction(() => [...document.querySelectorAll('.participant-picker-row')].filter(el => el.getClientRects().length).length === 2);
    assert.equal(await page.locator('.participant-picker-row:visible').count(), 2);
    await page.locator('#search').fill('peserta11@');
    await page.waitForFunction(() => [...document.querySelectorAll('.participant-picker-row')].filter(el => el.getClientRects().length).length === 1);
    assert.equal(await page.locator('.participant-picker-row:visible').count(), 1);
    await page.setViewportSize({ width: 390, height: 844 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
    assert.deepEqual(errors, []);
    console.log('PASS: selection, expand/collapse, summaries, filters, pagination, hidden form values, mobile width');
} finally {
    await browser.close();
}
