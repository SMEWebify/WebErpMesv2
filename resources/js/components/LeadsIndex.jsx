import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-info',     label: 'new' },
    2: { badge: 'badge-warning',  label: 'assigned' },
    3: { badge: 'badge-primary',  label: 'in_progress' },
    4: { badge: 'badge-success',  label: 'converted' },
    5: { badge: 'badge-danger',   label: 'lost' },
};

const PRIORITY_CONFIG = {
    1: { badge: 'badge-danger',   label: 'burning' },
    2: { badge: 'badge-warning',  label: 'hot' },
    3: { badge: 'badge-primary',  label: 'lukewarm' },
    4: { badge: 'badge-success',  label: 'cold' },
};

const STATUS_COLORS = {
    1: 'rgba(23, 162, 184, 1)',
    2: 'rgba(255, 193, 7, 1)',
    3: 'rgba(0, 123, 255, 1)',
    4: 'rgba(40, 167, 69, 1)',
    5: 'rgba(220, 53, 69, 1)',
};

const LS_COL_ORDER   = 'leads_table_col_order';
const LS_HIDDEN_COLS = 'leads_table_hidden_cols';
const LS_FILTERS     = 'leads_list_filters';

const DEFAULT_COL_ORDER = ['companie', 'contact', 'user', 'source', 'priority', 'campaign', 'statu', 'created_at'];
const TEXT_FILTER_COLS  = new Set(['companie', 'contact', 'user', 'source', 'campaign']);
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
        err.errors = body.errors ?? {};
        err.status = res.status;
        throw err;
    }
    return res.json();
}

// ---------------------------------------------------------------------------
// DonutChart — pure SVG (by status)
// ---------------------------------------------------------------------------

