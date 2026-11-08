import { describe, expect, it } from 'vitest';
import { impactTotal, proposedDeliveryDate, reworkPayload, toNumber, usesActualInternal } from '../../lib/ncImpact.js';

describe('toNumber', () => {
    it('lit la virgule décimale et ignore le vide', () => {
        expect(toNumber('12,5')).toBe(12.5);
        expect(toNumber(' 3 ')).toBe(3);
        expect(toNumber('')).toBeNull();
        expect(toNumber(null)).toBeNull();
        expect(toNumber('abc')).toBeNull();
    });
});

describe('impactTotal', () => {
    it('retient le coût interne saisi plutôt que le réel', () => {
        expect(impactTotal({ internal: '100', external: '20,5', scrap: 10, internalActual: 999 })).toBe(130.5);
        expect(usesActualInternal('100')).toBe(false);
    });

    it('retombe sur le coût réel des retouches sans coût interne saisi', () => {
        expect(impactTotal({ internal: '', external: null, scrap: '5', internalActual: 42.255 })).toBe(47.26);
        expect(usesActualInternal('')).toBe(true);
    });

    it('un coût interne saisi à zéro reste zéro', () => {
        expect(impactTotal({ internal: '0', internalActual: 80 })).toBe(0);
    });
});

describe('proposedDeliveryDate', () => {
    it('ajoute le délai à la date de la ligne', () => {
        expect(proposedDeliveryDate('2026-11-28', 5)).toBe('2026-12-03');
        expect(proposedDeliveryDate('2026-12-30 00:00:00', '3')).toBe('2027-01-02');
    });

    it('ne propose rien sans date ou sans délai positif', () => {
        expect(proposedDeliveryDate(null, 5)).toBeNull();
        expect(proposedDeliveryDate('2026-11-28', 0)).toBeNull();
        expect(proposedDeliveryDate('2026-11-28', '')).toBeNull();
    });
});

describe('reworkPayload', () => {
    it('ne garde que les opérations avec un poste, temps jamais négatif', () => {
        expect(reworkPayload([
            { methods_services_id: '4', hours: '1,5', label: '  Reprise soudure ' },
            { methods_services_id: '', hours: '2' },
            { methods_services_id: 7, hours: '-3', label: '' },
        ])).toEqual([
            { methods_services_id: 4, hours: 1.5, label: 'Reprise soudure' },
            { methods_services_id: 7, hours: 0, label: null },
        ]);
    });
});
