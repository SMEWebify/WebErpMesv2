import { describe, expect, it } from 'vitest';
import {
    columnCount,
    columnLetter,
    fillPlaceholders,
    formatSize,
    mailDocument,
    previewErrorKey,
} from '../../lib/files/filePreview.js';

describe('filePreview', () => {
    it('numbers columns like Excel', () => {
        expect(columnLetter(0)).toBe('A');
        expect(columnLetter(25)).toBe('Z');
        expect(columnLetter(26)).toBe('AA');
        expect(columnLetter(51)).toBe('AZ');
        expect(columnLetter(702)).toBe('AAA');
    });

    it('draws as many columns as the longest row', () => {
        expect(columnCount([['a'], ['a', 'b', 'c'], []])).toBe(3);
        expect(columnCount([])).toBe(0);
        expect(columnCount(undefined)).toBe(0);
    });

    it('fills every placeholder of a translated text', () => {
        expect(fillPlaceholders(':rows lignes sur :total_rows', { rows: 500, total_rows: 1200 }))
            .toBe('500 lignes sur 1200');
    });

    it('formats an attachment size in the page language', () => {
        expect(formatSize(512, 'fr')).toBe('512 o');
        expect(formatSize(2048, 'fr')).toBe('2.0 Ko');
        expect(formatSize(3 * 1024 * 1024, 'en')).toBe('3.0 MB');
        expect(formatSize(undefined)).toBe('');
    });

    it('locks the mail document down: no script, no remote resource, links in a new tab', () => {
        const doc = mailDocument('<p>Bonjour</p>');

        expect(doc).toContain("default-src 'none'");
        expect(doc).toContain('img-src data:');
        expect(doc).not.toContain('script-src');
        expect(doc).toContain('<base target="_blank">');
        expect(doc).toContain('<body><p>Bonjour</p></body>');
    });

    it('maps a refusal to its message', () => {
        expect(previewErrorKey('too_large')).toBe('preview_too_large');
        expect(previewErrorKey('missing')).toBe('preview_missing');
        expect(previewErrorKey('anything')).toBe('preview_unreadable');
    });
});
