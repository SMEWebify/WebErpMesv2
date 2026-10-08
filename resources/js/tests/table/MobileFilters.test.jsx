import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, afterEach } from 'vitest';
import { MobileFilters } from '../../components/table';

const mobile = matches => {
    window.matchMedia = q => ({ matches, media: q, addEventListener: () => {}, removeEventListener: () => {} });
};

afterEach(() => { delete window.matchMedia; });

describe('MobileFilters', () => {
    it('sur PC rend les filtres tels quels, sans bouton', () => {
        mobile(false);
        const { container } = render(<MobileFilters count={2}><div className="grp">Statuts</div></MobileFilters>);
        expect(container.innerHTML).toBe('<div class="grp">Statuts</div>');
    });

    it('sur mobile replie les filtres derrière un bouton qui compte les filtres actifs', () => {
        mobile(true);
        render(<MobileFilters count={2} trans={{ filters: 'Filtres' }}><div>Statuts</div></MobileFilters>);
        expect(screen.queryByText('Statuts')).toBeNull();

        const toggle = screen.getByRole('button', { name: /Filtres/ });
        expect(toggle).toHaveTextContent('2');
        expect(toggle).toHaveAttribute('aria-expanded', 'false');

        fireEvent.click(toggle);
        expect(screen.getByText('Statuts')).toBeInTheDocument();
        expect(toggle).toHaveAttribute('aria-expanded', 'true');
    });

    it("n'affiche pas de compteur quand aucun filtre ne restreint la liste", () => {
        mobile(true);
        render(<MobileFilters count={0}><div>Statuts</div></MobileFilters>);
        expect(document.querySelector('.badge')).toBeNull();
    });
});
