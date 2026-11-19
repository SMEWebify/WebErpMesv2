import { describe, it, expect } from 'vitest';
import { toDrafts, toPayload, summarize } from '../../lib/dpgfImport.js';

const preview = [
    { row: 5, type: 'section', code: '', label: '8.1 GARDE-CORPS', qty: null, methods_units_id: null, unit_text: null, selling_price: null },
    { row: 6, type: 'article', code: '8.1.1', label: 'Garde-corps', qty: 24.5, methods_units_id: null, unit_text: 'ml', selling_price: null, qty_missing: false },
    { row: 7, type: 'article', code: '8.1.2', label: 'Habillage', qty: 0, methods_units_id: 3, unit_text: null, selling_price: 85.5, qty_missing: true, description: 'Texte long' },
    { row: 8, type: 'subtotal', code: '', label: 'Total lot 8.1' },
    { row: 9, type: 'ignore', code: '', label: 'TOTAL TTC' },
    { row: 10, type: 'text', code: '', label: '   ' },
];

describe('import DPGF', () => {
    it('garde le numéro de ligne du fichier comme clé', () => {
        const drafts = toDrafts(preview);
        expect(drafts.map((d) => d.key)).toEqual([5, 6, 7, 8, 9, 10]);
        expect(drafts[1].methods_units_id).toBe('');
        expect(drafts[2].qty_missing).toBe(true);
    });

    it('n\'envoie ni les lignes ignorées ni les textes vides, et seulement les champs utiles', () => {
        const payload = toPayload(toDrafts(preview));
        expect(payload.map((l) => l.type)).toEqual(['section', 'article', 'article', 'subtotal']);
        expect(payload[0]).toEqual({ type: 'section', label: '8.1 GARDE-CORPS', description: null });
        expect(payload[1]).toMatchObject({ code: '8.1.1', qty: 24.5, methods_units_id: null, selling_price: 0 });
        expect(payload[2]).toMatchObject({ methods_units_id: 3, selling_price: 85.5, description: 'Texte long' });
    });

    it('un changement de type par l\'utilisateur est respecté', () => {
        const drafts = toDrafts(preview).map((d) => (d.row === 9 ? { ...d, type: 'subtotal' } : d.row === 6 ? { ...d, type: 'ignore' } : d));
        expect(toPayload(drafts).map((l) => l.type)).toEqual(['section', 'article', 'subtotal', 'subtotal']);
    });

    it('compte les types et signale quantités et unités à reprendre', () => {
        const s = summarize(toDrafts(preview));
        expect(s.counts).toEqual({ article: 2, section: 1, subtotal: 1, text: 1, ignore: 1 });
        expect(s.qtyMissing).toBe(1);
        expect(s.unitUnknown).toBe(1);
        expect(s.kept).toBe(4);
    });
});
