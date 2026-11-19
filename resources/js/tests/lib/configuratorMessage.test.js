import { describe, expect, it } from 'vitest';
import {
    CANCEL_TYPE, DONE_TYPE, RETURN_TYPE, isIdentifier, readIframeMessage, readReturnMessage,
} from '../../lib/configuratorMessage.js';

const ORIGIN = 'https://configurateur.exemple.com';
const ERP = 'https://erp.client.fr';
const SESSION = '9f1c2b7a-3d4e-4f5a-8b6c-7d8e9f0a1b2c';
const frame = {};

const iframeEvent = (overrides = {}) => ({
    origin: ORIGIN,
    source: frame,
    data: { type: DONE_TYPE, sessionId: 'remote-1', resultId: 'res-42' },
    ...overrides,
});

describe('readIframeMessage', () => {
    it('returns the resultId of a done message from the configurator', () => {
        expect(readIframeMessage(iframeEvent(), ORIGIN, frame)).toEqual({ type: 'done', resultId: 'res-42' });
    });

    it('reads a cancel message', () => {
        expect(readIframeMessage(iframeEvent({ data: { type: CANCEL_TYPE } }), ORIGIN, frame)).toEqual({ type: 'cancel' });
    });

    it('ignores another origin, another window or another shape', () => {
        expect(readIframeMessage(iframeEvent({ origin: 'https://evil.test' }), ORIGIN, frame)).toBeNull();
        expect(readIframeMessage(iframeEvent({ origin: 'http://configurateur.exemple.com' }), ORIGIN, frame)).toBeNull();
        expect(readIframeMessage(iframeEvent({ source: {} }), ORIGIN, frame)).toBeNull();
        expect(readIframeMessage(iframeEvent({ data: 'done' }), ORIGIN, frame)).toBeNull();
        expect(readIframeMessage(iframeEvent({ data: { type: 'other', resultId: 'x' } }), ORIGIN, frame)).toBeNull();
        expect(readIframeMessage(iframeEvent(), null, frame)).toBeNull();
    });

    it('refuses an unreadable resultId', () => {
        expect(readIframeMessage(iframeEvent({ data: { type: DONE_TYPE, resultId: '' } }), ORIGIN, frame)).toBeNull();
        expect(readIframeMessage(iframeEvent({ data: { type: DONE_TYPE, resultId: 42 } }), ORIGIN, frame)).toBeNull();
        expect(readIframeMessage(iframeEvent({ data: { type: DONE_TYPE, resultId: 'a\nb' } }), ORIGIN, frame)).toBeNull();
        expect(readIframeMessage(iframeEvent({ data: { type: DONE_TYPE, resultId: 'x'.repeat(256) } }), ORIGIN, frame)).toBeNull();
    });
});

describe('readReturnMessage', () => {
    const returnEvent = (overrides = {}) => ({
        origin: ERP,
        data: { type: RETURN_TYPE, session: SESSION, resultId: 'res-42' },
        ...overrides,
    });

    it('returns the resultId sent by the ERP return page for this session', () => {
        expect(readReturnMessage(returnEvent(), ERP, SESSION)).toBe('res-42');
    });

    it('ignores another origin, another session or a missing resultId', () => {
        expect(readReturnMessage(returnEvent({ origin: ORIGIN }), ERP, SESSION)).toBeNull();
        expect(readReturnMessage(returnEvent({ data: { type: RETURN_TYPE, session: 'other', resultId: 'res-42' } }), ERP, SESSION)).toBeNull();
        expect(readReturnMessage(returnEvent({ data: { type: RETURN_TYPE, session: SESSION, resultId: '' } }), ERP, SESSION)).toBeNull();
        expect(readReturnMessage(returnEvent({ data: { type: DONE_TYPE, session: SESSION, resultId: 'res-42' } }), ERP, SESSION)).toBeNull();
    });
});

describe('isIdentifier', () => {
    it('accepts short printable strings only', () => {
        expect(isIdentifier('0192f1c9-abc')).toBe(true);
        expect(isIdentifier(null)).toBe(false);
        expect(isIdentifier('tab\there')).toBe(false);
    });
});
