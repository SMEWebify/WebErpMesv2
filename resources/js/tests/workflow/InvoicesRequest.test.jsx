import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import InvoicesRequest from '../../components/InvoicesRequest.jsx';

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const ENDPOINTS = {
    lines:       '/fr/invoices/lines',
    store:       '/fr/invoices/store',
    generateAll: '/fr/invoices/generate-all',
};

const TRANS = {
    company: 'Société', external_id: 'Code', label: 'Libellé',
    select_company: '-- Choisir --', no_company: 'Aucune société',
    address: 'Adresse', contact: 'Contact', user: 'Responsable',
    date_from: 'Du', date_to: 'Au',
    save: 'Facturer', saving: 'Enregistrement…',
    new_invoice: 'Facturer', generate_all: 'Tout facturer',
    no_lines: 'Aucune ligne sélectionnée',
    order: 'Commande', product: 'Produit', bl_code: 'N° BL', qty: 'Qté',
    delivery_date: 'Date BL',
};

const COMPANIES = [
    { id: 1, label: 'Acme Corp' },
];

const USERS = [
    { id: 1, name: 'Alice' },
];

const LINES_RESPONSE = {
    addresses: [{ id: 10, label: '1 rue de la Paix, Paris' }],
    contacts:  [{ id: 20, label: 'Jean Dupont' }],
    lines:     [
        {
            id: 200, order_code: 'OR-002', order_url: null, order_type: 1,
            delivery_code: 'BL-002', delivery_url: null,
            companie_label: 'Acme Corp', companie_url: null,
            line_code: 'OL-002', line_label: 'Tôle 2mm',
            qty: 4, unit_label: 'u', selling_price: 80,
            discount: 0, vat_label: '20', delivery_date: '2026-05-20',
        },
    ],
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function mockFetch(responses = {}) {
    return vi.spyOn(globalThis, 'fetch').mockImplementation((url) => {
        const match = Object.entries(responses).find(([k]) => url.includes(k));
        const body  = match ? match[1] : {};
        return Promise.resolve({ ok: true, json: () => Promise.resolve(body) });
    });
}

function renderRequest(props = {}) {
    return render(
        <InvoicesRequest
            initialCode="FA-TEST-001"
            userId={1}
            users={USERS}
            companies={COMPANIES}
            endpoints={ENDPOINTS}
            trans={TRANS}
            {...props}
        />
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('InvoicesRequest', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('renders the form without crashing', () => {
        mockFetch();
        expect(() => renderRequest()).not.toThrow();
    });

    it('pre-fills code field with initialCode', () => {
        mockFetch();
        renderRequest();
        const inputs = screen.getAllByDisplayValue('FA-TEST-001');
        expect(inputs.length).toBeGreaterThan(0);
    });

    it('lists available companies in the select', () => {
        mockFetch();
        renderRequest();
        expect(screen.getByText('Acme Corp')).toBeInTheDocument();
    });

    it('fetches lines when a company is selected', async () => {
        const fetchSpy = mockFetch({ '/fr/invoices/lines': LINES_RESPONSE });
        renderRequest();

        fireEvent.change(screen.getByDisplayValue('-- Choisir --'), { target: { value: '1' } });

        await waitFor(() => expect(fetchSpy).toHaveBeenCalled());
        const url = fetchSpy.mock.calls[0][0];
        expect(url).toContain('company_id=1');
    });

    it('displays delivery lines after company selection', async () => {
        mockFetch({ '/fr/invoices/lines': LINES_RESPONSE });
        renderRequest();

        fireEvent.change(screen.getByDisplayValue('-- Choisir --'), { target: { value: '1' } });

        await waitFor(() => {
            expect(screen.getByText('Tôle 2mm')).toBeInTheDocument();
        });
        expect(screen.getAllByText('BL-002').length).toBeGreaterThan(0);
    });

    it('shows error message when submitting with no lines selected', async () => {
        mockFetch({ '/fr/invoices/lines': LINES_RESPONSE });
        renderRequest();

        fireEvent.change(screen.getByDisplayValue('-- Choisir --'), { target: { value: '1' } });
        await waitFor(() => screen.getByText('Tôle 2mm'));

        // Facturer = trans.new_invoice on the submit button
        const submitBtn = screen.getAllByText('Facturer').find(el => el.tagName === 'BUTTON');
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(screen.getByText('Aucune ligne sélectionnée')).toBeInTheDocument();
        });
    });

    it('submits selected lines and redirects on success', async () => {
        Object.defineProperty(window, 'location', {
            writable: true,
            value: { href: '' },
        });

        mockFetch({
            '/fr/invoices/lines': LINES_RESPONSE,
            '/fr/invoices/store': { redirect: '/fr/invoices/50' },
        });
        renderRequest();

        fireEvent.change(screen.getByDisplayValue('-- Choisir --'), { target: { value: '1' } });
        await waitFor(() => screen.getByText('Tôle 2mm'));

        // Select all lines with the header checkbox
        const checkboxes = screen.getAllByRole('checkbox');
        fireEvent.click(checkboxes[0]); // select-all

        const submitBtn = screen.getAllByText('Facturer').find(el => el.tagName === 'BUTTON');
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(window.location.href).toBe('/fr/invoices/50');
        });
    });

    it('shows generate-all button after company selection', async () => {
        mockFetch({ '/fr/invoices/lines': LINES_RESPONSE });
        renderRequest();

        fireEvent.change(screen.getByDisplayValue('-- Choisir --'), { target: { value: '1' } });

        await waitFor(() => {
            expect(screen.getByText('Tout facturer')).toBeInTheDocument();
        });
    });
});
