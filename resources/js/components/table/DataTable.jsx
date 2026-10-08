import React, { useRef, useState } from 'react';
import SortIcon from './SortIcon.jsx';
import useColumnLayout from './useColumnLayout.js';
import useMediaQuery from './useMediaQuery.js';
import { toISODate } from './dates.js';

/*
 * Tableau d'index déclaratif. L'écran garde sa récupération de données (fetch, tri et
 * pagination serveur) et décrit ses colonnes :
 *
 *   {
 *     key:       'total_amount',              // identifiant, sert aussi aux clés localStorage
 *     label:     trans.total,
 *     render:    row => node,                 // contenu de la cellule (défaut : row[key])
 *     sortable:  true | 'champ_serveur',      // true = trié sur `key`
 *     align:     '' | 'center' | 'right',
 *     bold:      true,                        // gras + sans retour à la ligne
 *     nowrap:    false,                       // défaut : vrai si aligné à droite ou en gras
 *     filter:    'text' | 'date',             // ligne de filtres (page courante)
 *     filterValue: row => string,             // texte, ou date (JJ/MM/AAAA ou ISO)
 *     total:     { value: row => number, format: sum => node },   // pied de tableau
 *     mobile:    'title' | 'subtitle' | 'badge' | 'amount' | 'hidden',
 *     mobileRender: row => node,              // contenu sur la carte (défaut : render)
 *     mobileOrder: 1,                         // ordre sur la carte (défaut : ordre des colonnes)
 *     hideable:  false,                       // colonne déplaçable mais jamais masquée
 *   }
 *
 * Sur PC : colonnes masquables et réordonnables (persistées), ligne de filtres, total
 * de la page. Sous `md` : une carte par ligne, entièrement cliquable vers `rowHref`.
 */

export const MOBILE_QUERY = '(max-width: 767.98px)';

const INPUT_STYLE = { fontSize: '0.72rem', height: '24px', padding: '1px 4px' };
const EMPTY_VALUES = [null, undefined, '', '—'];

const alignClass = align => (align === 'right' ? 'text-right' : align === 'center' ? 'text-center' : '');
const sortFieldOf = col => (col.sortable === true ? col.key : col.sortable || null);
const renderCell = (col, row) => (col.render ? col.render(row) : row[col.key]);

function filterValueOf(col, row) {
    const raw = col.filterValue ? col.filterValue(row) : row[col.key];
    return col.filter === 'date' ? toISODate(raw) : raw;
}

export function matchesFilter(col, row, value) {
    if (col.filter === 'date') {
        const { from, to } = value ?? {};
        const iso = filterValueOf(col, row);
        if (!iso) return true;
        if (from && iso < from) return false;
        if (to && iso > to) return false;
        return true;
    }
    if (col.filter === 'text') {
        const needle = (value ?? '').toLowerCase().trim();
        if (!needle) return true;
        return String(filterValueOf(col, row) ?? '').toLowerCase().includes(needle);
    }
    return true;
}

function joinNodes(nodes, separator) {
    return nodes.flatMap((node, i) => (i === 0 ? [node] : [separator, node]))
        .map((node, i) => <React.Fragment key={i}>{node}</React.Fragment>);
}

function DefaultRowAction({ href, title }) {
    return (
        <a href={href} className="btn btn-xs btn-info" title={title}>
            <i className="fas fa-eye" />
        </a>
    );
}

// ---------------------------------------------------------------------------
// Rendu cartes (mobile)
// ---------------------------------------------------------------------------

