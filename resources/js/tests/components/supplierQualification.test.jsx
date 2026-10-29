import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { describe, it, expect, vi, afterEach } from 'vitest';
import {
    canSubmitOverride,
    overridePayload,
    qualificationConfigFrom,
    showsBadge,
    supplierOptionLabel,
    sendWithSupplierOverride,
    SupplierOverrideDialog,
    SupplierQualificationBadge,
} from '../../lib/supplierQualification.jsx';

const BADGES = {
    enabled: true,
    texts: { qualified: 'Qualifié', unqualified: 'Non qualifié', review_overdue: 'Revue échue' },
};

const REFUSAL = {
    suppliers: [{ id: 7, label: 'Aciers du Nord', status: 'review_overdue', status_label: 'Revue échue', detail: 'revue de qualification échue depuis le 01/10/2026.' }],
    can_override: true,
    texts: {
        title: 'Fournisseur non qualifié', message: 'Commande d\'achat refusée : fournisseur non qualifié.',
        no_permission: 'Pas le droit', reason_label: 'Motif du passe-droit', reason_help: 'Tracé',
        override: 'Commander quand même', cancel: 'Annuler',
    },
};

const response = (status, body) => Promise.resolve({ ok: status < 300, status, json: () => Promise.resolve(body) });

afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
});

describe('badges et libellés', () => {
    it('n\'affiche rien quand l\'option est inactive', () => {
        expect(showsBadge({ enabled: false, texts: BADGES.texts }, 'unqualified')).toBe(false);
        expect(showsBadge(null, 'unqualified')).toBe(false);
        expect(showsBadge(BADGES, null)).toBe(false);
        expect(showsBadge(BADGES, 'unqualified')).toBe(true);

        const { container } = render(<SupplierQualificationBadge status="unqualified" config={{ enabled: false }} />);
        expect(container.innerHTML).toBe('');
    });

    it('signale dans l\'option un fournisseur non qualifié, pas un qualifié', () => {
        expect(supplierOptionLabel('Aciers du Nord', 'qualified', BADGES)).toBe('Aciers du Nord');
        expect(supplierOptionLabel('Aciers du Nord', 'review_overdue', BADGES)).toBe('Aciers du Nord — Revue échue');
        expect(supplierOptionLabel('Aciers du Nord', 'unqualified', { enabled: false })).toBe('Aciers du Nord');
    });

    it('colore le badge selon l\'état', () => {
        render(<SupplierQualificationBadge status="unqualified" config={BADGES} />);
        expect(screen.getByText('Non qualifié').className).toContain('text-bg-danger');
    });
});

describe('passe-droit', () => {
    it('exige un motif d\'au moins trois caractères', () => {
        expect(canSubmitOverride('  ab ')).toBe(false);
        expect(canSubmitOverride(null)).toBe(false);
        expect(canSubmitOverride('Urgence')).toBe(true);
        expect(overridePayload('  Urgence  ')).toEqual({ supplier_override: 1, supplier_override_reason: 'Urgence' });
    });

    it('lit la configuration portée par la 422', () => {
        expect(qualificationConfigFrom({ supplier_qualification: REFUSAL })).toBe(REFUSAL);
        expect(qualificationConfigFrom({ message: 'x' })).toBeNull();
    });

    it('sans le droit, la fenêtre explique et ne propose pas de forcer', () => {
        render(<SupplierOverrideDialog config={{ ...REFUSAL, can_override: false }} onClose={() => {}} />);
        expect(screen.getByText('Pas le droit')).toBeTruthy();
        expect(screen.queryByText('Commander quand même')).toBeNull();
    });
});

describe('sendWithSupplierOverride', () => {
    it('ouvre la fenêtre sur la 422 et renvoie la requête avec le motif', async () => {
        const fetchMock = vi.spyOn(globalThis, 'fetch')
            .mockImplementationOnce(() => response(422, { message: 'refus', supplier_qualification: REFUSAL }))
            .mockImplementationOnce(() => response(201, { redirect: '/purchases/9' }));

        let promise;
        await act(async () => { promise = sendWithSupplierOverride('/purchases/json/store', { code: 'PU-1' }); });

        await waitFor(() => screen.getByText('Aciers du Nord'));
        const confirm = screen.getByText('Commander quand même');
        expect(confirm).toBeDisabled();
        fireEvent.change(screen.getByLabelText(/Motif du passe-droit/), { target: { value: 'Seul fournisseur' } });
        await act(async () => { fireEvent.click(confirm); });

        const result = await promise;
        expect(result).toMatchObject({ ok: true, data: { redirect: '/purchases/9' } });
        expect(JSON.parse(fetchMock.mock.calls[1][1].body)).toEqual({
            code: 'PU-1', supplier_override: 1, supplier_override_reason: 'Seul fournisseur',
        });
    });

    it('fenêtre refermée : rien n\'est renvoyé, le refus reste lisible', async () => {
        const fetchMock = vi.spyOn(globalThis, 'fetch')
            .mockImplementationOnce(() => response(422, { message: 'refus', supplier_qualification: REFUSAL }));

        let promise;
        await act(async () => { promise = sendWithSupplierOverride('/x', {}); });
        await waitFor(() => screen.getByText('Annuler'));
        await act(async () => { fireEvent.click(screen.getByText('Annuler')); });

        const result = await promise;
        expect(result).toMatchObject({ ok: false, cancelled: true, refused: true, data: { message: 'refus' } });
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    it('laisse passer une autre 422 telle quelle', async () => {
        vi.spyOn(globalThis, 'fetch').mockImplementationOnce(() => response(422, { errors: { code: ['pris'] } }));
        const result = await sendWithSupplierOverride('/x', {});
        expect(result).toMatchObject({ ok: false, cancelled: false, status: 422 });
    });

    it('s\'enchaîne avec un autre envoi (flèches de statut)', async () => {
        const send = vi.fn().mockResolvedValue({ ok: true, status: 200, data: {}, cancelled: false });
        const result = await sendWithSupplierOverride('/statu', { statu: 2 }, { send });
        expect(send).toHaveBeenCalledWith({ statu: 2 });
        expect(result.ok).toBe(true);
    });
});
