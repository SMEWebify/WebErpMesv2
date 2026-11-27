import React, { useEffect, useState } from 'react';
import { canSpeak, readingEnabled, speak, stopSpeaking } from '../lib/readAloud.js';

// « Lire à voix haute » (synthèse vocale du navigateur) : rendu seulement pour qui a
// choisi la lecture facilitée et si le navigateur sait lire. getText est appelé au clic.
export default function ReadAloudButton({ getText, t = {}, className = 'btn btn-outline-secondary' }) {
    const [reading, setReading] = useState(false);

    // Lecture arrêtée quand le bouton disparaît (fenêtre fermée, autre fichier).
    useEffect(() => () => { if (reading) stopSpeaking(); }, [reading]);

    if (!canSpeak() || !readingEnabled()) return null;

    const toggle = () => {
        if (reading) { stopSpeaking(); return; }
        setReading(true);
        speak(getText(), { onEnd: () => setReading(false) });
    };

    return (
        <button type="button" className={`${className}${reading ? ' active' : ''}`} aria-pressed={reading} onClick={toggle}>
            <i className="fas fa-volume-up me-1" />
            {reading ? (t.stop ?? 'Stop') : (t.read ?? 'Read aloud')}
        </button>
    );
}
