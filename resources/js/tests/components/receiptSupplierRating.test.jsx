import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, afterEach } from 'vitest';
import ReceiptSupplierRating, {
    fillText,
    initialScores,
    canSaveRating,
    withoutRateParam,
} from '../../components/purchases/ReceiptSupplierRating.jsx';

const TEXTS = {
    title: 'Noter le fournisseur', intro: 'Facultatif',
    conformity: 'Conformité', conformity_help: 'Pièces conformes',
    delay: 'Délai', delay_help: 'Date promise',
    documents: 'Documents', documents_help: 'BL',
    comment: 'Commentaire', later: 'Plus tard', save: 'Enregistrer', saving: '…',
    rate: 'Noter', edit: 'Modifier', not_rated: 'Réception non notée.', no_lines: 'Sans ligne',
    days_late: ':days j de retard', on_time: 'À l\'heure', no_promised: 'Pas de date',
    suggested: 'proposé', has_nc: 'NC : qualité :score/5',
    rolling: 'Note glissante :months mois', rolling_detail: ':count évaluation(s), :nc NC',
    no_rolling: 'Aucune évaluation sur :months mois', error: 'Erreur',
};

const STATE = {
    rated: false, scores: null, comment: null, can_rate: true,
    days_late: 5, suggested_delay: 3, has_nc: false, nc_quality_score: 1,
    rolling: { rating: null, ratings_count: 0, nc_receipts: 0, window_months: 12 },
};

afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
});

describe('helpers', () => {
    it('remplace les :clés', () => {
        expect(fillText(':days j de retard', { days: 4 })).toBe('4 j de retard');
    });

    it('part du délai proposé, ou de la note enregistrée', () => {
        expect(initialScores(STATE)).toEqual({ conformity: null, delay: 3, documents: null });
        const saved = { conformity: 5, delay: 2, documents: 4 };
        expect(initialScores({ ...STATE, scores: saved })).toEqual(saved);
    });

    it('exige les trois critères de 1 à 5', () => {
        expect(canSaveRating({ conformity: 5, delay: 3, documents: null })).toBe(false);
        expect(canSaveRating({ conformity: 5, delay: 3, documents: 6 })).toBe(false);
        expect(canSaveRating({ conformity: 5, delay: 3, documents: 1 })).toBe(true);
    });

    it('retire le paramètre rate de l’URL', () => {
        expect(withoutRateParam('http://x.test/fr/purchases/receipt/4?rate=1&tab=a')).toBe('http://x.test/fr/purchases/receipt/4?tab=a');
    });
});

describe('ReceiptSupplierRating', () => {
    it('s’ouvre d’elle-même avec le délai pré-rempli, et « Plus tard » ferme sans rien envoyer', () => {
        const fetchSpy = vi.spyOn(global, 'fetch');
        render(<ReceiptSupplierRating endpoint="/rate" autoOpen state={STATE} texts={TEXTS} />);

        expect(screen.getByRole('dialog')).toBeInTheDocument();
        expect(screen.getByLabelText('Délai 3/5')).toHaveAttribute('aria-checked', 'true');
        expect(screen.getByText(/5 j de retard/)).toBeInTheDocument();
        expect(screen.getByText('Enregistrer').closest('button')).toBeDisabled();

        fireEvent.click(screen.getByText('Plus tard'));
        expect(screen.queryByRole('dialog')).toBeNull();
        expect(fetchSpy).not.toHaveBeenCalled();
        expect(screen.getByText('Réception non notée.')).toBeInTheDocument();
    });

    it('enregistre les trois notes et affiche le résultat', async () => {
        const saved = {
            ...STATE, rated: true, scores: { conformity: 4, delay: 3, documents: 5 },
            rolling: { rating: 4, ratings_count: 1, nc_receipts: 0, window_months: 12 },
        };
        const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue({
            ok: true, status: 200, json: async () => ({ state: saved }),
        });

        render(<ReceiptSupplierRating endpoint="/rate" autoOpen state={STATE} texts={TEXTS} />);
        fireEvent.click(screen.getByLabelText('Conformité 4/5'));
        fireEvent.click(screen.getByLabelText('Documents 5/5'));
        fireEvent.click(screen.getByText('Enregistrer'));

        await waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());
        const [url, options] = fetchSpy.mock.calls[0];
        expect(url).toBe('/rate');
        expect(JSON.parse(options.body)).toEqual({ conformity: 4, delay: 3, documents: 5, comment: null });
        expect(screen.getByText('4.0/5')).toBeInTheDocument();
        expect(screen.getByText('Modifier')).toBeInTheDocument();
    });

    it('ne propose pas de noter une réception sans ligne', () => {
        render(<ReceiptSupplierRating endpoint="/rate" state={{ ...STATE, can_rate: false }} texts={TEXTS} />);
        expect(screen.getByText('Sans ligne')).toBeInTheDocument();
        expect(screen.queryByText('Noter')).toBeNull();
    });
});
