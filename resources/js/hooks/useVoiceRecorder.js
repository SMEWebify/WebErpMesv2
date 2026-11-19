// Micro du navigateur : enregistrement (MediaRecorder, envoyé au serveur pour
// transcription) ou, à défaut, dictée du navigateur (SpeechRecognition).
// Même logique que le mémo de la visite sur site (OpportunityVisitApp).
// L'enregistrement n'existe qu'en mémoire : il est rendu à l'appelant à l'arrêt.

import { useEffect, useRef, useState } from 'react';

const MAX_SECONDS = 10 * 60;

export function pickRecorderMime() {
    if (typeof MediaRecorder === 'undefined') return null;
    const candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
    return candidates.find((type) => MediaRecorder.isTypeSupported?.(type)) ?? '';
}

export function extensionFor(mime) {
    if (mime.includes('mp4')) return 'm4a';
    if (mime.includes('ogg')) return 'ogg';
    return 'webm';
}

function speechLang(locale) {
    if (locale === 'fr') return 'fr-FR';
    if (locale === 'es') return 'es-ES';
    return 'en-US';
}

/**
 * @param {object} options
 * @param {boolean} options.serverTranscription  le serveur sait transcrire un enregistrement
 * @param {(memo: {blob: Blob, mime: string}) => void} options.onRecorded
 * @param {(text: string) => void} options.onDictated
 * @param {string} options.locale
 * @param {number} [options.maxSeconds]
 */
export default function useVoiceRecorder({ serverTranscription, onRecorded, onDictated, locale, maxSeconds = MAX_SECONDS }) {
    const recorderMime = pickRecorderMime();
    const canRecord = !!serverTranscription && recorderMime !== null && !!navigator.mediaDevices?.getUserMedia;
    const SpeechRecognition = typeof window !== 'undefined' ? (window.SpeechRecognition || window.webkitSpeechRecognition) : null;
    const canDictate = !canRecord && !!SpeechRecognition;

    const [recording, setRecording] = useState(false);
    const [dictating, setDictating] = useState(false);
    const [seconds, setSeconds] = useState(0);
    const [denied, setDenied] = useState(false);

    const recorderRef = useRef(null);
    const chunksRef = useRef([]);
    const streamRef = useRef(null);
    const timerRef = useRef(null);
    const recognitionRef = useRef(null);
    // Les rappels changent à chaque rendu : on lit toujours le dernier.
    const callbacks = useRef({ onRecorded, onDictated });
    callbacks.current = { onRecorded, onDictated };

    const stopTracks = () => {
        streamRef.current?.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
        clearInterval(timerRef.current);
    };

    useEffect(() => () => {
        stopTracks();
        if (recorderRef.current?.state === 'recording') {
            recorderRef.current.onstop = null;
            recorderRef.current.stop();
        }
        recognitionRef.current?.abort?.();
    }, []);

    const start = async () => {
        setDenied(false);
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            streamRef.current = stream;
            const options = { audioBitsPerSecond: 32000 };
            if (recorderMime) options.mimeType = recorderMime;
            const recorder = new MediaRecorder(stream, options);
            chunksRef.current = [];
            recorder.ondataavailable = (e) => { if (e.data.size) chunksRef.current.push(e.data); };
            recorder.onstop = () => {
                stopTracks();
                const mime = recorder.mimeType || recorderMime || 'audio/webm';
                const blob = new Blob(chunksRef.current, { type: mime });
                chunksRef.current = [];
                setRecording(false);
                if (blob.size > 0) callbacks.current.onRecorded?.({ blob, mime });
            };
            recorderRef.current = recorder;
            recorder.start(1000);
            setSeconds(0);
            setRecording(true);
            timerRef.current = setInterval(() => {
                setSeconds((s) => {
                    if (s + 1 >= maxSeconds) recorderRef.current?.stop();
                    return s + 1;
                });
            }, 1000);
        } catch {
            stopTracks();
            setDenied(true);
        }
    };

    const stop = () => {
        if (recorderRef.current?.state === 'recording') recorderRef.current.stop();
    };

    const toggleDictation = () => {
        if (dictating) {
            recognitionRef.current?.stop();
            return;
        }
        setDenied(false);
        const recognition = new SpeechRecognition();
        recognition.lang = speechLang(locale);
        recognition.continuous = true;
        recognition.interimResults = false;
        recognition.onresult = (event) => {
            const said = Array.from(event.results)
                .slice(event.resultIndex)
                .filter((result) => result.isFinal)
                .map((result) => result[0].transcript.trim())
                .join(' ');
            if (said) callbacks.current.onDictated?.(said);
        };
        recognition.onerror = (event) => {
            if (event.error === 'not-allowed') setDenied(true);
        };
        recognition.onend = () => setDictating(false);
        recognitionRef.current = recognition;
        recognition.start();
        setDictating(true);
    };

    return { canRecord, canDictate, recording, dictating, seconds, denied, start, stop, toggleDictation };
}
