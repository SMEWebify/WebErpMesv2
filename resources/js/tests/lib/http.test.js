import { describe, it, expect, vi, afterEach } from 'vitest';
import {
    csrfToken, jsonHeaders, apiRequest, apiFetch, apiFetchWithStatus, apiFetchOrThrow,
} from '../../lib/http';

// Copies des `apiFetch` locales que lib/http.js remplace, gardées ici pour
// prouver que la version partagée se comporte exactement pareil.
const legacy = {
    // QuotesIndex, CompaniesIndex, ProductsIndex, InvoicesIndex…
    async apiFetch(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'Accept':       'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                ...options.headers,
            },
            ...options,
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({ message: res.statusText }));
            throw err;
        }
        return res.json();
    },
    // DeliverysRequest, InvoicesRequest, PurchasesRequest, PurchasesQuotation*
    async apiFetchWithStatus(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     csrfToken(),
                'Content-Type':     'application/json',
                ...options.headers,
            },
            ...options,
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw { status: res.status, data };
        }
        return res.json();
    },
    // LeadsIndex, OpportunitiesIndex, OrdersIndex, PurchasesIndex
    async apiFetchOrThrow(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'Accept':       'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                ...options.headers,
            },
            ...options,
        });
        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            const err  = new Error(body.message || `HTTP ${res.status}`);
            err.errors = body.errors ?? {};
            err.status = res.status;
            throw err;
        }
        return res.json();
    },
    // QuoteLinesPage, OrderLinesPage, PurchaseLinesPage, PurchaseReceiptLinesPage
    apiRequest(url, options = {}) {
        return fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                ...(options.headers ?? {}),
            },
            ...options,
        });
    },
};

const RESPONSES = {
    ok:        () => new Response(JSON.stringify({ id: 7, name: 'Devis' }), { status: 200, statusText: 'OK' }),
    created:   () => new Response(JSON.stringify({ id: 8 }), { status: 201, statusText: 'Created' }),
    invalid:   () => new Response(JSON.stringify({ message: 'Champ requis', errors: { label: ['Requis'] } }), { status: 422, statusText: 'Unprocessable Content' }),
    noMessage: () => new Response(JSON.stringify({ foo: 'bar' }), { status: 403, statusText: 'Forbidden' }),
    html500:   () => new Response('<html>Server Error</html>', { status: 500, statusText: 'Internal Server Error' }),
    expired:   () => new Response('<html>Page Expired</html>', { status: 419, statusText: 'unknown status' }),
    emptyOk:   () => new Response('', { status: 200, statusText: 'OK' }),
};

const CALLS = [
    ['GET', undefined],
    ['POST', { method: 'POST', body: JSON.stringify({ a: 1 }) }],
    ['DELETE', { method: 'DELETE' }],
];

async function outcome(fn, url, options) {
    try {
        return { resolved: await fn(url, options) };
    } catch (e) {
        return {
            rejected:   { ...e, message: e?.message },
            isError:    e instanceof Error,
            errorClass: e?.constructor?.name,
        };
    }
}

function stubFetch(makeResponse) {
    const spy = vi.fn(() => Promise.resolve(makeResponse()));
    vi.stubGlobal('fetch', spy);
    return spy;
}

afterEach(() => vi.unstubAllGlobals());

describe('csrfToken / jsonHeaders', () => {
    it('lit le jeton de la balise meta', () => {
        expect(csrfToken()).toBe('test-csrf-token');
    });

    it('renvoie une chaîne vide sans balise meta', () => {
        const head = document.head.innerHTML;
        document.head.innerHTML = '';
        expect(csrfToken()).toBe('');
        document.head.innerHTML = head;
    });

    it('fusionne les en-têtes supplémentaires sans perdre le jeton', () => {
        expect(jsonHeaders({ 'X-Foo': '1' })).toEqual({
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': 'test-csrf-token',
            'X-Foo': '1',
        });
    });
});

describe.each([
    ['apiFetch',           apiFetch,           legacy.apiFetch],
    ['apiFetchWithStatus', apiFetchWithStatus, legacy.apiFetchWithStatus],
    ['apiFetchOrThrow',    apiFetchOrThrow,    legacy.apiFetchOrThrow],
])('%s se comporte comme la copie locale qu’il remplace', (_name, shared, old) => {
    for (const [scenario, makeResponse] of Object.entries(RESPONSES)) {
        for (const [method, options] of CALLS) {
            it(`${method} — ${scenario}`, async () => {
                const spyOld = stubFetch(makeResponse);
                const before = await outcome(old, '/x', options);
                const spyNew = stubFetch(makeResponse);
                const after  = await outcome(shared, '/x', options);

                expect(after).toEqual(before);
                expect(spyNew.mock.calls).toEqual(spyOld.mock.calls);
            });
        }
    }
});

describe('apiRequest se comporte comme la copie locale des pages de lignes', () => {
    for (const [method, options] of CALLS) {
        it(`${method} — même requête, même réponse brute`, async () => {
            const spyOld = stubFetch(RESPONSES.invalid);
            const before = await legacy.apiRequest('/x', options);
            const spyNew = stubFetch(RESPONSES.invalid);
            const after  = await apiRequest('/x', options);

            expect(spyNew.mock.calls).toEqual(spyOld.mock.calls);
            expect(after.status).toBe(before.status);
            expect(await after.json()).toEqual(await before.json());
        });
    }
});
