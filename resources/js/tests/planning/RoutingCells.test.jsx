import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { LinksCell, PlannedQtyCell } from '../../components/planning/RoutingCells.jsx';

const inRow = (cell) => render(<table><tbody><tr>{cell}</tr></tbody></table>);

describe('LinksCell — liens d\'une opération', () => {
    it('shows an implicit link in grey and an entered one as notation', () => {
        const { rerender } = inRow(<LinksCell info={{ implicit: true, notation: '', display: 'après 10' }} canEdit={false} onSave={vi.fn()} />);
        expect(screen.getByText('après 10')).toHaveClass('fst-italic');

        rerender(<table><tbody><tr><LinksCell info={{ implicit: false, notation: '20FD;30FD', display: '20FD;30FD' }} canEdit={false} onSave={vi.fn()} /></tr></tbody></table>);
        expect(screen.getByText('20FD;30FD').tagName).toBe('CODE');
    });

    it('saves the typed notation on Enter', async () => {
        const onSave = vi.fn().mockResolvedValue({ ok: true });
        inRow(<LinksCell info={{ implicit: true, notation: '', display: 'après 20' }} canEdit onSave={onSave} />);

        fireEvent.click(screen.getByText('après 20'));
        const input = screen.getByLabelText('Liens');
        expect(input).toHaveValue('');
        fireEvent.change(input, { target: { value: ' 20DD+1pc;20FF+1pc ' } });
        fireEvent.keyDown(input, { key: 'Enter' });

        await waitFor(() => expect(onSave).toHaveBeenCalledWith('20DD+1pc;20FF+1pc'));
        await waitFor(() => expect(screen.queryByLabelText('Liens')).toBeNull());
    });

    it('keeps the field open and shows the refusal', async () => {
        const onSave = vi.fn().mockResolvedValue({ ok: false, error: 'Liens circulaires : 20 → 10 → 20.' });
        inRow(<LinksCell info={{ implicit: false, notation: '20FD', display: '20FD' }} canEdit onSave={onSave} />);

        fireEvent.click(screen.getByText('20FD'));
        fireEvent.keyDown(screen.getByLabelText('Liens'), { key: 'Enter' });

        expect(await screen.findByText('Liens circulaires : 20 → 10 → 20.')).toBeInTheDocument();
        expect(screen.getByLabelText('Liens')).toHaveValue('20FD');
    });

    it('does not open when the document is locked', () => {
        inRow(<LinksCell info={{ implicit: true, notation: '', display: 'après 10' }} canEdit={false} onSave={vi.fn()} />);
        fireEvent.click(screen.getByText('après 10'));
        expect(screen.queryByLabelText('Liens')).toBeNull();
    });
});

describe('PlannedQtyCell — quantité de l\'opération', () => {
    it('shows the line quantity in grey when none is entered', () => {
        inRow(<PlannedQtyCell plannedQty={null} lineQty={100} canEdit={false} onSave={vi.fn()} />);
        expect(screen.getByText('100')).toHaveClass('text-muted');
    });

    it('saves a share of the batch, then clears it back to the line quantity', async () => {
        const onSave = vi.fn().mockResolvedValue({ ok: true });
        const { rerender } = inRow(<PlannedQtyCell plannedQty={null} lineQty={100} canEdit onSave={onSave} />);

        fireEvent.click(screen.getByText('100'));
        fireEvent.change(screen.getByLabelText('Quantité de l\'opération'), { target: { value: '60' } });
        fireEvent.keyDown(screen.getByLabelText('Quantité de l\'opération'), { key: 'Enter' });
        await waitFor(() => expect(onSave).toHaveBeenLastCalledWith(60));

        rerender(<table><tbody><tr><PlannedQtyCell plannedQty={60} lineQty={100} canEdit onSave={onSave} /></tr></tbody></table>);
        fireEvent.click(screen.getByText('60'));
        fireEvent.change(screen.getByLabelText('Quantité de l\'opération'), { target: { value: '' } });
        fireEvent.keyDown(screen.getByLabelText('Quantité de l\'opération'), { key: 'Enter' });
        await waitFor(() => expect(onSave).toHaveBeenLastCalledWith(null));
    });

    it('refuses a negative quantity without calling the server', async () => {
        const onSave = vi.fn();
        inRow(<PlannedQtyCell plannedQty={null} lineQty={10} canEdit onSave={onSave} />);

        fireEvent.click(screen.getByText('10'));
        fireEvent.change(screen.getByLabelText('Quantité de l\'opération'), { target: { value: '-3' } });
        fireEvent.keyDown(screen.getByLabelText('Quantité de l\'opération'), { key: 'Enter' });

        expect(await screen.findByText('Quantité invalide')).toBeInTheDocument();
        expect(onSave).not.toHaveBeenCalled();
    });
});
