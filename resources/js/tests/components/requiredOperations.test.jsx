import React from 'react';
import { render, screen, fireEvent, act, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, afterEach } from 'vitest';
import { askRequiredOperationsOverride, lineCaption } from '../../lib/requiredOperations.jsx';
import RequiredOperationsBanner from '../../components/affairs/RequiredOperationsBanner.jsx';

const MISSING = [
    { line_id: 7, ordre: 2, code: 'DN50', label: 'Débitmètre', services: [{ id: 3, code: 'PEINT', label: 'Peinture' }] },
];
const TEXTS = {
    title: 'Opérations manquantes', message: 'Conversion refusée', line: 'Ligne', missing: 'Manquantes',
    no_permission: 'Pas le droit', reason_label: 'Motif', reason_help: 'Tracé', override: 'Convertir malgré tout', cancel: 'Annuler',
};

afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
});

describe('lineCaption', () => {
    it('assemble ordre, code et libellé sans séparateur vide', () => {
        expect(lineCaption({ ordre: 2, code: 'DN50', label: 'Débitmètre' })).toBe('2 — DN50 Débitmètre');
        expect(lineCaption({ ordre: 3, code: null, label: 'Bride' })).toBe('3 — Bride');
    });
});

describe('askRequiredOperationsOverride', () => {
    it('exige un motif avant de renvoyer le passe-droit', async () => {
        let promise;
        await act(async () => {
            promise = askRequiredOperationsOverride({ missing: MISSING, can_override: true, texts: TEXTS });
        });

        expect(screen.getByText('2 — DN50 Débitmètre')).toBeTruthy();
        expect(screen.getByText('PEINT — Peinture')).toBeTruthy();

        const button = screen.getByRole('button', { name: 'Convertir malgré tout' });
        expect(button.disabled).toBe(true);

        fireEvent.change(screen.getByLabelText('Motif *'), { target: { value: '  Peinture client  ' } });
        expect(button.disabled).toBe(false);
        await act(async () => { fireEvent.click(button); });

        await expect(promise).resolves.toEqual({
            required_operations_override: 1,
            required_operations_override_reason: 'Peinture client',
        });
    });

    it('sans le droit : explication, pas de bouton, annulation = null', async () => {
        let promise;
        await act(async () => {
            promise = askRequiredOperationsOverride({ missing: MISSING, can_override: false, texts: TEXTS });
        });

        expect(screen.getByText('Pas le droit')).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Convertir malgré tout' })).toBeNull();
        await act(async () => { fireEvent.click(screen.getByRole('button', { name: 'Annuler' })); });

        await expect(promise).resolves.toBeNull();
    });
});

describe('RequiredOperationsBanner', () => {
    const stubFetch = (body) => vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve(body) })));

    it("n'affiche rien quand aucune opération ne manque", async () => {
        stubFetch({ missing: [], overrides: [], texts: {} });
        const { container } = render(<RequiredOperationsBanner endpoint="/ops" refreshKey="a" />);
        await waitFor(() => expect(fetch).toHaveBeenCalled());
        expect(container.innerHTML).toBe('');
    });

    it('liste les lignes incomplètes et les passe-droits accordés', async () => {
        stubFetch({
            missing: MISSING,
            overrides: [{ id: 1, user: 'Alice', reason: 'Urgence', created_at: '28/10/2026 10:00', order_code: 'OR-12', order_url: '/orders/12' }],
            texts: { title: 'Opérations manquantes', line: 'Ligne', missing: 'Manquantes', overrides: 'Passe-droits', override_entry: ':date — :user : :reason' },
        });
        render(<RequiredOperationsBanner endpoint="/ops" refreshKey="a" />);

        await waitFor(() => expect(screen.getByText('Opérations manquantes')).toBeTruthy());
        expect(screen.getByText('PEINT — Peinture')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'OR-12' }).getAttribute('href')).toBe('/orders/12');
        expect(screen.getByText(/Alice : Urgence/)).toBeTruthy();
    });
});
