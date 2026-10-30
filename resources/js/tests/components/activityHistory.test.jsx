import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, afterEach } from 'vitest';
import ActivityHistory, { ActivityHistoryList } from '../../components/ActivityHistory.jsx';

const TRANS = {
    history: 'Historique des modifications',
    history_empty: 'Aucune modification enregistrée.',
    history_loading: 'Chargement…',
    history_error: 'Historique indisponible.',
};

const ENTRY = {
    id: 7,
    event: 'Modification',
    causer: 'Alice',
    at: '30/10/2026 10:00',
    changes: [
        { field: 'statu', label: 'Statut', old: 'En cours', new: 'Résolue' },
        { field: 'causes_id', label: 'Cause', old: null, new: 'Outil usé' },
    ],
};

afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
});

describe('ActivityHistoryList', () => {
    it('affiche l\'ancienne valeur barrée puis la nouvelle', () => {
        const { container } = render(<ActivityHistoryList entries={[ENTRY]} trans={TRANS} />);

        expect(screen.getByText('Alice')).toBeTruthy();
        expect(container.querySelector('del').textContent).toBe('En cours');
        expect(screen.getByText('Résolue')).toBeTruthy();
        // Un champ vide auparavant n'a pas de valeur barrée.
        expect(container.querySelectorAll('del')).toHaveLength(1);
        expect(screen.getByText('Outil usé')).toBeTruthy();
    });

    it('signale un historique vide', () => {
        render(<ActivityHistoryList entries={[]} trans={TRANS} />);
        expect(screen.getByText('Aucune modification enregistrée.')).toBeTruthy();
    });
});

describe('ActivityHistory', () => {
    it('charge l\'historique depuis l\'URL de la fiche', async () => {
        const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
            ok: true,
            json: async () => ({ data: [ENTRY] }),
        });

        render(<ActivityHistory url="/quality/nonConformitie/api/3/history" trans={TRANS} />);

        expect(screen.getByText('Chargement…')).toBeTruthy();
        await waitFor(() => expect(screen.getByText('Résolue')).toBeTruthy());
        expect(fetchMock.mock.calls[0][0]).toBe('/quality/nonConformitie/api/3/history');
    });

    it('affiche une erreur si la lecture échoue', async () => {
        vi.spyOn(globalThis, 'fetch').mockResolvedValue({ ok: false, status: 500 });

        render(<ActivityHistory url="/x" trans={TRANS} />);

        await waitFor(() => expect(screen.getByText('Historique indisponible.')).toBeTruthy());
    });
});
