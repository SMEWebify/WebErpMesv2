import { describe, it, expect } from 'vitest';
import {
    parseDuration, formatDuration, hoursToUnit, toInputValue, parseNumber, getTimeUnit, getMinutesDecimals, unitLabel,
} from '../../lib/duration';

describe('parseDuration (saisie → heures stockées)', () => {
    it('saisir 30 min stocke 0,5 h', () => {
        expect(parseDuration('30', 'minutes')).toBe(0.5);
    });
    it('arrondit à 3 décimales d\'heure', () => {
        expect(parseDuration('0.5', 'minutes')).toBe(0.008);
        expect(parseDuration('1', 'minutes')).toBe(0.017);
    });
    it('accepte la virgule décimale française', () => {
        expect(parseDuration('1,5', 'minutes')).toBe(0.025);
        expect(parseDuration('0,5', 'hours')).toBe(0.5);
    });
    it('en heures, la saisie est inchangée', () => {
        expect(parseDuration('2.25', 'hours')).toBe(2.25);
    });
    it('vide ou illisible → null', () => {
        expect(parseDuration('', 'minutes')).toBeNull();
        expect(parseDuration('  ', 'hours')).toBeNull();
        expect(parseDuration('abc', 'minutes')).toBeNull();
        expect(parseDuration(null, 'minutes')).toBeNull();
    });
});

describe('affichage', () => {
    it('heures → minutes (1 décimale, zéros retirés)', () => {
        expect(formatDuration(0.5, { unit: 'minutes' })).toBe('30 min');
        expect(formatDuration(0.025, { unit: 'minutes' })).toBe('1.5 min');
        expect(formatDuration('0.008', { unit: 'minutes' })).toBe('0.5 min');
        expect(formatDuration(0, { unit: 'minutes' })).toBe('0 min');
    });
    it('décimales configurables', () => {
        expect(formatDuration(0.008, { unit: 'minutes', decimals: 2 })).toBe('0.48 min');
        expect(formatDuration(0.008, { unit: 'minutes', decimals: 0 })).toBe('0 min');
    });
    it('mode heures : rendu historique inchangé', () => {
        expect(formatDuration('0.500', { unit: 'hours' })).toBe('0.500 h');
        expect(formatDuration('0.500', { unit: 'hours', hoursSuffix: 'h' })).toBe('0.500h');
        expect(formatDuration(1.5, { unit: 'hours', hoursDecimals: 2 })).toBe('1.50 h');
        expect(formatDuration(null, { unit: 'hours', hoursDecimals: 3, hoursSuffix: 'h' })).toBe('0.000h');
    });
    it('aller-retour minutes → heures → minutes', () => {
        for (const m of [30, 12.5, 1.5, 90, 0.2, 480]) {
            const h = parseDuration(String(m), 'minutes');
            expect(Math.abs(hoursToUnit(h, 'minutes', 1) - m)).toBeLessThanOrEqual(0.1);
        }
    });
    it('valeurs d\'un champ', () => {
        expect(toInputValue(0.5, 'minutes')).toBe('30');
        expect(toInputValue('', 'minutes')).toBe('');
        expect(toInputValue(0.5, 'hours')).toBe('0.5');
    });
});

describe('réglage lu dans la page', () => {
    const doc = (unit, dec) => {
        const d = document.implementation.createHTMLDocument('x');
        if (unit) d.head.innerHTML = `<meta name="time-unit" content="${unit}"><meta name="time-unit-decimals" content="${dec}">`;
        return d;
    };
    it('heures par défaut (aucune balise ou valeur inconnue)', () => {
        expect(getTimeUnit(doc(null))).toBe('hours');
        expect(getTimeUnit(doc('weeks', 1))).toBe('hours');
        expect(getMinutesDecimals(doc(null))).toBe(1);
    });
    it('minutes quand le réglage le dit', () => {
        expect(getTimeUnit(doc('minutes', 2))).toBe('minutes');
        expect(getMinutesDecimals(doc('minutes', 2))).toBe(2);
        expect(unitLabel('minutes')).toBe('min');
        expect(unitLabel('hours')).toBe('h');
    });
    it('parseNumber', () => {
        expect(parseNumber('1 234,5')).toBe(1234.5);
        expect(parseNumber(3)).toBe(3);
    });
});
