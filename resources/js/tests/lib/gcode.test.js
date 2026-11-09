import { describe, it, expect } from 'vitest';
import { parseGcode, parseBlock, formatMinutes } from '../../lib/gcode/parse';
import { buildLineIndex } from '../../lib/gcode/toThree';

const ends = (parsed) => {
    const p = parsed.positions;
    return [p[p.length - 3], p[p.length - 2], p[p.length - 1]].map((v) => Math.round(v * 1e6) / 1e6);
};

describe('gcode parser', () => {
    it('reads words without spaces, decimals and comments', () => {
        expect(parseBlock('N10G1X-.5Y2.Z0 F100')).toEqual({ g: [1], m: [], words: { N: 10, X: -0.5, Y: 2, Z: 0, F: 100 } });
    });

    it('does not draw until every axis is known, then draws rapids and feeds', () => {
        const parsed = parseGcode('G90 G21\nG0 X10 Y0\nG43 Z5 H1\nG1 Z-1 F100\nX20 (cut)\n');
        expect(parsed.rapid).toEqual([0, 0]); // seule la plongée et la passe
        expect(parsed.segmentLines).toEqual([3, 4]);
        expect(parsed.units).toBe('mm');
        expect(parsed.stats.feedLength).toBeCloseTo(16); // 6 de plongée + 10 de passe
        expect(parsed.stats.feedMinutes).toBeCloseTo(0.16);
    });

    it('keeps G0 modal and treats G91 as incremental', () => {
        const parsed = parseGcode('G0 X0 Y0 Z0\nX5\nG91 X5 Y5\n');
        expect(parsed.rapid).toEqual([1, 1]);
        expect(ends(parsed)).toEqual([10, 5, 0]);
    });

    it('draws a G17 arc from its incremental centre, counter-clockwise for G3', () => {
        const parsed = parseGcode('G0 X10 Y0 Z0\nG3 X0 Y10 I-10 J0\n');
        const p = parsed.positions;
        // Point au milieu de l'arc : 45°, rayon 10.
        const mid = (parsed.rapid.length / 2) | 0;
        const [x, y] = [p[mid * 6 + 3], p[mid * 6 + 4]];
        expect(Math.hypot(x, y)).toBeCloseTo(10, 5);
        expect(x).toBeGreaterThan(0);
        expect(y).toBeGreaterThan(0);
        expect(ends(parsed)).toEqual([0, 10, 0]);
        expect(parsed.stats.feedLength).toBeCloseTo(Math.PI * 5, 1);
    });

    it('draws a full circle when start and end coincide, and the long way for a negative R', () => {
        const circle = parseGcode('G0 X10 Y0 Z0\nG2 X10 Y0 I-10 J0\n');
        expect(circle.stats.feedLength).toBeCloseTo(2 * Math.PI * 10, 0);

        const short = parseGcode('G0 X10 Y0 Z0\nG3 X0 Y10 R10\n');
        const long = parseGcode('G0 X10 Y0 Z0\nG3 X0 Y10 R-10\n');
        expect(short.stats.feedLength).toBeCloseTo(Math.PI * 5, 1);
        expect(long.stats.feedLength).toBeCloseTo(Math.PI * 15, 1);
    });

    it('follows the G18 / G19 planes for arcs', () => {
        // G18 : arc dans le plan ZX, centre par I (X) et K (Z) ; Y ne bouge pas.
        const parsed = parseGcode('G0 X0.5 Y1 Z0.55\nG18 G3 X0.45 Z0.5 I-0.05 K0\n');
        const p = parsed.positions;
        for (let s = 0; s < parsed.rapid.length; s++) expect(p[s * 6 + 4]).toBe(1);
        expect(ends(parsed)).toEqual([0.45, 1, 0.5]);
        expect(parsed.stats.feedLength).toBeCloseTo((Math.PI * 0.05) / 2, 3);
    });

    it('expands drilling cycles and ignores dwell and machine coordinates', () => {
        const parsed = parseGcode('G0 X0 Y0 Z10\nG99 G81 X5 Y5 Z-3 R2 F80\nX15\nG80\nG4 X2.\nG53 G0 Z0\n');
        // Par trou : rapide en XY, rapide à R, plongée, remontée à R.
        expect(parsed.rapid).toEqual([1, 1, 0, 1, 1, 0, 1]);
        expect(ends(parsed)).toEqual([15, 5, 2]);
    });

    it('lists tools with their header comment and flags what it does not interpret', () => {
        const parsed = parseGcode('(T1 D=10 - fraise 2T)\nT1 M6\nG41 D1\n#100=5\nG0 X[#100] Y0 Z0\n');
        expect(parsed.tools).toEqual([{ number: 1, comment: 'D=10 - fraise 2T' }]);
        expect(parsed.warnings).toEqual(expect.arrayContaining(['G41/G42', 'macro']));
        expect(parsed.rapid).toEqual([]);
    });

    it('indexes segments per line for the simulation and the highlight', () => {
        const parsed = parseGcode('G0 X0 Y0 Z0\nG1 X1 F10\nG0 Z5\nG1 X2 Z0\n');
        const index = buildLineIndex(parsed);
        expect(Array.from(index.feedUpTo)).toEqual([0, 1, 1, 2, 2]);
        expect(Array.from(index.rapidUpTo)).toEqual([0, 0, 1, 1, 1]);
        expect(index.segmentsOf(2)).toEqual([1, 2]);
        expect(parsed.lineEnd[2]).toEqual([1, 0, 5]);
    });

    it('formats feed time', () => {
        expect(formatMinutes(0)).toBeNull();
        expect(formatMinutes(0.4)).toBe('< 1 min');
        expect(formatMinutes(12.4)).toBe('12 min');
        expect(formatMinutes(65)).toBe('1 h 05');
    });
});
