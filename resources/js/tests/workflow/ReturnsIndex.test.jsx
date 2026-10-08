import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import ReturnsIndex from '../../components/ReturnsIndex.jsx';

const ENDPOINTS = {
    list: '/fr/returns/json/list', store: '/fr/returns/json/store',
    diagnose: '/fr/returns/__ID__/diagnose', reopen: '/fr/returns/__ID__/reopen',
    close: '/fr/returns/__ID__/close', show: '/fr/returns/__ID__',
};

const TRANS = {
    code: 'Code', label: 'Libellé', delivery: 'BL', non_conformity: 'NC', status: 'Statut', created_at: 'Créé le',
    actions: 'Actions', search: 'Rechercher', add_return: 'Nouveau retour', diagnosis: 'Diagnostic',
    reopen_tasks: 'Rouvrir', close_return: 'Clôturer', view: 'Voir', loading: 'Chargement…', no_data: 'Aucune donnée',
    save: 'Enregistrer', customer_report: 'Rapport client', closure_comment: 'Commentaire de clôture',
    status_received: 'Reçu', status_diagnosed: 'Diagnostiqué', status_in_rework: 'En reprise', status_closed: 'Clos',
};

const ROWS = [
    { id: 1, code: 'RET-001', label: 'Capots rayés', statu: 1, delivery: { id: 1, code: 'BL-1' }, non_conformity: null, created_at: '01/05/2026', url: '/fr/returns/1' },
    { id: 2, code: 'RET-002', label: 'Bâti', statu: 4, delivery: null, non_conformity: { id: 2, code: 'NC-2' }, created_at: '15/06/2026', url: '/fr/returns/2' },
];

function mockFetch() {
    return vi.spyOn(globalThis, 'fetch').mockImplementation(() => Promise.resolve({
        ok: true, json: () => Promise.resolve({ data: ROWS, meta: { total: 2, current_page: 1, last_page: 1 } }),
    }));
}

const renderIndex = () => render(
    <ReturnsIndex endpoints={ENDPOINTS} props={{ nextCode: 'RET-9', deliveries: [], nonConformities: [] }} trans={TRANS} />
);

describe('ReturnsIndex', () => {
    beforeEach(() => vi.restoreAllMocks());

    it('affiche les retours dans le tableau partagé', async () => {
        mockFetch();
        renderIndex();
        await waitFor(() => expect(screen.getByText('RET-002')).toBeInTheDocument());
        expect(screen.getByText('BL-1')).toBeInTheDocument();
        expect(document.querySelector('tbody .badge-success')).toHaveTextContent('Clos');
    });

    it('ouvre le diagnostic sous la ligne, sur toute la largeur', async () => {
        mockFetch();
        renderIndex();
        await waitFor(() => screen.getByText('RET-001'));
        expect(screen.queryByText('Rapport client')).toBeNull();

        fireEvent.click(screen.getAllByTitle('Diagnostic')[0]);

        const cell = screen.getByText('Rapport client').closest('td');
        expect(cell.colSpan).toBe(document.querySelectorAll('thead tr:first-child th').length);
        expect(cell.closest('tr').previousElementSibling).toHaveTextContent('RET-001');
    });

    it('envoie les statuts cochés en statuses[]', async () => {
        const fetchSpy = mockFetch();
        renderIndex();
        await waitFor(() => screen.getByText('RET-001'));

        fireEvent.click(screen.getByRole('button', { name: 'Diagnostiqué' }));
        fireEvent.click(screen.getByRole('button', { name: 'Clos' }));

        await waitFor(() => {
            const url = decodeURIComponent(fetchSpy.mock.calls.at(-1)[0]);
            expect(url).toContain('statuses[]=2');
            expect(url).toContain('statuses[]=4');
            expect(url).not.toContain('status=');
        });
    });
});
