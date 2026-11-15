import { describe, it, expect } from 'vitest';
import { toDrafts, toPayload, appendText, fill } from '../../lib/quoteDictation.js';
import { proposalState, fillMax } from '../../lib/aiRewrite.js';

describe('quoteDictation', () => {
    it('turns the proposal into editable drafts, all kept', () => {
        const drafts = toDrafts([
            { label: 'Garde-corps', qty: 1, methods_units_id: 3, unit_text: null, selling_price: null, material: 'S235', internal_comment: null },
        ]);
        expect(drafts[0]).toMatchObject({ key: 0, keep: true, label: 'Garde-corps', methods_units_id: 3, selling_price: '', material: 'S235', internal_comment: '' });
    });

    it('sends only kept lines with a description, price 0 when not dictated', () => {
        const payload = toPayload([
            { keep: true, label: ' Garde-corps ', qty: '2,5', methods_units_id: '3', selling_price: '', material: ' ', internal_comment: 'Ø42' },
            { keep: false, label: 'Pose', qty: 1 },
            { keep: true, label: '  ', qty: 1 },
            { keep: true, label: 'Transport', qty: 'abc', methods_units_id: '', selling_price: '120.5' },
        ]);
        expect(payload).toEqual([
            { label: 'Garde-corps', qty: 2.5, methods_units_id: 3, selling_price: 0, material: null, internal_comment: 'Ø42' },
            { label: 'Transport', qty: 1, methods_units_id: null, selling_price: 120.5, material: null, internal_comment: null },
        ]);
    });

    it('appends dictated text after what is already typed', () => {
        expect(appendText('', ' garde-corps ')).toBe('garde-corps');
        expect(appendText('garde-corps', 'pose')).toBe('garde-corps pose');
        expect(appendText('garde-corps', '  ')).toBe('garde-corps');
    });

    it('fills placeholders', () => {
        expect(fill('Créer :count ligne(s)', { count: 2 })).toBe('Créer 2 ligne(s)');
    });
});

describe('aiRewrite', () => {
    it('tells an unchanged, too long or usable proposal apart', () => {
        expect(proposalState('Pose comprise.', ' Pose comprise. ', 255)).toBe('unchanged');
        expect(proposalState('pose', 'x'.repeat(256), 255)).toBe('too_long');
        expect(proposalState('pose', 'x'.repeat(256), '')).toBe('ok');
        expect(proposalState('pose comprize', 'Pose comprise.', 255)).toBe('ok');
    });

    it('fills the maximum length', () => {
        expect(fillMax('au plus :max caractères', 255)).toBe('au plus 255 caractères');
    });
});
