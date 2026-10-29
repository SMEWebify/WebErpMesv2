import { describe, expect, it } from 'vitest';
import {
    certificateGap, commentRequired, decisionBadge, gapMessage, statusBadge,
} from '../../lib/receiptDocumentCheck';

const TYPES = ['2.1', '2.2', '3.1', '3.2'];

describe('certificateGap', () => {
    it('ne signale rien sans exigence', () => {
        expect(certificateGap(null, '2.1', TYPES)).toBeNull();
        expect(certificateGap('', null, TYPES)).toBeNull();
    });

    it('signale un certificat manquant', () => {
        expect(certificateGap('3.1', '', TYPES)).toEqual({ code: 'missing', required: '3.1', received: null });
    });

    it('signale un certificat de rang inférieur', () => {
        expect(certificateGap('3.1', '2.2', TYPES)).toEqual({ code: 'insufficient', required: '3.1', received: '2.2' });
    });

    it('accepte le même certificat ou un rang supérieur', () => {
        expect(certificateGap('3.1', '3.1', TYPES)).toBeNull();
        expect(certificateGap('2.2', '3.2', TYPES)).toBeNull();
    });

    it('signale une valeur hors liste comme différente', () => {
        expect(certificateGap('3.1', 'X', TYPES).code).toBe('different');
    });
});

describe('affichage', () => {
    const texts = {
        gap_insufficient: 'Certificat :received reçu, inférieur au :required exigé.',
        status_compliant: 'Conforme', status_pending: 'En attente',
        decision_rejected: 'Refusée', decision_pending: 'En attente',
    };

    it('rédige le message d\'écart', () => {
        expect(gapMessage({ code: 'insufficient', required: '3.1', received: '2.2' }, texts))
            .toBe('Certificat 2.2 reçu, inférieur au 3.1 exigé.');
        expect(gapMessage(null, texts)).toBeNull();
    });

    it('donne thème et libellé des badges', () => {
        expect(statusBadge('compliant', texts)).toEqual({ theme: 'success', label: 'Conforme' });
        expect(statusBadge('inconnu', texts)).toEqual({ theme: 'warning', label: 'En attente' });
        expect(decisionBadge('rejected', texts)).toEqual({ theme: 'danger', label: 'Refusée' });
    });

    it('exige un commentaire pour un conforme malgré un écart seulement', () => {
        const gap = { code: 'missing', required: '3.1', received: null };
        expect(commentRequired('compliant', gap)).toBe(true);
        expect(commentRequired('non_compliant', gap)).toBe(false);
        expect(commentRequired('compliant', null)).toBe(false);
    });
});
