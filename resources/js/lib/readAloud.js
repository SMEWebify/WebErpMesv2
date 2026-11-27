// « Lire à voix haute » : synthèse vocale du navigateur (speechSynthesis), rien
// ne part vers un serveur. Boutons affichés pour l'utilisateur qui a choisi la
// « Lecture facilitée » (Profil → Apparence, body.wem-readable).

export function canSpeak(w = typeof window !== 'undefined' ? window : undefined) {
    return !!(w && w.speechSynthesis && typeof w.SpeechSynthesisUtterance === 'function');
}

export function readingEnabled(doc = typeof document !== 'undefined' ? document : undefined) {
    return !!doc?.body?.classList.contains('wem-readable');
}

/** Voix de la langue de la page : exacte (fr-FR), sinon même langue (fr-CA), sinon celle du navigateur. */
export function pickVoice(voices, lang) {
    const wanted = String(lang || '').toLowerCase().replace('_', '-');
    if (!wanted || !voices?.length) return null;
    const base = wanted.split('-')[0];
    const norm = (v) => String(v.lang || '').toLowerCase().replace('_', '-');
    return voices.find((v) => norm(v) === wanted)
        ?? voices.find((v) => norm(v).split('-')[0] === base && v.localService)
        ?? voices.find((v) => norm(v).split('-')[0] === base)
        ?? null;
}

/**
 * Découpe en phrases de 200 caractères au plus : certains navigateurs coupent
 * une lecture trop longue au bout d'une quinzaine de secondes.
 */
export function chunks(text, max = 200) {
    const clean = String(text ?? '').replace(/\s+/g, ' ').trim();
    if (!clean) return [];
    const sentences = clean.match(/[^.!?…;:]+[.!?…;:]*\s*/g) ?? [clean];
    const out = [];
    let current = '';
    for (const sentence of sentences) {
        for (const piece of splitLong(sentence.trim(), max)) {
            if ((current + ' ' + piece).trim().length > max && current) {
                out.push(current.trim());
                current = piece;
            } else {
                current = (current + ' ' + piece).trim();
            }
        }
    }
    if (current.trim()) out.push(current.trim());
    return out;
}

function splitLong(sentence, max) {
    if (sentence.length <= max) return [sentence];
    const words = sentence.split(' ');
    const out = [];
    let current = '';
    for (const word of words) {
        if ((current + ' ' + word).trim().length > max && current) {
            out.push(current);
            current = word;
        } else {
            current = (current + ' ' + word).trim();
        }
    }
    if (current) out.push(current);
    return out;
}

/** Texte d'un HTML (corps d'e-mail, éditeur) : DOMParser n'exécute rien. */
export function htmlToText(html) {
    if (!html) return '';
    const doc = new DOMParser().parseFromString(
        String(html).replace(/<br\s*\/?>/gi, '\n').replace(/<\/(p|div|li|tr|h[1-6])>/gi, '</$1>\n'),
        'text/html',
    );
    doc.querySelectorAll('script, style').forEach((el) => el.remove());
    return (doc.body?.textContent ?? '').replace(/\n{3,}/g, '\n\n').trim();
}

let current = null;

/** Lit le texte ; un nouvel appel arrête la lecture en cours. onEnd est appelé à la fin ou à l'arrêt. */
export function speak(text, { lang = document.documentElement.lang || 'fr', onEnd } = {}) {
    stopSpeaking();
    const parts = chunks(text);
    if (!parts.length || !canSpeak()) { onEnd?.(); return; }

    const synth = window.speechSynthesis;
    const voice = pickVoice(synth.getVoices(), lang);
    const session = { onEnd, done: false };
    current = session;
    const finish = () => {
        if (session.done) return;
        session.done = true;
        if (current === session) current = null;
        onEnd?.();
    };

    parts.forEach((part, i) => {
        const utterance = new window.SpeechSynthesisUtterance(part);
        utterance.lang = voice?.lang ?? lang;
        if (voice) utterance.voice = voice;
        utterance.rate = 0.95;
        if (i === parts.length - 1) utterance.onend = finish;
        utterance.onerror = finish;
        synth.speak(utterance);
    });
}

export function stopSpeaking() {
    if (!current) return;
    const session = current;
    current = null;
    if (canSpeak()) window.speechSynthesis.cancel();
    if (!session.done) { session.done = true; session.onEnd?.(); }
}

// Champs Blade : bouton posé par include/read-aloud-button. Cible = éditeur riche
// (#{cible}-editor, Quill), sinon le champ #{cible}, sinon le premier textarea du bloc.
function textOf(box) {
    const target = box.dataset.target;
    if (target) {
        const editorEl = document.getElementById(`${target}-editor`);
        const editor = editorEl && window.Quill?.find ? window.Quill.find(editorEl) : null;
        if (editor) return editor.getText();
        const el = document.getElementById(target);
        if (el) return 'value' in el ? el.value : el.textContent;
    }
    const field = box.closest('[data-read-aloud-scope]')?.querySelector('textarea');
    return field?.value ?? '';
}

export function bindReadAloudButtons(root = document) {
    if (!canSpeak()) return;
    root.querySelectorAll?.('[data-read-aloud]').forEach((box) => box.classList.remove('d-none'));

    root.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-read-aloud-button]');
        if (!button) return;
        const box = button.closest('[data-read-aloud]');
        const label = button.querySelector('[data-label]');
        const setReading = (reading) => {
            button.classList.toggle('active', reading);
            button.setAttribute('aria-pressed', reading ? 'true' : 'false');
            if (label) label.textContent = reading ? button.dataset.stop : button.dataset.read;
        };

        if (button.getAttribute('aria-pressed') === 'true') { stopSpeaking(); return; }
        // Une autre lecture en cours (autre bouton) s'arrête et remet son bouton à zéro.
        stopSpeaking();
        setReading(true);
        speak(textOf(box), { onEnd: () => setReading(false) });
    });
}
