import { describe, it, expect } from 'vitest';
import { fillPlaceholders, MIN_REASON_LENGTH } from '../../lib/cancelReason.jsx';

describe('fillPlaceholders', () => {
    it('remplace les :cle de la traduction', () => {
        expect(fillPlaceholders('Reste (:qty) annulé, livré :delivered', { qty: '6', delivered: '4' }))
            .toBe('Reste (6) annulé, livré 4');
    });

    it('vide une valeur absente et tolère un texte manquant', () => {
        expect(fillPlaceholders('Annulée le :at — :reason', { at: '16/11/2026', reason: null }))
            .toBe('Annulée le 16/11/2026 — ');
        expect(fillPlaceholders(undefined, { qty: 1 })).toBe('');
    });

    it('exige le même motif minimal que le serveur', () => {
        expect(MIN_REASON_LENGTH).toBe(3);
    });
});
