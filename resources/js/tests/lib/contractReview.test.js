import { describe, expect, it } from 'vitest';
import {
    amendmentBadge, amendmentBlocker, amendmentDelta, decisionBlocker, draftPayload, reviewBadge, uncheckedItems,
} from '../../lib/contractReview.js';

const checklist = [
    { key: 'requirements_understood', label: 'Exigences comprises', checked: true, configured: true },
    { key: 'lead_time_achievable', label: 'Délai tenable', checked: false, configured: true },
    // Point d'une revue passée, retiré depuis de la configuration.
    { key: 'old_item', label: 'old_item', checked: false, configured: false },
];

describe('reviewBadge', () => {
    it('donne un badge par état de revue', () => {
        expect(reviewBadge('done').className).toBe('text-bg-success');
        expect(reviewBadge('rejected').className).toBe('text-bg-danger');
        expect(reviewBadge('todo').className).toBe('text-bg-warning');
    });

    it('ne donne rien pour une commande interne', () => {
        expect(reviewBadge(null)).toBeNull();
        expect(reviewBadge(undefined)).toBeNull();
    });
});

describe('uncheckedItems', () => {
    it('ignore les points retirés de la configuration', () => {
        expect(uncheckedItems(checklist).map(i => i.key)).toEqual(['lead_time_achievable']);
    });
});

describe('decisionBlocker', () => {
    it('exige les écarts pour rejeter', () => {
        expect(decisionBlocker('rejected', checklist, '')).toBe('gaps_required_reject');
        expect(decisionBlocker('rejected', checklist, '   ')).toBe('gaps_required_reject');
        expect(decisionBlocker('rejected', checklist, 'Matière indisponible')).toBeNull();
    });

    it('exige les écarts pour valider avec un point non coché', () => {
        expect(decisionBlocker('approved', checklist, null)).toBe('gaps_required_unchecked');
        expect(decisionBlocker('approved', checklist, 'Délai décalé, accord écrit')).toBeNull();
    });

    it('laisse valider une check-list complète sans écart', () => {
        const complete = checklist.map(i => ({ ...i, checked: i.configured }));
        expect(decisionBlocker('approved', complete, '')).toBeNull();
    });
});

describe('draftPayload', () => {
    it('envoie les points configurés et des écarts nettoyés', () => {
        expect(draftPayload(checklist, '  Écart de prix  ')).toEqual({
            review_gaps: 'Écart de prix',
            review_checklist: { requirements_understood: true, lead_time_achievable: false },
        });
    });

    it('envoie null pour des écarts vides', () => {
        expect(draftPayload([], ' ').review_gaps).toBeNull();
    });
});

describe('amendmentBadge', () => {
    it('donne un badge par statut d’avenant', () => {
        expect(amendmentBadge('pending').className).toBe('text-bg-warning');
        expect(amendmentBadge('approved').className).toBe('text-bg-success');
        expect(amendmentBadge('rejected').className).toBe('text-bg-danger');
        expect(amendmentBadge('cancelled').className).toBe('text-bg-secondary');
    });

    it('retombe sur un badge neutre pour un statut inconnu', () => {
        expect(amendmentBadge('autre').className).toBe('text-bg-secondary');
    });
});

describe('amendmentBlocker', () => {
    it('propose la création sur une commande client sans avenant ouvert', () => {
        expect(amendmentBlocker({ applicable: true, pending_amendment: null })).toBeNull();
    });

    it('refuse un second avenant ouvert', () => {
        expect(amendmentBlocker({ applicable: true, pending_amendment: { id: 3, number: 2 } })).toBe('pending_exists');
    });

    it('refuse sur une commande interne', () => {
        expect(amendmentBlocker({ applicable: false })).toBe('not_applicable');
        expect(amendmentBlocker(null)).toBe('not_applicable');
    });
});

describe('amendmentDelta', () => {
    it('vaut null tant que l’avenant n’est pas visé', () => {
        expect(amendmentDelta({ amount_before: 1200, amount_after: null })).toBeNull();
    });

    it('calcule l’écart TTC arrondi au centime', () => {
        expect(amendmentDelta({ amount_before: 1200, amount_after: 1500.456 })).toBe(300.46);
        expect(amendmentDelta({ amount_before: 1200.1, amount_after: 1000 })).toBe(-200.1);
    });
});
