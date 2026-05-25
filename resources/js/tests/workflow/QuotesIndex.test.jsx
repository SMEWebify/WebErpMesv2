import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import QuotesIndex from '../../components/QuotesIndex.jsx';

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const ENDPOINTS = {
    list:         '/fr/quotes/json/list',
    store:        '/fr/quotes/json/store',
    selectData:   '/fr/quotes/json/select-data',
    addresses:    '/fr/companies/__ID__/addresses',
    contacts:     '/fr/companies/__ID__/contacts',
    storeAddress: '/fr/companies/__ID__/addresses/store',
    storeContact: '/fr/companies/__ID__/contacts/store',
};

const TRANS = {
    dashboard: 'Tableau de bord', quotes_list: 'Liste des devis',
    open: 'Ouvert', send: 'Envoyé', win: 'Gagné', lost: 'Perdu',
    closed: 'Clôturé', obsolete: 'Obsolète',
    search: 'Rechercher', new_quote: 'Nouveau devis',
    code: 'Code', label: 'Libellé', company: 'Société',
    currency: 'EUR', locale: 'fr-FR',
    // Month labels required by LineChart SVG
    jan: 'Janvier', feb: 'Février', mar: 'Mars', apr: 'Avril',
    may: 'Mai',     jun: 'Juin',   jul: 'Juillet', aug: 'Août',
    sep: 'Septembre', oct: 'Octobre', nov: 'Novembre', dec: 'Décembre',
};

const CHART_DATA = {
    quoteMonthlyRecap: [], quoteMonthlyRecapPreviousYear: [],
    quotesDataRate: [], fiscalYearStartMonth: 1,
};

const KPI = { averageAmount: '1 000,00 €', conversionRate: 25, responseRate: 60 };

const QUOTE_ROW = {
    id: 1, code: 'QT-001', label: 'Devis test',
    statu: 1, validity_date: '2026-06-01', created_at: '2026-05-01',
    total_amount: 1000, companie: { label: 'Acme' }, contact: null,
    order_lines_count: 2,
};

const LIST_RESPONSE = {
    data: [QUOTE_ROW],
    meta: { total: 1, current_page: 1, last_page: 1 },
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function mockFetch(responses = {}) {
    return vi.spyOn(globalThis, 'fetch').mockImplementation((url) => {
        const match = Object.entries(responses).find(([k]) => url.includes(k));
        const body  = match ? match[1] : LIST_RESPONSE;
        return Promise.resolve({
            ok:   true,
            json: () => Promise.resolve(body),
        });
    });
}

function renderIndex(props = {}) {
    return render(
        <QuotesIndex
            kpi={KPI}
            chartData={CHART_DATA}
            topCustomers={[]}
            quotesByUser={[]}
            endpoints={ENDPOINTS}
            trans={TRANS}
            {...props}
        />
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('QuotesIndex', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
    });

    it('renders without crashing', () => {
        mockFetch();
        expect(() => renderIndex()).not.toThrow();
    });

    it('displays dashboard and list tabs', () => {
        mockFetch();
        renderIndex();
        expect(screen.getByText('Tableau de bord')).toBeInTheDocument();
        expect(screen.getByText('Liste des devis')).toBeInTheDocument();
    });

    it('fetches list and displays a quote row', async () => {
        mockFetch({ '/fr/quotes/json/list': LIST_RESPONSE });
        renderIndex();

        // switch to list tab
        fireEvent.click(screen.getByText('Liste des devis'));

        await waitFor(() => {
            expect(screen.getByText('QT-001')).toBeInTheDocument();
        });
        expect(screen.getByText('Devis test')).toBeInTheDocument();
        expect(screen.getByText('Acme')).toBeInTheDocument();
    });

    it('calls the list endpoint with status filter when toggled', async () => {
        const fetchSpy = mockFetch();
        renderIndex();
        fireEvent.click(screen.getByText('Liste des devis'));

        await waitFor(() => expect(fetchSpy).toHaveBeenCalled());

        fireEvent.click(screen.getByRole('button', { name: 'Ouvert' }));

        await waitFor(() => {
            const calls = fetchSpy.mock.calls.map(([url]) => url);
            const filtered = calls.find(u => u.includes('statuses') && u.includes('/fr/quotes/json/list'));
            expect(filtered).toBeDefined();
        });
    });

    it('shows search input in list tab', async () => {
        mockFetch();
        renderIndex();
        fireEvent.click(screen.getByText('Liste des devis'));

        await waitFor(() => {
            expect(screen.getByPlaceholderText('Rechercher')).toBeInTheDocument();
        });
    });

    it('shows new quote button in list tab', async () => {
        mockFetch();
        renderIndex();
        fireEvent.click(screen.getByText('Liste des devis'));

        await waitFor(() => {
            expect(screen.getByText('Nouveau devis')).toBeInTheDocument();
        });
    });
});