function DonutChart({ data, trans }) {
    const [hovered, setHovered] = useState(null);
    const size = 180;
    const cx = size / 2, cy = size / 2;
    const outerR = 70, innerR = 42;
    const total = data.reduce((s, v) => s + v.count, 0);

    if (total === 0) {
        return (
            <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
                <circle cx={cx} cy={cy} r={outerR} fill="#eee" />
                <circle cx={cx} cy={cy} r={innerR} fill="white" />
                <text x={cx} y={cy + 5} textAnchor="middle" fill="#bbb" fontSize="13">—</text>
            </svg>
        );
    }

    let angle = -Math.PI / 2;
    const slices = data.map((item) => {
        const sweep = (item.count / total) * 2 * Math.PI;
        const x1 = cx + outerR * Math.cos(angle);
        const y1 = cy + outerR * Math.sin(angle);
        const x2 = cx + outerR * Math.cos(angle + sweep);
        const y2 = cy + outerR * Math.sin(angle + sweep);
        const xi1 = cx + innerR * Math.cos(angle);
        const yi1 = cy + innerR * Math.sin(angle);
        const xi2 = cx + innerR * Math.cos(angle + sweep);
        const yi2 = cy + innerR * Math.sin(angle + sweep);
        const large = sweep > Math.PI ? 1 : 0;
        const d = [
            `M ${x1} ${y1}`,
            `A ${outerR} ${outerR} 0 ${large} 1 ${x2} ${y2}`,
            `L ${xi2} ${yi2}`,
            `A ${innerR} ${innerR} 0 ${large} 0 ${xi1} ${yi1}`,
            'Z',
        ].join(' ');
        const result = { d, color: STATUS_COLORS[item.statu] ?? '#ccc', statu: item.statu, count: item.count };
        angle += sweep;
        return result;
    });

    const hoveredItem = hovered !== null ? slices[hovered] : null;

    return (
        <div style={{ textAlign: 'center' }}>
            <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
                {slices.map((s, i) => (
                    <path key={i} d={s.d} fill={s.color}
                        opacity={hovered === null || hovered === i ? 1 : 0.4}
                        onMouseEnter={() => setHovered(i)}
                        onMouseLeave={() => setHovered(null)}
                        style={{ cursor: 'pointer', transition: 'opacity 0.15s' }}
                    />
                ))}
                <circle cx={cx} cy={cy} r={innerR} fill="white" />
                {hoveredItem ? (
                    <>
                        <text x={cx} y={cy - 4} textAnchor="middle" fill="#333" fontSize="11" fontWeight="600">
                            {trans[STATUS_CONFIG[hoveredItem.statu]?.label] ?? hoveredItem.statu}
                        </text>
                        <text x={cx} y={cy + 13} textAnchor="middle" fill="#666" fontSize="13">
                            {hoveredItem.count}
                        </text>
                    </>
                ) : (
                    <text x={cx} y={cy + 5} textAnchor="middle" fill="#333" fontSize="14" fontWeight="600">
                        {total}
                    </text>
                )}
            </svg>
            <div className="mt-1" style={{ fontSize: '0.72rem' }}>
                {slices.map((s, i) => (
                    <span key={i} className="mr-2" style={{ color: s.color, fontWeight: hovered === i ? 700 : 400 }}>
                        ● {trans[STATUS_CONFIG[s.statu]?.label] ?? s.statu}
                    </span>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// StatusBadge / PriorityBadge
// ---------------------------------------------------------------------------

function StatusBadge({ statu, trans }) {
    const cfg   = STATUS_CONFIG[statu] ?? { badge: 'badge-secondary', label: String(statu) };
    const label = trans[cfg.label] ?? cfg.label;
    return <span className={`badge ${cfg.badge}`}>{label}</span>;
}

function PriorityBadge({ priority, trans }) {
    const cfg   = PRIORITY_CONFIG[priority] ?? { badge: 'badge-secondary', label: String(priority) };
    const label = trans[cfg.label] ?? cfg.label;
    return <span className={`badge ${cfg.badge}`}>{label}</span>;
}

// ---------------------------------------------------------------------------
// StatusFilter / PriorityFilter
// ---------------------------------------------------------------------------

function StatusFilter({ active, onToggle, trans }) {
    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.25rem' }}>
            {Object.entries(STATUS_CONFIG).map(([id, cfg]) => {
                const sid      = Number(id);
                const isActive = active.includes(sid);
                return (
                    <button key={sid}
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

function PriorityFilter({ active, onToggle, trans }) {
    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.25rem' }}>
            {Object.entries(PRIORITY_CONFIG).map(([id, cfg]) => {
                const pid      = Number(id);
                const isActive = active.includes(pid);
                return (
                    <button key={pid}
                        className={`btn btn-sm ${isActive ? cfg.badge.replace('badge-', 'btn-') : 'btn-outline-secondary'}`}
                        onClick={() => onToggle(pid)}
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
// DashboardTab
// ---------------------------------------------------------------------------

function DashboardTab({ kpi, chart, byCompany, byPriority, byUser, trans }) {
    return (
        <div className="row">
            {/* Donut chart by status */}
            <div className="col-md-3">
                <div className="card card-teal">
                    <div className="card-header bg-teal">
                        <h3 className="card-title text-white">
                            <i className="fas fa-chart-pie mr-1" />{trans.statistics ?? 'Statistics'}
                        </h3>
                    </div>
                    <div className="card-body d-flex justify-content-center">
                        <DonutChart data={chart} trans={trans} />
                    </div>
                </div>

                <div className="small-box bg-purple" style={{ overflow: 'hidden' }}>
                    <div className="inner">
                        <h3>{kpi.count}</h3>
                        <p style={{ fontSize: '0.78rem' }}>{trans.lead_count ?? 'Leads'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-chart-bar" /></div>
                </div>
            </div>

            {/* By company */}
            <div className="col-md-3">
                <div className="card">
                    <div className="card-header bg-secondary">
                        <h3 className="card-title text-white" style={{ fontSize: '0.82rem' }}>
                            {trans.by_company ?? 'By company'}
                        </h3>
                    </div>
                    <div className="card-body p-0">
                        <table className="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style={{ fontSize: '0.75rem' }}>{trans.company ?? 'Company'}</th>
                                    <th style={{ fontSize: '0.75rem' }}>#</th>
                                </tr>
                            </thead>
                            <tbody>
                                {byCompany.length === 0 && (
                                    <tr><td colSpan={2} className="text-center text-muted">{trans.no_data ?? '—'}</td></tr>
                                )}
                                {byCompany.map((row, i) => (
                                    <tr key={i}>
                                        <td style={{ fontSize: '0.75rem' }}>
                                            <a href={row.company_url}>{row.company_label}</a>
                                        </td>
                                        <td style={{ fontSize: '0.75rem' }}>{row.count}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* By priority */}
            <div className="col-md-2">
                <div className="card">
                    <div className="card-header bg-warning">
                        <h3 className="card-title text-white" style={{ fontSize: '0.82rem' }}>
                            {trans.by_priority ?? 'By priority'}
                        </h3>
                    </div>
                    <div className="card-body p-0">
                        <table className="table table-sm mb-0">
                            <tbody>
                                {byPriority.length === 0 && (
                                    <tr><td colSpan={2} className="text-center text-muted">{trans.no_data ?? '—'}</td></tr>
                                )}
                                {byPriority.map((row, i) => (
                                    <tr key={i}>
                                        <td style={{ fontSize: '0.75rem' }}>
                                            <PriorityBadge priority={row.priority} trans={trans} />
                                        </td>
                                        <td style={{ fontSize: '0.75rem' }}>{row.count}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* By user */}
            <div className="col-md-4">
                <div className="card">
                    <div className="card-header bg-orange">
                        <h3 className="card-title text-white" style={{ fontSize: '0.82rem' }}>
                            {trans.by_user ?? 'By user'}
                        </h3>
                    </div>
                    <div className="card-body p-0">
                        <table className="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style={{ fontSize: '0.72rem' }}>{trans.user ?? 'User'}</th>
                                    {Object.entries(STATUS_CONFIG).map(([id, cfg]) => (
                                        <th key={id} style={{ fontSize: '0.72rem' }}>
                                            <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {byUser.length === 0 && (
                                    <tr><td colSpan={7} className="text-center text-muted">{trans.no_data ?? '—'}</td></tr>
                                )}
                                {byUser.map((row, i) => (
                                    <tr key={i}>
                                        <td style={{ fontSize: '0.72rem' }}>{row.user_name}</td>
                                        {[1, 2, 3, 4, 5].map(s => (
                                            <td key={s} style={{ fontSize: '0.72rem', textAlign: 'center' }}>
                                                {row.counts[s] ?? 0}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// LeadsTable — column drag-and-drop + hidden cols + per-col filters
// ---------------------------------------------------------------------------

function colDefs(trans) {
    return {
        companie:   { label: trans.company    ?? 'Company',  sortable: true,  align: 'left'   },
        contact:    { label: trans.contact    ?? 'Contact',  sortable: false, align: 'left'   },
        user:       { label: trans.user       ?? 'User',     sortable: false, align: 'left'   },
        source:     { label: trans.source     ?? 'Source',   sortable: true,  align: 'left'   },
        priority:   { label: trans.priority   ?? 'Priority', sortable: true,  align: 'center' },
        campaign:   { label: trans.campaign   ?? 'Campaign', sortable: true,  align: 'left'   },
        statu:      { label: trans.status     ?? 'Status',   sortable: true,  align: 'center' },
        created_at: { label: trans.created_at ?? 'Created',  sortable: true,  align: 'center' },
    };
}

function readSavedColOrder() {
    try {
        const saved = JSON.parse(localStorage.getItem(LS_COL_ORDER) ?? 'null');
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

function LeadsTable({ items, loading, trans, onSort, sortField, sortAsc, locale }) {
    const [colOrder,   setColOrder]   = useState(readSavedColOrder);
    const [hiddenCols, setHiddenCols] = useState(readSavedHiddenCols);
    const [colFilters, setColFilters] = useState({});
    const [dragOver,   setDragOver]   = useState(null);
    const dragCol = useRef(null);

    const COLS = colDefs(trans);

    const hideCol = (colId) => {
        const next = new Set(hiddenCols);
        next.add(colId);
        setHiddenCols(next);
        localStorage.setItem(LS_HIDDEN_COLS, JSON.stringify([...next]));
    };

    const showCol = (colId) => {
        const next = new Set(hiddenCols);
        next.delete(colId);
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

    const visibleCols = colOrder.filter(c => !hiddenCols.has(c) && COLS[c]);

    const filtered = items.filter(l =>
        visibleCols.every(colId => {
            if (DATE_RANGE_COLS.has(colId)) {
                const { from, to } = colFilters[colId] ?? {};
                const iso = l.created_at ?? '';
                if (!iso) return true;
                if (from && iso < from) return false;
                if (to   && iso > to)   return false;
                return true;
            }
            const val = colFilters[colId] ?? '';
            if (!val) return true;
            const v = val.toLowerCase();
            if (colId === 'companie') return (l.companie?.label ?? '').toLowerCase().includes(v);
            if (colId === 'contact')  return (l.contact?.name  ?? '').toLowerCase().includes(v);
            if (colId === 'user')     return (l.user?.name     ?? '').toLowerCase().includes(v);
            if (colId === 'source')   return (l.source  ?? '').toLowerCase().includes(v);
            if (colId === 'campaign') return (l.campaign ?? '').toLowerCase().includes(v);
            return true;
        })
    );

    const renderCell = (colId, l) => {
        switch (colId) {
            case 'companie':   return l.companie ? <span>{l.companie.label}</span> : '—';
            case 'contact':    return l.contact  ? <span>{l.contact.name}</span>  : '—';
            case 'user':       return <span>{l.user?.name ?? '—'}</span>;
            case 'source':     return <span>{l.source ?? '—'}</span>;
            case 'priority':   return <PriorityBadge priority={l.priority} trans={trans} />;
            case 'campaign':   return <span>{l.campaign ?? '—'}</span>;
            case 'statu':      return <StatusBadge statu={l.statu} trans={trans} />;
            case 'created_at': return l.created_at ? formatDate(l.created_at, locale) : '—';
            default:           return '—';
        }
    };

    const inputStyle = { fontSize: '0.72rem', height: '24px', padding: '1px 4px' };

    return (
        <div>
            {/* Hidden-column restore chips */}
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
                            + {COLS[colId]?.label ?? colId}
                        </button>
                    ))}
                </div>
            )}

            <div className="table-responsive">
                <table className="table table-hover table-sm mb-0">
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
                                        style={{
                                            cursor:     'pointer',
                                            whiteSpace: 'nowrap',
                                            userSelect: 'none',
                                            borderLeft: dropping ? '3px solid #007bff' : undefined,
                                            background: dropping ? '#e8f0fe' : undefined,
                                        }}
                                        onDragStart={() => onDragStart(colId)}
                                        onDragOver={e => onDragOver(e, colId)}
                                        onDragLeave={onDragLeave}
                                        onDrop={() => onDrop(colId)}
                                        onClick={() => col.sortable && onSort(colId)}
                                    >
                                        <i className="fas fa-grip-vertical text-muted mr-1" style={{ fontSize: '0.65rem', opacity: 0.4 }} />
                                        {col.label}
                                        {col.sortable && <SortIcon field={colId} sortField={sortField} sortAsc={sortAsc} />}
                                        <span
                                            role="button"
                                            aria-label="Masquer la colonne"
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
                            <th style={{ width: 36 }} />
                        </tr>
                        {/* Per-column filters */}
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
                                            onChange={e => setColFilters(f => ({ ...f, [colId]: e.target.value }))}
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
                                                onChange={e => setColFilters(f => ({
                                                    ...f,
                                                    [colId]: { ...(f[colId] ?? {}), from: e.target.value },
                                                }))}
                                            />
                                            <input
                                                type="date"
                                                className="form-control form-control-sm"
                                                style={{ ...inputStyle, flex: 1, minWidth: 0 }}
                                                title="Au"
                                                value={(colFilters[colId] ?? {}).to ?? ''}
                                                onChange={e => setColFilters(f => ({
                                                    ...f,
                                                    [colId]: { ...(f[colId] ?? {}), to: e.target.value },
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
                        {loading ? (
                            <tr><td colSpan={visibleCols.length + 1} className="text-center py-4"><i className="fas fa-spinner fa-spin" /></td></tr>
                        ) : filtered.length === 0 ? (
                            <tr><td colSpan={visibleCols.length + 1} className="text-center text-muted py-3">{trans.no_data ?? '—'}</td></tr>
                        ) : null}
                        {!loading && filtered.map(l => (
                            <tr key={l.id}>
                                {visibleCols.map(colId => {
                                    const alignCls = COLS[colId]?.align === 'right' ? 'text-right' : COLS[colId]?.align === 'center' ? 'text-center' : '';
                                    return <td key={colId} className={alignCls}>{renderCell(colId, l)}</td>;
                                })}
                                <td>
                                    <a href={l.url} className="btn btn-xs btn-outline-secondary" style={{ fontSize: '0.72rem', padding: '1px 6px' }}>
                                        <i className="fas fa-eye" />
                                    </a>
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
// LeadCards
// ---------------------------------------------------------------------------

function LeadCards({ items, loading, trans }) {
    if (loading) {
        return <div className="text-center py-4"><i className="fas fa-spinner fa-spin" /></div>;
    }
    if (items.length === 0) {
        return <div className="text-center text-muted py-4">{trans.no_data ?? '—'}</div>;
    }
    return (
        <div className="row">
            {items.map(l => {
                const sCfg = STATUS_CONFIG[l.statu]   ?? {};
                const pCfg = PRIORITY_CONFIG[l.priority] ?? {};
                const bg   = sCfg.badge?.replace('badge-', 'bg-') ?? 'bg-secondary';
                return (
                    <div key={l.id} className="col-md-3 mb-3">
                        <div className="card h-100">
                            <div className={`card-header ${bg} py-2`}>
                                <div className="d-flex align-items-center justify-content-between">
                                    <span className="text-white font-weight-bold text-truncate" style={{ fontSize: '0.82rem' }}>
                                        {l.companie?.label ?? '—'}
                                    </span>
                                    <span className={`badge ${pCfg.badge}`} style={{ fontSize: '0.65rem' }}>
                                        {trans[pCfg.label] ?? ''}
                                    </span>
                                </div>
                            </div>
                            <div className="card-body py-2">
                                <p className="mb-1" style={{ fontSize: '0.78rem' }}>
                                    <strong>{trans.contact ?? 'Contact'}</strong> {l.contact?.name ?? '—'}
                                </p>
                                <p className="mb-1" style={{ fontSize: '0.78rem' }}>
                                    <strong>{trans.source ?? 'Source'}</strong> {l.source ?? '—'}
                                </p>
                                {l.campaign && (
                                    <p className="mb-1" style={{ fontSize: '0.78rem' }}>
                                        <strong>{trans.campaign ?? 'Campaign'}</strong> {l.campaign}
                                    </p>
                                )}
                                <p className="mb-0" style={{ fontSize: '0.78rem' }}>
                                    <StatusBadge statu={l.statu} trans={trans} />
                                </p>
                            </div>
                            <div className="card-footer bg-secondary py-1 d-flex justify-content-between align-items-center">
                                <span className="text-white" style={{ fontSize: '0.72rem' }}>
                                    {l.user?.name ?? '—'}
                                </span>
                                <a href={l.url} className="btn btn-xs btn-light" style={{ fontSize: '0.7rem', padding: '1px 6px' }}>
                                    <i className="fas fa-eye" />
                                </a>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

// ---------------------------------------------------------------------------
// KanbanBoard
// ---------------------------------------------------------------------------

function KanbanBoard({ endpoints, trans }) {
    const [items,    setItems]    = useState([]);
    const [loading,  setLoading]  = useState(true);
    const [dragItem, setDragItem] = useState(null);
    const [dragOver, setDragOver] = useState(null);

    useEffect(() => {
        apiFetch(endpoints.kanban)
            .then(data => setItems(data))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [endpoints.kanban]);

    const handleDragStart = item => setDragItem(item);
    const handleDragOver  = (e, statusId) => { e.preventDefault(); setDragOver(statusId); };
    const handleDrop      = async (statusId) => {
        if (!dragItem || dragItem.statu === statusId) { setDragItem(null); setDragOver(null); return; }
        setItems(prev => prev.map(i => i.id === dragItem.id ? { ...i, statu: statusId } : i));
        setDragItem(null);
        setDragOver(null);
        try {
            const url = endpoints.kanbanMove.replace('__ID__', dragItem.id);
            await apiFetch(url, { method: 'PUT', body: JSON.stringify({ statu: statusId }) });
        } catch {
            setItems(prev => prev.map(i => i.id === dragItem.id ? { ...i, statu: dragItem.statu } : i));
        }
    };

    if (loading) return <div className="text-center py-4"><i className="fas fa-spinner fa-spin fa-lg" /></div>;

    return (
        <div className="d-flex" style={{ overflowX: 'auto', gap: '0.75rem', paddingBottom: '0.5rem' }}>
            {Object.entries(STATUS_CONFIG).map(([id, cfg]) => {
                const statusId     = Number(id);
                const bg           = cfg.badge.replace('badge-', 'bg-');
                const colItems     = items.filter(l => l.statu === statusId);
                const isDropTarget = dragOver === statusId;
                return (
                    <div key={statusId}
                        className="card flex-shrink-0"
                        style={{ minWidth: 200, maxWidth: 240, width: '100%', border: isDropTarget ? '2px dashed #007bff' : undefined }}
                        onDragOver={e => handleDragOver(e, statusId)}
                        onDrop={() => handleDrop(statusId)}
                        onDragLeave={() => setDragOver(null)}
                    >
                        <div className={`card-header py-1 d-flex justify-content-between align-items-center ${bg}`}>
                            <span className="text-white font-weight-bold" style={{ fontSize: '0.82rem' }}>
                                {trans[cfg.label] ?? cfg.label}
                            </span>
                            <span className="badge badge-light">{colItems.length}</span>
                        </div>
                        <div className="card-body p-2" style={{ maxHeight: 440, overflowY: 'auto' }}>
                            {colItems.map(l => (
                                <div key={l.id}
                                    className="card mb-2 shadow-sm"
                                    draggable
                                    onDragStart={() => handleDragStart(l)}
                                    style={{ cursor: 'grab', opacity: dragItem?.id === l.id ? 0.4 : 1 }}
                                >
                                    <div className="card-body p-2">
                                        <a href={l.url} className="font-weight-bold text-primary d-block mb-1"
                                            style={{ fontSize: '0.8rem' }}
                                            onClick={e => e.stopPropagation()}>
                                            {l.companie?.label ?? `#${l.id}`}
                                        </a>
                                        {l.contact && (
                                            <div className="text-muted" style={{ fontSize: '0.7rem' }}>{l.contact.name}</div>
                                        )}
                                        <div className="d-flex justify-content-between mt-1">
                                            <span style={{ fontSize: '0.68rem' }}>{l.source ?? '—'}</span>
                                            <PriorityBadge priority={l.priority} trans={trans} />
                                        </div>
                                    </div>
                                </div>
                            ))}
                            {colItems.length === 0 && (
                                <div className="text-muted text-center" style={{ fontSize: '0.75rem', padding: '0.5rem' }}>—</div>
                            )}
                        </div>
                    </div>
                );
            })}
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
    for (let p = Math.max(1, current_page - 2); p <= Math.min(last_page, current_page + 2); p++) pages.push(p);

    return (
        <nav>
            <ul className="pagination pagination-sm mb-0">
                <li className={`page-item ${current_page === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(1)}>«</button>
                </li>
                <li className={`page-item ${current_page === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page - 1)}>‹</button>
                </li>
                {pages[0] > 1 && <li className="page-item disabled"><span className="page-link">…</span></li>}
                {pages.map(p => (
                    <li key={p} className={`page-item ${p === current_page ? 'active' : ''}`}>
                        <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                    </li>
                ))}
                {pages[pages.length - 1] < last_page && <li className="page-item disabled"><span className="page-link">…</span></li>}
                <li className={`page-item ${current_page === last_page ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page + 1)}>›</button>
                </li>
                <li className={`page-item ${current_page === last_page ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(last_page)}>»</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// CreateModal
// ---------------------------------------------------------------------------

function CreateModal({ endpoints, trans, onClose }) {
    const [form, setForm] = useState({
        label: '', user_id: '', priority: '3', campaign: '', comment: '',
        companies_id: '', companies_addresses_id: '', companies_contacts_id: '',
        source: '',
    });
    const [errors,     setErrors]     = useState({});
    const [saving,     setSaving]     = useState(false);
    const [selectData, setSelectData] = useState(null);
    const [addresses,  setAddresses]  = useState([]);
    const [contacts,   setContacts]   = useState([]);

    useEffect(() => {
        apiFetch(endpoints.selectData).then(setSelectData).catch(() => {});
    }, [endpoints.selectData]);

    useEffect(() => {
        if (!form.companies_id) { setAddresses([]); setContacts([]); return; }
        apiFetch(endpoints.addresses.replace('__ID__', form.companies_id)).then(setAddresses).catch(() => {});
        apiFetch(endpoints.contacts.replace('__ID__',  form.companies_id)).then(setContacts).catch(() => {});
    }, [form.companies_id, endpoints.addresses, endpoints.contacts]);

    const set  = (k, v) => setForm(f => ({ ...f, [k]: v }));
    const save = async () => {
        setSaving(true); setErrors({});
        try {
            const data = await apiFetch(endpoints.store, {
                method: 'POST',
                body:   JSON.stringify(form),
            });
            if (data.redirect) window.location.href = data.redirect;
        } catch (e) { setErrors(e.errors ?? {}); }
        finally { setSaving(false); }
    };

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1040 }} onClick={onClose} />
            <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1050 }}>
                <div className="modal-dialog modal-lg">
                    <div className="modal-content">
                        <div className="modal-header bg-success">
                            <h5 className="modal-title text-white">{trans.new_lead ?? 'New lead'}</h5>
                            <button className="close text-white" onClick={onClose}><span>×</span></button>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                {/* Company */}
                                <div className="col-12 mb-2">
                                    <label className="mb-0 small">{trans.company ?? 'Company'} *</label>
                                    <select className={`form-control form-control-sm ${errors.companies_id ? 'is-invalid' : ''}`}
                                        value={form.companies_id} onChange={e => {
                                            set('companies_id', e.target.value);
                                            set('companies_addresses_id', '');
                                            set('companies_contacts_id', '');
                                        }}>
                                        <option value="">—</option>
                                        {(selectData?.companies ?? []).map(c => (
                                            <option key={c.id} value={c.id}>{c.label ?? c.code}</option>
                                        ))}
                                    </select>
                                    {errors.companies_id && <div className="invalid-feedback">{errors.companies_id[0]}</div>}
                                </div>
                                {/* Address */}
                                <div className="col-md-6 mb-2">
                                    <label className="mb-0 small">{trans.address_select ?? 'Address'} *</label>
                                    <select className={`form-control form-control-sm ${errors.companies_addresses_id ? 'is-invalid' : ''}`}
                                        value={form.companies_addresses_id}
                                        onChange={e => set('companies_addresses_id', e.target.value)}
                                        disabled={!form.companies_id}>
                                        <option value="">—</option>
                                        {addresses.map(a => <option key={a.id} value={a.id}>{a.label} — {a.adress}</option>)}
                                    </select>
                                    {errors.companies_addresses_id && <div className="invalid-feedback">{errors.companies_addresses_id[0]}</div>}
                                </div>
                                {/* Contact */}
                                <div className="col-md-6 mb-2">
                                    <label className="mb-0 small">{trans.contact_select ?? 'Contact'} *</label>
                                    <select className={`form-control form-control-sm ${errors.companies_contacts_id ? 'is-invalid' : ''}`}
                                        value={form.companies_contacts_id}
                                        onChange={e => set('companies_contacts_id', e.target.value)}
                                        disabled={!form.companies_id}>
                                        <option value="">—</option>
                                        {contacts.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                    </select>
                                    {errors.companies_contacts_id && <div className="invalid-feedback">{errors.companies_contacts_id[0]}</div>}
                                </div>
                                {/* User */}
                                <div className="col-md-6 mb-2">
                                    <label className="mb-0 small">{trans.user ?? 'User'} *</label>
                                    <select className={`form-control form-control-sm ${errors.user_id ? 'is-invalid' : ''}`}
                                        value={form.user_id} onChange={e => set('user_id', e.target.value)}>
                                        <option value="">—</option>
                                        {(selectData?.users ?? []).map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                                    </select>
                                    {errors.user_id && <div className="invalid-feedback">{errors.user_id[0]}</div>}
                                </div>
                                {/* Priority */}
                                <div className="col-md-6 mb-2">
                                    <label className="mb-0 small">{trans.priority ?? 'Priority'} *</label>
                                    <select className={`form-control form-control-sm ${errors.priority ? 'is-invalid' : ''}`}
                                        value={form.priority} onChange={e => set('priority', e.target.value)}>
                                        {Object.entries(PRIORITY_CONFIG).map(([id, cfg]) => (
                                            <option key={id} value={id}>{trans[cfg.label] ?? cfg.label}</option>
                                        ))}
                                    </select>
                                    {errors.priority && <div className="invalid-feedback">{errors.priority[0]}</div>}
                                </div>
                                {/* Source */}
                                <div className="col-md-6 mb-2">
                                    <label className="mb-0 small">{trans.source ?? 'Source'} *</label>
                                    <input className={`form-control form-control-sm ${errors.source ? 'is-invalid' : ''}`}
                                        value={form.source} onChange={e => set('source', e.target.value)} />
                                    {errors.source && <div className="invalid-feedback">{errors.source[0]}</div>}
                                </div>
                                {/* Campaign */}
                                <div className="col-md-6 mb-2">
                                    <label className="mb-0 small">{trans.campaign ?? 'Campaign'}</label>
                                    <input className="form-control form-control-sm"
                                        value={form.campaign} onChange={e => set('campaign', e.target.value)} />
                                </div>
                                {/* Comment */}
                                <div className="col-12 mb-2">
                                    <label className="mb-0 small">{trans.comment ?? 'Comment'}</label>
                                    <textarea className="form-control form-control-sm" rows={3}
                                        value={form.comment} onChange={e => set('comment', e.target.value)} />
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button className="btn btn-secondary btn-sm" onClick={onClose}>
                                {trans.close ?? 'Close'}
                            </button>
                            <button className="btn btn-success btn-sm" onClick={save} disabled={saving}>
                                <i className="fas fa-save mr-1" />
                                {saving ? '…' : (trans.submit ?? 'Submit')}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// ListTab
// ---------------------------------------------------------------------------

function ListTab({ endpoints, trans, locale, companieId }) {
    const [items,      setItems]      = useState([]);
    const [meta,       setMeta]       = useState(null);
    const [loading,    setLoading]    = useState(false);
    const [search,     setSearch]     = useState('');
    const [statuses,   setStatuses]   = useState([]);
    const [priorities, setPriorities] = useState([]);
    const [sortField,  setSortField]  = useState('created_at');
    const [sortAsc,    setSortAsc]    = useState(false);
    const [page,       setPage]       = useState(1);
    const [viewType,   setViewType]   = useState('table');
    const [showModal,  setShowModal]  = useState(false);
    const debounceRef = useRef(null);

    // Restore filters from localStorage
    useEffect(() => {
        try {
            const saved = localStorage.getItem(LS_FILTERS);
            if (saved) {
                const f = JSON.parse(saved);
                if (f.search     !== undefined) setSearch(f.search);
                if (f.statuses   !== undefined) setStatuses(f.statuses);
                if (f.priorities !== undefined) setPriorities(f.priorities);
                if (f.sortField  !== undefined) setSortField(f.sortField);
                if (f.sortAsc    !== undefined) setSortAsc(f.sortAsc);
                if (f.viewType   !== undefined) setViewType(f.viewType);
            }
        } catch {}
    }, []);

    // Persist filters
    useEffect(() => {
        localStorage.setItem(LS_FILTERS, JSON.stringify({ search, statuses, priorities, sortField, sortAsc, viewType }));
    }, [search, statuses, priorities, sortField, sortAsc, viewType]);

    const fetchItems = useCallback(async (overrides = {}) => {
        setLoading(true);
        const p   = overrides.page       ?? page;
        const q   = overrides.search     ?? search;
        const s   = overrides.statuses   ?? statuses;
        const pr  = overrides.priorities ?? priorities;
        const sf  = overrides.sortField  ?? sortField;
        const sa  = overrides.sortAsc    ?? sortAsc;

        const params = new URLSearchParams({ search: q, sort: sf, asc: sa ? '1' : '0', page: p });
        s.forEach(id  => params.append('statuses[]', id));
        pr.forEach(id => params.append('priorities[]', id));
        if (companieId) params.set('company_id', companieId);

        try {
            const data = await apiFetch(`${endpoints.list}?${params}`);
            setItems(data.data ?? []);
            setMeta(data.meta ?? null);
        } catch {}
        finally { setLoading(false); }
    }, [endpoints.list, page, search, statuses, priorities, sortField, sortAsc, companieId]);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => fetchItems(), 200);
        return () => clearTimeout(debounceRef.current);
    }, [fetchItems]);

    const handleSort           = field => {
        const newAsc = sortField === field ? !sortAsc : true;
        setSortField(field);
        setSortAsc(newAsc);
        setPage(1);
    };
    const handleStatusToggle   = sid => { setStatuses(s => s.includes(sid) ? s.filter(x => x !== sid) : [...s, sid]); setPage(1); };
    const handlePriorityToggle = pid => { setPriorities(p => p.includes(pid) ? p.filter(x => x !== pid) : [...p, pid]); setPage(1); };
    const handleSearch         = val => { setSearch(val); setPage(1); };
    const handlePage           = p   => setPage(p);

    return (
        <div>
            {/* Toolbar */}
            <div className="d-flex flex-wrap align-items-center mb-2" style={{ gap: '0.5rem' }}>
                {/* Search */}
                <div className="input-group input-group-sm flex-shrink-0" style={{ width: 200 }}>
                    <div className="input-group-prepend">
                        <span className="input-group-text"><i className="fas fa-search" /></span>
                    </div>
                    <input type="text" className="form-control"
                        placeholder={trans.search ?? 'Search…'}
                        value={search}
                        onChange={e => handleSearch(e.target.value)}
                    />
                </div>

                {/* Status filter */}
                <StatusFilter active={statuses} onToggle={handleStatusToggle} trans={trans} />

                {/* Priority filter */}
                <PriorityFilter active={priorities} onToggle={handlePriorityToggle} trans={trans} />

                <div className="flex-grow-1" />

                {/* View toggle */}
                <div className="btn-group btn-group-sm flex-shrink-0">
                    {[{ key: 'table', icon: 'fa-list' }, { key: 'card', icon: 'fa-th' }, { key: 'kanban', icon: 'fa-columns' }].map(v => (
                        <button key={v.key}
                            className={`btn ${viewType === v.key ? 'btn-primary' : 'btn-secondary'}`}
                            onClick={() => setViewType(v.key)}
                            title={v.key}
                        >
                            <i className={`fas ${v.icon}`} />
                        </button>
                    ))}
                </div>

                {/* New lead */}
                <button className="btn btn-sm btn-success flex-shrink-0" onClick={() => setShowModal(true)}>
                    <i className="fas fa-plus mr-1" />{trans.new_lead ?? 'New lead'}
                </button>
            </div>

            {viewType === 'table' && (
                <LeadsTable
                    items={items}
                    loading={loading}
                    trans={trans}
                    onSort={handleSort}
                    sortField={sortField}
                    sortAsc={sortAsc}
                    locale={locale}
                />
            )}
            {viewType === 'card' && (
                <LeadCards items={items} loading={loading} trans={trans} />
            )}
            {viewType === 'kanban' && (
                <KanbanBoard endpoints={endpoints} trans={trans} />
            )}

            {meta && viewType !== 'kanban' && (
                <div className="d-flex justify-content-between align-items-center mt-2">
                    <small className="text-muted">
                        {meta.total} {trans.list ?? 'leads'} — {trans.page ?? 'Page'} {meta.current_page}/{meta.last_page}
                    </small>
                    <Pagination meta={meta} onPage={handlePage} />
                </div>
            )}

            {showModal && (
                <CreateModal
                    endpoints={endpoints}
                    trans={trans}
                    onClose={() => setShowModal(false)}
                />
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function LeadsIndex({ kpi, chart, byCompany, byPriority, byUser, endpoints, trans, companieId = null }) {
    const [activeTab, setActiveTab] = useState(companieId ? 'list' : 'dashboard');
    const locale = trans.locale ?? 'fr-FR';

    return (
        <div className="card card-outline card-primary">
            <div className="card-header p-2">
                <ul className="nav nav-pills">
                    <li className="nav-item">
                        <a className={`nav-link ${activeTab === 'dashboard' ? 'active' : ''}`}
                            href="#" onClick={e => { e.preventDefault(); setActiveTab('dashboard'); }}>
                            {trans.dashboard ?? 'Dashboard'}
                        </a>
                    </li>
                    <li className="nav-item">
                        <a className={`nav-link ${activeTab === 'list' ? 'active' : ''}`}
                            href="#" onClick={e => { e.preventDefault(); setActiveTab('list'); }}>
                            {trans.list ?? 'Leads list'}
                        </a>
                    </li>
                </ul>
            </div>
            <div className="card-body">
                {activeTab === 'dashboard' && (
                    <DashboardTab
                        kpi={kpi}
                        chart={chart}
                        byCompany={byCompany}
                        byPriority={byPriority}
                        byUser={byUser}
                        trans={trans}
                    />
                )}
                {activeTab === 'list' && (
                    <ListTab
                        endpoints={endpoints}
                        trans={trans}
                        locale={locale}
                        companieId={companieId}
                    />
                )}
            </div>
        </div>
    );
}
