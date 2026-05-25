import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import OrdersIndex from '../../components/OrdersIndex.jsx';

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const ENDPOINTS = {
    list:          '/fr/orders/json/list',
    store:         '/fr/orders/json/store',
    statu:         '/fr/orders/__ID__/statu',
    addresses:     '/fr/companies/__ID__/addresses',
    contacts:      '/fr/companies/__ID__/contacts',
    selectData:    '/fr/orders/json/select-data',
};

const TRANS = {
    dashboard: 'Tableau de bord', orders_list: 'Liste des commandes',
    open: 'Ouvert', in_progress: 'En cours', delivered: 'Livré',
    partly_delivered: 'Partiellement livré', stopped: 'Arrêté',
    canceled: 'Annulé', awaiting_payment: 'En attente',
    search: 'Rechercher', new_order: 'Nouvelle commande',
    code: 'Code', label: 'Libellé', company: 'Société',
    currency: 'EUR', locale: 'fr-FR',
    orders: 'commandes', page: 'Page',
};

const CHART_DATA = {
    quoteMonthlyRecap: [], fiscalYearStartMonth: 1,
    ordersDataRate: [], orderMonthlyRecap: [],
};

const KPI = {};

const ORDER_ROW = {
    id: 10, code: 'OR-001', label: 'Commande test',
    statu: 1, type: 1, validity_date: '2026-06-01', created_at: '2026-05-01',
    total_amount: 2500, companie: { label: 'Client A' }, contact: null,
    order_lines_count: 3, customer_reference: 'REF-A',
};

const LIST_RESPONSE = {
    data: [ORDER_ROW],
    meta: { total: 1, current_page: 1, last_page: 1 },
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function mockFetch(responses = {}) {
    return vi.spyOn(globalThis, 'fetch').mockImplementation((url) => {
        const match = Object.entries(responses).find(([k]) => url.includes(k));
        const body  = match ? match[1] : LIST_RESPONSE;
        return Promise.resolve({ ok: true, json: () => Promise.resolve(body) });
    });
}

function renderIndex(props = {}) {
    return render(
        <OrdersIndex
            kpi={KPI}
            chartData={CHART_DATA}
            topCustomers={[]}
            endpoints={ENDPOINTS}
            trans={TRANS}
            {...props}
        />
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('OrdersIndex', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('renders without crashing', () => {
        mockFetch();
        expect(() => renderIndex()).not.toThrow();
    });

    it('displays dashboard and list tabs', () => {
        mockFetch();
        renderIndex();
        expect(screen.getByText('Tableau de bord')).toBeInTheDocument();
        expect(screen.getByText('Liste des commandes')).toBeInTheDocument();
    });

    it('fetches list and displays an order row', async () => {
        mockFetch({ '/fr/orders/json/list': LIST_RESPONSE });
        renderIndex();

        fireEvent.click(screen.getByText('Liste des commandes'));

        await waitFor(() => {
            expect(screen.getByText('OR-001')).toBeInTheDocument();
        });
        expect(screen.getByText('Commande test')).toBeInTheDocument();
        expect(screen.getByText('Client A')).toBeInTheDocument();
    });

    it('shows status filter buttons', async () => {
        mockFetch();
        renderIndex();
        fireEvent.click(screen.getByText('Liste des commandes'));

        await waitFor(() => {
            expect(screen.getByText('En cours')).toBeInTheDocument();
        });
        expect(screen.getByText('Annulé')).toBeInTheDocument();
    });

    it('calls list endpoint with filter when status toggled', async () => {
        const fetchSpy = mockFetch();
        renderIndex();
        fireEvent.click(screen.getByText('Liste des commandes'));
        await waitFor(() => expect(fetchSpy).toHaveBeenCalled());

        fireEvent.click(screen.getByRole('button', { name: 'En cours' }));

        await waitFor(() => {
            const filtered = fetchSpy.mock.calls
                .map(([url]) => url)
                .find(u => u.includes('statuses') && u.includes('/fr/orders/json/list'));
            expect(filtered).toBeDefined();
        });
    });

    it('shows new order button', async () => {
        mockFetch();
        renderIndex();
        fireEvent.click(screen.getByText('Liste des commandes'));
        await waitFor(() => {
            expect(screen.getByText('Nouvelle commande')).toBeInTheDocument();
        });
    });
});
