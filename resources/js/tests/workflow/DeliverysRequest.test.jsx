import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import DeliverysRequest from '../../components/DeliverysRequest.jsx';

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const ENDPOINTS = {
    companyData: '/fr/deliverys/company-data',
    store:       '/fr/deliverys/store',
};

const TRANS = {
    company: 'Société', external_id: 'Code', label: 'Libellé',
    select_company: '-- Choisir --', no_company: 'Aucune société',
    address: 'Adresse', contact: 'Contact', user: 'Responsable',
    save: 'Enregistrer', saving: 'Enregistrement…',
    new_delivery: 'Créer BL',
    no_lines: 'Aucune ligne sélectionnée',
    order: 'Commande', product: 'Produit', qty_remaining: 'Qté restante',
    qty: 'Qté BL',
};

const COMPANIES = [
    { id: 1, label: 'Acme Corp' },
    { id: 2, label: 'Beta Ltd' },
];

const USERS = [
    { id: 1, name: 'Alice' },
];

const COMPANY_DATA_RESPONSE = {
    addresses:          [{ id: 10, label: '1 rue de la Paix, Paris' }],
    contacts:           [{ id: 20, label: 'Jean Dupont' }],
    lines:              [
        {
            id: 100, order_code: 'OR-001', code: 'OL-001', label: 'Acier S235',
            delivered_remaining_qty: 5, qty: 10, unit_label: 'u',
            selling_price: 50, discount: 0, vat_label: '20', delivery_date: null,
        },
    ],
    default_address_id: 10,
    default_contact_id: 20,
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
        <DeliverysRequest
            initialCode="BL-TEST-001"
            userId={1}
            users={USERS}
            companies={COMPANIES}
            canManageStock={false}
            endpoints={ENDPOINTS}
            trans={TRANS}
            {...props}
        />
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('DeliverysRequest', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('renders the form without crashing', () => {
        mockFetch();
        expect(() => renderRequest()).not.toThrow();
    });

    it('pre-fills code field with initialCode', () => {
        mockFetch();
        renderRequest();
        const inputs = screen.getAllByDisplayValue('BL-TEST-001');
        expect(inputs.length).toBeGreaterThan(0);
    });

    it('lists available companies in the select', () => {
        mockFetch();
        renderRequest();
        expect(screen.getByText('Acme Corp')).toBeInTheDocument();
        expect(screen.getByText('Beta Ltd')).toBeInTheDocument();
    });

    it('fetches company data when a company is selected', async () => {
        const fetchSpy = mockFetch({ '/fr/deliverys/company-data': COMPANY_DATA_RESPONSE });
        renderRequest();

        const select = screen.getByDisplayValue('-- Choisir --');
        fireEvent.change(select, { target: { value: '1' } });

        await waitFor(() => expect(fetchSpy).toHaveBeenCalled());
        const url = fetchSpy.mock.calls[0][0];
        expect(url).toContain('company_id=1');
    });

    it('displays order lines after company selection', async () => {
        mockFetch({ '/fr/deliverys/company-data': COMPANY_DATA_RESPONSE });
        renderRequest();

        fireEvent.change(screen.getByDisplayValue('-- Choisir --'), { target: { value: '1' } });

        await waitFor(() => {
            expect(screen.getByText('Acier S235')).toBeInTheDocument();
        });
        expect(screen.getAllByText('OR-001').length).toBeGreaterThan(0);
    });

    it('shows error message when submitting with no lines selected', async () => {
        mockFetch({ '/fr/deliverys/company-data': COMPANY_DATA_RESPONSE });
        renderRequest();

        fireEvent.change(screen.getByDisplayValue('-- Choisir --'), { target: { value: '1' } });
        await waitFor(() => screen.getByText('Acier S235'));

        fireEvent.click(screen.getByText('Créer BL'));

        await waitFor(() => {
            expect(screen.getByText('Aucune ligne sélectionnée')).toBeInTheDocument();
        });
    });

    it('submits selected line and redirects on success', async () => {
        const redirectSpy = vi.spyOn(window, 'location', 'get').mockReturnValue({
            href: '',
        });
        Object.defineProperty(window, 'location', {
            writable: true,
            value: { href: '' },
        });

        mockFetch({
            '/fr/deliverys/company-data': COMPANY_DATA_RESPONSE,
            '/fr/deliverys/store': { redirect: '/fr/deliverys/99' },
        });
        renderRequest();

        fireEvent.change(screen.getByDisplayValue('-- Choisir --'), { target: { value: '1' } });
        await waitFor(() => screen.getByText('Acier S235'));

        // Set qty > 0 to auto-check the line
        const qtyInput = screen.getByRole('spinbutton');
        fireEvent.change(qtyInput, { target: { value: '3' } });

        fireEvent.click(screen.getByText('Créer BL'));

        await waitFor(() => {
            expect(window.location.href).toBe('/fr/deliverys/99');
        });

        redirectSpy.mockRestore?.();
    });
});
