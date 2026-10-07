import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import QuoteLinesPage from '../../components/QuoteLinesPage.jsx';

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const ENDPOINTS = {
    lines:        '/fr/quotes/1/lines/json',
    selectData:   '/fr/quotes/1/lines/json/select-data',
    store:        '/fr/quotes/1/lines/json/store',
    update:       '/fr/quotes/1/lines/json/__ID__',
    destroy:      '/fr/quotes/1/lines/json/__ID__',
    reorder:      '/fr/quotes/1/lines/json/reorder',
    presentation: '/fr/quotes/1/lines/json/__ID__/presentation',
    storeOrder:   '/fr/quotes/1/lines/json/store-order',
};

const line = (over) => ({
    quotes_id: 1, code: '', product_id: null, discount: 0, statu: 1,
    hide_on_pdf: false, pdf_package: 0, line_type: 'article', task_count: 0,
    detail_url: '#', task_url: '#', unit_label: 'U', vat_label: '20', formatted_price: '',
    ...over,
});

const LINES = [
    line({ id: 1, ordre: 1, line_type: 'section', label: 'Garde-corps', qty: 0, effective_price: 0 }),
    line({ id: 2, ordre: 2, label: 'Garde-corps acier', qty: 12, effective_price: 85 }),
    line({ id: 3, ordre: 3, label: 'Pose', qty: 1, effective_price: 960, hide_on_pdf: true }),
    line({ id: 4, ordre: 4, line_type: 'subtotal', label: '', qty: 0, effective_price: 0 }),
];

function mockFetch(lines = LINES) {
    return vi.spyOn(globalThis, 'fetch').mockImplementation((url, opts = {}) => {
        let body = {};
        if (url === ENDPOINTS.lines) body = { lines, quote_statu: 1 };
        else if (url === ENDPOINTS.selectData) body = { units: [{ id: 1, label: 'Unité', default: 1 }], vats: [], currency: 'EUR' };
        else if (url === ENDPOINTS.storeOrder) body = { redirect: null, error: 'stop' };
        else if (url.endsWith('/presentation')) {
            const id = Number(url.split('/').slice(-2)[0]);
            body = { line: { ...lines.find((l) => l.id === id), ...JSON.parse(opts.body) } };
        }
        return Promise.resolve({ ok: true, json: () => Promise.resolve(body) });
    });
}

const renderPage = () => render(<QuoteLinesPage quoteId={1} quoteStatu={1} endpoints={ENDPOINTS} />);

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('QuoteLinesPage — lignes de présentation', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('renders a section title with its total, hidden line included', async () => {
        mockFetch();
        renderPage();

        expect(await screen.findByText('Garde-corps')).toBeInTheDocument();
        // 12 × 85 + 960 = 1 980 € pour la section et pour son sous-total
        expect(screen.getAllByText((t) => t.replace(/\s/g, '') === '1980,00€')).toHaveLength(2);
        expect(screen.getByText('Sous-total Garde-corps')).toBeInTheDocument();
    });

    it('warns that a hidden line will not be printed', async () => {
        mockFetch();
        renderPage();

        expect(await screen.findByText(/masquée hors forfait/)).toBeInTheDocument();
    });

    it('counts only articles in the line badge', async () => {
        mockFetch();
        renderPage();

        expect(await screen.findByText('2 lignes')).toBeInTheDocument();
    });

    it('toggles the PDF visibility of an article through the presentation endpoint', async () => {
        const spy = mockFetch();
        renderPage();

        fireEvent.click(await screen.findByTitle('Afficher sur le PDF'));

        await waitFor(() => {
            const call = spy.mock.calls.find(([url]) => url === '/fr/quotes/1/lines/json/3/presentation');
            expect(call).toBeTruthy();
            expect(JSON.parse(call[1].body)).toEqual({ hide_on_pdf: false });
        });
    });

    it('asks whether to carry the layout over to the order', async () => {
        const spy = mockFetch();
        const { container } = renderPage();
        await screen.findByText('Garde-corps acier');

        // Seuls les articles ont une case à cocher.
        const boxes = container.querySelectorAll('tbody input[type="checkbox"]');
        expect(boxes).toHaveLength(2);
        fireEvent.click(boxes[0]);

        fireEvent.click(screen.getByText(/Créer une commande \(1\)/));
        fireEvent.click(await screen.findByLabelText(/Articles seuls/));
        fireEvent.click(screen.getByText('Créer la commande'));

        await waitFor(() => {
            const call = spy.mock.calls.find(([url]) => url === ENDPOINTS.storeOrder);
            expect(JSON.parse(call[1].body)).toEqual({ line_ids: [2], presentation: 'drop' });
        });
    });
});
