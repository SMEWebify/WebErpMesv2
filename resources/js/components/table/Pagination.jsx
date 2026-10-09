import React from 'react';

/**
 * Pages à afficher, avec `null` pour une ellipse.
 * - around absent         : toutes les pages
 * - around + boundaries   : 1, dernière, courante ± around, ellipses dans les trous
 * - around sans boundaries: courante ± around, ellipse à chaque bout tronqué
 */
export function paginationItems(current, last, around = null, boundaries = false) {
    if (around === null || around === undefined) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    if (boundaries) {
        const visible = new Set([1, last]);
        for (let p = current - around; p <= current + around; p++) visible.add(p);
        const sorted = [...visible].filter(p => p >= 1 && p <= last).sort((a, b) => a - b);
        const items = [];
        sorted.forEach((p, i) => {
            if (i > 0 && sorted[i - 1] < p - 1) items.push(null);
            items.push(p);
        });
        return items;
    }

    const pages = [];
    for (let p = Math.max(1, current - around); p <= Math.min(last, current + around); p++) pages.push(p);
    return [
        ...(pages[0] > 1 ? [null] : []),
        ...pages,
        ...(pages[pages.length - 1] < last ? [null] : []),
    ];
}

/**
 * Pagination serveur : lit `meta.current_page`, `meta.last_page` et `meta.total`.
 *
 * Les props reproduisent les variantes historiques des écrans, sans les uniformiser :
 * - `jumpButtons` : « ‹ … › » (première / précédente / suivante / dernière) au lieu de « … »
 * - `prevNext={false}` : numéros seuls, sans flèches
 * - `showTotal` : « N résultat(s) » à gauche de la pagination
 * - `ellipsis={false}` : pas de « … » aux bouts tronqués de la fenêtre
 */
export default function Pagination({
    meta,
    onPage,
    around = null,
    boundaries = false,
    jumpButtons = false,
    prevNext = true,
    showTotal = false,
    ellipsis = true,
    navClassName,
    ulClassName = 'pagination pagination-sm',
}) {
    if (!meta || meta.last_page <= 1) return null;

    const cur   = meta.current_page;
    const last  = meta.last_page;
    const items = paginationItems(cur, last, around, boundaries).filter(p => ellipsis || p !== null);

    const edge = (disabled, label, page) => (
        <li className={`page-item ${disabled ? 'disabled' : ''}`}>
            <button className="page-link" onClick={() => onPage(page)}>{label}</button>
        </li>
    );

    return (
        <nav className={navClassName}>
            {showTotal && (
                <small className="text-muted">
                    {meta.total} {meta.total > 1 ? 'résultats' : 'résultat'}
                </small>
            )}
            <ul className={ulClassName}>
                {jumpButtons && edge(cur === 1, '«', 1)}
                {prevNext && edge(cur === 1, jumpButtons ? '‹' : '«', cur - 1)}
                {items.map((p, i) => p === null ? (
                    <li key={`gap-${i}`} className="page-item disabled"><span className="page-link">…</span></li>
                ) : (
                    <li key={p} className={`page-item ${p === cur ? 'active' : ''}`}>
                        <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                    </li>
                ))}
                {prevNext && edge(cur === last, jumpButtons ? '›' : '»', cur + 1)}
                {jumpButtons && edge(cur === last, '»', last)}
            </ul>
        </nav>
    );
}
