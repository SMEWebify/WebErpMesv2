import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Helpers
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

// ---------------------------------------------------------------------------
// KPI Cards
// ---------------------------------------------------------------------------

function KPICards({ kpi, trans }) {
    const cards = [
        { value: kpi.total,     label: trans.total,    bg: 'bg-info',    icon: 'fa-building' },
        { value: kpi.clients,   label: trans.client,   bg: 'bg-success', icon: 'fa-user-tie' },
        { value: kpi.prospects, label: trans.prospect, bg: 'bg-warning', icon: 'fa-user-clock' },
        { value: kpi.suppliers, label: trans.supplier, bg: 'bg-danger',  icon: 'fa-truck' },
    ];
    return (
        <div className="row">
            {cards.map((c, i) => (
                <div key={i} className="col-lg-3 col-sm-6">
                    <div className={`small-box ${c.bg}`}>
                        <div className="inner">
                            <h3>{c.value ?? 0}</h3>
                            <p>{c.label}</p>
                        </div>
                        <div className="icon"><i className={`fas ${c.icon}`} /></div>
                    </div>
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Donut Chart — pure SVG, type distribution
// ---------------------------------------------------------------------------

const TYPE_COLORS = {
    clients:         '#28a745',
    prospects:       '#ffc107',
    suppliers:       '#dc3545',
    clientSuppliers: '#17a2b8',
};

function DonutChart({ chartData, trans }) {
    const [hovered, setHovered] = useState(null);

    const items = [
        { key: 'clients',         label: trans.client,          color: TYPE_COLORS.clients,         value: chartData.clients         ?? 0 },
        { key: 'prospects',       label: trans.prospect,        color: TYPE_COLORS.prospects,       value: chartData.prospects       ?? 0 },
        { key: 'suppliers',       label: trans.supplier,        color: TYPE_COLORS.suppliers,       value: chartData.suppliers       ?? 0 },
        { key: 'clientSuppliers', label: trans.client_supplier, color: TYPE_COLORS.clientSuppliers, value: chartData.clientSuppliers ?? 0 },
    ].filter(i => i.value > 0);

    const total = items.reduce((s, i) => s + i.value, 0);
    if (!items.length) return <p className="text-muted text-center small py-3">—</p>;

    const R = 80, r = 44, cx = 110, cy = 110, size = 220;
    let angle = -Math.PI / 2;

    const slices = items.map((item) => {
        const sweep  = (item.value / total) * 2 * Math.PI;
        const x1 = cx + R * Math.cos(angle);
        const y1 = cy + R * Math.sin(angle);
        angle += sweep;
        const x2 = cx + R * Math.cos(angle);
        const y2 = cy + R * Math.sin(angle);
        const ix1 = cx + r * Math.cos(angle);
        const iy1 = cy + r * Math.sin(angle);
        const ix2 = cx + r * Math.cos(angle - sweep);
        const iy2 = cy + r * Math.sin(angle - sweep);
        const large    = sweep > Math.PI ? 1 : 0;
        const midAngle = angle - sweep / 2;
        return {
            ...item,
            path: `M ${x1} ${y1} A ${R} ${R} 0 ${large} 1 ${x2} ${y2} L ${ix1} ${iy1} A ${r} ${r} 0 ${large} 0 ${ix2} ${iy2} Z`,
            midAngle,
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
                        <path
                            key={i}
                            d={s.path}
                            fill={s.color}
                            stroke="#fff"
                            strokeWidth="2"
                            transform={`translate(${ox}, ${oy})`}
                            style={{ cursor: 'pointer', transition: 'transform 0.15s ease' }}
                            onMouseEnter={() => setHovered(i)}
                            onMouseLeave={() => setHovered(null)}
                        />
                    );
                })}
                <text x={cx} y={cy - 6} textAnchor="middle" fontSize="20" fontWeight="700" fill="#343a40">
                    {hov ? hov.value : total}
                </text>
                <text x={cx} y={cy + 12} textAnchor="middle" fontSize="9" fill="#6c757d">
                    {hov ? hov.label : trans.total}
                </text>
            </svg>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.3rem', marginTop: '0.5rem' }}>
                {slices.map((s, i) => (
                    <div
                        key={i}
                        className="d-flex align-items-center"
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
// Monthly Bar Chart — new companies added per month
// ---------------------------------------------------------------------------

const MONTHS_KEYS = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
const CHART_GREEN = 'rgba(40,167,69,0.85)';

function niceMax(value) {
    if (value <= 0) return 5;
    const exp = Math.pow(10, Math.floor(Math.log10(value)));
    return Math.ceil(value / exp) * exp;
}

function MonthlyBarChart({ chartData, trans }) {
    const [hovered, setHovered] = useState(null);
    const monthlyNew = chartData.monthlyNew ?? {};

    const values = MONTHS_KEYS.map((_, i) => monthlyNew[i + 1] ?? 0);
    const maxVal  = niceMax(Math.max(...values, 1));

    const W = 560, H = 220;
    const PAD = { top: 12, right: 12, bottom: 36, left: 36 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top - PAD.bottom;
    const barW  = plotW / 12 * 0.65;
    const gap   = plotW / 12;

    const xBar  = (i) => PAD.left + gap * i + (gap - barW) / 2;
    const yBar  = (v) => PAD.top + plotH - (v / maxVal) * plotH;
    const Y_TICKS = 4;

    const currentMonth = new Date().getMonth(); // 0-indexed

    return (
        <div>
            <svg viewBox={`0 0 ${W} ${H}`} style={{ width: '100%', display: 'block' }}>
                {/* Y grid + labels */}
                {Array.from({ length: Y_TICKS + 1 }, (_, t) => {
                    const v  = Math.round((maxVal / Y_TICKS) * t);
                    const yp = PAD.top + plotH - (v / maxVal) * plotH;
                    return (
                        <g key={t}>
                            <line x1={PAD.left} y1={yp} x2={W - PAD.right} y2={yp} stroke="#e9ecef" strokeWidth="1" />
                            <text x={PAD.left - 4} y={yp + 4} textAnchor="end" fontSize="8" fill="#6c757d">{v}</text>
                        </g>
                    );
                })}

                {/* Bars */}
                {values.map((v, i) => {
                    const bx   = xBar(i);
                    const by   = yBar(v);
                    const bh   = plotH - (by - PAD.top);
                    const past = i < currentMonth;
                    const fill = v > 0 ? CHART_GREEN : '#dee2e6';
                    const isHov = hovered === i;
                    return (
                        <g key={i} onMouseEnter={() => setHovered(i)} onMouseLeave={() => setHovered(null)} style={{ cursor: 'default' }}>
                            <rect
                                x={bx} y={by} width={barW} height={Math.max(bh, 0)}
                                fill={fill}
                                opacity={past ? 0.7 : 1}
                                rx="2"
                            />
                            {isHov && v > 0 && (
                                <text x={bx + barW / 2} y={by - 3} textAnchor="middle" fontSize="9" fontWeight="600" fill="#343a40">{v}</text>
                            )}
                        </g>
                    );
                })}

                {/* X axis labels */}
                {MONTHS_KEYS.map((k, i) => (
                    <text
                        key={k}
                        x={xBar(i) + barW / 2}
                        y={PAD.top + plotH + 14}
                        textAnchor="middle"
                        fontSize="8"
                        fill={i === currentMonth ? '#007bff' : '#6c757d'}
                        fontWeight={i === currentMonth ? '700' : '400'}
                    >
                        {trans[k]}
                    </text>
                ))}
            </svg>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Customer / Supplier Status Badges
// ---------------------------------------------------------------------------

function CustomerBadge({ statu }) {
    if (statu === 2) return <span className="badge badge-warning"><i className="fa fa-check mr-1" />Client</span>;
    if (statu === 3) return <span className="badge badge-success"><i className="fa fa-check-double mr-1" />Prospect</span>;
    return <span className="badge badge-secondary">—</span>;
}

function SupplierBadge({ statu }) {
    if (statu === 2) return <span className="badge badge-success"><i className="fa fa-check mr-1" /></span>;
    return <span className="badge badge-secondary"><i className="fa fa-times" /></span>;
}

function ActiveBadge({ active }) {
    if (active == 1) return <span className="badge badge-success"><i className="fa fa-check" /></span>;
    return <span className="badge badge-danger"><i className="fa fa-times" /></span>;
}

// ---------------------------------------------------------------------------
// Sort Icon
// ---------------------------------------------------------------------------

function SortIcon({ field, sortField, sortAsc }) {
    if (field !== sortField) return <i className="fas fa-sort text-muted ml-1" style={{ fontSize: '0.7rem' }} />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} style={{ fontSize: '0.7rem' }} />;
}

// ---------------------------------------------------------------------------
// Type Filter
// ---------------------------------------------------------------------------

const TYPE_FILTERS = [
    { key: 'all',            label: (t) => t.view_all,       btn: 'btn-secondary' },
    { key: 'client',         label: (t) => t.client,         btn: 'btn-success' },
    { key: 'prospect',       label: (t) => t.prospect,       btn: 'btn-warning' },
    { key: 'supplier',       label: (t) => t.supplier,       btn: 'btn-danger' },
    { key: 'client_supplier',label: (t) => t.client_supplier,btn: 'btn-info' },
];

function TypeFilter({ selected, onChange, trans }) {
    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.25rem' }}>
            {TYPE_FILTERS.map(f => {
                const active = selected === f.key;
                return (
                    <button
                        key={f.key}
                        className={`btn btn-sm ${active ? f.btn : 'btn-outline-secondary'}`}
                        onClick={() => onChange(f.key)}
                    >
                        {f.label(trans)}
                    </button>
                );
            })}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Companies Table — drag-and-drop columns + per-column filters
// ---------------------------------------------------------------------------

const LS_COL_ORDER     = 'companies_table_col_order';
const LS_HIDDEN_COLS   = 'companies_table_hidden_cols';
const DEFAULT_COL_ORDER = ['code', 'label', 'active', 'statu_customer', 'statu_supplier', 'created_at'];
const TEXT_FILTER_COLS  = new Set(['code', 'label']);
const DATE_RANGE_COLS   = new Set(['created_at']);

function dmyToISO(str) {
    if (!str || !str.includes('/')) return str ?? '';
    const [d, m, y] = str.split('/');
    return `${y}-${m.padStart(2,'0')}-${d.padStart(2,'0')}`;
}

function colDefs(trans) {
    return {
        code:           { label: trans.code,            sortField: 'code',           align: '',       render: c => <code>{c.code}</code> },
        label:          { label: trans.label,           sortField: 'label',          align: '',       render: c => c.label },
        active:         { label: trans.active,          sortField: 'active',         align: 'center', render: c => <ActiveBadge active={c.active} /> },
        statu_customer: { label: trans.status_client,   sortField: 'statu_customer', align: 'center', render: c => <CustomerBadge statu={c.statu_customer} /> },
        statu_supplier: { label: trans.status_supplier, sortField: 'statu_supplier', align: 'center', render: c => <SupplierBadge statu={c.statu_supplier} /> },
        created_at:     { label: trans.created_at,      sortField: 'created_at',     align: '',       render: c => c.created_at ?? '—' },
    };
}

function matchesColFilter(c, colId, value) {
    if (DATE_RANGE_COLS.has(colId)) {
        const { from, to } = value ?? {};
        const iso = dmyToISO(c.created_at ?? '');
        if (!iso) return true;
        if (from && iso < from) return false;
        if (to   && iso > to)   return false;
        return true;
    }
    const v = (value ?? '').toLowerCase().trim();
    if (!v) return true;
    switch (colId) {
        case 'code':  return (c.code  ?? '').toLowerCase().includes(v);
        case 'label': return (c.label ?? '').toLowerCase().includes(v);
        default:      return true;
    }
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

function CompaniesTable({ companies, sortField, sortAsc, onSort, trans }) {
    const [colOrder,   setColOrder]   = useState(readSavedColOrder);
    const [hiddenCols, setHiddenCols] = useState(readSavedHiddenCols);
    const [colFilters, setColFilters] = useState({});
    const [dragOver,   setDragOver]   = useState(null);
    const dragCol = useRef(null);

    const COLS        = colDefs(trans);
    const visibleCols = colOrder.filter(c => !hiddenCols.has(c));

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

    const filtered = companies.filter(c =>
        visibleCols.every(colId => matchesColFilter(c, colId, colFilters[colId] ?? ''))
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
                        <tr>
                            {visibleCols.map(colId => {
                                const col      = COLS[colId];
                                const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                const dropping = dragOver === colId;
                                return (
                                    <th
                                        key={colId}
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
                                        onDragOver={(e) => onDragOver(e, colId)}
                                        onDragLeave={onDragLeave}
                                        onDrop={() => onDrop(colId)}
                                        onClick={() => onSort(col.sortField)}
                                    >
                                        <i className="fas fa-grip-vertical text-muted mr-1" style={{ fontSize: '0.65rem', opacity: 0.4 }} />
                                        {col.label}
                                        <SortIcon field={col.sortField} sortField={sortField} sortAsc={sortAsc} />
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
                            <tr><td colSpan={visibleCols.length + 1} className="text-center text-muted py-3">{trans.no_results}</td></tr>
                        )}
                        {filtered.map(c => (
                            <tr key={c.id}>
                                {visibleCols.map(colId => {
                                    const col      = COLS[colId];
                                    const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                    return (
                                        <td key={colId} className={alignCls}>
                                            {col.render(c)}
                                        </td>
                                    );
                                })}
                                <td>
                                    <a href={c.url} className="btn btn-xs btn-info">
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
// Create Company Modal
// ---------------------------------------------------------------------------

function CreateModal({ show, onClose, endpoints, trans }) {
    const emptyForm = { code: '', label: '', client_type: 1, civility: '', last_name: '', user_id: '', comment: '' };
    const [form,       setForm]       = useState(emptyForm);
    const [errors,     setErrors]     = useState({});
    const [saving,     setSaving]     = useState(false);
    const [selectData, setSelectData] = useState(null);

    useEffect(() => {
        if (show && !selectData) {
            apiFetch(endpoints.selectData).then(data => {
                setSelectData(data);
                setForm(prev => ({ ...prev, code: data.next_code ?? '' }));
            });
        }
        if (show) { setErrors({}); }
    }, [show]);

    const set    = (f) => (e) => setForm(prev => ({ ...prev, [f]: e.target.value }));
    const setVal = (f, v) => setForm(prev => ({ ...prev, [f]: v }));
    const fe     = (f) => errors[f] ? <span className="text-danger small d-block">{errors[f][0]}</span> : null;

    const isIndividual = parseInt(form.client_type) === 2;

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const result = await apiFetch(endpoints.store, {
                method: 'POST',
                body:   JSON.stringify(form),
            });
            if (result.redirect) {
                window.location.href = result.redirect;
            }
        } catch (err) {
            setErrors(err.errors ?? {});
        } finally {
            setSaving(false);
        }
    };

    if (!show) return null;

    return (
        <div className="modal fade show d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,.5)', zIndex: 1050 }}>
            <div className="modal-dialog modal-dialog-centered modal-xl">
                <div className="modal-content">
                    <div className="modal-header bg-success">
                        <h5 className="modal-title text-white">{trans.new_company}</h5>
                        <button type="button" className="close text-white" onClick={onClose}>&times;</button>
                    </div>
                    <form onSubmit={handleSubmit}>
                        <div className="modal-body">
                            {!selectData && (
                                <div className="text-center py-4"><i className="fas fa-spinner fa-spin fa-2x text-secondary" /></div>
                            )}
                            {selectData && (
                                <>
                                    <div className="card card-body mb-3">
                                        <div className="form-row">
                                            <div className="form-group col-md-4">
                                                <label>{trans.external_id}</label>
                                                <input className="form-control" value={form.code} onChange={set('code')} />
                                                {fe('code')}
                                            </div>
                                            <div className="form-group col-md-4">
                                                <label>{trans.customer_type}</label>
                                                <select
                                                    className="form-control"
                                                    value={form.client_type}
                                                    onChange={e => {
                                                        const t = parseInt(e.target.value);
                                                        setVal('client_type', t);
                                                        if (t === 1) { setVal('civility', ''); setVal('last_name', ''); }
                                                    }}
                                                >
                                                    <option value={1}>{trans.legal_entity}</option>
                                                    <option value={2}>{trans.individual}</option>
                                                </select>
                                                {fe('client_type')}
                                            </div>
                                            <div className="form-group col-md-4">
                                                <label>{trans.user_management}</label>
                                                <select className="form-control" value={form.user_id} onChange={set('user_id')}>
                                                    <option value="">—</option>
                                                    {selectData.users.map(u => (
                                                        <option key={u.id} value={u.id}>{u.name}</option>
                                                    ))}
                                                </select>
                                                {fe('user_id')}
                                            </div>
                                        </div>
                                        <div className="form-row">
                                            {!isIndividual && (
                                                <div className="form-group col-md-12">
                                                    <label>{trans.name_company} *</label>
                                                    <input className="form-control" value={form.label} onChange={set('label')} placeholder={trans.name_company} />
                                                    {fe('label')}
                                                </div>
                                            )}
                                            {isIndividual && (
                                                <>
                                                    <div className="form-group col-md-3">
                                                        <label>{trans.civility}</label>
                                                        <input className="form-control" value={form.civility} onChange={set('civility')} placeholder={trans.civility} />
                                                        {fe('civility')}
                                                    </div>
                                                    <div className="form-group col-md-4">
                                                        <label>{trans.first_name} *</label>
                                                        <input className="form-control" value={form.label} onChange={set('label')} placeholder={trans.first_name} />
                                                        {fe('label')}
                                                    </div>
                                                    <div className="form-group col-md-5">
                                                        <label>{trans.contact_name} *</label>
                                                        <input className="form-control" value={form.last_name} onChange={set('last_name')} placeholder={trans.contact_name} />
                                                        {fe('last_name')}
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                    <div className="card card-body">
                                        <label>{trans.comment}</label>
                                        <textarea className="form-control" rows="3" value={form.comment} onChange={set('comment')} placeholder="..." />
                                        {fe('comment')}
                                    </div>
                                </>
                            )}
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary" onClick={onClose}>{trans.cancel}</button>
                            <button type="submit" className="btn btn-success" disabled={saving || !selectData}>
                                {saving ? <><i className="fas fa-spinner fa-spin mr-1" />{trans.saving}</> : trans.save}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// LocalStorage helpers
// ---------------------------------------------------------------------------

const LS_KEY = 'companies_list_filters';

function loadFilters() {
    try {
        const raw = localStorage.getItem(LS_KEY);
        if (!raw) return null;
        return JSON.parse(raw);
    } catch { return null; }
}

function saveFilters(filters) {
    try { localStorage.setItem(LS_KEY, JSON.stringify(filters)); } catch {}
}

// ---------------------------------------------------------------------------
// List Tab
// ---------------------------------------------------------------------------

function ListTab({ endpoints, trans }) {
    const saved = loadFilters();

    const [companies,  setCompanies]  = useState([]);
    const [meta,       setMeta]       = useState(null);
    const [loading,    setLoading]    = useState(false);
    const [search,     setSearch]     = useState(saved?.search     ?? '');
    const [status,     setStatus]     = useState(saved?.status     ?? 'all');
    const [sortField,  setSortField]  = useState(saved?.sortField  ?? 'created_at');
    const [sortAsc,    setSortAsc]    = useState(saved?.sortAsc    ?? false);
    const [page,       setPage]       = useState(1);
    const [showModal,  setShowModal]  = useState(false);

    const searchTimeout = useRef(null);

    const fetchCompanies = useCallback((opts = {}) => {
        setLoading(true);
        const params = new URLSearchParams({
            search: opts.search  ?? search,
            status: opts.status  ?? status,
            sort:   opts.sort    ?? sortField,
            asc:    (opts.asc    ?? sortAsc) ? '1' : '0',
            page:   opts.page    ?? page,
        });
        apiFetch(`${endpoints.list}?${params}`)
            .then(data => { setCompanies(data.data); setMeta(data.meta); })
            .finally(() => setLoading(false));
    }, [search, status, sortField, sortAsc, page, endpoints.list]);

    useEffect(() => { fetchCompanies(); }, [sortField, sortAsc, page, status]);

    const handleSearch = (val) => {
        setSearch(val);
        clearTimeout(searchTimeout.current);
        searchTimeout.current = setTimeout(() => {
            setPage(1);
            fetchCompanies({ search: val, page: 1 });
        }, 400);
    };

    useEffect(() => {
        saveFilters({ search, status, sortField, sortAsc });
    }, [search, status, sortField, sortAsc]);

    const handleSort = (field) => {
        const asc = field === sortField ? !sortAsc : true;
        setSortField(field);
        setSortAsc(asc);
    };

    const handleStatusChange = (next) => {
        setStatus(next);
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
                        placeholder={trans.search}
                        value={search}
                        onChange={e => handleSearch(e.target.value)}
                    />
                </div>
                <TypeFilter selected={status} onChange={handleStatusChange} trans={trans} />
                <div className="flex-grow-1" />
                <button className="btn btn-sm btn-success flex-shrink-0" onClick={() => setShowModal(true)}>
                    <i className="fas fa-plus mr-1" />{trans.new_company}
                </button>
            </div>

            {loading && (
                <div className="text-center py-2">
                    <i className="fas fa-spinner fa-spin text-secondary" />
                </div>
            )}

            {!loading && (
                <CompaniesTable
                    companies={companies}
                    sortField={sortField}
                    sortAsc={sortAsc}
                    onSort={handleSort}
                    trans={trans}
                />
            )}

            <Pagination meta={meta} onPageChange={p => { setPage(p); fetchCompanies({ page: p }); }} />

            <CreateModal
                show={showModal}
                onClose={() => setShowModal(false)}
                endpoints={endpoints}
                trans={trans}
            />
        </div>
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
                <div className="col-md-4">
                    <div className="card card-teal">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-chart-pie mr-1" />{trans.statistiques}</h3>
                        </div>
                        <div className="card-body">
                            <DonutChart chartData={chartData} trans={trans} />
                        </div>
                    </div>
                </div>
                <div className="col-md-8">
                    <div className="card card-primary">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-chart-bar mr-1" />{trans.monthly_new} ({new Date().getFullYear()})</h3>
                        </div>
                        <div className="card-body">
                            <MonthlyBarChart chartData={chartData} trans={trans} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Root Component
// ---------------------------------------------------------------------------

export default function CompaniesIndex({ kpi, chartData, endpoints, trans }) {
    const [activeTab, setActiveTab] = useState('dashboard');

    return (
        <div className="card">
            <div className="card-header p-2">
                <ul className="nav nav-pills">
                    <li className="nav-item">
                        <a
                            className={`nav-link ${activeTab === 'dashboard' ? 'active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('dashboard'); }}
                        >
                            {trans.dashboard}
                        </a>
                    </li>
                    <li className="nav-item">
                        <a
                            className={`nav-link ${activeTab === 'list' ? 'active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('list'); }}
                        >
                            {trans.companies_list}
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
