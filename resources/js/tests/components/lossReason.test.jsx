import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { describe, it, expect, vi, afterEach } from 'vitest';
import {
    canSubmitLoss,
    lossPayload,
    lossConfigFrom,
    LossReasonDialog,
    sendWithLossReason,
} from '../../lib/lossReason.jsx';
import { barWidth } from '../../components/quotes/QuoteLossStats.jsx';

const CONFIG = {
    kind: 'quote',
    status: 4,
    required: true,
    reasons: [{ id: 1, label: 'Prix' }, { id: 2, label: 'Délai' }],
    current: { loss_reason_id: null, loss_comment: null, label: null },
    texts: { title: 'Devis perdu : pourquoi ?', reason: 'Motif', choose: '— Choisir —', comment: 'Commentaire', cancel: 'Annuler', confirm: 'Enregistrer' },
};

afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
});

describe('canSubmitLoss', () => {
    it('exige un motif seulement si le serveur le demande', () => {
        expect(canSubmitLoss({ required: true }, '')).toBe(false);
        expect(canSubmitLoss({ required: true }, null)).toBe(false);
        expect(canSubmitLoss({ required: true }, '2')).toBe(true);
        expect(canSubmitLoss({ required: false }, '')).toBe(true);
    });
});

describe('lossPayload', () => {
    it('convertit le motif en nombre et vide les commentaires blancs', () => {
        expect(lossPayload('2', '  Concurrent  ')).toEqual({ loss_reason_id: 2, loss_comment: 'Concurrent' });
        expect(lossPayload('', '   ')).toEqual({ loss_reason_id: null, loss_comment: null });
    });
});

describe('lossConfigFrom', () => {
    it('lit la configuration portée par une 422', () => {
        expect(lossConfigFrom({ loss_reason: CONFIG })).toBe(CONFIG);
        expect(lossConfigFrom({ message: 'x' })).toBeNull();
        expect(lossConfigFrom(null)).toBeNull();
    });
});

describe('LossReasonDialog', () => {
    it('ne valide pas sans motif quand il est obligatoire, puis renvoie la saisie', () => {
        const onClose = vi.fn();
        render(<LossReasonDialog config={CONFIG} onClose={onClose} />);

        const confirm = screen.getByText('Enregistrer');
        expect(confirm).toBeDisabled();

        fireEvent.change(screen.getByLabelText(/Motif/), { target: { value: '1' } });
        fireEvent.change(screen.getByLabelText('Commentaire'), { target: { value: 'Trop cher' } });
        expect(confirm).not.toBeDisabled();

        fireEvent.click(confirm);
        expect(onClose).toHaveBeenCalledWith({ loss_reason_id: 1, loss_comment: 'Trop cher' });
    });

    it('reprend le motif déjà saisi', () => {
        render(<LossReasonDialog config={{ ...CONFIG, current: { loss_reason_id: 2, loss_comment: 'Hors délai' } }} onClose={() => {}} />);
        expect(screen.getByLabelText(/Motif/).value).toBe('2');
        expect(screen.getByLabelText('Commentaire').value).toBe('Hors délai');
    });

    it('annuler résout null', () => {
        const onClose = vi.fn();
        render(<LossReasonDialog config={CONFIG} onClose={onClose} />);
        fireEvent.click(screen.getByText('Annuler'));
        expect(onClose).toHaveBeenCalledWith(null);
    });
});

describe('sendWithLossReason', () => {
    const response = (status, body) => Promise.resolve({ ok: status < 300, status, json: () => Promise.resolve(body) });

    it('ouvre la fenêtre sur une 422 « motif manquant » et renvoie la requête complétée', async () => {
        const fetchMock = vi.spyOn(globalThis, 'fetch')
            .mockImplementationOnce(() => response(422, { message: 'Motif', loss_reason: CONFIG }))
            .mockImplementationOnce(() => response(200, { ok: true }));

        let promise;
        await act(async () => { promise = sendWithLossReason('/move', { statu: 5 }, { method: 'PUT' }); });

        await waitFor(() => screen.getByText('Devis perdu : pourquoi ?'));
        fireEvent.change(screen.getByLabelText(/Motif/), { target: { value: '2' } });
        await act(async () => { fireEvent.click(screen.getByText('Enregistrer')); });

        const result = await promise;
        expect(result.ok).toBe(true);
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(fetchMock.mock.calls[1][1].method).toBe('PUT');
        expect(JSON.parse(fetchMock.mock.calls[1][1].body)).toEqual({ statu: 5, loss_reason_id: 2, loss_comment: null });
    });

    it('signale l\'annulation sans renvoyer la requête', async () => {
        const fetchMock = vi.spyOn(globalThis, 'fetch')
            .mockImplementationOnce(() => response(422, { loss_reason: CONFIG }));

        let promise;
        await act(async () => { promise = sendWithLossReason('/move', { statu: 5 }); });
        await waitFor(() => screen.getByText('Annuler'));
        await act(async () => { fireEvent.click(screen.getByText('Annuler')); });

        const result = await promise;
        expect(result).toMatchObject({ ok: false, cancelled: true });
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    it('laisse passer une autre erreur telle quelle', async () => {
        vi.spyOn(globalThis, 'fetch').mockImplementationOnce(() => response(500, { error: 'Update failed' }));
        const result = await sendWithLossReason('/statu', { statu: 2 });
        expect(result).toMatchObject({ ok: false, cancelled: false, status: 500, data: { error: 'Update failed' } });
    });
});

describe('barWidth (statistique des pertes)', () => {
    it('rapporte la barre au motif le plus fréquent, avec un minimum visible', () => {
        expect(barWidth(10, 10)).toBe(100);
        expect(barWidth(5, 10)).toBe(50);
        expect(barWidth(1, 1000)).toBe(2);
        expect(barWidth(0, 10)).toBe(0);
        expect(barWidth(3, 0)).toBe(0);
    });
});
