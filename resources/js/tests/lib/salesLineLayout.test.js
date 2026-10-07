import { describe, it, expect } from 'vitest';
import { computeLayout, moveLine, lineNet, isArticle } from '../../lib/salesLineLayout';

const article = (id, ordre, qty, price, extra = {}) => ({ id, ordre, line_type: 'article', qty, effective_price: price, discount: 0, ...extra });
const section = (id, ordre, extra = {}) => ({ id, ordre, line_type: 'section', label: `S${id}`, pdf_package: 0, ...extra });
const subtotal = (id, ordre) => ({ id, ordre, line_type: 'subtotal', label: '' });
const text = (id, ordre) => ({ id, ordre, line_type: 'text', label: 'note' });

describe('salesLineLayout', () => {
    it('treats a line without line_type as an article', () => {
        expect(isArticle({ id: 1 })).toBe(true);
        expect(lineNet({ qty: 2, effective_price: 10, discount: 10 })).toBe(18);
    });

    it('computes section totals and subtotals, hidden lines included', () => {
        const lines = [
            section(1, 1),
            article(2, 2, 12, 85),
            article(3, 3, 1, 960, { hide_on_pdf: true }),
            subtotal(4, 4),
            section(5, 5),
            article(6, 6, 1, 100),
            subtotal(7, 7),
        ];
        const { byId, hiddenOutsidePackage } = computeLayout(lines);

        expect(byId[1].amount).toBe(1980);
        expect(byId[4].amount).toBe(1980);
        expect(byId[5].amount).toBe(100);
        expect(byId[7].amount).toBe(100);
        expect(hiddenOutsidePackage).toEqual({ count: 1, amount: 960 });
    });

    it('does not warn about hidden lines inside a package section', () => {
        const lines = [
            section(1, 1, { pdf_package: 1 }),
            article(2, 2, 1, 500, { hide_on_pdf: true }),
            article(3, 3, 1, 300),
        ];
        const { byId, hiddenOutsidePackage } = computeLayout(lines);

        expect(byId[1].amount).toBe(800);
        expect(byId[3].inPackage).toBe(true);
        expect(hiddenOutsidePackage.count).toBe(0);
    });

    it('sums from the document start when there is no section', () => {
        const { byId } = computeLayout([article(1, 1, 1, 10), article(2, 2, 2, 5), subtotal(3, 3), text(4, 4)]);
        expect(byId[3].amount).toBe(20);
        expect(byId[4].amount).toBeNull();
    });

    it('starts each subtotal from the previous one', () => {
        const lines = [
            section(1, 1),
            article(2, 2, 1, 100), subtotal(3, 3),
            article(4, 4, 1, 40), article(5, 5, 1, 2), subtotal(6, 6),
        ];
        const { byId } = computeLayout(lines);

        expect(byId[3].amount).toBe(100);
        expect(byId[6].amount).toBe(42);
        expect(byId[1].amount).toBe(142);
    });

    it('flags a section with nothing below it and does not treat it as a package', () => {
        const lines = [
            article(1, 1, 1, 10, { hide_on_pdf: true }),
            section(2, 2, { pdf_package: 2 }),
        ];
        const { byId, hiddenOutsidePackage } = computeLayout(lines);

        expect(byId[2].empty).toBe(true);
        expect(byId[2].amount).toBe(0);
        expect(hiddenOutsidePackage.count).toBe(1);
    });

    it('moves a single line exactly like the original drag and drop', () => {
        const lines = [article(1, 1, 1, 1), article(2, 2, 1, 1), article(3, 3, 1, 1)];

        expect(moveLine(lines, 1, 3).map((l) => l.id)).toEqual([2, 3, 1]);
        expect(moveLine(lines, 3, 1).map((l) => l.id)).toEqual([3, 1, 2]);
        expect(moveLine(lines, 1, 2).map((l) => l.id)).toEqual([2, 1, 3]);
    });

    it('moves a section with all its lines and renumbers ordre', () => {
        const lines = [
            section(1, 1), article(2, 2, 1, 1), article(3, 3, 1, 1),
            section(4, 4), article(5, 5, 1, 1),
        ];
        const moved = moveLine(lines, 4, 1);

        expect(moved.map((l) => l.id)).toEqual([4, 5, 1, 2, 3]);
        expect(moved.map((l) => l.ordre)).toEqual([1, 2, 3, 4, 5]);
    });

    it('ignores a section dropped inside its own block', () => {
        const lines = [section(1, 1), article(2, 2, 1, 1), article(3, 3, 1, 1)];
        expect(moveLine(lines, 1, 3).map((l) => l.id)).toEqual([1, 2, 3]);
    });
});
