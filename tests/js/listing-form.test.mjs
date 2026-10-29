import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

function setup() {
    const revoked = [];
    const context = {
        window: {},
        URL: { createObjectURL: file => `blob:${file.name}`, revokeObjectURL: url => revoked.push(url) },
        DataTransfer: class {
            files = [];
            items = { add: file => this.files.push(file) };
        },
    };
    vm.createContext(context);
    vm.runInContext(fs.readFileSync('public/js/listing-form.js', 'utf8'), context);
    const state = context.window.listingForm({
        gameId: '1', categoryId: '1', primary: 'existing:10',
        categories: [
            { id: 1, game_id: 1, type: 'cosmetic' },
            { id: 2, game_id: 2, type: 'service' },
            { id: 3, game_id: null, type: 'service' },
        ],
        existing: [{ id: 10, token: 'existing:10', url: '/original.png' }],
    });
    state.$refs = { files: { files: [] } };
    state.init();
    return { state, revoked };
}

const photo = (name, type = 'image/png', size = 100) => ({ name, type, size });

test('categories follow the game, allow global categories, and expose coaching fields', () => {
    const { state } = setup();
    assert.equal(state.compatibleCategories.length, 2);
    assert.equal(state.isService, false);
    state.categoryId = '3';
    assert.equal(state.isService, true);
    state.gameId = '2';
    assert.equal(state.compatibleCategories.some(category => category.id === 1), false);
});

test('six-image limit includes retained photos and recovers after removal', () => {
    const { state } = setup();
    state.selectFiles({ files: Array.from({ length: 6 }, (_, i) => photo(`${i}.png`)) });
    assert.equal(state.photos.length, 7);
    assert.match(state.photoError, /6 fotos/);
    state.removePhoto('existing:10');
    assert.equal(state.photoError, '');
    assert.equal(state.primary, 'new:0');
});

test('new-photo removal updates the actual upload and preserves cover identity', () => {
    const { state } = setup();
    state.selectFiles({ files: [photo('a.png'), photo('b.png'), photo('c.png')] });
    state.primary = 'new:2';
    state.removePhoto('new:0');
    assert.equal(state.$refs.files.files.length, 2);
    assert.equal(state.$refs.files.files[0].name, 'b.png');
    assert.equal(state.primary, 'new:1');
    state.removePhoto('new:1');
    assert.equal(state.primary, 'existing:10');
});

test('invalid MIME and size block submission and replacing files releases previews', () => {
    const { state, revoked } = setup();
    state.selectFiles({ files: [photo('bad.svg', 'image/svg+xml')] });
    assert.match(state.photoError, /JPG/);
    state.selectFiles({ files: [photo('big.png', 'image/png', 4096 * 1024 + 1)] });
    assert.match(state.photoError, /4 MB/);
    state.selectFiles({ files: [photo('valid.png')] });
    assert.equal(state.photoError, '');
    assert.ok(revoked.includes('blob:bad.svg'));
    state.destroy();
    assert.ok(revoked.includes('blob:valid.png'));
});
