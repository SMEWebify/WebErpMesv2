import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import Pagination, { paginationItems } from '../../components/table/Pagination.jsx';

const meta = (current_page, last_page, total = last_page * 15) => ({ current_page, last_page, total, per_page: 15 });
const labels = container => [...container.querySelectorAll('.page-link')].map(el => el.textContent);

describe('paginationItems', () => {
    it('liste toutes les pages sans fenêtre', () => {
        expect(paginationItems(3, 5)).toEqual([1, 2, 3, 4, 5]);
    });

    it('fenêtre avec bornes : 1, dernière et ellipses dans les trous', () => {
        expect(paginationItems(6, 12, 2, true)).toEqual([1, null, 4, 5, 6, 7, 8, null, 12]);
        expect(paginationItems(1, 12, 2, true)).toEqual([1, 2, 3, null, 12]);
        // un trou d'une seule page reste une ellipse, comme dans les écrans d'origine
        expect(paginationItems(5, 12, 1, true)).toEqual([1, null, 4, 5, 6, null, 12]);
    });

    it('fenêtre sans bornes : ellipse à chaque bout tronqué', () => {
        expect(paginationItems(6, 12, 2, false)).toEqual([null, 4, 5, 6, 7, 8, null]);
        expect(paginationItems(2, 3, 2, false)).toEqual([1, 2, 3]);
    });
});

describe('Pagination', () => {
    it("ne rend rien s'il n'y a qu'une page ou pas de meta", () => {
        const { container: a } = render(<Pagination meta={meta(1, 1)} onPage={() => {}} />);
        const { container: b } = render(<Pagination meta={null} onPage={() => {}} />);
        expect(a.innerHTML).toBe('');
        expect(b.innerHTML).toBe('');
    });

    it('« » par défaut, désactivés aux extrémités', () => {
        const { container } = render(<Pagination meta={meta(1, 3)} onPage={() => {}} />);
        expect(labels(container)).toEqual(['«', '1', '2', '3', '»']);
        const items = container.querySelectorAll('.page-item');
        expect(items[0]).toHaveClass('disabled');
        expect(items[1]).toHaveClass('active');
        expect(items[4]).not.toHaveClass('disabled');
    });

    it('jumpButtons : première, précédente, suivante, dernière', () => {
        const onPage = vi.fn();
        const { container } = render(<Pagination meta={meta(5, 9)} onPage={onPage} around={2} jumpButtons />);
        expect(labels(container)).toEqual(['«', '‹', '…', '3', '4', '5', '6', '7', '…', '›', '»']);
        fireEvent.click(screen.getByText('«'));
        fireEvent.click(screen.getByText('‹'));
        fireEvent.click(screen.getByText('›'));
        fireEvent.click(screen.getByText('»'));
        fireEvent.click(screen.getByText('7'));
        expect(onPage.mock.calls.map(c => c[0])).toEqual([1, 4, 6, 9, 7]);
    });

    it('prevNext={false} : numéros seuls', () => {
        const { container } = render(<Pagination meta={meta(2, 3)} onPage={() => {}} prevNext={false} />);
        expect(labels(container)).toEqual(['1', '2', '3']);
    });

    it('showTotal : singulier et pluriel', () => {
        const { container, rerender } = render(<Pagination meta={meta(1, 2, 1)} onPage={() => {}} showTotal />);
        expect(container.querySelector('small').textContent).toBe('1 résultat');
        rerender(<Pagination meta={meta(1, 2, 30)} onPage={() => {}} showTotal />);
        expect(container.querySelector('small').textContent).toBe('30 résultats');
    });

    it('applique les classes de nav et de liste fournies par l’écran', () => {
        const { container } = render(
            <Pagination meta={meta(1, 2)} onPage={() => {}} navClassName="mt-2" ulClassName="pagination pagination-sm m-0 flex-wrap" />
        );
        expect(container.querySelector('nav')).toHaveClass('mt-2');
        expect(container.querySelector('ul').className).toBe('pagination pagination-sm m-0 flex-wrap');
    });
});
