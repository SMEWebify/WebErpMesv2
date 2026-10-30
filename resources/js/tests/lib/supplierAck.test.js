import { describe, it, expect } from 'vitest';
import {
    canAcknowledge, referenceDate, gapDays, gapBadgeClass, formatGap, ackDraftLines, ackPayload,
} from '../../lib/supplierAck';

describe('supplierAck', () => {
    it('allows an acknowledgement only on a placed order', () => {
        expect(canAcknowledge(1)).toBe(false);
        expect(canAcknowledge(5)).toBe(false);
        expect(canAcknowledge(2)).toBe(true);
        expect(canAcknowledge('3')).toBe(true);
        expect(canAcknowledge(4)).toBe(true);
    });

    it('uses the confirmed date as reference, otherwise the requested one', () => {
        expect(referenceDate({ delivery_date: '2026-11-10', confirmed_delivery_date: '2026-11-14' })).toBe('2026-11-14');
        expect(referenceDate({ delivery_date: '2026-11-10', confirmed_delivery_date: null })).toBe('2026-11-10');
        expect(referenceDate({ delivery_date: null })).toBeNull();
    });

    it('computes the gap between confirmed and requested dates', () => {
        expect(gapDays('2026-11-10', '2026-11-14')).toBe(4);
        expect(gapDays('2026-11-10', '2026-11-08')).toBe(-2);
        expect(gapDays('2026-11-10', '2026-11-10')).toBe(0);
        // Changement d'heure (fin octobre) : toujours un nombre entier de jours
        expect(gapDays('2026-10-24', '2026-10-26')).toBe(2);
        expect(gapDays(null, '2026-11-10')).toBeNull();
        expect(gapBadgeClass(4)).toBe('text-bg-danger');
        expect(gapBadgeClass(-2)).toBe('text-bg-success');
        expect(gapBadgeClass(0)).toBeNull();
        expect(formatGap(4)).toBe('+4 j');
        expect(formatGap(-2, 'd')).toBe('−2 d');
    });

    it('proposes the lines without acknowledgement, with the requested date by default', () => {
        const lines = [
            { id: 1, qty: 2, delivery_date: '2026-11-10', confirmed_delivery_date: null, supplier_ack_at: null },
            { id: 2, qty: 1, delivery_date: '2026-11-12', confirmed_delivery_date: '2026-11-15', supplier_ack_at: '2026-10-28' },
            { id: 3, qty: 0, delivery_date: '2026-11-12', supplier_ack_at: null },
        ];

        expect(ackDraftLines(lines)).toEqual([
            { id: 1, checked: true, confirmed_delivery_date: '2026-11-10' },
            { id: 2, checked: false, confirmed_delivery_date: '2026-11-15' },
        ]);
        expect(ackDraftLines(lines, 2).map((l) => l.checked)).toEqual([false, true]);
        // Toutes les lignes ont un AR : nouvel AR (modifié) proposé sur toutes
        expect(ackDraftLines([lines[1]])[0].checked).toBe(true);
    });

    it('sends only the checked lines', () => {
        const draft = [
            { id: 1, checked: true, confirmed_delivery_date: '2026-11-10' },
            { id: 2, checked: false, confirmed_delivery_date: '2026-11-15' },
            { id: 4, checked: true, confirmed_delivery_date: '' },
        ];

        expect(ackPayload(draft, '2026-10-30', '  AR-123 ')).toEqual({
            ack_at: '2026-10-30',
            reference: 'AR-123',
            lines: [
                { id: 1, confirmed_delivery_date: '2026-11-10' },
                { id: 4, confirmed_delivery_date: null },
            ],
        });
        expect(ackPayload(draft, '2026-10-30', '').reference).toBeNull();
    });
});
