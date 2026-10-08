import { MOBILE_QUERY } from './DataTable.jsx';
import { matchesMedia } from './useMediaQuery.js';

/**
 * Onglet d'ouverture d'un index : sur téléphone, la liste (rendue en cartes)
 * plutôt que le tableau de bord et ses graphiques.
 *
 * Seul « dashboard » est remplacé : un onglet demandé explicitement
 * (?tab=templates, fiche société…) est respecté. À appeler dans l'initialiseur
 * de useState — évalué une fois, faire pivoter l'écran ne change pas d'onglet.
 */
export function listFirstOnMobile(tab) {
    return tab === 'dashboard' && matchesMedia(MOBILE_QUERY) ? 'list' : tab;
}
