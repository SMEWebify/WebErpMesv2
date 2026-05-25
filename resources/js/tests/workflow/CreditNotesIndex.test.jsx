import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import CreditNotesIndex from '../../components/CreditNotesIndex.jsx';

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const ENDPOINTS = {
    list: '/fr/credit-notes/json/list',
    show: '/fr/credit-notes/__ID__',
};

const TRANS = {
    pending: 'En attente', approved: 'Approuvé', rejected: 'Rejeté',
    statistiques: 'Statistiques', monthly_recap: 'Récap mensuel',
    no_data: 'Aucune donnée', search: 'Rechercher',
    code: 'Code', label: 'Libellé', company: 'Société',
    new_credit_note: 'Nouvel avoir',
    currency: 'EUR', locale: 'fr-FR',
};

const CHART_DATA = {
    creditNotesDataRate: [], creditNoteMonthlyRecap: [], fiscalYearStartMonth: 1,
};

const CREDIT_NOTE_ROW = {
    id: 40, code: 'AV-001', label: 'Avoir test',
    statu: 1, created_at: '2026-05-20',
    companie: { label: 'Client C' }, contact: null,
    invoices_id: 30,
};

const LIST_RESPONSE = {
    data: [CREDIT_NOTE_ROW],
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
        <CreditNotesIndex
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

describe('CreditNotesIndex', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('renders without crashing', () => {
        mockFetch();
        expect(() => renderIndex()).not.toThrow();
    });

    it('shows KPI section with stats chart', () => {
        mockFetch();
        renderIndex();
        expect(screen.getByText('Statistiques')).toBeInTheDocument();
    });

    it('fetches list and displays a credit note row', async () => {
        mockFetch({ '/fr/credit-notes/json/list': LIST_RESPONSE });
        renderIndex();

        await waitFor(() => {
            expect(screen.getByText('AV-001')).toBeInTheDocument();
        });
        expect(screen.getByText('Avoir test')).toBeInTheDocument();
        expect(screen.getByText('Client C')).toBeInTheDocument();
    });

    it('toggles status filter and triggers new fetch', async () => {
        const fetchSpy = mockFetch();
        renderIndex();
        await waitFor(() => expect(fetchSpy).toHaveBeenCalledTimes(1));

        fireEvent.click(screen.getByRole('button', { name: 'En attente' }));

        await waitFor(() => expect(fetchSpy).toHaveBeenCalledTimes(2));
        const lastUrl = fetchSpy.mock.calls.at(-1)[0];
        expect(lastUrl).toContain('statuses');
    });

    it('shows search input', async () => {
        mockFetch();
        renderIndex();
        await waitFor(() => {
            expect(screen.getByPlaceholderText('Rechercher')).toBeInTheDocument();
        });
    });

    it('shows empty chart message when no monthly data', () => {
        mockFetch();
        renderIndex();
        expect(screen.getByText('Aucune donnée')).toBeInTheDocument();
    });
});
