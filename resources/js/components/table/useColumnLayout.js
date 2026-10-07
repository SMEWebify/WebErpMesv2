import { useState } from 'react';

function readJson(key) {
    if (!key) return null;
    try { return JSON.parse(localStorage.getItem(key)); } catch { return null; }
}

function writeJson(key, value) {
    if (!key) return;
    try { localStorage.setItem(key, JSON.stringify(value)); } catch {}
}

/**
 * Ordre effectif des colonnes à partir de l'ordre enregistré.
 * - 'merge'  : garde l'ordre de l'utilisateur pour les colonnes connues et ajoute
 *              à la fin celles qu'il n'a jamais vues (colonne ajoutée depuis).
 * - 'strict' : comportement historique — l'ordre enregistré est pris tel quel s'il
 *              ne contient que des colonnes connues, sinon on repart du défaut.
 */
export function resolveOrder(saved, keys, mode = 'merge') {
    if (!Array.isArray(saved)) return keys;
    if (mode === 'strict') return saved.every(k => keys.includes(k)) ? saved : keys;
    const known = saved.filter((k, i) => keys.includes(k) && saved.indexOf(k) === i);
    return [...known, ...keys.filter(k => !known.includes(k))];
}

/**
 * Ordre et masquage des colonnes, persistés sous les clés localStorage de l'écran
 * (formats inchangés : tableau de clés de colonnes dans les deux cas).
 */
export default function useColumnLayout(keys, storage = {}, mode = 'merge') {
    const [savedOrder, setSavedOrder] = useState(() => readJson(storage.order));
    const [hidden, setHidden] = useState(() => {
        const saved = readJson(storage.hidden);
        return new Set(Array.isArray(saved) ? saved.filter(k => keys.includes(k)) : []);
    });

    // Recalculé à chaque rendu : une colonne qui apparaît plus tard (option activée
    // par la réponse du serveur) est prise en compte sans recharger la page.
    const order = resolveOrder(savedOrder, keys, mode).filter(k => keys.includes(k));

    const setHiddenAndSave = next => {
        setHidden(next);
        writeJson(storage.hidden, [...next]);
    };

    return {
        order,
        hidden,
        visible: order.filter(k => !hidden.has(k)),
        hide: key => setHiddenAndSave(new Set(hidden).add(key)),
        show: key => { const next = new Set(hidden); next.delete(key); setHiddenAndSave(next); },
        move: (source, target) => {
            if (!source || source === target) return;
            const next = [...order];
            next.splice(next.indexOf(target), 0, next.splice(next.indexOf(source), 1)[0]);
            setSavedOrder(next);
            writeJson(storage.order, next);
        },
    };
}
