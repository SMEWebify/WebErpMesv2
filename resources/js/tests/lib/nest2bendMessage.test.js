import { describe, expect, it } from 'vitest';
import { DONE_TYPE, isStepFile, originOf, readDoneMessage } from '../../lib/nest2bendMessage.js';

const ORIGIN = 'https://app.nest2bend.fr';
// UUID v7, comme ceux de NEST2BEND.
const RESULT = '0193f2a1-7c4e-7b2d-9a1f-3c5d7e9f1a2b';
const frame = {};

const event = (overrides = {}) => ({
    origin: ORIGIN,
    source: frame,
    data: { type: DONE_TYPE, resultId: RESULT },
    ...overrides,
});

describe('readDoneMessage', () => {
    it('returns the resultId of a done message from NEST2BEND', () => {
        expect(readDoneMessage(event(), ORIGIN, frame)).toBe(RESULT);
        expect(readDoneMessage(event(), ORIGIN)).toBe(RESULT);
    });

    it('ignores any other origin', () => {
        expect(readDoneMessage(event({ origin: 'https://evil.test' }), ORIGIN, frame)).toBeNull();
        expect(readDoneMessage(event({ origin: 'https://app.nest2bend.fr:8443' }), ORIGIN, frame)).toBeNull();
        expect(readDoneMessage(event({ origin: 'http://app.nest2bend.fr' }), ORIGIN, frame)).toBeNull();
        expect(readDoneMessage(event(), null, frame)).toBeNull();
    });

    it('ignores a message from another window', () => {
        expect(readDoneMessage(event({ source: {} }), ORIGIN, frame)).toBeNull();
    });

    it('ignores other message types and malformed ids', () => {
        expect(readDoneMessage(event({ data: { type: 'nest2bend:ready', resultId: RESULT } }), ORIGIN)).toBeNull();
        expect(readDoneMessage(event({ data: `${DONE_TYPE}` }), ORIGIN)).toBeNull();
        expect(readDoneMessage(event({ data: null }), ORIGIN)).toBeNull();
        expect(readDoneMessage(event({ data: { type: DONE_TYPE, resultId: '../compte' } }), ORIGIN)).toBeNull();
        expect(readDoneMessage(event({ data: { type: DONE_TYPE, resultId: 42 } }), ORIGIN)).toBeNull();
    });

    it('only reads the resultId, never the rest of the message', () => {
        const message = event({ data: { type: DONE_TYPE, resultId: RESULT, temps: { unitaire: 1 } } });
        expect(readDoneMessage(message, ORIGIN)).toBe(RESULT);
    });
});

describe('originOf', () => {
    it('keeps scheme, host and port only', () => {
        expect(originOf('https://app.nest2bend.fr/integre/abc?x=1')).toBe(ORIGIN);
        expect(originOf('http://localhost:8000/x')).toBe('http://localhost:8000');
        expect(originOf('not a url')).toBeNull();
    });
});

describe('isStepFile', () => {
    it('recognises .step and .stp files', () => {
        expect(isStepFile({ extension: 'STEP' })).toBe(true);
        expect(isStepFile({ extension: 'stp' })).toBe(true);
        expect(isStepFile({ name: 'support.Stp' })).toBe(true);
        expect(isStepFile({ extension: 'dxf', name: 'a.step' })).toBe(false);
        expect(isStepFile({ name: 'plan.pdf' })).toBe(false);
        expect(isStepFile(null)).toBe(false);
    });
});
