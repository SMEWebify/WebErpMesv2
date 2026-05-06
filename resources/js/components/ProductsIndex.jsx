import React, { useState, useEffect, useRef } from 'react';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
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
    return (
        <div className="row">
            <div className="col-lg-4">
                <div className="small-box bg-info">
                    <div className="inner">
                        <h3>{kpi.totalCount ?? 0}</h3>
                        <p>{trans.total_products}</p>
                    </div>
                    <div className="icon"><i className="fas fa-boxes" /></div>
                </div>
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-success">
                    <div className="inner">
                        <h3>
                            {kpi.soldCount ?? 0}
                            <sup style={{ fontSize: '1.2rem' }}> {kpi.soldRate ?? 0}%</sup>
                        </h3>
                        <p>{trans.sold_products}</p>
                    </div>
                    <div className="icon"><i className="fas fa-tag" /></div>
                </div>
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-warning">
                    <div className="inner">
                        <h3>
                            {kpi.purchasedCount ?? 0}
                            <sup style={{ fontSize: '1.2rem' }}> {kpi.purchasedRate ?? 0}%</sup>
                        </h3>
                        <p>{trans.purchased_products}</p>
                    </div>
                    <div className="icon"><i className="fas fa-shopping-cart" /></div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Service Pie Chart — Donut SVG
// ---------------------------------------------------------------------------

const SERVICE_COLORS = [
    '#17a2b8', '#007bff', '#28a745', '#ffc107', '#dc3545',
    '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6c757d',
];

function ServicePieChart({ chartData, trans }) {
    const [hovered, setHovered] = useState(null);

    const items = (chartData.byService ?? []).filter(s => Number(s.count) > 0);
    const total = items.reduce((s, i) => s + Number(i.count), 0);

    if (!items.length) return <p className="text-muted text-center small py-3">—</p>;

    const R = 80, r = 44, cx = 110, cy = 110, size = 220;
    let angle = -Math.PI / 2;

    const slices = items.map((item, idx) => {
        const value  = Number(item.count);
        const sweep  = (value / total) * 2 * Math.PI;
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
            value,
            color:    SERVICE_COLORS[idx % SERVICE_COLORS.length],
            label:    item.service_label ?? '—',
            path:     `M ${x1} ${y1} A ${R} ${R} 0 ${large} 1 ${x2} ${y2} L ${ix1} ${iy1} A ${r} ${r} 0 ${large} 0 ${ix2} ${iy2} Z`,
            midAngle,
        };
    });

    const hoveredItem = hovered !== null ? slices[hovered] : null;

    return (
        <div>
            <svg viewBox={`0 0 ${size} ${size}`} style={{ width: '100%', maxWidth: 220, display: 'block', margin: '0 auto' }}>
                {slices.map((s, i) => {
                    const isHov = hovered === i;
                    const ox = isHov ? Math.cos(s.midAngle) * 6 : 0;
                    const oy = isHov ? Math.sin(s.midAngle) * 6 : 0;
                    return (
                        <path key={i} d={s.path} fill={s.color} stroke="#fff" strokeWidth="2"
                            transform={`translate(${ox}, ${oy})`}
                            style={{ cursor: 'pointer', transition: 'transform 0.15s ease' }}
                            onMouseEnter={() => setHovered(i)}
                            onMouseLeave={() => setHovered(null)}
                        />
                    );
                })}
                <text x={cx} y={cy - 6} textAnchor="middle" fontSize="18" fontWeight="700" fill="#343a40">
                    {hoveredItem ? hoveredItem.value : total}
                </text>
                <text x={cx} y={cy + 12} textAnchor="middle" fontSize="9" fill="#6c757d">
                    {hoveredItem ? hoveredItem.label : trans.products_label}
                </text>
            </svg>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.3rem', marginTop: '0.5rem' }}>
                {slices.map((s, i) => (
                    <div key={i}
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
// Family Bar Chart — horizontal bars SVG
// ---------------------------------------------------------------------------

function FamilyBarChart({ chartData }) {
    const [hovered, setHovered] = useState(null);

    const families = (chartData.byFamily ?? []).slice(0, 10);
    if (!families.length) return <p className="text-muted text-center small py-3">—</p>;

    const maxCount = Math.max(...families.map(f => Number(f.count)), 1);
    const W = 500, rowH = 30;
    const PAD = { top: 8, right: 52, bottom: 8, left: 130 };
    const plotW = W - PAD.left - PAD.right;
    const H = PAD.top + families.length * rowH + PAD.bottom;

    return (
        <svg viewBox={`0 0 ${W} ${H}`} style={{ width: '100%', display: 'block' }}>
            {families.map((f, i) => {
                const count  = Number(f.count);
                const barW   = (count / maxCount) * plotW;
                const y      = PAD.top + i * rowH;
                const isHov  = hovered === i;
                const lbl    = (f.family_label ?? '—');
                const label  = lbl.length > 18 ? lbl.slice(0, 16) + '…' : lbl;
                return (
                    <g key={i}
                        onMouseEnter={() => setHovered(i)}
                        onMouseLeave={() => setHovered(null)}
                        style={{ cursor: 'default' }}
                    >
                        <rect x={0} y={y} width={W} height={rowH} fill={isHov ? '#f0f9ff' : 'transparent'} />
                        <text x={PAD.left - 8} y={y + rowH / 2 + 4}
                            textAnchor="end" fontSize="11" fill={isHov ? '#007bff' : '#555'}>
                            {label}
                        </text>
                        <rect x={PAD.left} y={y + 5} width={Math.max(barW, 0)} height={rowH - 10}
                            fill={isHov ? '#007bff' : '#17a2b8'} rx="3"
                            style={{ transition: 'fill 0.15s' }} />
                        <text x={PAD.left + barW + 5} y={y + rowH / 2 + 4}
                            fontSize="11" fontWeight={isHov ? '700' : '400'} fill="#333">
                            {count}
                        </text>
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
                <div className="col-md-5">
                    <div className="card card-info">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-pie mr-1" />
                                {trans.by_service}
                            </h3>
                        </div>
                        <div className="card-body">
                            <ServicePieChart chartData={chartData} trans={trans} />
                        </div>
                    </div>
                </div>
                <div className="col-md-7">
                    <div className="card card-teal">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar mr-1" />
                                {trans.by_family}
                            </h3>
                        </div>
                        <div className="card-body">
                            <FamilyBarChart chartData={chartData} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Products Table — drag-and-drop colonnes, filtres par colonne, masquage
// ---------------------------------------------------------------------------

const LS_COL_ORDER    = 'products_table_col_order';
const LS_HIDDEN_COLS  = 'products_table_hidden_cols';
const DEFAULT_COL_ORDER = ['code', 'label', 'service', 'family', 'sold', 'selling_price', 'purchased', 'purchased_price', 'tasks', 'created_at'];
const TEXT_FILTER_COLS  = new Set(['code', 'label', 'service', 'family']);
const DATE_RANGE_COLS   = new Set(['created_at']);

function formatPrice(value, locale) {
    if (value === null || value === undefined || value === '') return '—';
    return new Intl.NumberFormat(locale ?? 'fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
}

function dmyToISO(str) {
    if (!str || !str.includes('/')) return str ?? '';
    const [d, m, y] = str.split('/');
    return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
}

function SortIcon({ field, sortField, sortAsc }) {
    if (field !== sortField) return <i className="fas fa-sort text-muted ml-1" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

function BoolBadge({ value }) {
    if (value === 1) return <span className="badge badge-success"><i className="fas fa-check" /></span>;
    return <span className="badge badge-danger"><i className="fas fa-times" /></span>;
}

function colDefs(trans) {
    return {
        code:       { label: trans.code,       sortField: 'code',       align: '',       render: p => <code>{p.code}</code> },
        label:      { label: trans.label,      sortField: 'label',      align: '',       render: p => p.label },
        service:    { label: trans.service,    sortField: null,         align: '',       render: p => p.service ?? '—' },
        family:     { label: trans.family,     sortField: null,         align: '',       render: p => p.family ?? '—' },
        sold:           { label: trans.sold,           sortField: 'sold',           align: 'center', render: p => <BoolBadge value={p.sold} /> },
        selling_price:  { label: trans.selling_price,  sortField: 'selling_price',  align: 'right',  render: p => p.sold === 1 ? formatPrice(p.selling_price, trans.locale) : <span className="text-muted">—</span> },
        purchased:      { label: trans.purchased,      sortField: 'purchased',      align: 'center', render: p => <BoolBadge value={p.purchased} /> },
        purchased_price:{ label: trans.purchased_price,sortField: 'purchased_price',align: 'right',  render: p => p.purchased === 1 ? formatPrice(p.purchased_price, trans.locale) : <span className="text-muted">—</span> },
        tasks:      { label: trans.tasks,      sortField: null,         align: 'center', render: p => <span className="badge badge-secondary">{p.task_count}</span> },
        created_at: { label: trans.created_at, sortField: 'created_at', align: '',       render: p => p.created_at },
    };
}

function matchesColFilter(product, colId, value) {
    if (DATE_RANGE_COLS.has(colId)) {
        const { from, to } = value ?? {};
        const iso = dmyToISO(product.created_at ?? '');
        if (!iso) return true;
        if (from && iso < from) return false;
        if (to   && iso > to)   return false;
        return true;
    }
    const v = (value ?? '').toLowerCase().trim();
    if (!v) return true;
    switch (colId) {
        case 'code':    return (product.code    ?? '').toLowerCase().includes(v);
        case 'label':   return (product.label   ?? '').toLowerCase().includes(v);
        case 'service': return (product.service ?? '').toLowerCase().includes(v);
        case 'family':  return (product.family  ?? '').toLowerCase().includes(v);
        default:        return true;
    }
}

function readSavedColOrder() {
    try {
        const saved = JSON.parse(localStorage.getItem(LS_COL_ORDER));
        if (Array.isArray(saved)) {
            const valid   = saved.filter(c => DEFAULT_COL_ORDER.includes(c));
            const missing = DEFAULT_COL_ORDER.filter(c => !valid.includes(c));
            return [...valid, ...missing];
        }
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

function ProductsTable({ products, loading, sortField, sortAsc, onSort, trans }) {
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

    const filtered = products.filter(p =>
        visibleCols.every(colId => matchesColFilter(p, colId, colFilters[colId] ?? ''))
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
                        <button key={colId} type="button"
                            className="btn btn-sm btn-outline-secondary"
                            style={{ fontSize: '0.72rem', padding: '1px 8px' }}
                            onClick={() => showCol(colId)}
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
                                const sortable = col.sortField !== null;
                                return (
                                    <th key={colId} className={alignCls} draggable
                                        style={{
                                            cursor: sortable ? 'pointer' : 'default',
                                            whiteSpace: 'nowrap', userSelect: 'none',
                                            borderLeft: dropping ? '3px solid #007bff' : undefined,
                                            background: dropping ? '#e8f0fe' : undefined,
                                        }}
                                        onDragStart={() => onDragStart(colId)}
                                        onDragOver={(e) => onDragOver(e, colId)}
                                        onDragLeave={onDragLeave}
                                        onDrop={() => onDrop(colId)}
                                        onClick={() => sortable && onSort(col.sortField)}
                                    >
                                        <i className="fas fa-grip-vertical text-muted mr-1" style={{ fontSize: '0.65rem', opacity: 0.4 }} />
                                        {col.label}
                                        {sortable && <SortIcon field={col.sortField} sortField={sortField} sortAsc={sortAsc} />}
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
                        <tr>
                            {visibleCols.map(colId => (
                                <th key={colId} style={{ padding: '2px 4px', fontWeight: 'normal' }}>
                                    {TEXT_FILTER_COLS.has(colId) && (
                                        <input type="text" className="form-control form-control-sm"
                                            style={inputStyle} placeholder="⌕"
                                            value={colFilters[colId] ?? ''}
                                            onChange={e => setColFilters(prev => ({ ...prev, [colId]: e.target.value }))}
                                        />
                                    )}
                                    {DATE_RANGE_COLS.has(colId) && (
                                        <div style={{ display: 'flex', gap: '2px', minWidth: '200px' }}>
                                            <input type="date" className="form-control form-control-sm"
                                                style={{ ...inputStyle, flex: 1, minWidth: 0 }} title="Du"
                                                value={(colFilters[colId] ?? {}).from ?? ''}
                                                onChange={e => setColFilters(prev => ({
                                                    ...prev, [colId]: { ...(prev[colId] ?? {}), from: e.target.value },
                                                }))}
                                            />
                                            <input type="date" className="form-control form-control-sm"
                                                style={{ ...inputStyle, flex: 1, minWidth: 0 }} title="Au"
                                                value={(colFilters[colId] ?? {}).to ?? ''}
                                                onChange={e => setColFilters(prev => ({
                                                    ...prev, [colId]: { ...(prev[colId] ?? {}), to: e.target.value },
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
                            <tr><td colSpan={visibleCols.length + 1} className="text-center text-muted py-3">{trans.no_results}</td></tr>
                        ) : null}
                        {!loading && filtered.map(p => (
                            <tr key={p.id}>
                                {visibleCols.map(colId => {
                                    const col      = COLS[colId];
                                    const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                    return (
                                        <td key={colId} className={alignCls}>
                                            {col.render(p)}
                                        </td>
                                    );
                                })}
                                <td style={{ whiteSpace: 'nowrap' }}>
                                    <a href={p.url} className="btn btn-xs btn-info" title={trans.view}>
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

    const current = meta.current_page;
    const last = meta.last_page;
    const delta = 2;

    const range = [];
    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
    }

    const items = [1];
    if (range[0] > 2) items.push('…left');
    items.push(...range);
    if (range[range.length - 1] < last - 1) items.push('…right');
    if (last > 1) items.push(last);

    return (
        <nav>
            <ul className="pagination pagination-sm justify-content-end flex-wrap">
                <li className={`page-item ${current === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPageChange(current - 1)}>«</button>
                </li>
                {items.map((p, i) =>
                    typeof p === 'string' ? (
                        <li key={p} className="page-item disabled">
                            <span className="page-link">…</span>
                        </li>
                    ) : (
                        <li key={p} className={`page-item ${p === current ? 'active' : ''}`}>
                            <button className="page-link" onClick={() => onPageChange(p)}>{p}</button>
                        </li>
                    )
                )}
                <li className={`page-item ${current === last ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPageChange(current + 1)}>»</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// Create Product Modal
// ---------------------------------------------------------------------------

const EMPTY_FORM = {
    code: '', label: '', ind: '',
    methods_services_id: '', methods_families_id: '', methods_units_id: '',
    sold: '', purchased: '', tracability_type: '',
    purchased_price: '', selling_price: '',
    material: '', thickness: '', weight: '', finishing: '',
    x_size: '', y_size: '', z_size: '',
    x_oversize: '', y_oversize: '', z_oversize: '',
    diameter: '', diameter_oversize: '', section_size: '',
    qty_eco_min: '', qty_eco_max: '',
    comment: '',
};

function CreateModal({ endpoints, trans, onClose }) {
    const [selectData, setSelectData] = useState(null);
    const [form, setForm]             = useState({ ...EMPTY_FORM });
    const [errors, setErrors]         = useState({});
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        apiFetch(endpoints.selectData).then(setSelectData).catch(() => {});
    }, []);

    const set = (field) => (e) => setForm(prev => ({ ...prev, [field]: e.target.value }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});
        try {
            const data = await apiFetch(endpoints.store, {
                method: 'POST',
                body: JSON.stringify(form),
            });
            window.location.href = data.url;
        } catch (err) {
            if (err.errors) setErrors(err.errors);
            setSubmitting(false);
        }
    };

    const textField = (name, label, required = false, extra = {}) => (
        <div className="form-group mb-2">
            <label className="mb-1" style={{ fontSize: '0.85rem' }}>
                {label}{required && <span className="text-danger"> *</span>}
            </label>
            <input type="text" className={`form-control form-control-sm ${errors[name] ? 'is-invalid' : ''}`}
                value={form[name]} onChange={set(name)} {...extra} />
            {errors[name] && <div className="invalid-feedback">{errors[name][0]}</div>}
        </div>
    );

    const numField = (name, label, extra = {}) => (
        <div className="form-group mb-2">
            <label className="mb-1" style={{ fontSize: '0.85rem' }}>{label}</label>
            <input type="number" className={`form-control form-control-sm ${errors[name] ? 'is-invalid' : ''}`}
                value={form[name]} onChange={set(name)} min="0" step="0.001" {...extra} />
            {errors[name] && <div className="invalid-feedback">{errors[name][0]}</div>}
        </div>
    );

    const selectField = (name, label, options, required = false) => (
        <div className="form-group mb-2">
            <label className="mb-1" style={{ fontSize: '0.85rem' }}>
                {label}{required && <span className="text-danger"> *</span>}
            </label>
            <select className={`form-control form-control-sm ${errors[name] ? 'is-invalid' : ''}`}
                value={form[name]} onChange={set(name)}>
                <option value="">—</option>
                {(options ?? []).map(o => <option key={o.id} value={o.id}>{o.label}</option>)}
            </select>
            {errors[name] && <div className="invalid-feedback">{errors[name][0]}</div>}
        </div>
    );

    return (
        <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}
            onClick={e => e.target === e.currentTarget && onClose()}>
            <div className="modal-dialog modal-xl" onClick={e => e.stopPropagation()}>
                <div className="modal-content">
                    <div className="modal-header bg-success">
                        <h5 className="modal-title text-white">{trans.new_product}</h5>
                        <button type="button" className="close text-white" onClick={onClose}>
                            <span>×</span>
                        </button>
                    </div>
                    <div className="modal-body" style={{ maxHeight: '80vh', overflowY: 'auto' }}>
                        <form onSubmit={handleSubmit}>

                            {/* Identification */}
                            <div className="card card-body mb-3 bg-light">
                                <div className="row">
                                    <div className="col-md-4">{textField('code', trans.code, true)}</div>
                                    <div className="col-md-4">{textField('label', trans.label, true)}</div>
                                    <div className="col-md-4">{textField('ind', trans.ind)}</div>
                                </div>
                            </div>

                            {/* Classification */}
                            <div className="card card-body mb-3 bg-light">
                                {!selectData ? (
                                    <div className="text-center py-2">
                                        <i className="fas fa-spinner fa-spin text-muted" />
                                    </div>
                                ) : (
                                    <div className="row">
                                        <div className="col-md-4">{selectField('methods_services_id', trans.service, selectData.services, true)}</div>
                                        <div className="col-md-4">{selectField('methods_families_id', trans.family, selectData.families, true)}</div>
                                        <div className="col-md-4">{selectField('methods_units_id', trans.unit, selectData.units, true)}</div>
                                    </div>
                                )}
                            </div>

                            {/* Statut + Prix */}
                            <div className="card card-body mb-3 bg-light">
                                <div className="row">
                                    <div className="col-md-4">
                                        <div className="form-group mb-2">
                                            <label className="mb-1" style={{ fontSize: '0.85rem' }}>{trans.sold}<span className="text-danger"> *</span></label>
                                            <select className={`form-control form-control-sm ${errors.sold ? 'is-invalid' : ''}`}
                                                value={form.sold} onChange={set('sold')}>
                                                <option value="">—</option>
                                                <option value="2">{trans.no}</option>
                                                <option value="1">{trans.yes}</option>
                                            </select>
                                            {errors.sold && <div className="invalid-feedback">{errors.sold[0]}</div>}
                                        </div>
                                    </div>
                                    <div className="col-md-4">
                                        <div className="form-group mb-2">
                                            <label className="mb-1" style={{ fontSize: '0.85rem' }}>{trans.purchased}<span className="text-danger"> *</span></label>
                                            <select className={`form-control form-control-sm ${errors.purchased ? 'is-invalid' : ''}`}
                                                value={form.purchased} onChange={set('purchased')}>
                                                <option value="">—</option>
                                                <option value="2">{trans.no}</option>
                                                <option value="1">{trans.yes}</option>
                                            </select>
                                            {errors.purchased && <div className="invalid-feedback">{errors.purchased[0]}</div>}
                                        </div>
                                    </div>
                                    <div className="col-md-4">
                                        <div className="form-group mb-2">
                                            <label className="mb-1" style={{ fontSize: '0.85rem' }}>{trans.tracability}<span className="text-danger"> *</span></label>
                                            <select className={`form-control form-control-sm ${errors.tracability_type ? 'is-invalid' : ''}`}
                                                value={form.tracability_type} onChange={set('tracability_type')}>
                                                <option value="">—</option>
                                                <option value="1">{trans.no_traceability}</option>
                                                <option value="2">{trans.batch_number}</option>
                                                <option value="3">{trans.serial_number}</option>
                                            </select>
                                            {errors.tracability_type && <div className="invalid-feedback">{errors.tracability_type[0]}</div>}
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-md-4">{numField('purchased_price', trans.purchased_price)}</div>
                                    <div className="col-md-4">{numField('selling_price', trans.selling_price)}</div>
                                </div>
                            </div>

                            {/* Propriétés physiques */}
                            <div className="card card-body mb-3 bg-light">
                                <div className="row">
                                    <div className="col-md-4">{textField('material', trans.material)}</div>
                                    <div className="col-md-4">{numField('thickness', trans.thickness)}</div>
                                    <div className="col-md-4">{numField('weight', trans.weight)}</div>
                                </div>
                                <div className="row">
                                    <div className="col-md-4">{textField('finishing', trans.finishing)}</div>
                                </div>
                                <hr className="my-2" />
                                <div className="row">
                                    <div className="col-md-2">{numField('x_size', 'X')}</div>
                                    <div className="col-md-2">{numField('y_size', 'Y')}</div>
                                    <div className="col-md-2">{numField('z_size', 'Z')}</div>
                                    <div className="col-md-2">{numField('x_oversize', 'X+')}</div>
                                    <div className="col-md-2">{numField('y_oversize', 'Y+')}</div>
                                    <div className="col-md-2">{numField('z_oversize', 'Z+')}</div>
                                </div>
                                <hr className="my-2" />
                                <div className="row">
                                    <div className="col-md-4">{numField('diameter', trans.diameter)}</div>
                                    <div className="col-md-4">{numField('diameter_oversize', trans.diameter_oversize)}</div>
                                    <div className="col-md-4">{numField('section_size', trans.section_size)}</div>
                                </div>
                            </div>

                            {/* Autres informations */}
                            <div className="card card-body mb-3 bg-light">
                                <div className="row">
                                    <div className="col-md-4">{numField('qty_eco_min', trans.qty_eco_min)}</div>
                                    <div className="col-md-4">{numField('qty_eco_max', trans.qty_eco_max)}</div>
                                    <div className="col-md-4">
                                        <div className="form-group mb-2">
                                            <label className="mb-1" style={{ fontSize: '0.85rem' }}>{trans.comment}</label>
                                            <textarea className="form-control form-control-sm" rows="3"
                                                value={form.comment} onChange={set('comment')} />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="d-flex justify-content-end" style={{ gap: '0.5rem' }}>
                                <button type="button" className="btn btn-secondary" onClick={onClose}>{trans.close}</button>
                                <button type="submit" className="btn btn-success" disabled={submitting}>
                                    {submitting
                                        ? <i className="fas fa-spinner fa-spin mr-1" />
                                        : <i className="fas fa-save mr-1" />}
                                    {trans.submit}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// LocalStorage helpers
// ---------------------------------------------------------------------------

const LS_FILTERS = 'products_list_filters';

function loadFilters() {
    try {
        const raw = localStorage.getItem(LS_FILTERS);
        if (!raw) return null;
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function saveFilters(filters) {
    try { localStorage.setItem(LS_FILTERS, JSON.stringify(filters)); } catch {}
}

// ---------------------------------------------------------------------------
// List Tab
// ---------------------------------------------------------------------------

function ListTab({ endpoints, trans }) {
    const saved = loadFilters();

    const [products, setProducts]         = useState([]);
    const [meta, setMeta]                 = useState(null);
    const [loading, setLoading]           = useState(false);
    const [search, setSearch]             = useState(saved?.search ?? '');
    const [filterSold, setFilterSold]     = useState(saved?.filterSold ?? '');
    const [filterPurchased, setFilterPurchased] = useState(saved?.filterPurchased ?? '');
    const [sortField, setSortField]       = useState(saved?.sortField ?? 'created_at');
    const [sortAsc, setSortAsc]           = useState(saved?.sortAsc   ?? false);
    const [page, setPage]                 = useState(1);
    const [showModal, setShowModal]       = useState(false);

    const searchTimeout = useRef(null);

    const fetchProducts = (params = {}) => {
        const qs = new URLSearchParams({
            search: params.search      ?? search,
            sort:   params.sortField   ?? sortField,
            asc:    (params.sortAsc    ?? sortAsc) ? '1' : '0',
            page:   params.page        ?? page,
        });
        const sold      = params.filterSold      ?? filterSold;
        const purchased = params.filterPurchased ?? filterPurchased;
        if (sold)      qs.set('sold',      sold);
        if (purchased) qs.set('purchased', purchased);

        setLoading(true);
        apiFetch(`${endpoints.list}?${qs}`)
            .then(res => { setProducts(res.data); setMeta(res.meta); })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        fetchProducts();
    }, [page, filterSold, filterPurchased, sortField, sortAsc]);

    useEffect(() => {
        saveFilters({ search, filterSold, filterPurchased, sortField, sortAsc });
    }, [search, filterSold, filterPurchased, sortField, sortAsc]);

    const handleSearchChange = (e) => {
        const val = e.target.value;
        setSearch(val);
        clearTimeout(searchTimeout.current);
        searchTimeout.current = setTimeout(() => {
            setPage(1);
            fetchProducts({ search: val, page: 1 });
        }, 350);
    };

    const handleSort = (field) => {
        const nextAsc = sortField === field ? !sortAsc : false;
        setSortField(field);
        setSortAsc(nextAsc);
        setPage(1);
    };

    const toggleSold = (val) => {
        const next = filterSold === val ? '' : val;
        setFilterSold(next);
        setPage(1);
    };

    const togglePurchased = (val) => {
        const next = filterPurchased === val ? '' : val;
        setFilterPurchased(next);
        setPage(1);
    };

    return (
        <div>
            {/* Toolbar */}
            <div className="d-flex flex-wrap align-items-center mb-3" style={{ gap: '0.75rem' }}>
                <div className="input-group" style={{ maxWidth: 280 }}>
                    <div className="input-group-prepend">
                        <span className="input-group-text"><i className="fas fa-search" /></span>
                    </div>
                    <input type="text" className="form-control"
                        placeholder={trans.search}
                        value={search}
                        onChange={handleSearchChange}
                    />
                </div>

                {/* Filtres vendu / acheté */}
                <button
                    className={`btn btn-sm ${filterSold === '1' ? 'btn-success' : 'btn-outline-success'}`}
                    onClick={() => toggleSold('1')}
                >
                    <i className="fas fa-tag mr-1" />{trans.sold}
                </button>
                <button
                    className={`btn btn-sm ${filterPurchased === '1' ? 'btn-warning' : 'btn-outline-warning'}`}
                    onClick={() => togglePurchased('1')}
                >
                    <i className="fas fa-shopping-cart mr-1" />{trans.purchased}
                </button>

                <div className="ml-auto">
                    <button className="btn btn-success" onClick={() => setShowModal(true)}>
                        <i className="fas fa-plus mr-1" />{trans.new_product}
                    </button>
                </div>
            </div>

            <ProductsTable
                products={products}
                loading={loading}
                sortField={sortField}
                sortAsc={sortAsc}
                onSort={handleSort}
                trans={trans}
            />

            <Pagination meta={meta} onPageChange={(p) => setPage(p)} />

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
// Merge Modal
// ---------------------------------------------------------------------------

function MergeModal({ group, endpoints, onClose, onMerged }) {
    const [masterId, setMasterId]   = useState(group.products[0].id);
    const [preview, setPreview]     = useState(null);
    const [loading, setLoading]     = useState(false);
    const [merging, setMerging]     = useState(false);
    const [error, setError]         = useState(null);

    const duplicateId = group.products.find(p => p.id !== masterId)?.id;

    const loadPreview = async () => {
        setLoading(true);
        setError(null);
        try {
            const data = await apiFetch(
                `${endpoints.mergeBaseUrl}/${masterId}/${duplicateId}/preview`
            );
            setPreview(data);
        } catch {
            setError('Erreur lors du chargement de l\'aperçu.');
        } finally {
            setLoading(false);
        }
    };

    const handleMerge = async () => {
        setMerging(true);
        setError(null);
        try {
            await apiFetch(`${endpoints.mergeBaseUrl}/${masterId}/${duplicateId}`, { method: 'POST' });
            onMerged();
        } catch {
            setError('Erreur lors de la fusion.');
            setMerging(false);
        }
    };

    const impactRow = (label, count) => count > 0 ? (
        <div className="d-flex justify-content-between py-1 border-bottom">
            <span className="text-muted" style={{ fontSize: '0.85rem' }}>{label}</span>
            <span className="badge badge-warning">{count}</span>
        </div>
    ) : null;

    return (
        <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}
            onClick={e => e.target === e.currentTarget && onClose()}>
            <div className="modal-dialog modal-lg" onClick={e => e.stopPropagation()}>
                <div className="modal-content">
                    <div className="modal-header bg-warning">
                        <h5 className="modal-title">
                            <i className="fas fa-code-branch mr-2" />
                            Fusionner les doublons — code <code>{group.code}</code>
                        </h5>
                        <button type="button" className="close" onClick={onClose}><span>×</span></button>
                    </div>

                    <div className="modal-body">
                        {/* Choix du master */}
                        <p className="mb-2" style={{ fontSize: '0.85rem' }}>
                            <strong>Choisissez le produit maître</strong> à conserver.
                            L'autre sera supprimé et toutes ses références seront transférées.
                        </p>
                        <div className="row mb-3">
                            {group.products.map(p => (
                                <div key={p.id} className="col-md-6">
                                    <div
                                        className={`card mb-0 ${masterId === p.id ? 'border-success' : 'border-secondary'}`}
                                        style={{ cursor: 'pointer' }}
                                        onClick={() => { setMasterId(p.id); setPreview(null); }}
                                    >
                                        <div className="card-body py-2 px-3">
                                            <div className="d-flex align-items-center" style={{ gap: '0.5rem' }}>
                                                <input type="radio" readOnly checked={masterId === p.id} />
                                                <div>
                                                    <div style={{ fontWeight: 600 }}>{p.label}</div>
                                                    <div style={{ fontSize: '0.78rem', color: '#888' }}>
                                                        {p.service && <span className="mr-2">{p.service}</span>}
                                                        {p.family && <span>{p.family}</span>}
                                                    </div>
                                                </div>
                                                {masterId === p.id && (
                                                    <span className="badge badge-success ml-auto">Maître</span>
                                                )}
                                                {masterId !== p.id && (
                                                    <span className="badge badge-danger ml-auto">Doublon</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Bouton aperçu */}
                        {!preview && (
                            <div className="text-center mb-3">
                                <button className="btn btn-outline-info btn-sm" onClick={loadPreview} disabled={loading}>
                                    {loading
                                        ? <><i className="fas fa-spinner fa-spin mr-1" />Chargement…</>
                                        : <><i className="fas fa-eye mr-1" />Voir l'impact</>
                                    }
                                </button>
                            </div>
                        )}

                        {/* Aperçu */}
                        {preview && (
                            <div className="card card-body bg-light mb-3" style={{ fontSize: '0.85rem' }}>
                                <h6 className="mb-2">Impact du transfert</h6>

                                {impactRow('Lignes de devis', preview.impact.quote_lines)}
                                {impactRow('Lignes de commande', preview.impact.order_lines)}
                                {impactRow('Lignes d\'achat', preview.impact.purchase_lines)}
                                {impactRow('Lignes devis fournisseur', preview.impact.purchase_quotation_lines)}
                                {impactRow('Lignes avoir', preview.impact.credit_note_lines)}
                                {impactRow('Tâches', preview.impact.tasks)}
                                {impactRow('Sous-assemblages', preview.impact.sub_assemblies)}
                                {impactRow('Numéros de série', preview.impact.serial_numbers)}
                                {impactRow('Lots', preview.impact.batches)}
                                {impactRow('AMDEC qualité', preview.impact.quality_amdecs)}
                                {impactRow('Lignes pré-commande', preview.impact.pre_order_lines)}

                                {/* Stock */}
                                {preview.impact.stock.length > 0 && (
                                    <div className="mt-2">
                                        <div className="font-weight-bold mb-1">Stock</div>
                                        {preview.impact.stock.map((s, i) => (
                                            <div key={i} className="d-flex justify-content-between py-1 border-bottom">
                                                <span className="text-muted">Emplacement #{s.location_id}</span>
                                                <span>
                                                    {s.master_qty} + {s.dup_qty}
                                                    {' → '}
                                                    <strong>{s.merged_qty}</strong>
                                                    {s.conflict && <span className="badge badge-info ml-1">fusion</span>}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {/* Prix clients */}
                                <div className="alert alert-warning mt-2 mb-0 py-2 px-3" style={{ fontSize: '0.8rem' }}>
                                    <i className="fas fa-exclamation-triangle mr-1" />
                                    <strong>Tarifs clients :</strong>{' '}
                                    {preview.impact.customer_price_lists.duplicate_count > 0
                                        ? `Les ${preview.impact.customer_price_lists.duplicate_count} tarif(s) du doublon seront supprimés. `
                                        : 'Aucun tarif sur le doublon. '}
                                    {preview.impact.customer_price_lists.master_count > 0
                                        ? `Les ${preview.impact.customer_price_lists.master_count} tarif(s) du maître sont conservés.`
                                        : 'Aucun tarif sur le maître.'}
                                </div>

                                {preview.impact.quantity_prices.duplicate_count > 0 && (
                                    <div className="alert alert-warning mt-1 mb-0 py-2 px-3" style={{ fontSize: '0.8rem' }}>
                                        <i className="fas fa-exclamation-triangle mr-1" />
                                        <strong>Tarifs quantité :</strong>{' '}
                                        {`Les ${preview.impact.quantity_prices.duplicate_count} tarif(s) quantité du doublon seront supprimés. `}
                                        {preview.impact.quantity_prices.master_count > 0
                                            ? `Les ${preview.impact.quantity_prices.master_count} tarif(s) quantité du maître sont conservés.`
                                            : 'Aucun tarif quantité sur le maître.'}
                                    </div>
                                )}
                            </div>
                        )}

                        {error && (
                            <div className="alert alert-danger py-2">{error}</div>
                        )}
                    </div>

                    <div className="modal-footer">
                        <button type="button" className="btn btn-secondary" onClick={onClose}>Annuler</button>
                        {preview && (
                            <button
                                type="button"
                                className="btn btn-danger"
                                onClick={handleMerge}
                                disabled={merging}
                            >
                                {merging
                                    ? <><i className="fas fa-spinner fa-spin mr-1" />Fusion en cours…</>
                                    : <><i className="fas fa-code-branch mr-1" />Confirmer la fusion</>
                                }
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Duplicates Tab
// ---------------------------------------------------------------------------

function DuplicatesTab({ endpoints }) {
    const [groups, setGroups]     = useState(null);
    const [loading, setLoading]   = useState(true);
    const [mergeGroup, setMergeGroup] = useState(null);

    const load = () => {
        setLoading(true);
        apiFetch(endpoints.duplicates)
            .then(setGroups)
            .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, []);

    if (loading) {
        return <div className="text-center py-5"><i className="fas fa-spinner fa-spin fa-2x text-muted" /></div>;
    }

    if (!groups || groups.length === 0) {
        return (
            <div className="text-center py-5 text-muted">
                <i className="fas fa-check-circle fa-3x mb-3 text-success" />
                <p>Aucun doublon détecté.</p>
            </div>
        );
    }

    return (
        <div>
            <div className="alert alert-warning py-2 mb-3">
                <i className="fas fa-exclamation-triangle mr-1" />
                <strong>{groups.length} groupe(s) de doublons</strong> détectés sur le code produit.
            </div>

            <table className="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Produits concernés</th>
                        <th style={{ width: 100 }} />
                    </tr>
                </thead>
                <tbody>
                    {groups.map(group => (
                        <tr key={group.code}>
                            <td><code>{group.code}</code></td>
                            <td>
                                {group.products.map(p => (
                                    <div key={p.id}>
                                        <a href={p.url} target="_blank" rel="noreferrer" className="text-info">
                                            #{p.id}
                                        </a>
                                        <span className="ml-1 text-muted" style={{ fontSize: '0.85rem' }}>
                                            {p.label}
                                            {p.service && <span className="ml-1 badge badge-light">{p.service}</span>}
                                        </span>
                                    </div>
                                ))}
                            </td>
                            <td>
                                <button
                                    className="btn btn-sm btn-warning"
                                    onClick={() => setMergeGroup(group)}
                                >
                                    <i className="fas fa-code-branch mr-1" />Fusionner
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {mergeGroup && (
                <MergeModal
                    group={mergeGroup}
                    endpoints={endpoints}
                    onClose={() => setMergeGroup(null)}
                    onMerged={() => { setMergeGroup(null); load(); }}
                />
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Root component
// ---------------------------------------------------------------------------

export default function ProductsIndex({ kpi, chartData, endpoints, trans, canMerge }) {
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
                            <i className="fas fa-tachometer-alt mr-1" />{trans.dashboard}
                        </a>
                    </li>
                    <li className="nav-item">
                        <a
                            className={`nav-link ${activeTab === 'list' ? 'active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('list'); }}
                        >
                            <i className="fas fa-list mr-1" />{trans.products_list}
                        </a>
                    </li>
                    {canMerge && (
                        <li className="nav-item">
                            <a
                                className={`nav-link ${activeTab === 'duplicates' ? 'active' : ''}`}
                                href="#"
                                onClick={e => { e.preventDefault(); setActiveTab('duplicates'); }}
                            >
                                <i className="fas fa-code-branch mr-1" />Doublons
                            </a>
                        </li>
                    )}
                </ul>
            </div>

            <div className="card-body">
                {activeTab === 'dashboard' && (
                    <DashboardTab kpi={kpi} chartData={chartData} trans={trans} />
                )}
                {activeTab === 'list' && (
                    <ListTab endpoints={endpoints} trans={trans} />
                )}
                {activeTab === 'duplicates' && canMerge && (
                    <DuplicatesTab endpoints={endpoints} />
                )}
            </div>
        </div>
    );
}
