import React, { useState, useEffect, useRef } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-info',    label: 'in_progress'  },
    2: { badge: 'badge-warning', label: 'to_be_posted' },
    3: { badge: 'badge-success', label: 'closed'       },
};

const STATUS_COLORS = {
    1: '#17a2b8',
    2: '#ffc107',
    3: '#28a745',
};

const ALL_STATUSES = [1, 2, 3];

const LS_COL_ORDER   = 'purchase_invoices_table_col_order';
const LS_HIDDEN_COLS = 'purchase_invoices_table_hidden_cols';

const DEFAULT_COL_ORDER = ['code', 'label', 'companie', 'lines_count', 'statu', 'created_at'];
const TEXT_FILTER_COLS  = new Set(['code', 'label', 'companie']);

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function formatDate(dateStr, locale) {
    if (!dateStr) return '—';
    try {
        const [d, m, y] = dateStr.split('/').map(Number);
        return new Intl.DateTimeFormat(locale || 'fr-FR').format(new Date(y, m - 1, d));
    } catch {
        return dateStr;
    }
}

function formatCurrency(amount, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style: 'currency', currency: currency || 'EUR',
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        }).format(amount);
    } catch {
        return `${Number(amount).toFixed(2)} ${currency ?? '€'}`;
    }
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
        const body = await res.json().catch(() => ({}));
        const err  = new Error(body.message || `HTTP ${res.status}`);
        err.status = res.status;
        throw err;
    }
    return res.json();
}

function shortAmount(v) {
    if (v >= 1_000_000) return `${(v / 1_000_000).toFixed(1)}M`;
    if (v >= 1_000)     return `${(v / 1_000).toFixed(0)}k`;
    return String(Math.round(v));
}

function niceMax(value) {
    if (value <= 0) return 100;
    const exp = Math.pow(10, Math.floor(Math.log10(value)));
    return Math.ceil(value / exp) * exp;
}

// ---------------------------------------------------------------------------
// StatusBadge
// ---------------------------------------------------------------------------

function StatusBadge({ statu, trans }) {
    const cfg   = STATUS_CONFIG[statu] ?? { badge: 'badge-secondary', label: String(statu) };
    const label = trans[cfg.label] ?? cfg.label;
    return <span className={`badge ${cfg.badge}`}>{label}</span>;
}

// ---------------------------------------------------------------------------
// StatusFilter
// ---------------------------------------------------------------------------

