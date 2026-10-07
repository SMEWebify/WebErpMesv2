import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { SortIcon, StatusBadge, StatusFilter } from '../../components/table';

const CONFIG = {
    1: { badge: 'badge-info',    label: 'open' },
    2: { badge: 'badge-success', label: 'won' },
    3: { badge: 'badge-danger',  label: 'untranslated' },
};
const TRANS = { open: 'Ouvert', won: 'Gagné' };

describe('SortIcon', () => {
    it('neutre hors colonne triée ou sans champ', () => {
        const { container: a } = render(<SortIcon field="code" sortField="label" sortAsc />);
        const { container: b } = render(<SortIcon field={null} sortField="label" sortAsc />);
        expect(a.firstChild.className).toBe('fas fa-sort text-muted ml-1');
        expect(b.firstChild.className).toBe('fas fa-sort text-muted ml-1');
    });

    it('flèche selon le sens sur la colonne triée', () => {
        const { container: a } = render(<SortIcon field="code" sortField="code" sortAsc />);
        const { container: d } = render(<SortIcon field="code" sortField="code" sortAsc={false} />);
        expect(a.firstChild).toHaveClass('fa-sort-up');
        expect(d.firstChild).toHaveClass('fa-sort-down');
    });

    it('size="sm" réduit l’icône', () => {
        const { container } = render(<SortIcon field="code" sortField="code" sortAsc size="sm" />);
        expect(container.firstChild.style.fontSize).toBe('0.7rem');
    });
});

describe('StatusBadge', () => {
    it('traduit le libellé et applique la couleur du statut', () => {
        render(<StatusBadge statu={2} config={CONFIG} trans={TRANS} />);
        expect(screen.getByText('Gagné')).toHaveClass('badge', 'badge-success');
    });

    it("fallback 'key' : clé de traduction, ou statut brut s'il est inconnu", () => {
        const { container, rerender } = render(<StatusBadge statu={3} config={CONFIG} trans={TRANS} />);
        expect(container.textContent).toBe('untranslated');
        rerender(<StatusBadge statu={9} config={CONFIG} trans={TRANS} />);
        expect(container.textContent).toBe('9');
        expect(container.firstChild).toHaveClass('badge-secondary');
    });

    it("fallback 'value' : statut brut", () => {
        const { container } = render(<StatusBadge statu={3} config={CONFIG} trans={TRANS} fallback="value" />);
        expect(container.textContent).toBe('3');
    });
});

describe('StatusFilter', () => {
    it('un bouton par statut, coloré quand il est actif', () => {
        render(<StatusFilter config={CONFIG} selected={[1]} onToggle={() => {}} trans={TRANS} />);
        expect(screen.getByText('Ouvert')).toHaveClass('btn-info');
        expect(screen.getByText('Gagné')).toHaveClass('btn-outline-secondary');
    });

    it("onToggle : l'écran reçoit l'identifiant et gère la liste", () => {
        const onToggle = vi.fn();
        render(<StatusFilter config={CONFIG} selected={[]} onToggle={onToggle} trans={TRANS} />);
        fireEvent.click(screen.getByText('Gagné'));
        expect(onToggle).toHaveBeenCalledWith(2);
    });

    it('onChange : ajoute et retire', () => {
        const onChange = vi.fn();
        render(<StatusFilter config={CONFIG} selected={[1]} onChange={onChange} trans={TRANS} />);
        fireEvent.click(screen.getByText('Gagné'));
        fireEvent.click(screen.getByText('Ouvert'));
        expect(onChange.mock.calls).toEqual([[[1, 2]], [[]]]);
    });

    it('allowEmpty={false} : le dernier statut coché le reste', () => {
        const onChange = vi.fn();
        render(<StatusFilter config={CONFIG} selected={[1]} onChange={onChange} trans={TRANS} allowEmpty={false} />);
        fireEvent.click(screen.getByText('Ouvert'));
        expect(onChange).toHaveBeenCalledWith([1]);
    });

    it('ids restreint et ordonne les boutons', () => {
        const { container } = render(<StatusFilter config={CONFIG} ids={[2, 1]} selected={[]} onToggle={() => {}} trans={TRANS} />);
        expect([...container.querySelectorAll('button')].map(b => b.textContent)).toEqual(['Gagné', 'Ouvert']);
    });

    it('buttonType pose l’attribut type', () => {
        render(<StatusFilter config={CONFIG} selected={[]} onToggle={() => {}} trans={TRANS} buttonType="button" />);
        expect(screen.getByText('Ouvert')).toHaveAttribute('type', 'button');
    });
});
