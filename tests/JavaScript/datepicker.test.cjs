const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

let factory;
const context = { window: {}, flatpickr: () => {}, Alpine: { data: (_name, fn) => { factory = fn; } } };
vm.createContext(context);
const source = fs.readFileSync('resources/js/components/datepicker.js', 'utf8')
    .replace(/^import .*;\r?\n/gm, '').replace('export function', 'function');
vm.runInContext(source + '\nregisterDateTimePicker();', context);

for (const [input, expected] of [
    ['12:3', '12:03'], ['1:23', '01:23'], ['8:30', '08:30'], ['9:5', '09:05'],
    ['0830', '08:30'], ['830', '08:30'], ['23:59', '23:59'], ['00:00', '00:00'],
    ['12:', '12:00'], [':30', '00:30'], ['99:99', '23:59'], ['', ''],
]) {
    test(`normalizes ${JSON.stringify(input)} as ${JSON.stringify(expected)}`, () => {
        const picker = factory('2026-09-21', input);
        picker.normalizeTime();
        assert.equal(picker.time, expected);
        assert.equal(picker.combinedValue, `2026-09-21 ${expected || '08:00'}`);
    });
}
