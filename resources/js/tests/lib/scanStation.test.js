import { describe, expect, it } from 'vitest';
import { formatWorked, operatorExpired, parseQty, pushHistory, resultStyle } from '../../lib/scanStation.js';

describe('scanStation', () => {
    it('donne une couleur à chaque résultat, rouge par défaut', () => {
        expect(resultStyle('started').tone).toBe('success');
        expect(resultStyle('finished').tone).toBe('primary');
        expect(resultStyle('refused').tone).toBe('danger');
        expect(resultStyle('inconnu').tone).toBe('danger');
    });

    it('affiche le temps passé en minutes puis en heures', () => {
        expect(formatWorked(0)).toBe('0 min');
        expect(formatWorked(300)).toBe('5 min');
        expect(formatWorked(3900)).toBe('1 h 05');
    });

    it('n\'accepte que des quantités entières positives', () => {
        expect(parseQty('8')).toBe(8);
        expect(parseQty('')).toBe(0);
        expect(parseQty('2,5')).toBeNull();
        expect(parseQty('-1')).toBeNull();
    });

    it('oublie le badge après 15 min sans scan', () => {
        const operator = { name: 'Arnaud', badge: 'AB12', lastAt: 0 };
        expect(operatorExpired(null, 0, 15)).toBe(false);
        expect(operatorExpired(operator, 14 * 60 * 1000, 15)).toBe(false);
        expect(operatorExpired(operator, 16 * 60 * 1000, 15)).toBe(true);
    });

    it('garde les 5 derniers scans, le plus récent en tête', () => {
        let history = [];
        for (let i = 1; i <= 7; i++) history = pushHistory(history, i);
        expect(history).toEqual([7, 6, 5, 4, 3]);
    });
});
