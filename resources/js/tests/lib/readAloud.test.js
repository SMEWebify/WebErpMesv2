import { describe, it, expect, vi, beforeEach } from 'vitest';
import { chunks, pickVoice, htmlToText, canSpeak, readingEnabled, speak, stopSpeaking } from '../../lib/readAloud.js';

describe('chunks', () => {
    it('cuts on sentences and never exceeds the limit', () => {
        const text = 'Bonjour Paul. Voici notre devis pour 200 supports ! Merci de votre retour ? ' + 'mot '.repeat(80);
        const parts = chunks(text, 60);
        expect(parts[0]).toBe('Bonjour Paul. Voici notre devis pour 200 supports !');
        expect(parts.every((p) => p.length <= 60)).toBe(true);
        expect(parts.join(' ').replace(/\s+/g, ' ')).toBe(text.replace(/\s+/g, ' ').trim());
    });

    it('returns nothing for an empty text', () => {
        expect(chunks('   \n ')).toEqual([]);
    });
});

describe('pickVoice', () => {
    const voices = [
        { lang: 'en-US', localService: true },
        { lang: 'fr-CA', localService: false },
        { lang: 'fr-FR', localService: true },
    ];

    it('prefers the exact language, then the same language', () => {
        expect(pickVoice(voices, 'fr-FR').lang).toBe('fr-FR');
        expect(pickVoice(voices, 'fr').lang).toBe('fr-FR');
        expect(pickVoice([voices[1]], 'fr').lang).toBe('fr-CA');
        expect(pickVoice(voices, 'de')).toBeNull();
    });
});

describe('htmlToText', () => {
    it('keeps the text and the line breaks, never runs scripts', () => {
        const text = htmlToText('<p>Bonjour <strong>Paul</strong>,</p><p>Merci.<br>Cordialement</p><script>alert(1)</script>');
        expect(text).toBe('Bonjour Paul,\nMerci.\nCordialement');
    });
});

describe('speak', () => {
    let spoken;
    beforeEach(() => {
        spoken = [];
        window.SpeechSynthesisUtterance = function (text) { this.text = text; };
        window.speechSynthesis = {
            getVoices: () => [{ lang: 'fr-FR', localService: true }],
            speak: (u) => spoken.push(u),
            cancel: vi.fn(),
        };
        document.body.className = '';
    });

    it('is only offered with easier reading', () => {
        expect(canSpeak()).toBe(true);
        expect(readingEnabled()).toBe(false);
        document.body.classList.add('wem-readable');
        expect(readingEnabled()).toBe(true);
    });

    it('reads every piece in the page language and reports the end', () => {
        const onEnd = vi.fn();
        speak('Bonjour. Merci.', { lang: 'fr', onEnd });
        expect(spoken.map((u) => u.text)).toEqual(['Bonjour. Merci.']);
        expect(spoken[0].lang).toBe('fr-FR');
        spoken[spoken.length - 1].onend();
        expect(onEnd).toHaveBeenCalledOnce();
    });

    it('stops the previous reading when a new one starts', () => {
        const first = vi.fn();
        speak('Premier texte.', { onEnd: first });
        speak('Second texte.', {});
        expect(first).toHaveBeenCalledOnce();
        expect(window.speechSynthesis.cancel).toHaveBeenCalled();
        stopSpeaking();
    });
});
