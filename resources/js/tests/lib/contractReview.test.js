import { describe, expect, it } from 'vitest';
import { decisionBlocker, draftPayload, reviewBadge, uncheckedItems } from '../../lib/contractReview.js';

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
