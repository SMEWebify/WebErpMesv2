import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import {
    affairCategoryColumn,
    affairCategoryLabel,
    resolveCategoryFilter,
    AffairCategoryFilter,
} from '../../components/affairs/affairCategory.jsx';
import { matchesFilter } from '../../components/table';

const CATEGORIES = [
    { id: 1, code: 'DEBIT', label: 'Débitmétrie', active: true },
    { id: 2, code: 'OLD',   label: 'Ancienne',    active: false },
];
const TRANS = { affair_category: 'Catégorie', all_affair_categories: 'Toutes', affair_category_inactive: 'inactive' };

describe('resolveCategoryFilter', () => {
    it('garde une catégorie connue, en chaîne', () => {
        expect(resolveCategoryFilter(1, CATEGORIES)).toBe('1');
        expect(resolveCategoryFilter('2', CATEGORIES)).toBe('2');
    });

    it('retombe sur « toutes » pour une catégorie inconnue ou absente', () => {
        expect(resolveCategoryFilter('9', CATEGORIES)).toBe('');
        expect(resolveCategoryFilter('', CATEGORIES)).toBe('');
        expect(resolveCategoryFilter(null, CATEGORIES)).toBe('');
        expect(resolveCategoryFilter('1', [])).toBe('');
    });
});

describe('affairCategoryColumn', () => {
    const col = affairCategoryColumn(TRANS);

    it('filtre la page sur le libellé, ligne sans catégorie comprise', () => {
        const tagged = { affair_category: { id: 1, code: 'DEBIT', label: 'Débitmétrie' } };
        expect(affairCategoryLabel(tagged)).toBe('Débitmétrie');
        expect(matchesFilter(col, tagged, 'débit')).toBe(true);
        expect(matchesFilter(col, tagged, 'pyro')).toBe(false);
        expect(matchesFilter(col, { affair_category: null }, 'débit')).toBe(false);
        expect(matchesFilter(col, { affair_category: null }, '')).toBe(true);
    });
});

describe('AffairCategoryFilter', () => {
    it("n'affiche rien tant que le référentiel est vide", () => {
        const { container } = render(<AffairCategoryFilter categories={[]} value="" onChange={() => {}} trans={TRANS} />);
        expect(container.innerHTML).toBe('');
    });

    it('propose toutes les catégories et signale les inactives', () => {
        const onChange = vi.fn();
        render(<AffairCategoryFilter categories={CATEGORIES} value="" onChange={onChange} trans={TRANS} />);
        expect(screen.getByRole('option', { name: 'Ancienne (inactive)' })).toBeTruthy();
        fireEvent.change(screen.getByRole('combobox'), { target: { value: '1' } });
        expect(onChange).toHaveBeenLastCalledWith('1');
    });

    it('remet à « toutes » une catégorie mémorisée disparue', () => {
        const onChange = vi.fn();
        render(<AffairCategoryFilter categories={CATEGORIES} value="9" onChange={onChange} trans={TRANS} />);
        expect(onChange).toHaveBeenCalledWith('');
    });
});
