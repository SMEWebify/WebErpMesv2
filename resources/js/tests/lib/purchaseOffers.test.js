import { describe, expect, it } from 'vitest';
import { offerLabel, quotationLocks } from '../../lib/purchaseOffers';

const trans = { revision_short: 'ind.', no_revision: 'sans indice' };

describe('offerLabel', () => {
    it('joins reference and revision', () => {
        expect(offerLabel({ reference: 'OF-12', revision: 'B' }, trans)).toBe('OF-12 · ind. B');
    });

    it('keeps the part that is filled', () => {
        expect(offerLabel({ reference: 'OF-12', revision: null }, trans)).toBe('OF-12');
        expect(offerLabel({ reference: ' ', revision: 'C' }, trans)).toBe('ind. C');
    });

    it('falls back to "no revision"', () => {
        expect(offerLabel({}, trans)).toBe('sans indice');
        expect(offerLabel(null, trans)).toBe('sans indice');
    });
});

describe('quotationLocks', () => {
    it('locks a superseded revision whatever its status', () => {
        const locks = quotationLocks({ statu: 1, superseded: true, revision_blocked: 'superseded' });
        expect(locks).toEqual({ superseded: true, canEditLines: false, canOrder: false, canRevise: false });
    });

    it('keeps the former rules on the latest revision', () => {
        expect(quotationLocks({ statu: 1, superseded: false, revision_blocked: null }))
            .toEqual({ superseded: false, canEditLines: true, canOrder: true, canRevise: true });
        expect(quotationLocks({ statu: 2, superseded: false, revision_blocked: null }).canEditLines).toBe(false);
        expect(quotationLocks({ statu: 6, superseded: false, revision_blocked: 'ordered' }))
            .toMatchObject({ canOrder: false, canRevise: false });
    });
});
