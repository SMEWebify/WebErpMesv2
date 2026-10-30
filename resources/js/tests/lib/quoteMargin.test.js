import { describe, expect, it } from 'vitest';
import {
    formatMarginRate,
    marginLevel,
    MARGIN_COLUMN_STORAGE_KEY,
    readMarginColumnVisible,
    writeMarginColumnVisible,
} from '../../lib/quoteMargin.js';

const memoryStorage = () => {
    const data = new Map();
    return {
        getItem: (k) => (data.has(k) ? data.get(k) : null),
        setItem: (k, v) => data.set(k, String(v)),
    };
};

describe('marginLevel', () => {
    it('ne met rien en évidence sans coût de revient ni sur une ligne de présentation', () => {
        expect(marginLevel(null)).toBe('none');
        expect(marginLevel(undefined)).toBe('none');
        expect(marginLevel({ has_cost: false, margin_rate: null })).toBe('none');
    });

    it('signale une marge sous le seuil (0 % par défaut : marge négative)', () => {
        expect(marginLevel({ has_cost: true, margin_rate: -5 })).toBe('below');
        expect(marginLevel({ has_cost: true, margin_rate: 0 })).toBe('ok');
        expect(marginLevel({ has_cost: true, margin_rate: 12 })).toBe('ok');
    });

    it('applique le seuil de la configuration', () => {
        expect(marginLevel({ has_cost: true, margin_rate: 12 }, 15)).toBe('below');
        expect(marginLevel({ has_cost: true, margin_rate: 15 }, 15)).toBe('ok');
    });
});

describe('formatMarginRate', () => {
    it('affiche un tiret sans taux', () => {
        expect(formatMarginRate(null)).toBe('—');
        expect(formatMarginRate(undefined)).toBe('—');
    });

    it('arrondit au dixième', () => {
        expect(formatMarginRate(12.345, 'en-US')).toBe('12.3 %');
        expect(formatMarginRate(-4, 'en-US')).toBe('-4.0 %');
    });
});

describe('préférence de la colonne marge', () => {
    it('est visible par défaut et se mémorise', () => {
        const storage = memoryStorage();
        expect(readMarginColumnVisible(storage)).toBe(true);

        writeMarginColumnVisible(false, storage);
        expect(storage.getItem(MARGIN_COLUMN_STORAGE_KEY)).toBe('0');
        expect(readMarginColumnVisible(storage)).toBe(false);

        writeMarginColumnVisible(true, storage);
        expect(readMarginColumnVisible(storage)).toBe(true);
    });

    it('retombe sur le défaut quand le stockage est inaccessible', () => {
        const broken = { getItem: () => { throw new Error('denied'); }, setItem: () => { throw new Error('denied'); } };
        expect(readMarginColumnVisible(broken)).toBe(true);
        expect(() => writeMarginColumnVisible(false, broken)).not.toThrow();
    });
});
