/*
 * Ligne de tableau cliquable : un clic n'importe où sur la ligne ouvre la fiche, comme un
 * lien (Ctrl/⌘ + clic ou clic molette = nouvel onglet).
 *
 * Ne déclenche rien quand le clic vise un élément qui a son propre comportement (lien,
 * bouton, champ, fenêtre modale posée dans la ligne) ni quand l'utilisateur sélectionne
 * du texte pour le copier.
 *
 * - React : `<tr {...rowLinkProps(url)}>`
 * - Blade : `<tr data-row-href="{{ route(...) }}">`, pris en charge par installRowLinks()
 *   (appelé par app.js).
 */

const INTERACTIVE = 'a, button, input, select, textarea, label, [role="button"], .modal';

export function shouldIgnoreRowClick(e) {
    if (e.target.closest(INTERACTIVE)) return true;
    return Boolean(window.getSelection?.().toString());
}

export function openRowLink(e, href, newTab = false) {
    if (!href || shouldIgnoreRowClick(e)) return;
    if (newTab || e.ctrlKey || e.metaKey) window.open(href, '_blank', 'noopener');
    else window.location.href = href;
}

export function rowLinkProps(href, style) {
    if (!href) return style ? { style } : {};
    return {
        style:      { cursor: 'pointer', ...style },
        onClick:    e => openRowLink(e, href),
        onAuxClick: e => { if (e.button === 1) openRowLink(e, href, true); },
    };
}

export function installRowLinks(root = document) {
    const hrefOf = e => e.target.closest?.('tr[data-row-href]')?.dataset.rowHref;
    root.addEventListener('click', e => {
        const href = hrefOf(e);
        if (href) openRowLink(e, href);
    });
    root.addEventListener('auxclick', e => {
        const href = hrefOf(e);
        if (href && e.button === 1) openRowLink(e, href, true);
    });
}
