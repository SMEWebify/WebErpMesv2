import { describe, it, expect } from 'vitest';
import { isReleased, releasedOnText, releasableIds } from '../../lib/productionRelease.js';

describe('productionRelease', () => {
    it('ne considère une ligne libérée que si la date est présente', () => {
        expect(isReleased(null)).toBe(false);
        expect(isReleased({ released_at: null, released_by: 'Alice' })).toBe(false);
        expect(isReleased({ released_at: '2026-10-28T09:00:00+00:00', released_by: null })).toBe(true);
    });

    it('affiche la date et l’auteur du BPF', () => {
        const text = releasedOnText('BPF le :date par :user', {
            released_at: '2026-10-28T09:00:00+00:00', released_by: 'Alice',
        }, 'fr-FR');

        expect(text).toBe('BPF le 28/10/2026 par Alice');
    });

    it('retire « par » quand l’auteur n’est plus connu', () => {
        const text = releasedOnText('Released on :date by :user', {
            released_at: '2026-10-28T09:00:00+00:00', released_by: null,
        }, 'en-GB');

        expect(text).toBe('Released on 28/10/2026');
    });

    it('ne rend rien sans BPF', () => {
        expect(releasedOnText('BPF le :date par :user', { released_at: null })).toBe('');
    });

    it('ne retient que les articles cochés et pas encore libérés', () => {
        const lines = [
            { id: 1, line_type: 'article', release: { released_at: null } },
            { id: 2, line_type: 'article', release: { released_at: '2026-10-28T09:00:00+00:00' } },
            { id: 3, line_type: 'section', release: { released_at: null } },
            { id: 4, release: null },
            { id: 5, line_type: 'article', release: { released_at: null } },
        ];

        expect(releasableIds(lines, new Set([1, 2, 3, 4]))).toEqual([1, 4]);
        expect(releasableIds(lines, [])).toEqual([]);
    });
});