function MobileSort({ columns, sortField, sortAsc, onSort, trans }) {
    const sortable = columns.filter(sortFieldOf);
    if (!onSort || sortable.length === 0) return null;
    const touch = { minHeight: 44 };

    return (
        <div className="d-flex mb-2" style={{ gap: '0.5rem' }}>
            <select
                className="form-control"
                style={touch}
                aria-label={trans.sort_by ?? 'Trier par'}
                value={sortField ?? ''}
                onChange={e => onSort(e.target.value)}
            >
                {!sortable.some(c => sortFieldOf(c) === sortField) && <option value={sortField ?? ''}>—</option>}
                {sortable.map(c => <option key={c.key} value={sortFieldOf(c)}>{c.label}</option>)}
            </select>
            <button
                type="button"
                className="btn btn-outline-secondary"
                style={{ ...touch, minWidth: 44 }}
                aria-label={sortAsc ? (trans.sort_desc ?? 'Tri décroissant') : (trans.sort_asc ?? 'Tri croissant')}
                onClick={() => sortField && onSort(sortField)}
            >
                <i className={`fas fa-sort-amount-${sortAsc ? 'up' : 'down'}`} />
            </button>
        </div>
    );
}

function MobileCards({ rows, columns, rowKey, rowHref, mobileActions, loading, loadingContent, emptyText, totals, totalLabel }) {
    const byRole = role => columns
        .filter(c => c.mobile === role)
        .map((c, i) => [c.mobileOrder ?? 1000 + i, c])
        .sort((a, b) => a[0] - b[0])
        .map(([, c]) => c);
    const valuesFor = (role, row) => byRole(role)
        .map(c => (c.mobileRender ? c.mobileRender(row) : renderCell(c, row)))
        .filter(v => !EMPTY_VALUES.includes(v));

    if (loading) return <div className="text-center py-4">{loadingContent}</div>;
    if (rows.length === 0) return <div className="text-center text-muted py-3">{emptyText}</div>;

    return (
        <div className="list-group mb-2">
            {rows.map(row => {
                const href      = rowHref?.(row);
                const title     = valuesFor('title', row);
                const subtitles = valuesFor('subtitle', row);
                const badges    = valuesFor('badge', row);
                const amounts   = valuesFor('amount', row);
                const extra     = mobileActions?.(row);
                return (
                    <div key={row[rowKey]} className={`list-group-item position-relative py-2${href ? ' list-group-item-action' : ''}`} style={{ minHeight: 44 }}>
                        <div className="d-flex align-items-center" style={{ gap: '0.5rem' }}>
                            <div className="flex-grow-1" style={{ minWidth: 0 }}>
                                <div className="font-weight-bold text-truncate">
                                    {href
                                        ? <a href={href} className="stretched-link text-body text-decoration-none">{joinNodes(title, ' · ')}</a>
                                        : joinNodes(title, ' · ')}
                                </div>
                                {subtitles.length > 0 && (
                                    <div className="small text-muted text-truncate">{joinNodes(subtitles, ' · ')}</div>
                                )}
                            </div>
                            {(badges.length > 0 || amounts.length > 0) && (
                                <div className="text-right flex-shrink-0">
                                    {badges.map((b, i) => <div key={i}>{b}</div>)}
                                    {amounts.map((a, i) => <div key={i} className="font-weight-bold" style={{ whiteSpace: 'nowrap' }}>{a}</div>)}
                                </div>
                            )}
                        </div>
                        {extra && (
                            <div className="mt-2 position-relative" style={{ zIndex: 2 }}>{extra}</div>
                        )}
                    </div>
                );
            })}
            {totals.map(({ col, sum }) => (
                <div key={col.key} className="list-group-item d-flex justify-content-between font-weight-bold bg-light">
                    <span>{totalLabel}{totals.length > 1 ? ` — ${col.label}` : ''}</span>
                    <span style={{ whiteSpace: 'nowrap' }}>{col.total.format ? col.total.format(sum) : sum}</span>
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// DataTable
// ---------------------------------------------------------------------------

export default function DataTable({
    rows,
    columns,
    trans = {},
    rowKey = 'id',
    loading = false,
    sortField,
    sortAsc,
    onSort,
    storage = {},
    colOrderMode = 'merge',
    hideable = true,
    unsortableIcon = true,
    unsortableCursor = 'pointer',
    reorderable = true,
    colFilters: controlledFilters,
    onColFiltersChange,
    rowHref,
    rowActions,
    mobileActions,
    actionsColumn = true,
    actionsWidth = 36,
    actionsHeader = null,
    actionsCellStyle,
    chipsClassName = 'mb-2 d-flex flex-wrap',
    tableClassName = 'table table-hover table-sm',
    loadingContent = <i className="fas fa-spinner fa-spin" />,
    emptyText,
    totalLabel,
    forceLayout,
}) {
    const isMobileQuery = useMediaQuery(MOBILE_QUERY);
    const isMobile      = forceLayout ? forceLayout === 'cards' : isMobileQuery;

    const cols    = Object.fromEntries(columns.map(c => [c.key, c]));
    const layout  = useColumnLayout(columns.map(c => c.key), storage, colOrderMode);
    const visible = layout.visible.map(k => cols[k]);

    const [localFilters, setLocalFilters] = useState({});
    const colFilters    = controlledFilters ?? localFilters;
    const setColFilter  = (key, value) => {
        const next = { ...colFilters, [key]: value };
        if (onColFiltersChange) onColFiltersChange(next);
        if (controlledFilters === undefined) setLocalFilters(next);
    };

    const [dragOver, setDragOver] = useState(null);
    const dragCol = useRef(null);

    const empty      = emptyText ?? trans.no_results ?? 'No results';
    const totalText  = totalLabel ?? trans.total ?? 'Total';
    const filtered   = rows.filter(row => visible.every(col => matchesFilter(col, row, colFilters[col.key] ?? '')));
    const totals     = visible.filter(c => c.total).map(col => ({
        col,
        sum: filtered.reduce((s, row) => s + (Number(col.total.value ? col.total.value(row) : row[col.key]) || 0), 0),
    }));
    const hasFilters = visible.some(c => c.filter);
    const actions    = rowActions ?? (rowHref ? row => <DefaultRowAction href={rowHref(row)} /> : null);

    if (isMobile) {
        return (
            <div>
                <MobileSort columns={columns} sortField={sortField} sortAsc={sortAsc} onSort={onSort} trans={trans} />
                <MobileCards
                    rows={filtered}
                    columns={columns}
                    rowKey={rowKey}
                    rowHref={rowHref}
                    mobileActions={mobileActions}
                    loading={loading}
                    loadingContent={loadingContent}
                    emptyText={empty}
                    totals={filtered.length > 0 ? totals : []}
                    totalLabel={totalText}
                />
            </div>
        );
    }

    const firstTotalIdx = visible.findIndex(c => c.total);
    const colSpan       = visible.length + (actionsColumn ? 1 : 0);

    return (
        <div>
            {hideable && layout.hidden.size > 0 && (
                <div className={chipsClassName} style={{ gap: '4px' }}>
                    {layout.order.filter(k => layout.hidden.has(k)).map(key => (
                        <button
                            key={key}
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            style={{ fontSize: '0.72rem', padding: '1px 8px' }}
                            onClick={() => layout.show(key)}
                            title="Réafficher la colonne"
                        >
                            + {cols[key].label}
                        </button>
                    ))}
                </div>
            )}

            <div className="table-responsive">
                <table className={tableClassName}>
                    <thead>
                        <tr>
                            {visible.map(col => {
                                const field    = sortFieldOf(col);
                                const dropping = dragOver === col.key;
                                return (
                                    <th
                                        key={col.key}
                                        className={alignClass(col.align)}
                                        draggable={reorderable || undefined}
                                        style={{
                                            cursor:     field ? 'pointer' : unsortableCursor,
                                            whiteSpace: 'nowrap',
                                            userSelect: 'none',
                                            borderLeft: dropping ? '3px solid #007bff' : undefined,
                                            background: dropping ? '#e8f0fe' : undefined,
                                        }}
                                        onDragStart={reorderable ? () => { dragCol.current = col.key; } : undefined}
                                        onDragOver={reorderable ? e => { e.preventDefault(); setDragOver(col.key); } : undefined}
                                        onDragLeave={reorderable ? () => setDragOver(null) : undefined}
                                        onDrop={reorderable ? () => {
                                            layout.move(dragCol.current, col.key);
                                            setDragOver(null);
                                            dragCol.current = null;
                                        } : undefined}
                                        onClick={() => field && onSort?.(field)}
                                    >
                                        {reorderable && (
                                            <i className="fas fa-grip-vertical text-muted mr-1" style={{ fontSize: '0.65rem', opacity: 0.4 }} />
                                        )}
                                        {col.label}
                                        {(field || unsortableIcon) && (
                                            <SortIcon field={field} sortField={sortField} sortAsc={sortAsc} size={col.sortIconSize} />
                                        )}
                                        {hideable && col.hideable !== false && (
                                            <span
                                                role="button"
                                                aria-label="Masquer la colonne"
                                                style={{ marginLeft: '6px', opacity: 0.4, fontSize: '0.8rem', lineHeight: 1 }}
                                                className="text-danger"
                                                onClick={e => { e.stopPropagation(); layout.hide(col.key); }}
                                                onMouseEnter={e => { e.currentTarget.style.opacity = 1; }}
                                                onMouseLeave={e => { e.currentTarget.style.opacity = 0.4; }}
                                            >×</span>
                                        )}
                                    </th>
                                );
                            })}
                            {actionsColumn && <th style={{ width: actionsWidth }}>{actionsHeader}</th>}
                        </tr>
                        {hasFilters && (
                            <tr>
                                {visible.map(col => (
                                    <th key={col.key} style={{ padding: '2px 4px', fontWeight: 'normal' }}>
                                        {col.filter === 'text' && (
                                            <input
                                                type="text"
                                                className="form-control form-control-sm"
                                                style={INPUT_STYLE}
                                                placeholder="⌕"
                                                aria-label={col.label}
                                                value={colFilters[col.key] ?? ''}
                                                onChange={e => setColFilter(col.key, e.target.value)}
                                            />
                                        )}
                                        {col.filter === 'date' && (
                                            <div style={{ display: 'flex', gap: '2px', minWidth: '200px' }}>
                                                {['from', 'to'].map(bound => (
                                                    <input
                                                        key={bound}
                                                        type="date"
                                                        className="form-control form-control-sm"
                                                        style={{ ...INPUT_STYLE, flex: 1, minWidth: 0 }}
                                                        title={bound === 'from' ? 'Du' : 'Au'}
                                                        value={(colFilters[col.key] ?? {})[bound] ?? ''}
                                                        onChange={e => setColFilter(col.key, { ...(colFilters[col.key] ?? {}), [bound]: e.target.value })}
                                                    />
                                                ))}
                                            </div>
                                        )}
                                    </th>
                                ))}
                                {actionsColumn && <th />}
                            </tr>
                        )}
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={colSpan} className="text-center py-4">{loadingContent}</td></tr>
                        ) : filtered.length === 0 ? (
                            <tr><td colSpan={colSpan} className="text-center text-muted py-3">{empty}</td></tr>
                        ) : null}
                        {!loading && filtered.map(row => (
                            <tr key={row[rowKey]}>
                                {visible.map(col => (
                                    <td
                                        key={col.key}
                                        className={`${alignClass(col.align)}${col.bold ? ' font-weight-bold' : ''}`}
                                        style={(col.nowrap ?? (col.align === 'right' || col.bold)) ? { whiteSpace: 'nowrap' } : {}}
                                    >
                                        {renderCell(col, row)}
                                    </td>
                                ))}
                                {actionsColumn && <td style={actionsCellStyle}>{actions?.(row)}</td>}
                            </tr>
                        ))}
                    </tbody>
                    {filtered.length > 0 && totals.length > 0 && (
                        <tfoot>
                            <tr className="font-weight-bold bg-light">
                                {visible.map((col, i) => {
                                    const total = totals.find(t => t.col === col);
                                    if (total) return (
                                        <td key={col.key} className="text-right" style={{ whiteSpace: 'nowrap' }}>
                                            {col.total.format ? col.total.format(total.sum) : total.sum}
                                        </td>
                                    );
                                    if (i === firstTotalIdx - 1) return <td key={col.key} className="text-right">{totalText}</td>;
                                    return <td key={col.key} />;
                                })}
                                {actionsColumn && <td />}
                            </tr>
                        </tfoot>
                    )}
                </table>
            </div>
        </div>
    );
}
