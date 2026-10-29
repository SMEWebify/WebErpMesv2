import { describe, expect, it } from 'vitest';
import { mergePhaseRelease, releaseBadges } from '../../lib/taskRelease.js';

describe('releaseBadges', () => {
    it('ne produit rien sans visa : la carte reste celle d\'avant', () => {
        expect(releaseBadges(undefined)).toEqual([]);
        expect(releaseBadges(null)).toEqual([]);
    });

    it('traduit chaque état du visa', () => {
        const trans = { badge_awaiting: 'To sign off', badge_released: 'Released', badge_to_finish: 'Required' };
        expect(releaseBadges({ state: 'awaiting' }, trans)[0]).toMatchObject({ label: 'To sign off', className: 'text-bg-warning' });
        expect(releaseBadges({ state: 'released' }, trans)[0]).toMatchObject({ label: 'Released', className: 'text-bg-success' });
        expect(releaseBadges({ state: 'to_finish' }, trans)[0]).toMatchObject({ label: 'Required', className: 'text-bg-secondary' });
    });

    it('ajoute l\'avertissement de la précédente, avec son texte en infobulle', () => {
        const badges = releaseBadges({ state: null, warning: 'L\'opération 10 Pliage…' });
        expect(badges).toHaveLength(1);
        expect(badges[0]).toMatchObject({ key: 'predecessor', title: 'L\'opération 10 Pliage…' });
    });
});

describe('mergePhaseRelease', () => {
    const board = [
        { id: 1, tasks: [{ id: 10 }, { id: 11, phase_release: { state: 'to_finish' } }] },
        { id: 2, tasks: [{ id: 12 }] },
    ];

    it('reporte l\'état renvoyé par le serveur sans changer l\'ordre des cartes', () => {
        const server = [
            { id: 1, tasks: [{ id: 12 }] },
            { id: 4, tasks: [{ id: 11, phase_release: { state: 'awaiting' } }, { id: 10 }] },
        ];
        const merged = mergePhaseRelease(board, server);
        expect(merged[0].tasks.map((t) => t.id)).toEqual([10, 11]);
        expect(merged[0].tasks[1].phase_release).toEqual({ state: 'awaiting' });
        expect(merged[0].tasks[0]).toBe(board[0].tasks[0]);
    });

    it('efface un état disparu et garde une tâche absente de la réponse', () => {
        const merged = mergePhaseRelease(board, [{ id: 1, tasks: [{ id: 11 }] }]);
        expect(merged[0].tasks[1].phase_release).toBeNull();
        expect(merged[1].tasks[0]).toBe(board[1].tasks[0]);
    });

    it('ignore une réponse illisible', () => {
        expect(mergePhaseRelease(board, undefined)).toBe(board);
    });
});
