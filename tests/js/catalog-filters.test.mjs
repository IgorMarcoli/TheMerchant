import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

function fixture() {
    const fields = Object.fromEntries(['busca', 'tipo', 'jogo', 'categoria', 'preco_min', 'preco_max', 'nota_min', 'ordem']
        .map(name => [name, {name, value: name === 'ordem' ? 'recentes' : ''}]));
    const elements = Object.assign(Object.values(fields), fields);
    const ranges = {min: {}, max: {}};
    const form = {elements, action: 'https://example.test/anuncios', reportValidity: () => true,
        querySelector: selector => ranges[selector.includes('"min"') ? 'min' : 'max']};
    const requests = [];
    let timer;
    const window = {location: {href: form.action}};
    const context = vm.createContext({
        window, URL, AbortController,
        setTimeout(callback, delay) { timer = {callback, delay}; return timer; },
        clearTimeout() { timer = null; },
        FormData: class {
            entries() { return Object.values(fields).map(field => [field.name, field.value]); }
            [Symbol.iterator]() { return this.entries()[Symbol.iterator](); }
        },
        fetch(url, options) { return new Promise((resolve, reject) => requests.push({url, options, resolve, reject})); },
        DOMParser: class { parseFromString(html) { return {querySelector: () => ({innerHTML: html})}; } },
        history: {pushState(_state, _title, url) { window.location.href = url.href; }},
    });
    vm.runInContext(readFileSync(new URL('../../public/js/catalog-filters.js', import.meta.url), 'utf8'), context);
    const component = window.catalogFilters();
    component.$refs = {form, results: {innerHTML: 'original', focus() {}}};
    component.init();
    const resolve = (index, html) => requests[index].resolve({ok: true, text: async () => html});
    return {component, fields, requests, resolve, window, get timer() {return timer;}};
}

test('debounce is 350ms and invalidates responses immediately while typing', async () => {
    const f = fixture();
    const pending = f.component.search();
    f.fields.busca.value = 'novo';
    f.component.changed({target: {dataset: {}}});
    assert.equal(f.timer.delay, 350);
    assert.equal(f.requests[0].options.signal.aborted, true);
    f.resolve(0, 'obsoleto');
    await pending;
    assert.equal(f.component.$refs.results.innerHTML, 'original');
    f.timer.callback();
    assert.equal(f.requests.length, 2);
    assert.equal(f.requests[1].url.searchParams.get('busca'), 'novo');
});

test('out-of-order responses cannot overwrite results or history', async () => {
    const f = fixture();
    const first = f.component.search();
    f.fields.busca.value = 'novo';
    const second = f.component.search();
    f.resolve(1, 'novo resultado');
    await second;
    f.resolve(0, 'resultado antigo');
    await first;
    assert.equal(f.component.$refs.results.innerHTML, 'novo resultado');
    assert.equal(new URL(f.window.location.href).searchParams.get('busca'), 'novo');
    assert.equal(f.component.loading, false);
});

test('errors keep results and URL, and retries recover', async () => {
    const f = fixture();
    f.fields.busca.value = 'falha';
    const pending = f.component.search();
    f.requests[0].reject(new Error('offline'));
    await pending;
    assert.match(f.component.error, /Não foi possível/);
    assert.equal(f.component.$refs.results.innerHTML, 'original');
    assert.equal(f.window.location.href, f.component.$refs.form.action);
    const retry = f.component.search();
    f.resolve(1, 'recuperado');
    await retry;
    assert.equal(f.component.error, '');
    assert.equal(f.component.$refs.results.innerHTML, 'recuperado');
});

test('removing tags and clearing resets filters and page', async () => {
    const f = fixture();
    f.window.location.href += '?busca=abc&page=2';
    f.fields.busca.value = 'abc';
    f.fields.preco_min.value = '0';
    f.component.sync();
    assert.equal(f.component.tags.length, 2);
    f.component.remove('busca');
    assert.equal(f.fields.busca.value, '');
    assert.equal(f.requests[0].url.searchParams.has('page'), false);
    assert.equal(f.requests[0].url.searchParams.get('preco_min'), '0');
    f.component.clear();
    assert.equal(f.component.tags.length, 0);
    assert.equal(f.requests[1].url.search, '');
});

test('back navigation restores fields, and price limits prevent invalid requests', async () => {
    const f = fixture();
    f.window.location.href += '?busca=voltar&nota_min=4&page=2';
    f.component.restore();
    assert.equal(f.fields.busca.value, 'voltar');
    assert.equal(f.fields.nota_min.value, '4');
    assert.equal(f.requests[0].url.searchParams.get('page'), '2');
    f.fields.preco_min.value = '100';
    f.fields.preco_max.value = '50';
    await f.component.search();
    assert.equal(f.requests.length, 1);
    assert.match(f.component.error, /preço máximo/);
});
