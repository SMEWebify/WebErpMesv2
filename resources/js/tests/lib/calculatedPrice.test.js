import { describe, it, expect } from 'vitest';
import { calculatedPriceToggle, isValidReason } from '../../lib/calculatedPrice.js';

describe('calculatedPrice', () => {
    it('laisse activer une ligne de commande en préparation, sans motif', () => {
        const t = calculatedPriceToggle({ enabled: false, can_enable: true, needs_reason: false, blocked_reason: null });
        expect(t).toEqual({ enabled: false, disabled: false, needsReason: false, explanation: null });
    });

    it('grise l’interrupteur avec l’explication quand l’activation est refusée', () => {
        const t = calculatedPriceToggle({ enabled: false, can_enable: false, needs_reason: false, blocked_reason: 'Prix figé' });
        expect(t.disabled).toBe(true);
        expect(t.explanation).toBe('Prix figé');
        expect(t.needsReason).toBe(false);
    });

    it('demande un motif au porteur de la permission sur une commande validée', () => {
        const t = calculatedPriceToggle({ enabled: false, can_enable: true, needs_reason: true, blocked_reason: null });
        expect(t.disabled).toBe(false);
        expect(t.needsReason).toBe(true);
    });

    it('laisse toujours désactiver', () => {
        const t = calculatedPriceToggle({ enabled: true, can_enable: false, needs_reason: false, blocked_reason: null });
        expect(t.disabled).toBe(false);
        expect(t.enabled).toBe(true);
    });

    it('retombe sur le drapeau sans état serveur', () => {
        expect(calculatedPriceToggle(null, true).enabled).toBe(true);
        expect(calculatedPriceToggle(undefined, false).disabled).toBe(false);
    });

    it('exige un motif d’au moins 3 caractères', () => {
        expect(isValidReason('  ok ')).toBe(false);
        expect(isValidReason('Avenant client')).toBe(true);
        expect(isValidReason(null)).toBe(false);
    });
});
