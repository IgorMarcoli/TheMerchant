import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

function setup(fetch) {
    let factory;
    vm.runInNewContext(fs.readFileSync('public/js/review-form.js', 'utf8'), {
        document: { addEventListener: (_, callback) => callback() },
        Alpine: { data: (_, callback) => { factory = callback; } },
        fetch, FormData: class {}, AbortController, setTimeout, clearTimeout,
    });
    const state = factory();
    state.$nextTick = callback => callback();
    state.$refs = { confirmation: { focus() {} }, error: { focus() {} } };
    state.rating = '4';
    state.comment = 'Meu comentário';
    const form = { action: '/pedidos/1/avaliar/1', reportValidity: () => true };
    return { state, form };
}

test('success renders the persisted response and prevents a second submission', async () => {
    let count = 0;
    const { state, form } = setup(async () => {
        count++;
        return { ok: true, status: 201, json: async () => ({ review: { rating: 4, comment: 'Salvo' }, message: 'Publicado' }) };
    });
    await state.submit(form);
    await state.submit(form);
    assert.equal(count, 1);
    assert.equal(state.saved.comment, 'Salvo');
    assert.equal(state.toast, 'Publicado');
    assert.equal(state.sending, false);
});

test('pending request blocks duplicate clicks until it completes', async () => {
    let resolve;
    let count = 0;
    const { state, form } = setup(() => {
        count++;
        return new Promise(done => { resolve = done; });
    });
    const pending = state.submit(form);
    assert.equal(state.sending, true);
    await state.submit(form);
    assert.equal(count, 1);
    resolve({ ok: false, status: 503 });
    await pending;
    assert.equal(state.sending, false);
    assert.equal(state.saved, null);
});

test('validation errors retain draft and field-specific feedback', async () => {
    const { state, form } = setup(async () => ({ status: 422, json: async () => ({ errors: { rating: ['Item já avaliado'] } }) }));
    await state.submit(form);
    assert.equal(state.errors.rating[0], 'Item já avaliado');
    assert.equal(state.comment, 'Meu comentário');
    assert.equal(state.rating, '4');
    assert.equal(state.saved, null);
});

test('network failure keeps draft and permits retry', async () => {
    let count = 0;
    const { state, form } = setup(async () => { count++; throw new Error('offline'); });
    await state.submit(form);
    assert.match(state.error, /conexão/);
    assert.equal(state.comment, 'Meu comentário');
    await state.submit(form);
    assert.equal(count, 2);
    assert.equal(state.sending, false);
});

test('session, authorization and server failures never show success', async () => {
    for (const status of [401, 403, 419, 500]) {
        const { state, form } = setup(async () => ({ ok: false, status }));
        await state.submit(form);
        assert.ok(state.error);
        assert.equal(state.saved, null);
        assert.equal(state.toast, '');
        assert.equal(state.comment, 'Meu comentário');
    }
});

test('invalid form does not send and different items keep independent state', async () => {
    let count = 0;
    const first = setup(async () => { count++; });
    const second = setup(async () => { count++; });
    await first.state.submit({ ...first.form, reportValidity: () => false });
    first.state.errors.rating = ['Erro'];
    assert.equal(count, 0);
    assert.equal(second.state.errors.rating, undefined);
});
