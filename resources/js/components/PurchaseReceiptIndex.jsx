import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-info',    label: 'in_progress' },
    2: { badge: 'badge-warning', label: 'stock' },
};

const ALL_STATUSES = [1, 2];

const LS_FILTERS     = 'purchase_receipts_list_filters';
const LS_COL_ORDER   = 'purchase_receipts_table_col_order';
const LS_HIDDEN_COLS = 'purchase_receipts_table_hidden_cols';

const DEFAULT_COL_ORDER = ['code', 'label', 'companie', 'lines_count', 'receipt_note', 'statu', 'created_at'];
const TEXT_FILTER_COLS  = new Set(['code', 'label', 'companie']);
const DATE_RANGE_COLS   = new Set(['created_at']);

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function formatDate(dateStr, locale) {
    if (!dateStr) return '—';
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Intl.DateTimeFormat(locale || 'fr-FR').format(new Date(y, m - 1, d));
    } catch {
        return dateStr;
    }
}

function dmyToISO(str) {
    if (!str || !str.includes('/')) return str ?? '';
    const [d, m, y] = str.split('/');
    return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            'Accept':       'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...options.headers,
        },
        ...options,
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({ message: res.statusText }));
        throw err;
    }
    return res.json();
}

function loadFilters() {
    try {
        const raw = localStorage.getItem(LS_FILTERS);
        return raw ? JSON.parse(raw) : null;
    } catch { return null; }
}

function saveFilters(f) {
    try { localStorage.setItem(LS_FILTERS, JSON.stringify(f)); } catch {}
}

function readSavedColOrder() {
    try {
        const saved = JSON.parse(localStorage.getItem(LS_COL_ORDER));
        if (Array.isArray(saved) && saved.every(c => DEFAULT_COL_ORDER.includes(c))) return saved;
    } catch {}
    return DEFAULT_COL_ORDER;
}

function readSavedHiddenCols() {
    try {
        const saved = JSON.parse(localStorage.getItem(LS_HIDDEN_COLS));
        if (Array.isArray(saved)) return new Set(saved.filter(c => DEFAULT_COL_ORDER.includes(c)));
    } catch {}
    return new Set();
}

// ---------------------------------------------------------------------------
// StatusBadge
// ---------------------------------------------------------------------------

function StatusBadge({ statu, trans }) {
    const cfg = STATUS_CONFIG[statu] ?? { badge: 'badge-secondary', label: String(statu) };
    return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span>;
}

// ---------------------------------------------------------------------------
// StatusFilter
// ---------------------------------------------------------------------------

function StatusFilter({ selected, onChange, trans }) {
    const toggle = (id) => {
        const next = selected.includes(id)
            ? selected.filter(s => s !== id)
            : [...selected, id];
        onChange(next.length ? next : [id]);
    };
    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.25rem' }}>
            {ALL_STATUSES.map(id => {
                const cfg    = STATUS_CONFIG[id];
                const active = selected.includes(id);
                return (
                    <button
                        key={id}
                        className={`btn btn-sm ${active ? cfg.badge.replace('badge-', 'btn-') : 'btn-outline-secondary'}`}
                        onClick={() => toggle(id)}
                    >
                        {trans[cfg.label] ?? id}
                    </button>
                );
            })}
        </div>
    );
}

// ---------------------------------------------------------------------------
// SortIcon
// ---------------------------------------------------------------------------

