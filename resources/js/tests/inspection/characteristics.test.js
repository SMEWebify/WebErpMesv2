import { describe, it, expect } from 'vitest';
import { parseDimensionText, describe as describePoint } from '../../components/inspection/characteristics';

const pick = (text, ...keys) => {
    const r = parseDimensionText(text);
    return r && Object.fromEntries(keys.map(k => [k, r[k]]));
};

describe('parseDimensionText (texte PDF d\'une zone → cote)', () => {
    it('cote simple sans tolérance', () => {
        expect(pick('10', 'characteristic_type', 'nominal_value', 'tol_min', 'tol_max'))
            .toEqual({ characteristic_type: 'dimension', nominal_value: 10, tol_min: undefined, tol_max: undefined });
    });

    it('tolérance symétrique, virgule décimale', () => {
        expect(pick('134,11 ±0,1', 'nominal_value', 'tol_min', 'tol_max'))
            .toEqual({ nominal_value: 134.11, tol_min: -0.1, tol_max: 0.1 });
    });

    it('écarts dissymétriques', () => {
        expect(pick('12 +0.2/-0.1', 'tol_min', 'tol_max')).toEqual({ tol_min: -0.1, tol_max: 0.2 });
        expect(pick('12 0/-0.05', 'tol_min', 'tol_max')).toEqual({ tol_min: -0.05, tol_max: 0 });
    });

    it('deux limites → nominal au milieu', () => {
        expect(pick('12.1 / 11.9', 'nominal_value', 'tol_min', 'tol_max'))
            .toEqual({ nominal_value: 12, tol_min: -0.1, tol_max: 0.1 });
    });

    it('diamètre, rayon, angle, filetage, rugosité', () => {
        expect(pick('⌀12 ±0.1', 'characteristic_type', 'nominal_value')).toEqual({ characteristic_type: 'diameter', nominal_value: 12 });
        expect(pick('Ø6.5', 'characteristic_type', 'nominal_value')).toEqual({ characteristic_type: 'diameter', nominal_value: 6.5 });
        expect(pick('R0.25', 'characteristic_type', 'nominal_value')).toEqual({ characteristic_type: 'radius', nominal_value: 0.25 });
        expect(pick('23° ±2', 'characteristic_type', 'unit', 'tol_max')).toEqual({ characteristic_type: 'angle', unit: '°', tol_max: 2 });
        expect(pick('M8x1.25', 'characteristic_type', 'nominal_value')).toEqual({ characteristic_type: 'thread', nominal_value: 8 });
        expect(pick('Ra 1,6', 'characteristic_type', 'nominal_value')).toEqual({ characteristic_type: 'roughness', nominal_value: 1.6 });
    });

    it('multiplicateur et cote double mm / pouces', () => {
        expect(pick('2X ⌀4.83', 'count', 'characteristic_type', 'nominal_value'))
            .toEqual({ count: 2, characteristic_type: 'diameter', nominal_value: 4.83 });
        expect(pick('19.05mm ±0.25 .75in ±.01', 'nominal_value', 'unit', 'tol_max'))
            .toEqual({ nominal_value: 19.05, unit: 'mm', tol_max: 0.25 });
        expect(pick('[91.69mm] 3.61in', 'nominal_value')).toBeNull();
    });

    it('un texte qui n\'est pas une cote est laissé à l\'IA', () => {
        expect(parseDimensionText('')).toBeNull();
        expect(parseDimensionText('BOUNDARY')).toBeNull();
        expect(parseDimensionText('⟂ 0.03 A B')).toBeNull();
    });

    it('le libellé garde le texte lu', () => {
        expect(parseDimensionText('⌀12 ±0.1').label).toBe('⌀12 ±0.1');
        expect(describePoint({ characteristic_type: 'diameter', nominal_value: 12, tol_min: -0.1, tol_max: 0.1 })).toBe('⌀12 ±0.1');
    });
});
