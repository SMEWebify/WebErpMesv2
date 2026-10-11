import { describe, it, expect } from 'vitest';
import { normalizeSavedLayout, readingOrder } from '../../components/dashboard/DashboardGrid.jsx';
import { DEFAULT_LAYOUT } from '../../components/dashboard/WidgetRegistry.js';

describe('normalizeSavedLayout', () => {
    it('laisse intact un agencement 12 colonnes', () => {
        expect(normalizeSavedLayout(DEFAULT_LAYOUT)).toBe(DEFAULT_LAYOUT);
    });

    it('remet à l\'échelle un agencement enregistré en 6 colonnes (sm)', () => {
        // Cas réel : agencement par défaut recompacté par un écran étroit puis sauvegardé
        const saved = [
            { i: 'kpi_stats', x: 0, y: 0, w: 6, h: 2 },
            { i: 'recent_orders', x: 0, y: 2, w: 2, h: 5 },
            { i: 'orders_monthly', x: 2, y: 2, w: 4, h: 5 },
        ];
        expect(normalizeSavedLayout(saved)).toEqual([
            { i: 'kpi_stats', x: 0, y: 0, w: 12, h: 2 },
            { i: 'recent_orders', x: 0, y: 2, w: 4, h: 5 },
            { i: 'orders_monthly', x: 4, y: 2, w: 8, h: 5 },
        ]);
    });

    it('remet à l\'échelle un agencement enregistré en 4 colonnes (xs)', () => {
        const saved = [
            { i: 'kpi_stats', x: 0, y: 0, w: 4, h: 2 },
            { i: 'goal', x: 0, y: 2, w: 2, h: 5 },
            { i: 'quote_rate', x: 2, y: 2, w: 2, h: 5 },
        ];
        expect(normalizeSavedLayout(saved).map(l => [l.x, l.w])).toEqual([[0, 12], [0, 6], [6, 6]]);
    });

    it('accepte un agencement vide ou absent', () => {
        expect(normalizeSavedLayout([])).toEqual([]);
        expect(normalizeSavedLayout(null)).toBeNull();
    });
});

describe('readingOrder', () => {
    it('trie de haut en bas puis de gauche à droite sans modifier l\'original', () => {
        const layout = [
            { i: 'c', x: 4, y: 8 },
            { i: 'b', x: 0, y: 8 },
            { i: 'a', x: 0, y: 0 },
        ];
        expect(readingOrder(layout).map(l => l.i)).toEqual(['a', 'b', 'c']);
        expect(layout[0].i).toBe('c');
    });
});
