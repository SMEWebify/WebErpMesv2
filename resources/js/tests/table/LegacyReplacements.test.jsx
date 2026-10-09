import React from 'react';
import { render } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { SortIcon, Pagination } from '../../components/table';

// Composants locaux remplacés par les briques de table/, recopiés tels quels
// pour prouver que l'écran rend le même HTML qu'avant.

// DeliverysRequest, PurchasesRequest, ProformasIndex
function LegacySortIcon({ field, sortField, sortAsc }) {
    if (sortField !== field) return <i className="fas fa-sort ml-1 text-muted" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

// IncomingInvoicesIndex
function LegacyIncomingPagination({ meta, onPage }) {
    if (!meta || meta.last_page <= 1) return null;
    const { current_page, last_page } = meta;
    const pages = [];
    for (let p = Math.max(1, current_page - 2); p <= Math.min(last_page, current_page + 2); p++) pages.push(p);

    return (
        <nav>
            <ul className="pagination pagination-sm justify-content-center mb-0">
                <li className={`page-item ${current_page === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page - 1)}>&laquo;</button>
                </li>
                {pages.map(p => (
                    <li key={p} className={`page-item ${p === current_page ? 'active' : ''}`}>
                        <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                    </li>
                ))}
                <li className={`page-item ${current_page === last_page ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page + 1)}>&raquo;</button>
                </li>
            </ul>
        </nav>
    );
}

// Même rendu à l'ordre des classes près (sans effet visuel).
const normalize = html => html.replace(/class="([^"]*)"/g, (_, c) => `class="${c.split(/\s+/).filter(Boolean).sort().join(' ')}"`);
const html = el => normalize(render(el).container.innerHTML);

describe('SortIcon partagé = SortIcon local des écrans de demande', () => {
    it.each([
        ['colonne non triée', 'code', 'label', true],
        ['tri croissant',     'code', 'code',  true],
        ['tri décroissant',   'code', 'code',  false],
        ['aucun tri actif',   'code', null,    true],
    ])('%s', (_l, field, sortField, sortAsc) => {
        expect(html(<SortIcon field={field} sortField={sortField} sortAsc={sortAsc} />))
            .toBe(html(<LegacySortIcon field={field} sortField={sortField} sortAsc={sortAsc} />));
    });
});

describe('Pagination partagée = pagination locale des factures reçues', () => {
    const meta = (current_page, last_page) => ({ current_page, last_page, total: last_page * 20 });

    it.each([
        [1, 1], [1, 2], [1, 3], [2, 5], [1, 12], [6, 12], [11, 12], [12, 12], [3, 40],
    ])('page %i sur %i', (cur, last) => {
        const onPage = () => {};
        expect(html(
            <Pagination meta={meta(cur, last)} onPage={onPage} around={2} ellipsis={false}
                        ulClassName="pagination pagination-sm justify-content-center mb-0" />,
        )).toBe(html(<LegacyIncomingPagination meta={meta(cur, last)} onPage={onPage} />));
    });

    it('sans meta', () => {
        expect(html(<Pagination meta={null} onPage={() => {}} around={2} ellipsis={false} />)).toBe('');
    });
});
