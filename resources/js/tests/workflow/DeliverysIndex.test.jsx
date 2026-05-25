import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import DeliverysIndex from '../../components/DeliverysIndex.jsx';

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const ENDPOINTS = {
    list:  '/fr/deliverys/json/list',
    statu: '/fr/deliverys/__ID__/statu',
    show:  '/fr/deliverys/__ID__',
};

const TRANS = {
    in_progress: 'En cours', send: 'Envoyé', canceled: 'Annulé', closed: 'Clôturé',
    statistiques: 'Statistiques', monthly_recap: 'Récap mensuel',
    no_data: 'Aucune donnée', search: 'Rechercher',
    code: 'Code', label: 'Libellé', company: 'Société',
    new_delivery: 'Nouveau BL',
    currency: 'EUR', locale: 'fr-FR',
};

const CHART_DATA = {
    deliverysDataRate: [], deliveryMonthlyRecap: [], fiscalYearStartMonth: 1,
};

const KPI = { invoiced: 5 };

const DELIVERY_ROW = {
    id: 20, code: 'BL-001', label: 'Bon de livraison test',
    statu: 1, invoice_status: 1, created_at: '2026-05-10',
    companie: { label: 'Acme' }, contact: null,
    tracking_number: null,
};

const LIST_RESPONSE = {
    data: [DELIVERY_ROW],
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
        <DeliverysIndex
            kpi={KPI}
            chartData={CHART_DATA}
            endpoints={ENDPOINTS}
            trans={TRANS}
            {...props}
        />
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('DeliverysIndex', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('renders without crashing', () => {
        mockFetch();
        expect(() => renderIndex()).not.toThrow();
    });

    it('shows KPI section on mount', () => {
        mockFetch();
        renderIndex();
        expect(screen.getByText('Statistiques')).toBeInTheDocument();
    });

    it('fetches list and displays a delivery row', async () => {
        mockFetch({ '/fr/deliverys/json/list': LIST_RESPONSE });
        renderIndex();

        await waitFor(() => {
            expect(screen.getByText('BL-001')).toBeInTheDocument();
        });
        expect(screen.getByText('Bon de livraison test')).toBeInTheDocument();
        expect(screen.getByText('Acme')).toBeInTheDocument();
    });

    it('triggers a new fetch when status filter is toggled', async () => {
        const fetchSpy = mockFetch();
        renderIndex();
        await waitFor(() => expect(fetchSpy).toHaveBeenCalledTimes(1));

        fireEvent.click(screen.getByRole('button', { name: 'En cours' }));

        await waitFor(() => expect(fetchSpy).toHaveBeenCalledTimes(2));
        const lastUrl = fetchSpy.mock.calls.at(-1)[0];
        expect(lastUrl).toContain('statuses');
    });

    it('triggers a new fetch when search changes', async () => {
        const fetchSpy = mockFetch();
        renderIndex();
        await waitFor(() => expect(fetchSpy).toHaveBeenCalledTimes(1));

        const input = screen.getByPlaceholderText('Rechercher');
        fireEvent.change(input, { target: { value: 'BL-001' } });

        await waitFor(() => expect(fetchSpy).toHaveBeenCalledTimes(2));
        const lastUrl = fetchSpy.mock.calls.at(-1)[0];
        expect(lastUrl).toContain('BL-001');
    });

    it('shows empty chart message when no data', () => {
        mockFetch();
        renderIndex();
        expect(screen.getByText('Aucune donnée')).toBeInTheDocument();
    });
});
