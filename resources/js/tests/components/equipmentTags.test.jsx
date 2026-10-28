import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import {
    parseTags,
    formatTags,
    tagsExceedQty,
    EquipmentTagsInput,
    EquipmentTagBadges,
} from '../../lib/equipmentTags.jsx';

describe('equipmentTags', () => {
    it('splits on comma, semicolon and new line, trims and removes duplicates', () => {
        expect(parseTags(' 1RCP/001 ;1RCP/002\n1RCP/001,, \r\n2RRA010VP ')).toEqual(['1RCP/001', '1RCP/002', '2RRA010VP']);
        expect(parseTags('')).toEqual([]);
        expect(parseTags(null)).toEqual([]);
        expect(parseTags(['A', ' A ', 'B'])).toEqual(['A', 'B']);
    });

    it('formats the server list for the field and back', () => {
        expect(formatTags(['A', 'B'])).toBe('A, B');
        expect(formatTags(undefined)).toBe('');
        expect(parseTags(formatTags(['1RCP001PO', '1RCP002PO']))).toEqual(['1RCP001PO', '1RCP002PO']);
    });

    it('warns only when there are more tags than items', () => {
        expect(tagsExceedQty(['A', 'B', 'C'], 2)).toBe(true);
        expect(tagsExceedQty(['A', 'B'], 2)).toBe(false);
        expect(tagsExceedQty(['A'], '')).toBe(false);
        expect(tagsExceedQty(['A', 'B'], 0)).toBe(false);
    });

    it('shows a count and a non-blocking warning in the drawer field', () => {
        const onChange = vi.fn();
        render(<EquipmentTagsInput value="A, B, C" qty={2} onChange={onChange}
            trans={{ label: 'Repères', too_many: ':count repères pour une quantité de :qty' }} />);

        expect(screen.getByText('3')).toBeTruthy();
        expect(screen.getByText('3 repères pour une quantité de 2')).toBeTruthy();

        fireEvent.change(screen.getByLabelText(/Repères/), { target: { value: 'A' } });
        expect(onChange).toHaveBeenCalledWith('A');
    });

    it('renders nothing for a line without tags', () => {
        const { container } = render(<EquipmentTagBadges tags={[]} />);
        expect(container.innerHTML).toBe('');

        render(<EquipmentTagBadges tags={['1RCP001PO', '1RCP002PO']} />);
        expect(screen.getByText('1RCP001PO')).toBeTruthy();
        expect(screen.getByText('1RCP002PO')).toBeTruthy();
    });
});
