import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import InvoicesIndex from '../../components/InvoicesIndex.jsx';

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const ENDPOINTS = {
    list:  '/fr/invoices/json/list',
    statu: '/fr/invoices/__ID__/statu',
    show:  '/fr/invoices/__ID__',
};

const TRANS = {
    dashboard: 'Tableau de bord', invoices_list: 'Liste des factures',
    draft: 'Brouillon', sent: 'Envoyée', paid: 'Payée',
    partial: 'Partiel', overdue: 'En retard', canceled: 'Annulée',
    search: 'Rechercher', new_invoice: 'Nouvelle facture',
    code: 'Code', label: 'Libellé', company: 'Société',
    currency: 'EUR', locale: 'fr-FR',
};

const CHART_DATA = {
    invoiceMonthlyRecap: [], invoicesDataRate: [], fiscalYearStartMonth: 1,
};

const KPI = { total: 10, paid: 5, overdue: 2 };

const INVOICE_ROW = {
    id: 30, code: 'FA-001', label: 'Facture test',
    statu: 1, invoice_type: 1, accounting_status: 1,
    created_at: '2026-05-15', total_amount: 3000,
    companie: { label: 'Client B' }, contact: null,
};

const LIST_RESPONSE = {
    data: [INVOICE_ROW],
    meta: { total: 1, current_page: 1, last_page: 1 },
    qonto_enabled: false,
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
        <InvoicesIndex
            kpi={KPI}
            chartData={CHART_DATA}
            topClients={[]}
            endpoints={ENDPOINTS}
            trans={TRANS}
            {...props}
        />
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('InvoicesIndex', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('renders without crashing', () => {
        mockFetch();
        expect(() => renderIndex()).not.toThrow();
    });

    it('displays dashboard and list tabs', () => {
        mockFetch();
        renderIndex();
        expect(screen.getByText('Tableau de bord')).toBeInTheDocument();
        expect(screen.getByText('Liste des factures')).toBeInTheDocument();
    });

    it('fetches list and displays an invoice row', async () => {
        mockFetch({ '/fr/invoices/json/list': LIST_RESPONSE });
        renderIndex();
        fireEvent.click(screen.getByText('Liste des factures'));

        await waitFor(() => {
            expect(screen.getByText('FA-001')).toBeInTheDocument();
        });
        expect(screen.getByText('Facture test')).toBeInTheDocument();
        expect(screen.getByText('Client B')).toBeInTheDocument();
    });

    it('loads list directly when companieId is provided', async () => {
        mockFetch({ '/fr/invoices/json/list': LIST_RESPONSE });
        renderIndex({ companieId: 42 });

        await waitFor(() => {
            expect(screen.getByText('FA-001')).toBeInTheDocument();
        });
    });

    it('calls list endpoint with company_id filter when companieId set', async () => {
        const fetchSpy = mockFetch();
        renderIndex({ companieId: 42 });

        await waitFor(() => expect(fetchSpy).toHaveBeenCalled());
        const url = fetchSpy.mock.calls[0][0];
        expect(url).toContain('company_id=42');
    });
});