function SortIcon({ field, sortField, sortAsc }) {
    if (field !== sortField) return <i className="fas fa-sort text-muted ml-1" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

// ---------------------------------------------------------------------------
// KPI Cards
// ---------------------------------------------------------------------------

function KPICards({ kpi, trans }) {
    return (
        <div className="row">
            <div className="col-lg-4">
                <div className="small-box bg-teal">
                    <div className="inner">
                        <h3>{kpi.total ?? 0}</h3>
                        <p>{trans.total_receipts ?? 'Bons de réception'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-truck-loading" /></div>
                </div>
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-info">
                    <div className="inner">
                        <h3>{kpi.in_progress ?? 0}</h3>
                        <p>{trans.in_progress ?? 'En cours'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-spinner" /></div>
                </div>
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-warning">
                    <div className="inner">
                        <h3>{kpi.stock ?? 0}</h3>
                        <p>{trans.stock ?? 'En stock'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-boxes" /></div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// PieChart — SVG React
// ---------------------------------------------------------------------------

const STATUS_COLORS = { 1: '#17a2b8', 2: '#ffc107' };

function PieChart({ data, trans }) {
    const [hovered, setHovered] = useState(null);

    const items = (data ?? []).filter(d => (d.PurchaseReciepCountRate ?? 0) > 0);
    const total = items.reduce((s, d) => s + Number(d.PurchaseReciepCountRate), 0);

    if (!items.length) return <p className="text-muted text-center small py-3">—</p>;

    const R = 80, r = 44, cx = 110, cy = 110;

    // Cas 1 seul statut → cercle complet (arc A→A impossible en SVG)
    if (items.length === 1) {
        const item  = items[0];
        const cfg   = STATUS_CONFIG[item.statu];
        const color = STATUS_COLORS[item.statu] ?? '#6c757d';
        const label = cfg ? (trans[cfg.label] ?? cfg.label) : String(item.statu);
        return (
            <div style={{ textAlign: 'center' }}>
                <svg viewBox="0 0 220 220" style={{ width: '100%', maxWidth: 220 }}>
                    <circle cx={cx} cy={cy} r={R} fill={color} />
                    <circle cx={cx} cy={cy} r={r} fill="white" />
                    <text x={cx} y={cy - 8} textAnchor="middle" fontSize="11" fill="#333">{label}</text>
                    <text x={cx} y={cy + 10} textAnchor="middle" fontSize="16" fontWeight="bold" fill="#333">{item.PurchaseReciepCountRate}</text>
                </svg>
                <div className="d-flex justify-content-center flex-wrap mt-1" style={{ gap: '0.5rem' }}>
                    <span className="d-flex align-items-center" style={{ gap: '4px', fontSize: '0.78rem' }}>
                        <span style={{ width: 10, height: 10, borderRadius: '50%', background: color, display: 'inline-block' }} />
                        {label} — {item.PurchaseReciepCountRate}
                    </span>
                </div>
            </div>
        );
    }

    // Cas multi-statuts → donut normal
    let angle = -Math.PI / 2;
    const slices = items.map((item) => {
        const value  = Number(item.PurchaseReciepCountRate);
        const sweep  = (value / total) * 2 * Math.PI;
        const x1 = cx + R * Math.cos(angle), y1 = cy + R * Math.sin(angle);
        angle += sweep;
        const x2 = cx + R * Math.cos(angle), y2 = cy + R * Math.sin(angle);
        const ix1 = cx + r * Math.cos(angle), iy1 = cy + r * Math.sin(angle);
        const ix2 = cx + r * Math.cos(angle - sweep), iy2 = cy + r * Math.sin(angle - sweep);
        const large = sweep > Math.PI ? 1 : 0;
        const cfg   = STATUS_CONFIG[item.statu];
        const color = STATUS_COLORS[item.statu] ?? '#6c757d';
        const label = cfg ? (trans[cfg.label] ?? cfg.label) : String(item.statu);
        return { value, x1, y1, x2, y2, ix1, iy1, ix2, iy2, large, color, label };
    });

    return (
        <div style={{ textAlign: 'center' }}>
            <svg viewBox="0 0 220 220" style={{ width: '100%', maxWidth: 220 }}>
                {slices.map((s, i) => (
                    <path
                        key={i}
                        d={`M ${cx} ${cy} L ${s.x1} ${s.y1} A ${R} ${R} 0 ${s.large} 1 ${s.x2} ${s.y2} L ${s.ix1} ${s.iy1} A ${r} ${r} 0 ${s.large} 0 ${s.ix2} ${s.iy2} Z`}
                        fill={s.color}
                        opacity={hovered === null || hovered === i ? 1 : 0.55}
                        onMouseEnter={() => setHovered(i)}
                        onMouseLeave={() => setHovered(null)}
                        style={{ cursor: 'pointer' }}
                    />
                ))}
                {hovered !== null && slices[hovered] && (
                    <>
                        <text x={cx} y={cy - 8} textAnchor="middle" fontSize="11" fill="#333">{slices[hovered].label}</text>
                        <text x={cx} y={cy + 10} textAnchor="middle" fontSize="16" fontWeight="bold" fill="#333">{slices[hovered].value}</text>
                    </>
                )}
            </svg>
            {/* Légende */}
            <div className="d-flex justify-content-center flex-wrap mt-1" style={{ gap: '0.5rem' }}>
                {slices.map((s, i) => (
                    <span key={i} className="d-flex align-items-center" style={{ gap: '4px', fontSize: '0.78rem' }}>
                        <span style={{ width: 10, height: 10, borderRadius: '50%', background: s.color, display: 'inline-block' }} />
                        {s.label} — {s.value}
                    </span>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// BarChart — SVG React
// ---------------------------------------------------------------------------

function BarChart({ data, trans }) {
    const months = [
        trans.jan ?? 'Jan', trans.feb ?? 'Fév', trans.mar ?? 'Mar',
        trans.apr ?? 'Avr', trans.may ?? 'Mai', trans.jun ?? 'Jun',
        trans.jul ?? 'Jul', trans.aug ?? 'Aoû', trans.sep ?? 'Sep',
        trans.oct ?? 'Oct', trans.nov ?? 'Nov', trans.dec ?? 'Déc',
    ];
    const byMonth = {};
    (data ?? []).forEach(d => { byMonth[d.month] = d.receiptCount; });
    const values = Array.from({ length: 12 }, (_, i) => byMonth[i + 1] ?? 0);
    const maxVal = Math.max(...values, 1);
    const W = 560, H = 160, PAD = { top: 16, right: 16, bottom: 28, left: 24 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top - PAD.bottom;
    const barW  = plotW / 12 - 4;

    return (
        <svg viewBox={`0 0 ${W} ${H}`} style={{ width: '100%', display: 'block' }}>
            {values.map((v, i) => {
                const barH = Math.max(2, (v / maxVal) * plotH);
                const x    = PAD.left + i * (plotW / 12) + 2;
                const y    = PAD.top + plotH - barH;
                return (
                    <g key={i}>
                        <rect x={x} y={y} width={barW} height={barH} fill="rgba(60,141,188,0.85)" rx="2" />
                        <text x={x + barW / 2} y={PAD.top + plotH + 14} textAnchor="middle" fontSize="9" fill="#666">
                            {months[i].substring(0, 3)}
                        </text>
                        {v > 0 && (
                            <text x={x + barW / 2} y={y - 3} textAnchor="middle" fontSize="9" fill="#333">{v}</text>
                        )}
                    </g>
                );
            })}
        </svg>
    );
}

// ---------------------------------------------------------------------------
// Dashboard Tab
// ---------------------------------------------------------------------------

function DashboardTab({ kpi, chartData, trans }) {
    return (
        <div>
            <KPICards kpi={kpi} trans={trans} />
            <div className="row">
                <div className="col-md-3">
                    <div className="card card-teal">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-pie mr-1" />
                                {trans.statistics ?? 'Statistiques'}
                            </h3>
                        </div>
                        <div className="card-body d-flex justify-content-center">
                            <PieChart data={chartData.statusRate} trans={trans} />
                        </div>
                    </div>
                </div>
                <div className="col-md-9">
                    <div className="card card-primary">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar mr-1" />
                                {trans.monthly_recap ?? 'Récap mensuel'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <BarChart data={chartData.monthlyRecap} trans={trans} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Column definitions
// ---------------------------------------------------------------------------

function colDefs(trans) {
    return {
        code: {
            label: trans.code ?? 'Code', sortField: 'code',
            render: r => <code>{r.code}</code>,
        },
        label: {
            label: trans.label ?? 'Libellé', sortField: 'label',
            render: r => r.label,
        },
        companie: {
            label: trans.company ?? 'Fournisseur', sortField: 'companies_id',
            render: r => r.companie_label,
        },
        lines_count: {
            label: trans.lines_count ?? 'Lignes', sortField: null,
            align: 'center',
            render: r => <span className="badge badge-secondary">{r.lines_count}</span>,
        },
        receipt_note: {
            label: trans.receipt_note ?? 'Note BR', sortField: null,
            render: r => {
                if (r.recept_controle === 1 && r.reception_controlled === 0)
                    return <span className="badge badge-info">{trans.yes ?? 'Oui'}</span>;
                return <span className="badge badge-success">{trans.no ?? 'Non'}</span>;
            },
        },
        statu: {
            label: trans.status ?? 'Statut', sortField: 'statu',
            render: r => <StatusBadge statu={r.statu} trans={trans} />,
        },
        created_at: {
            label: trans.created_at ?? 'Créé le', sortField: 'created_at',
            render: r => formatDate(r.created_at, trans.locale),
        },
    };
}

function matchesColFilter(row, colId, value) {
    if (DATE_RANGE_COLS.has(colId)) {
        const { from, to } = value ?? {};
        const iso = row.created_at ?? '';
        if (!iso) return true;
        if (from && iso < from) return false;
        if (to   && iso > to)   return false;
        return true;
    }
    const v = (value ?? '').toLowerCase().trim();
    if (!v) return true;
    switch (colId) {
        case 'code':    return (row.code ?? '').toLowerCase().includes(v);
        case 'label':   return (row.label ?? '').toLowerCase().includes(v);
        case 'companie': return (row.companie_label ?? '').toLowerCase().includes(v);
        default:        return true;
    }
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

function Pagination({ meta, onPageChange }) {
    if (!meta || meta.last_page <= 1) return null;
    const pages = Array.from({ length: meta.last_page }, (_, i) => i + 1);
    return (
        <nav>
            <ul className="pagination pagination-sm justify-content-end">
                <li className={`page-item ${meta.current_page === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPageChange(meta.current_page - 1)}>«</button>
                </li>
                {pages.map(p => (
                    <li key={p} className={`page-item ${p === meta.current_page ? 'active' : ''}`}>
                        <button className="page-link" onClick={() => onPageChange(p)}>{p}</button>
                    </li>
                ))}
                <li className={`page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPageChange(meta.current_page + 1)}>»</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// ReceiptsTable — draggable columns + per-column filters
// ---------------------------------------------------------------------------

function ReceiptsTable({ rows, sortField, sortAsc, onSort, trans }) {
    const [colOrder,   setColOrder]   = useState(readSavedColOrder);
    const [hiddenCols, setHiddenCols] = useState(readSavedHiddenCols);
    const [colFilters, setColFilters] = useState({});
    const [dragOver,   setDragOver]   = useState(null);
    const dragCol = useRef(null);

    const COLS        = colDefs(trans);
    const visibleCols = colOrder.filter(c => !hiddenCols.has(c));

    const hideCol = (colId) => {
        const next = new Set(hiddenCols); next.add(colId);
        setHiddenCols(next);
        localStorage.setItem(LS_HIDDEN_COLS, JSON.stringify([...next]));
    };
    const showCol = (colId) => {
        const next = new Set(hiddenCols); next.delete(colId);
        setHiddenCols(next);
        localStorage.setItem(LS_HIDDEN_COLS, JSON.stringify([...next]));
    };

    const filtered = rows.filter(r =>
        visibleCols.every(colId => matchesColFilter(r, colId, colFilters[colId] ?? ''))
    );

    const onDragStart = (colId) => { dragCol.current = colId; };
    const onDragOver  = (e, colId) => { e.preventDefault(); setDragOver(colId); };
    const onDragLeave = () => setDragOver(null);
    const onDrop      = (targetId) => {
        const src = dragCol.current;
        if (!src || src === targetId) { setDragOver(null); return; }
        const next = [...colOrder];
        next.splice(next.indexOf(targetId), 0, next.splice(next.indexOf(src), 1)[0]);
        setColOrder(next);
        localStorage.setItem(LS_COL_ORDER, JSON.stringify(next));
        setDragOver(null);
        dragCol.current = null;
    };

    const inputStyle = { fontSize: '0.72rem', height: '24px', padding: '1px 4px' };

    return (
        <div>
            {/* Hidden column restore chips */}
            {hiddenCols.size > 0 && (
                <div className="mb-2 d-flex flex-wrap" style={{ gap: '4px' }}>
                    {colOrder.filter(c => hiddenCols.has(c)).map(colId => (
                        <button
                            key={colId}
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            style={{ fontSize: '0.72rem', padding: '1px 8px' }}
                            onClick={() => showCol(colId)}
                            title="Réafficher la colonne"
                        >
                            + {COLS[colId].label}
                        </button>
                    ))}
                </div>
            )}

            <div className="table-responsive">
                <table className="table table-hover table-sm">
                    <thead>
                        {/* Row 1 — sortable + draggable headers */}
                        <tr>
                            {visibleCols.map(colId => {
                                const col      = COLS[colId];
                                const dropping = dragOver === colId;
                                const alignCls = col.align === 'center' ? 'text-center' : col.align === 'right' ? 'text-right' : '';
                                return (
                                    <th
                                        key={colId}
                                        className={alignCls}
                                        draggable
                                        style={{
                                            cursor:     col.sortField ? 'pointer' : 'default',
                                            whiteSpace: 'nowrap',
                                            userSelect: 'none',
                                            borderLeft: dropping ? '3px solid #007bff' : undefined,
                                            background: dropping ? '#e8f0fe' : undefined,
                                        }}
                                        onDragStart={() => onDragStart(colId)}
                                        onDragOver={e => onDragOver(e, colId)}
                                        onDragLeave={onDragLeave}
                                        onDrop={() => onDrop(colId)}
                                        onClick={() => col.sortField && onSort(col.sortField)}
                                    >
                                        <i className="fas fa-grip-vertical text-muted mr-1" style={{ fontSize: '0.65rem', opacity: 0.4 }} />
                                        {col.label}
                                        {col.sortField && <SortIcon field={col.sortField} sortField={sortField} sortAsc={sortAsc} />}
                                        <span
                                            role="button"
                                            style={{ marginLeft: '6px', opacity: 0.4, fontSize: '0.8rem', lineHeight: 1 }}
                                            className="text-danger"
                                            onClick={e => { e.stopPropagation(); hideCol(colId); }}
                                            onMouseEnter={e => e.currentTarget.style.opacity = 1}
                                            onMouseLeave={e => e.currentTarget.style.opacity = 0.4}
                                        >
                                            ×
                                        </span>
                                    </th>
                                );
                            })}
                            <th style={{ width: 60 }} />
                        </tr>
                        {/* Row 2 — per-column filter inputs */}
                        <tr>
                            {visibleCols.map(colId => (
                                <th key={colId} style={{ padding: '2px 4px', fontWeight: 'normal' }}>
                                    {TEXT_FILTER_COLS.has(colId) && (
                                        <input
                                            type="text"
                                            className="form-control form-control-sm"
                                            style={inputStyle}
                                            placeholder="⌕"
                                            value={colFilters[colId] ?? ''}
                                            onChange={e => setColFilters(prev => ({ ...prev, [colId]: e.target.value }))}
                                        />
                                    )}
                                    {DATE_RANGE_COLS.has(colId) && (
                                        <div style={{ display: 'flex', gap: '2px', minWidth: '200px' }}>
                                            <input
                                                type="date"
                                                className="form-control form-control-sm"
                                                style={{ ...inputStyle, flex: 1, minWidth: 0 }}
                                                title="Du"
                                                value={(colFilters[colId] ?? {}).from ?? ''}
                                                onChange={e => setColFilters(prev => ({
                                                    ...prev,
                                                    [colId]: { ...(prev[colId] ?? {}), from: e.target.value },
                                                }))}
                                            />
                                            <input
                                                type="date"
                                                className="form-control form-control-sm"
                                                style={{ ...inputStyle, flex: 1, minWidth: 0 }}
                                                title="Au"
                                                value={(colFilters[colId] ?? {}).to ?? ''}
                                                onChange={e => setColFilters(prev => ({
                                                    ...prev,
                                                    [colId]: { ...(prev[colId] ?? {}), to: e.target.value },
                                                }))}
                                            />
                                        </div>
                                    )}
                                </th>
                            ))}
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {filtered.length === 0 && (
                            <tr>
                                <td colSpan={visibleCols.length + 1} className="text-center text-muted py-3">
                                    {trans.no_data ?? 'Aucune donnée'}
                                </td>
                            </tr>
                        )}
                        {filtered.map(row => (
                            <tr key={row.id}>
                                {visibleCols.map(colId => {
                                    const col      = COLS[colId];
                                    const alignCls = col.align === 'center' ? 'text-center' : col.align === 'right' ? 'text-right' : '';
                                    return (
                                        <td key={colId} className={alignCls}>
                                            {col.render(row)}
                                        </td>
                                    );
                                })}
                                <td>
                                    <div className="d-flex" style={{ gap: '2px' }}>
                                        <a href={row.url} className="btn btn-xs btn-info" title={trans.view ?? 'Voir'}>
                                            <i className="fas fa-eye" />
                                        </a>
                                        <a href={row.pdf_url} className="btn btn-xs btn-default" title="PDF" target="_blank" rel="noreferrer">
                                            <i className="fas fa-file-pdf text-danger" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// List Tab
// ---------------------------------------------------------------------------

function ListTab({ endpoints, trans }) {
    const saved = loadFilters();

    const [rows, setRows]           = useState([]);
    const [meta, setMeta]           = useState(null);
    const [loading, setLoading]     = useState(false);
    const [search, setSearch]       = useState(saved?.search    ?? '');
    const [statuses, setStatuses]   = useState(saved?.statuses  ?? ALL_STATUSES);
    const [sortField, setSortField] = useState(saved?.sortField ?? 'created_at');
    const [sortAsc, setSortAsc]     = useState(saved?.sortAsc   ?? false);
    const [page, setPage]           = useState(1);
    const searchRef = useRef(null);

    const fetchRows = useCallback((opts = {}) => {
        setLoading(true);
        const params = new URLSearchParams({
            sort: opts.sort ?? sortField,
            asc:  (opts.asc ?? sortAsc) ? '1' : '0',
            page: opts.page ?? page,
        });
        if (opts.search ?? search) params.set('search', opts.search ?? search);
        (opts.statuses ?? statuses).forEach(s => params.append('statuses[]', s));

        apiFetch(`${endpoints.list}?${params}`)
            .then(data => { setRows(data.data); setMeta(data.meta); })
            .finally(() => setLoading(false));
    }, [search, sortField, sortAsc, page, statuses, endpoints.list]);

    useEffect(() => { fetchRows(); }, [sortField, sortAsc, page, statuses]);

    useEffect(() => {
        saveFilters({ search, statuses, sortField, sortAsc });
    }, [search, statuses, sortField, sortAsc]);

    const handleSearch = (val) => {
        setSearch(val);
        clearTimeout(searchRef.current);
        searchRef.current = setTimeout(() => {
            setPage(1);
            fetchRows({ search: val, page: 1 });
        }, 400);
    };

    const handleSort = (field) => {
        const asc = field === sortField ? !sortAsc : true;
        setSortField(field);
        setSortAsc(asc);
        setPage(1);
    };

    const handleStatusChange = (next) => {
        setStatuses(next);
        setPage(1);
    };

    return (
        <div>
            {/* Toolbar */}
            <div className="d-flex flex-wrap align-items-center mb-2" style={{ gap: '0.5rem' }}>
                <div className="input-group input-group-sm flex-shrink-0" style={{ width: 200 }}>
                    <div className="input-group-prepend">
                        <span className="input-group-text"><i className="fas fa-search" /></span>
                    </div>
                    <input
                        type="text"
                        className="form-control"
                        placeholder={trans.search ?? 'Rechercher…'}
                        value={search}
                        onChange={e => handleSearch(e.target.value)}
                    />
                </div>
                <StatusFilter selected={statuses} onChange={handleStatusChange} trans={trans} />
                <div className="flex-grow-1" />
                {meta && (
                    <small className="text-muted flex-shrink-0">
                        {meta.total} {trans.total_receipts ?? 'résultats'}
                    </small>
                )}
            </div>

            {/* Loading */}
            {loading && (
                <div className="text-center py-2">
                    <i className="fas fa-spinner fa-spin text-secondary" />
                </div>
            )}

            {/* Table */}
            {!loading && (
                <ReceiptsTable
                    rows={rows}
                    sortField={sortField}
                    sortAsc={sortAsc}
                    onSort={handleSort}
                    trans={trans}
                />
            )}

            <Pagination meta={meta} onPageChange={p => setPage(p)} />
        </div>
    );
}

// ---------------------------------------------------------------------------
// Root Component
// ---------------------------------------------------------------------------

export default function PurchaseReceiptIndex({ kpi, chartData, endpoints, trans }) {
    const [activeTab, setActiveTab] = useState('dashboard');

    return (
        <div className="card card-outline card-orange">
            <div className="card-header p-2">
                <ul className="nav nav-pills">
                    <li className="nav-item">
                        <a
                            className={`nav-link ${activeTab === 'dashboard' ? 'active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('dashboard'); }}
                        >
                            {trans.dashboard ?? 'Dashboard'}
                        </a>
                    </li>
                    <li className="nav-item">
                        <a
                            className={`nav-link ${activeTab === 'list' ? 'active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('list'); }}
                        >
                            {trans.receipt_list ?? 'Bons de réception'}
                        </a>
                    </li>
                </ul>
            </div>
            <div className="card-body p-3">
                {activeTab === 'dashboard' && (
                    <DashboardTab kpi={kpi} chartData={chartData} trans={trans} />
                )}
                {activeTab === 'list' && (
                    <ListTab endpoints={endpoints} trans={trans} />
                )}
            </div>
        </div>
    );
}