function StatusFilter({ active, onToggle, trans }) {
    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.25rem' }}>
            {Object.entries(STATUS_CONFIG).map(([id, cfg]) => {
                const sid      = Number(id);
                const isActive = active.includes(sid);
                return (
                    <button
                        key={sid}
                        className={`btn btn-sm ${isActive ? cfg.badge.replace('badge-', 'btn-') : 'btn-outline-secondary'}`}
                        onClick={() => onToggle(sid)}
                    >
                        {trans[cfg.label] ?? cfg.label}
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
                <div className="small-box bg-info">
                    <div className="inner">
                        <h3>{kpi.totalCount ?? 0}</h3>
                        <p>{trans.total_invoices ?? 'Total factures'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-file-invoice" /></div>
                </div>
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-warning">
                    <div className="inner">
                        <h3>{kpi.toBePostedCount ?? 0}</h3>
                        <p>{trans.to_be_posted ?? 'À lettrer'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-clock" /></div>
                </div>
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-success">
                    <div className="inner">
                        <h3>{kpi.closedCount ?? 0}</h3>
                        <p>{trans.closed ?? 'Clôturées'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-check-circle" /></div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// PieChart — SVG React
// ---------------------------------------------------------------------------

function PieChart({ data, trans }) {
    const [hovered, setHovered] = useState(null);

    const items = (data ?? []).filter(d => (d.PurchaseInvoiceCountRate ?? 0) > 0);
    const total = items.reduce((s, d) => s + Number(d.PurchaseInvoiceCountRate), 0);

    if (!items.length) return <p className="text-muted text-center small py-3">—</p>;

    const R = 80, r = 44, cx = 110, cy = 110, size = 220;
    let angle = -Math.PI / 2;

    const slices = items.map(item => {
        const value = Number(item.PurchaseInvoiceCountRate);
        const sweep = (value / total) * 2 * Math.PI;
        const x1 = cx + R * Math.cos(angle), y1 = cy + R * Math.sin(angle);
        angle += sweep;
        const x2 = cx + R * Math.cos(angle), y2 = cy + R * Math.sin(angle);
        const ix1 = cx + r * Math.cos(angle), iy1 = cy + r * Math.sin(angle);
        const ix2 = cx + r * Math.cos(angle - sweep), iy2 = cy + r * Math.sin(angle - sweep);
        const large    = sweep > Math.PI ? 1 : 0;
        const midAngle = angle - sweep / 2;
        const cfg      = STATUS_CONFIG[item.statu];
        return {
            statu: item.statu, value, midAngle,
            color: STATUS_COLORS[item.statu] ?? '#aaa',
            label: trans[cfg?.label] ?? item.statu,
            path:  `M ${x1} ${y1} A ${R} ${R} 0 ${large} 1 ${x2} ${y2} L ${ix1} ${iy1} A ${r} ${r} 0 ${large} 0 ${ix2} ${iy2} Z`,
        };
    });

    const hov = hovered !== null ? slices[hovered] : null;

    return (
        <div>
            <svg viewBox={`0 0 ${size} ${size}`} style={{ width: '100%', maxWidth: 220, display: 'block', margin: '0 auto' }}>
                {slices.map((s, i) => {
                    const isHov = hovered === i;
                    const ox = isHov ? Math.cos(s.midAngle) * 6 : 0;
                    const oy = isHov ? Math.sin(s.midAngle) * 6 : 0;
                    return (
                        <path key={i} d={s.path}
                            fill={s.color} stroke="#fff" strokeWidth="2"
                            transform={`translate(${ox}, ${oy})`}
                            style={{ cursor: 'pointer', transition: 'transform 0.15s ease' }}
                            onMouseEnter={() => setHovered(i)}
                            onMouseLeave={() => setHovered(null)}
                        />
                    );
                })}
                <text x={cx} y={cy - 6} textAnchor="middle" fontSize="18" fontWeight="700" fill="#343a40">
                    {hov ? hov.value : total}
                </text>
                <text x={cx} y={cy + 12} textAnchor="middle" fontSize="9" fill="#6c757d">
                    {hov ? hov.label : (trans.invoices ?? 'factures')}
                </text>
            </svg>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.3rem', marginTop: '0.5rem' }}>
                {slices.map((s, i) => (
                    <div key={i} className="d-flex align-items-center"
                        style={{ gap: '0.4rem', cursor: 'default', opacity: hovered !== null && hovered !== i ? 0.4 : 1, transition: 'opacity 0.15s' }}
                        onMouseEnter={() => setHovered(i)}
                        onMouseLeave={() => setHovered(null)}
                    >
                        <span style={{ width: 10, height: 10, borderRadius: 2, background: s.color, flexShrink: 0 }} />
                        <span style={{ fontSize: '0.78rem', flex: 1 }}>{s.label}</span>
                        <span style={{ fontSize: '0.78rem', fontWeight: 600 }}>
                            {s.value} <span style={{ color: '#aaa', fontWeight: 400 }}>({Math.round(s.value / total * 100)}%)</span>
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Bar Chart — SVG React
// ---------------------------------------------------------------------------

const CHART_BLUE = 'rgba(60,141,188,0.9)';

function buildMonthlyData(items) {
    return Array.from({ length: 12 }, (_, i) => {
        const found = (items ?? []).find(d => d.month === i + 1);
        return found ? parseFloat(found.purchaseSum) || 0 : 0;
    });
}

function BarChart({ data, trans, currency, locale }) {
    const [hovered, setHovered] = useState(null);

    const MONTHS = [
        trans.jan, trans.feb, trans.mar, trans.apr, trans.may, trans.jun,
        trans.jul, trans.aug, trans.sep, trans.oct, trans.nov, trans.dec,
    ];

    const values = buildMonthlyData(data.purchaseInvoiceMonthlyRecap);

    const W = 560, H = 220;
    const PAD = { top: 16, right: 16, bottom: 36, left: 52 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top - PAD.bottom;

    const maxVal  = niceMax(Math.max(...values, 1));
    const Y_TICKS = 4;
    const barW    = plotW / 12;

    const xPos = i => PAD.left + i * barW + barW * 0.1;
    const barWd = barW * 0.8;
    const yPos  = v => PAD.top + plotH - Math.min(v / maxVal, 1) * plotH;

    return (
        <div>
            <svg viewBox={`0 0 ${W} ${H}`} style={{ width: '100%', display: 'block' }}>
                {Array.from({ length: Y_TICKS + 1 }, (_, i) => {
                    const v = (maxVal / Y_TICKS) * i;
                    const y = yPos(v);
                    return (
                        <g key={i}>
                            <line x1={PAD.left} y1={y} x2={PAD.left + plotW} y2={y}
                                stroke="#dee2e6" strokeWidth="1" strokeDasharray={i === 0 ? '0' : '4,2'} />
                            <text x={PAD.left - 4} y={y + 4} textAnchor="end" fontSize="9" fill="#6c757d">
                                {shortAmount(v)}
                            </text>
                        </g>
                    );
                })}

                {values.map((v, i) => {
                    const x = xPos(i);
                    const bh = Math.max((v / maxVal) * plotH, v > 0 ? 2 : 0);
                    const y  = PAD.top + plotH - bh;
                    const isHov = hovered === i;
                    return (
                        <g key={i}
                            onMouseEnter={() => setHovered(i)}
                            onMouseLeave={() => setHovered(null)}
                            style={{ cursor: 'pointer' }}>
                            <rect x={x} y={y} width={barWd} height={bh}
                                fill={isHov ? '#2980b9' : CHART_BLUE}
                                rx="2"
                                style={{ transition: 'fill 0.1s' }} />
                            {isHov && v > 0 && (
                                <text x={x + barWd / 2} y={y - 4} textAnchor="middle" fontSize="8" fill="#343a40" fontWeight="600">
                                    {shortAmount(v)}
                                </text>
                            )}
                            <text x={x + barWd / 2} y={PAD.top + plotH + 13} textAnchor="middle" fontSize="8" fill="#6c757d">
                                {MONTHS[i] ?? ''}
                            </text>
                        </g>
                    );
                })}
            </svg>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Column definitions
// ---------------------------------------------------------------------------

function colDefs(trans) {
    return {
        code:        { label: trans.code        ?? 'Code',       sortField: 'code',        align: '',       bold: true },
        label:       { label: trans.label       ?? 'Label',      sortField: 'label',       align: ''                   },
        companie:    { label: trans.company     ?? 'Fournisseur', sortField: 'companie',   align: ''                   },
        lines_count: { label: trans.lines_count ?? 'Lignes',     sortField: 'lines_count', align: 'center'             },
        statu:       { label: trans.status      ?? 'Statut',     sortField: 'statu',       align: 'center'             },
        created_at:  { label: trans.created_at  ?? 'Créé le',    sortField: 'created_at',  align: 'center'             },
    };
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

function matchesColFilter(inv, colId, value) {
    const v = (value ?? '').toLowerCase().trim();
    if (!v) return true;
    switch (colId) {
        case 'code':     return (inv.code ?? '').toLowerCase().includes(v);
        case 'label':    return (inv.label ?? '').toLowerCase().includes(v);
        case 'companie': return (inv.companie?.label ?? '').toLowerCase().includes(v);
        default:         return true;
    }
}

// ---------------------------------------------------------------------------
// Table
// ---------------------------------------------------------------------------

function PurchaseInvoicesTable({ invoices, trans, onSort, sortField, sortAsc, locale }) {
    const [colOrder,   setColOrder]   = useState(readSavedColOrder);
    const [hiddenCols, setHiddenCols] = useState(readSavedHiddenCols);
    const [colFilters, setColFilters] = useState({});
    const [dragOver,   setDragOver]   = useState(null);
    const dragCol = useRef(null);

    const COLS        = colDefs(trans);
    const visibleCols = colOrder.filter(c => !hiddenCols.has(c) && COLS[c]);

    const hideCol = colId => {
        const next = new Set(hiddenCols); next.add(colId);
        setHiddenCols(next);
        localStorage.setItem(LS_HIDDEN_COLS, JSON.stringify([...next]));
    };
    const showCol = colId => {
        const next = new Set(hiddenCols); next.delete(colId);
        setHiddenCols(next);
        localStorage.setItem(LS_HIDDEN_COLS, JSON.stringify([...next]));
    };

    const onDragStart = colId => { dragCol.current = colId; };
    const onDragOver  = (e, colId) => { e.preventDefault(); setDragOver(colId); };
    const onDragLeave = () => setDragOver(null);
    const onDrop      = targetId => {
        const src = dragCol.current;
        if (!src || src === targetId) { setDragOver(null); return; }
        const next = [...colOrder];
        next.splice(next.indexOf(targetId), 0, next.splice(next.indexOf(src), 1)[0]);
        setColOrder(next);
        localStorage.setItem(LS_COL_ORDER, JSON.stringify(next));
        setDragOver(null);
        dragCol.current = null;
    };

    const filtered  = invoices.filter(inv => visibleCols.every(colId => matchesColFilter(inv, colId, colFilters[colId] ?? '')));
    const inputStyle = { fontSize: '0.72rem', height: '24px', padding: '1px 4px' };

    const cellRender = (inv, colId) => {
        switch (colId) {
            case 'code':        return <code>{inv.code}</code>;
            case 'label':       return inv.label;
            case 'companie':    return inv.companie?.label ?? '—';
            case 'lines_count': return <span className="badge badge-secondary">{inv.lines_count}</span>;
            case 'statu':       return <StatusBadge statu={inv.statu} trans={trans} />;
            case 'created_at':  return inv.created_at;
            default: return '—';
        }
    };

    return (
        <div>
            {hiddenCols.size > 0 && (
                <div className="mb-2 d-flex flex-wrap" style={{ gap: '4px' }}>
                    {colOrder.filter(c => hiddenCols.has(c)).map(colId => (
                        <button key={colId} type="button"
                            className="btn btn-sm btn-outline-secondary"
                            style={{ fontSize: '0.72rem', padding: '1px 8px' }}
                            onClick={() => showCol(colId)}
                            title="Réafficher la colonne"
                        >
                            + {COLS[colId]?.label ?? colId}
                        </button>
                    ))}
                </div>
            )}

            <div className="table-responsive">
                <table className="table table-hover table-sm">
                    <thead>
                        <tr>
                            {visibleCols.map(colId => {
                                const col      = COLS[colId];
                                const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                const dropping = dragOver === colId;
                                return (
                                    <th key={colId}
                                        className={alignCls}
                                        draggable
                                        onDragStart={() => onDragStart(colId)}
                                        onDragOver={e => onDragOver(e, colId)}
                                        onDragLeave={onDragLeave}
                                        onDrop={() => onDrop(colId)}
                                        style={{ borderLeft: dropping ? '2px solid #007bff' : undefined, cursor: 'grab', userSelect: 'none' }}
                                    >
                                        <div className="d-flex align-items-center" style={{ gap: '4px' }}>
                                            {col.sortField ? (
                                                <button
                                                    type="button"
                                                    className="btn btn-sm btn-link p-0 text-dark font-weight-bold text-nowrap"
                                                    style={{ fontSize: '0.82rem', textDecoration: 'none' }}
                                                    onClick={() => onSort(col.sortField)}
                                                >
                                                    {col.label}
                                                    <SortIcon field={col.sortField} sortField={sortField} sortAsc={sortAsc} />
                                                </button>
                                            ) : (
                                                <span style={{ fontSize: '0.82rem' }}>{col.label}</span>
                                            )}
                                            <button type="button"
                                                className="btn btn-sm p-0 text-muted ml-auto"
                                                style={{ fontSize: '0.65rem', lineHeight: 1, opacity: 0.4 }}
                                                onClick={() => hideCol(colId)}
                                                title="Masquer"
                                            >✕</button>
                                        </div>
                                        {TEXT_FILTER_COLS.has(colId) && (
                                            <input
                                                type="text"
                                                className="form-control mt-1"
                                                style={inputStyle}
                                                placeholder="…"
                                                value={colFilters[colId] ?? ''}
                                                onChange={e => setColFilters(f => ({ ...f, [colId]: e.target.value }))}
                                            />
                                        )}
                                    </th>
                                );
                            })}
                            <th style={{ width: '80px' }}>{trans.action ?? 'Action'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filtered.length === 0 ? (
                            <tr>
                                <td colSpan={visibleCols.length + 1} className="text-center text-muted py-4">
                                    {trans.no_data ?? 'Aucune donnée'}
                                </td>
                            </tr>
                        ) : filtered.map(inv => (
                            <tr key={inv.id}>
                                {visibleCols.map(colId => {
                                    const col      = COLS[colId];
                                    const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                    return (
                                        <td key={colId} className={alignCls} style={{ fontWeight: col.bold ? 600 : undefined }}>
                                            {cellRender(inv, colId)}
                                        </td>
                                    );
                                })}
                                <td>
                                    <a href={inv.url} className="btn btn-sm btn-outline-primary" title={trans.view ?? 'Voir'}>
                                        <i className="fas fa-folder-open" />
                                    </a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot>
                        <tr>
                            {visibleCols.map(colId => (
                                <th key={colId}>{COLS[colId]?.label ?? colId}</th>
                            ))}
                            <th>{trans.action ?? 'Action'}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

function Pagination({ meta, onPage }) {
    if (!meta || meta.last_page <= 1) return null;
    const { current_page, last_page } = meta;

    const pages = [];
    for (let p = Math.max(1, current_page - 2); p <= Math.min(last_page, current_page + 2); p++) {
        pages.push(p);
    }

    return (
        <nav>
            <ul className="pagination pagination-sm justify-content-center mb-0">
                <li className={`page-item ${current_page === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page - 1)}>&laquo;</button>
                </li>
                {pages[0] > 1 && (
                    <>
                        <li className="page-item"><button className="page-link" onClick={() => onPage(1)}>1</button></li>
                        {pages[0] > 2 && <li className="page-item disabled"><span className="page-link">…</span></li>}
                    </>
                )}
                {pages.map(p => (
                    <li key={p} className={`page-item ${p === current_page ? 'active' : ''}`}>
                        <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                    </li>
                ))}
                {pages[pages.length - 1] < last_page && (
                    <>
                        {pages[pages.length - 1] < last_page - 1 && <li className="page-item disabled"><span className="page-link">…</span></li>}
                        <li className="page-item"><button className="page-link" onClick={() => onPage(last_page)}>{last_page}</button></li>
                    </>
                )}
                <li className={`page-item ${current_page === last_page ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page + 1)}>&raquo;</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function PurchaseInvoicesIndex({
    kpi        = {},
    chartData  = {},
    endpoints  = {},
    trans      = {},
    locale,
    currency,
}) {
    const [invoices,        setInvoices]        = useState([]);
    const [meta,            setMeta]            = useState(null);
    const [loading,         setLoading]         = useState(false);
    const [error,           setError]           = useState(null);
    const [search,          setSearch]          = useState('');
    const [activeStatuses,  setActiveStatuses]  = useState(ALL_STATUSES);
    const [sortField,       setSortField]       = useState('created_at');
    const [sortAsc,         setSortAsc]         = useState(false);
    const [page,            setPage]            = useState(1);

    const searchRef = useRef('');

    const fetchData = (opts = {}) => {
        const params = new URLSearchParams();
        if (opts.search ?? searchRef.current) params.set('search', opts.search ?? searchRef.current);
        (opts.statuses ?? activeStatuses).forEach(s => params.append('statuses[]', s));
        params.set('sort', opts.sortField ?? sortField);
        params.set('asc',  opts.sortAsc  ?? sortAsc ? '1' : '0');
        params.set('page', opts.page     ?? page);

        setLoading(true);
        setError(null);
        apiFetch(`${endpoints.list}?${params}`)
            .then(res => {
                setInvoices(res.data);
                setMeta(res.meta);
            })
            .catch(err => setError(err.message))
            .finally(() => setLoading(false));
    };

    useEffect(() => { fetchData(); }, []);

    const handleSearch = value => {
        searchRef.current = value;
        setSearch(value);
        setPage(1);
        fetchData({ search: value, page: 1 });
    };

    const handleStatusToggle = sid => {
        const next = activeStatuses.includes(sid)
            ? activeStatuses.filter(s => s !== sid)
            : [...activeStatuses, sid];
        setActiveStatuses(next);
        setPage(1);
        fetchData({ statuses: next, page: 1 });
    };

    const handleSort = field => {
        const asc = sortField === field ? !sortAsc : false;
        setSortField(field);
        setSortAsc(asc);
        setPage(1);
        fetchData({ sortField: field, sortAsc: asc, page: 1 });
    };

    const handlePage = p => {
        setPage(p);
        fetchData({ page: p });
    };

    return (
        <div>
            {/* KPI */}
            <KPICards kpi={kpi} trans={trans} />

            <div className="row">
                {/* Charts */}
                <div className="col-md-3">
                    <div className="card card-teal">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-pie mr-1" />
                                {trans.status_distribution ?? 'Répartition'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <PieChart data={chartData.purchaseInvoicesDataRate} trans={trans} />
                        </div>
                    </div>
                    <div className="card card-warning">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar mr-1" />
                                {trans.monthly_recap ?? 'Récap mensuel'}
                            </h3>
                        </div>
                        <div className="card-body p-2">
                            <BarChart data={chartData} trans={trans} currency={currency} locale={locale} />
                        </div>
                    </div>
                </div>

                {/* Table */}
                <div className="col-md-9">
                    <div className="card">
                        <div className="card-body pb-2">
                            <div className="row align-items-end">
                                <div className="col-md-5">
                                    <div className="input-group input-group-sm">
                                        <div className="input-group-prepend">
                                            <span className="input-group-text"><i className="fas fa-search" /></span>
                                        </div>
                                        <input
                                            type="text"
                                            className="form-control"
                                            placeholder={trans.search ?? 'Recherche…'}
                                            value={search}
                                            onChange={e => handleSearch(e.target.value)}
                                        />
                                        {search && (
                                            <div className="input-group-append">
                                                <button className="btn btn-outline-secondary btn-sm" onClick={() => handleSearch('')}>
                                                    <i className="fas fa-times" />
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                                <div className="col-md-7">
                                    <StatusFilter active={activeStatuses} onToggle={handleStatusToggle} trans={trans} />
                                </div>
                            </div>
                        </div>

                        <div className="card-body p-0">
                            {error && (
                                <div className="alert alert-danger mx-3">{error}</div>
                            )}
                            {loading ? (
                                <div className="text-center py-5">
                                    <i className="fas fa-spinner fa-spin fa-2x text-muted" />
                                </div>
                            ) : (
                                <PurchaseInvoicesTable
                                    invoices={invoices}
                                    trans={trans}
                                    onSort={handleSort}
                                    sortField={sortField}
                                    sortAsc={sortAsc}
                                    locale={locale}
                                />
                            )}
                        </div>

                        {meta && (
                            <div className="card-footer d-flex align-items-center justify-content-between">
                                <small className="text-muted">
                                    {meta.total} {trans.results ?? 'résultats'}
                                </small>
                                <Pagination meta={meta} onPage={handlePage} />
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
