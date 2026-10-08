import { useCallback, useState } from 'react';
import { listFirstOnMobile } from './viewport.js';

function read(key) {
    try { return localStorage.getItem(key); } catch { return null; }
}

function write(key, value) {
    try { localStorage.setItem(key, value); } catch { /* navigation privée : pas de mémoire */ }
}

/**
 * Onglet actif d'un index (tableau de bord / liste / …), mémorisé par navigateur.
 *
 * À l'ouverture, par ordre de priorité :
 *  1. `forced` — onglet imposé par le contexte (liste intégrée à une fiche société,
 *     ?tab= dans l'URL) ; un changement d'onglet fait dans ce contexte n'est pas
 *     mémorisé, pour ne pas fausser l'index autonome ;
 *  2. le dernier onglet choisi, s'il fait toujours partie de `tabs` (un onglet
 *     soumis à un droit retiré entre-temps est ignoré) ;
 *  3. « list » sur téléphone, `fallback` ailleurs.
 */
export default function useIndexTab(storageKey, { tabs = ['dashboard', 'list'], fallback = 'dashboard', forced = null } = {}) {
    const [tab, setTabState] = useState(() => {
        if (forced) return forced;
        const saved = read(storageKey);
        return saved && tabs.includes(saved) ? saved : listFirstOnMobile(fallback);
    });

    const setTab = useCallback((next) => {
        setTabState(next);
        if (!forced) write(storageKey, next);
    }, [storageKey, forced]);

    return [tab, setTab];
}
