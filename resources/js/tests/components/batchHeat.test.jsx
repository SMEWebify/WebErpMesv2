import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { BatchFields, BatchList, createsUntrackedBatch, initialBatchForm } from '../../components/PurchaseReceiptLinesPage.jsx';
import { ConsumedBatches } from '../../components/TaskStatuApp.jsx';

const TEXTS = {
    section: 'Lot / coulée', hint: 'Un lot est créé…', heat_number: 'N° de coulée', heat_short: 'Coulée',
    supplier_batch_code: 'Lot fournisseur', certificate_type: 'Certificat EN 10204', none: '—',
};

describe('initialBatchForm', () => {
    it('reprend le certificat reçu de la ligne de réception', () => {
        expect(initialBatchForm({ received_certificate_type: '3.1' })).toEqual({
            heat_number: '', supplier_batch_code: '', certificate_type: '3.1',
        });
    });

    it('part vide sans ligne', () => {
        expect(initialBatchForm(null).certificate_type).toBe('');
    });
});

describe('BatchFields', () => {
    it('remonte la coulée saisie et garde un certificat hors liste', () => {
        const onChange = vi.fn();
        render(
            <BatchFields
                form={{ heat_number: '', supplier_batch_code: '', certificate_type: '3.2X' }}
                onChange={onChange}
                certificateTypes={['2.1', '2.2', '3.1', '3.2']}
                texts={TEXTS}
            />
        );

        fireEvent.change(screen.getByLabelText('N° de coulée'), { target: { value: 'C-48213' } });
        expect(onChange).toHaveBeenCalledWith('heat_number', 'C-48213');

        const options = Array.from(screen.getByLabelText('Certificat EN 10204').options).map((o) => o.value);
        expect(options).toEqual(['', '2.1', '2.2', '3.1', '3.2', '3.2X']);
    });

    it('affiche l\'erreur de validation du certificat', () => {
        render(
            <BatchFields form={initialBatchForm(null)} onChange={() => {}} certificateTypes={['3.1']}
                texts={TEXTS} errors={{ certificate_type: ['Valeur invalide'] }} />
        );
        expect(screen.getByText('Valeur invalide')).toBeTruthy();
    });
});

describe('lot créé sur un article non suivi', () => {
    const untracked = { ...TEXTS, untracked_warning: 'L\'article ne demande aucune traçabilité, mais un lot va être créé.' };
    const withHeat = { heat_number: 'C-1', supplier_batch_code: '', certificate_type: '' };

    it('ne prévient que si une coulée ou un lot fournisseur est saisi sur un article non suivi', () => {
        expect(createsUntrackedBatch(withHeat, 1)).toBe(true);
        expect(createsUntrackedBatch({ ...withHeat, heat_number: '', supplier_batch_code: 'L-7' }, null)).toBe(true);
        expect(createsUntrackedBatch(withHeat, 2)).toBe(false);
        expect(createsUntrackedBatch({ ...withHeat, heat_number: '  ' }, 1)).toBe(false);
    });

    it('affiche l\'avertissement à la place de l\'aide', () => {
        const { rerender } = render(<BatchFields form={withHeat} onChange={() => {}} tracabilityType={1} texts={untracked} />);
        expect(screen.getByRole('status').textContent).toContain('aucune traçabilité');
        expect(screen.queryByText(TEXTS.hint)).toBeNull();

        rerender(<BatchFields form={withHeat} onChange={() => {}} tracabilityType={2} texts={untracked} />);
        expect(screen.queryByRole('status')).toBeNull();
        expect(screen.getByText(TEXTS.hint)).toBeTruthy();
    });
});

describe('BatchList', () => {
    it('ne rend rien sans lot', () => {
        const { container } = render(<BatchList batches={[]} texts={TEXTS} />);
        expect(container.innerHTML).toBe('');
    });

    it('affiche le lien du lot et sa coulée', () => {
        render(<BatchList batches={[{ id: 1, code: 'BATCH-1', heat_number: 'C-1', certificate_type: '3.1', url: '/b/1' }]} texts={TEXTS} />);
        expect(screen.getByRole('link', { name: 'BATCH-1' }).getAttribute('href')).toBe('/b/1');
        expect(screen.getByText(/Coulée C-1/)).toBeTruthy();
    });
});

describe('ConsumedBatches', () => {
    const trans = { consumed_batches_trans_key: 'Lots consommés', heat_trans_key: 'Coulée' };

    it('ne rend rien quand la tâche n\'a consommé aucun lot', () => {
        const { container } = render(<ConsumedBatches batches={[]} trans={trans} />);
        expect(container.innerHTML).toBe('');
    });

    it('liste les coulées consommées, avec lien seulement si autorisé', () => {
        render(
            <ConsumedBatches
                trans={trans}
                batches={[
                    { id: 1, code: 'LOT-1', heat_number: 'C-1', certificate_type: '3.1', qty: 3, url: '/b/1' },
                    { id: 2, code: 'LOT-2', heat_number: null, certificate_type: null, qty: 1, url: null },
                ]}
            />
        );
        expect(screen.getByText('Lots consommés')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'LOT-1' })).toBeTruthy();
        expect(screen.getByText('Coulée C-1')).toBeTruthy();
        expect(screen.queryByRole('link', { name: 'LOT-2' })).toBeNull();
        expect(screen.getByText('LOT-2')).toBeTruthy();
    });
});
