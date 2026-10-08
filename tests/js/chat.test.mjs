import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const template = fs.readFileSync('resources/views/chat/index.blade.php', 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];
const message = (id, sender = 2) => ({ id, sender_id: sender, client_uuid: `message-${id}`, body: `Texto ${id}` });
const response = data => ({ ok: true, json: async () => data });

function setup(fetch, storage = { getItem() {}, setItem() {} }) {
    let poll;
    let cleared = false;
    const context = {
        fetch, localStorage: storage, window: {}, console: { error() {} },
        setInterval(callback) { poll = callback; return 1; },
        clearInterval() { cleared = true; },
    };
    vm.createContext(context);
    vm.runInContext(script, context);
    const state = context.chatInbox({
        authId: 1, authName: 'Comprador', activeConversationId: 1,
        initialMessages: [], messagesUrl: '/messages', sendUrl: '/send', readUrl: '/read', csrfToken: 'token',
    });
    state.$nextTick = callback => callback();
    state.$refs = {};
    return { state, poll: () => poll(), cleared: () => cleared };
}

test('sending persists a message, carries CSRF, and updates the displayed entry', async () => {
    const { state } = setup(async (url, options) => {
        assert.equal(url, '/send');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'token');
        const payload = JSON.parse(options.body);
        assert.equal(payload.body, 'Olá vendedor');
        return response({ message: { ...message(1, 1), ...payload } });
    });
    state.draftText = ' Olá vendedor ';
    await state.sendMessage();
    assert.equal(state.messages.length, 1);
    assert.equal(state.messages[0].status, 'sent');
    assert.equal(state.messages[0].id, 1);
    assert.equal(state.draftText, '');
    assert.equal(state.isSubmitting, false);
});

test('retry preserves the idempotency key after network failure', async () => {
    const payloads = [];
    const { state } = setup(async (_, options) => {
        const payload = JSON.parse(options.body);
        payloads.push(payload);
        if (payloads.length === 1) throw new Error('offline');
        return response({ message: { ...message(1, 1), ...payload } });
    });
    state.draftText = 'Olá';
    await state.sendMessage();
    assert.equal(state.messages[0].status, 'error');
    await state.retryMessage(state.messages[0]);
    assert.deepEqual(payloads[0], payloads[1]);
    assert.equal(state.messages.length, 1);
    assert.equal(state.messages[0].status, 'sent');
});

test('polling does not skip a seller reply when a newer outgoing message arrives first', async () => {
    const urls = [];
    const { state } = setup(async (url, options) => {
        urls.push(url);
        if (url === '/read') {
            assert.equal(JSON.parse(options.body).last_read_message_id, 3);
            return response({});
        }
        return response({ messages: [message(2), message(3, 1)] });
    });
    state.lastFetchedId = 1;
    state.messages = [message(1), message(3, 1)];
    await state.fetchIncrementalMessages();
    assert.equal(urls[0], '/messages?after_id=1');
    assert.deepEqual(Array.from(state.messages, m => m.id), [1, 2, 3]);
    assert.equal(state.lastFetchedId, 3);
});

test('storage restrictions do not disable chat initialization or sending', async () => {
    const { state, poll, cleared } = setup(async (_, options) => options?.method === 'POST'
        ? response({ message: { ...message(1, 1), ...JSON.parse(options.body) } })
        : response({ messages: [] }), {
        getItem() { throw new Error('Storage blocked'); },
        setItem() { throw new Error('Storage blocked'); },
    });
    state.init();
    state.draftText = 'Olá';
    await state.sendMessage();
    assert.equal(state.messages[0].status, 'sent');
    poll();
    state.destroy();
    assert.equal(cleared(), true);
});

test('sync reports an outage and recovers on the next successful poll', async () => {
    let online = false;
    const { state } = setup(async () => online ? response({ messages: [] }) : { ok: false, status: 503 });
    await state.fetchIncrementalMessages();
    assert.equal(state.connectionStatus, 'reconnecting');
    online = true;
    await state.fetchIncrementalMessages();
    assert.equal(state.connectionStatus, 'connected');
});

test('messages with the same client key from different participants are not merged', () => {
    const { state } = setup();
    state.handleIncomingMessage({ ...message(1, 1), client_uuid: 'shared' });
    state.handleIncomingMessage({ ...message(2, 2), client_uuid: 'shared' });
    assert.equal(state.messages.length, 2);
});

test('Shift+Enter and IME composition retain their native behavior', () => {
    const handler = template.match(/@keydown.enter="([^"]+)"/)[1];
    for (const event of [{ shiftKey: true }, { isComposing: true }, {}]) {
        let prevented = false;
        let sent = false;
        vm.runInNewContext(handler, {
            $event: { ...event, preventDefault() { prevented = true; } },
            sendMessage() { sent = true; },
        });
        assert.equal(sent, !event.shiftKey && !event.isComposing);
        assert.equal(prevented, sent);
    }
});
