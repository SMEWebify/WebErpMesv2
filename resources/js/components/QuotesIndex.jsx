import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-info',      label: 'open' },
    2: { badge: 'badge-warning',   label: 'send' },
    3: { badge: 'badge-success',   label: 'win' },
    4: { badge: 'badge-danger',    label: 'lost' },
    5: { badge: 'badge-secondary', label: 'closed' },
    6: { badge: 'badge-dark',      label: 'obsolete' },
};

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

function formatCurrency(amount, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style:    'currency',
            currency: currency || 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
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
        const err = await res.json().catch(() => ({ message: res.statusText }));
        throw err;
    }
    return res.json();
}

// ---------------------------------------------------------------------------
// KPI Cards + top customer below each
// ---------------------------------------------------------------------------

const RANK_STYLES = [
    { bg: '#ffc107', color: '#000' },
    { bg: '#adb5bd', color: '#fff' },
    { bg: '#cd7f32', color: '#fff' },
];

function CustomerMini({ customer, rank, trans }) {
    if (!customer) return null;
    const { bg, color } = RANK_STYLES[rank];
    const name = customer.companie?.label ?? 'internal';
    return (
        <div className="d-flex align-items-center mb-3" style={{ gap: '0.5rem' }}>
            <span style={{
                width: 20, height: 20, borderRadius: '50%', flexShrink: 0,
                background: bg, color, fontSize: '0.7rem', fontWeight: 700,
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
            }}>
                {rank + 1}
            </span>
            <span style={{ fontSize: '0.82rem', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', flex: 1 }} title={name}>
                {name}
            </span>
            <span className="badge badge-secondary" style={{ fontSize: '0.72rem' }}>{customer.quote_count}</span>
        </div>
    );
}

function KPICards({ kpi, topCustomers, trans }) {
    const customers = topCustomers ?? [];
    return (
        <div className="row">
            <div className="col-lg-4">
                <div className="small-box bg-success">
                    <div className="inner">
                        <h3>{kpi.averageAmount}</h3>
                        <p>{trans.average_quote_amount}</p>
                    </div>
                    <div className="icon"><i className="fas fa-shipping-fast" /></div>
                </div>
                <CustomerMini customer={customers[0]} rank={0} trans={trans} />
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-info">
                    <div className="inner">
                        <h3>{kpi.conversionRate} %</h3>
                        <p>{trans.quote_conversion_rate}</p>
                    </div>
                    <div className="icon"><i className="fas fa-file-invoice-dollar" /></div>
                </div>
                <CustomerMini customer={customers[1]} rank={1} trans={trans} />
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-primary">
                    <div className="inner">
                        <h3>{kpi.responseRate} %</h3>
                        <p>{trans.quote_response_rate}</p>
                    </div>
                    <div className="icon"><i className="fas fa-chart-line" /></div>
                </div>
                <CustomerMini customer={customers[2]} rank={2} trans={trans} />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Pie Chart — pure React SVG, no dependency
// ---------------------------------------------------------------------------

const STATUS_COLORS = {
    1: '#17a2b8',
    2: '#ffc107',
    3: '#28a745',
    4: '#dc3545',
    5: '#6c757d',
    6: '#007bff',
};

function PieChart({ chartData, trans }) {
    const [hovered, setHovered] = useState(null);

    const statusTrans = { 1: trans.open, 2: trans.send, 3: trans.win, 4: trans.lost, 5: trans.closed, 6: trans.obsolete };
    const items  = (chartData.quotesDataRate ?? []).filter(i => i.QuoteCountRate > 0);
    const total  = items.reduce((s, i) => s + Number(i.QuoteCountRate), 0);

    if (!items.length) return <p className="text-muted text-center small py-3">—</p>;

    const R = 80;          // outer radius
    const r = 44;          // inner radius (donut hole)
    const cx = 110, cy = 110;
    const size = 220;

    // Build slices
    let angle = -Math.PI / 2;
    const slices = items.map((item) => {
        const value   = Number(item.QuoteCountRate);
        const sweep   = (value / total) * 2 * Math.PI;
        const x1 = cx + R * Math.cos(angle);
        const y1 = cy + R * Math.sin(angle);
        angle += sweep;
        const x2 = cx + R * Math.cos(angle);
        const y2 = cy + R * Math.sin(angle);
        const ix1 = cx + r * Math.cos(angle);
        const iy1 = cy + r * Math.sin(angle);
        const ix2 = cx + r * Math.cos(angle - sweep);
        const iy2 = cy + r * Math.sin(angle - sweep);
        const large = sweep > Math.PI ? 1 : 0;
        const midAngle = angle - sweep / 2;

        return {
            statu:    item.statu,
            value,
            color:    STATUS_COLORS[item.statu] ?? '#aaa',
            label:    statusTrans[item.statu] ?? item.statu,
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
                {/* Centre : label au survol, total sinon */}
                <text x={cx} y={cy - 6} textAnchor="middle" fontSize="18" fontWeight="700" fill="#343a40">
                    {hoveredItem ? hoveredItem.value : total}
                </text>
                <text x={cx} y={cy + 12} textAnchor="middle" fontSize="9" fill="#6c757d">
                    {hoveredItem ? hoveredItem.label : trans.quote_trans}
                </text>
            </svg>

            {/* Legend */}
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
// Line Chart
// ---------------------------------------------------------------------------

function LineChart({ chartData, trans }) {
    const canvasRef = useRef(null);
    const chartRef  = useRef(null);

    useEffect(() => {
        if (!canvasRef.current || !window.Chart) return;
        if (chartRef.current) { chartRef.current.destroy(); }

        const months = [
            trans.jan, trans.feb, trans.mar, trans.apr, trans.may, trans.jun,
            trans.jul, trans.aug, trans.sep, trans.oct, trans.nov, trans.dec,
        ];

        const buildMonthlyData = (items) =>
            Array.from({ length: 12 }, (_, i) => {
                const found = (items ?? []).find(d => d.month === i + 1);
                return found ? parseFloat(found.quoteSum) : 0;
            });

        chartRef.current = new window.Chart(canvasRef.current.getContext('2d'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: trans.quote_forecast,
                        borderColor: 'rgba(60,141,188,0.8)',
                        data: buildMonthlyData(chartData.quoteMonthlyRecap),
                        fill: true,
                        pointRadius: 5,
                    },
                    {
                        label: trans.quote_last_year,
                        borderColor: 'rgba(240,173,78,0.8)',
                        data: buildMonthlyData(chartData.quoteMonthlyRecapPreviousYear),
                        fill: false,
                        pointRadius: 5,
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                legend: { display: true },
            },
        });
    }, [chartData, trans]);

    return <canvas ref={canvasRef} style={{ minHeight: 400, height: '100%', maxWidth: '100%' }} />;
}

// ---------------------------------------------------------------------------
// Quotes by User KPI
// ---------------------------------------------------------------------------

function QuotesByUser({ quotesByUser, trans }) {
    return (
        <div className="quote-kpi-list">
            {Object.entries(quotesByUser ?? {}).map(([userId, quotes]) => {
                const name    = quotes[0]?.UserManagement?.name ?? 'N/A';
                const getSum  = (statu) => quotes.filter(q => q.statu === statu).reduce((s, q) => s + (q.total ?? 0), 0);
                return (
                    <div key={userId} className="quote-kpi-item border rounded p-2 mb-2">
                        <div className="font-weight-bold mb-2 text-truncate">{name}</div>
                        <div className="d-flex flex-wrap">
                            <span className="badge badge-info m-1">{trans.open}: {getSum(1)}</span>
                            <span className="badge badge-warning m-1">{trans.send}: {getSum(2)}</span>
                            <span className="badge badge-success m-1">{trans.win}: {getSum(3)}</span>
                            <span className="badge badge-danger m-1">{trans.lost}: {getSum(4)}</span>
                            <span className="badge badge-secondary m-1">{trans.closed}: {getSum(5)}</span>
                            <span className="badge badge-dark m-1">{trans.obsolete}: {getSum(6)}</span>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Dashboard Tab
// ---------------------------------------------------------------------------

function DashboardTab({ kpi, chartData, topCustomers, quotesByUser, trans }) {
    return (
        <div>
            <KPICards kpi={kpi} topCustomers={topCustomers} trans={trans} />
            <div className="row">
                <div className="col-md-3">
                    <div className="card card-teal">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-chart-bar mr-1" />{trans.statistiques}</h3>
                        </div>
                        <div className="card-body">
                            <PieChart chartData={chartData} trans={trans} />
                        </div>
                    </div>
                </div>
                <div className="col-lg-6">
                    <div className="card card-purple">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-chart-bar mr-1" />{trans.monthly_recap}</h3>
                        </div>
                        <div className="card-body">
                            <LineChart chartData={chartData} trans={trans} />
                        </div>
                    </div>
                </div>
                <div className="col-md-3">
                    <div className="card card-orange">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-users mr-1" />{trans.statistiques}</h3>
                        </div>
                        <div className="card-body">
                            <QuotesByUser quotesByUser={quotesByUser} trans={trans} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Status Badge
// ---------------------------------------------------------------------------

function StatusBadge({ statu, trans }) {
    const cfg = STATUS_CONFIG[statu] ?? { badge: 'badge-secondary', label: 'unknown' };
    return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? statu}</span>;
}

// ---------------------------------------------------------------------------
// Status Filter
// ---------------------------------------------------------------------------

const ALL_STATUSES = [1, 2, 3, 4, 5, 6];

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
// Quotes Table — with drag-and-drop column reorder + per-column filters
// ---------------------------------------------------------------------------

const LS_COL_ORDER     = 'quotes_table_col_order';
const LS_HIDDEN_COLS   = 'quotes_table_hidden_cols';
const DEFAULT_COL_ORDER = ['code', 'label', 'client', 'contact', 'validity_date', 'status', 'lines', 'created_at', 'total'];
const TEXT_FILTER_COLS  = new Set(['code', 'label', 'client', 'contact']);
const DATE_RANGE_COLS   = new Set(['validity_date', 'created_at']);

// Parse "dd/mm/yyyy" → "yyyy-mm-dd" for date comparison
function dmyToISO(str) {
    if (!str || !str.includes('/')) return str ?? '';
    const [d, m, y] = str.split('/');
    return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
}

function SortIcon({ field, sortField, sortAsc }) {
    if (field !== sortField) return <i className="fas fa-sort text-muted ml-1" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

function colDefs(trans) {
    return {
        code:          { label: trans.code,          sortField: 'code',               align: '',       render: q => <code>{q.code}</code> },
        label:         { label: trans.label,         sortField: 'label',              align: '',       render: q => q.label },
        client:        { label: trans.client,        sortField: 'companie',           align: '',       render: q => q.companie?.label ?? '—' },
        contact:       { label: trans.contact,       sortField: 'contact',            align: '',       render: q => q.contact?.name ?? '—' },
        validity_date: { label: trans.validity_date, sortField: 'validity_date',      align: '',       render: q => formatDate(q.validity_date, trans.locale) },
        status:        { label: trans.status,        sortField: 'statu',              align: '',       render: q => <StatusBadge statu={q.statu} trans={trans} /> },
        lines:         { label: trans.lines,         sortField: 'quote_lines_count',  align: 'center', render: q => <span className="badge badge-secondary">{q.quote_lines_count}</span> },
        created_at:    { label: trans.created_at,   sortField: 'created_at',         align: '',       render: q => q.created_at },
        total:         { label: trans.total,         sortField: 'total_amount',       align: 'right',  bold: true,
                         render: q => q.total_amount > 0
                             ? formatCurrency(q.total_amount, trans.currency, trans.locale)
                             : <span className="text-muted">—</span> },
    };
}

function matchesColFilter(q, colId, value) {
    if (DATE_RANGE_COLS.has(colId)) {
        const { from, to } = value ?? {};
        const iso = colId === 'validity_date' ? (q.validity_date ?? '') : dmyToISO(q.created_at ?? '');
        if (!iso) return true;
        if (from && iso < from) return false;
        if (to   && iso > to)   return false;
        return true;
    }
    const v = (value ?? '').toLowerCase().trim();
    if (!v) return true;
    switch (colId) {
        case 'code':    return (q.code ?? '').toLowerCase().includes(v);
        case 'label':   return (q.label ?? '').toLowerCase().includes(v);
        case 'client':  return (q.companie?.label ?? '').toLowerCase().includes(v);
        case 'contact': return (q.contact?.name ?? '').toLowerCase().includes(v);
        default:        return true;
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

function QuotesTable({ quotes, sortField, sortAsc, onSort, trans }) {
    const [colOrder,   setColOrder]   = useState(readSavedColOrder);
    const [hiddenCols, setHiddenCols] = useState(readSavedHiddenCols);
    const [colFilters, setColFilters] = useState({});
    const [dragOver,   setDragOver]   = useState(null);
    const dragCol = useRef(null);

    const COLS       = colDefs(trans);
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

    // Apply per-column filters on top of whatever quotes were passed in
    const filtered = quotes.filter(q =>
        visibleCols.every(colId => matchesColFilter(q, colId, colFilters[colId] ?? ''))
    );

    // ---- drag handlers ----
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

    const pageTotal  = filtered.reduce((s, q) => s + (q.total_amount ?? 0), 0);
    const totalIdx   = visibleCols.indexOf('total');
    const inputStyle = { fontSize: '0.72rem', height: '24px', padding: '1px 4px' };

    return (
        <div>
            {/* ── hidden-column restore chips ── */}
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
                        {/* ── row 1 : column headers (draggable + hide button) ── */}
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
                        {/* ── row 2 : per-column filter inputs ── */}
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
                        {filtered.map(q => (
                            <tr key={q.id}>
                                {visibleCols.map(colId => {
                                    const col      = COLS[colId];
                                    const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                    return (
                                        <td
                                            key={colId}
                                            className={`${alignCls}${col.bold ? ' font-weight-bold' : ''}`}
                                            style={(col.align === 'right' || col.bold) ? { whiteSpace: 'nowrap' } : {}}
                                        >
                                            {col.render(q)}
                                        </td>
                                    );
                                })}
                                <td>
                                    <a href={q.url} className="btn btn-xs btn-info">
                                        <i className="fas fa-eye" />
                                    </a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                    {filtered.length > 0 && (
                        <tfoot>
                            <tr className="font-weight-bold bg-light">
                                {visibleCols.map((colId, i) => {
                                    if (colId === 'total') return (
                                        <td key={colId} className="text-right" style={{ whiteSpace: 'nowrap' }}>
                                            {formatCurrency(pageTotal, trans.currency, trans.locale)}
                                        </td>
                                    );
                                    if (i === totalIdx - 1) return <td key={colId} className="text-right">{trans.total}</td>;
                                    return <td key={colId} />;
                                })}
                                <td />
                            </tr>
                        </tfoot>
                    )}
                </table>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Quote Cards
// ---------------------------------------------------------------------------

function QuoteCards({ quotes, trans }) {
    return (
        <div className="row">
            {quotes.length === 0 && (
                <div className="col-12 text-center text-muted py-3">{trans.no_results}</div>
            )}
            {quotes.map(q => (
                <div key={q.id} className="col-md-4 col-lg-3 mb-3">
                    <div className="card h-100">
                        <div className="card-header py-1 px-2 d-flex justify-content-between align-items-center">
                            <code className="small">{q.code}</code>
                            <StatusBadge statu={q.statu} trans={trans} />
                        </div>
                        <div className="card-body py-2 px-2">
                            <p className="mb-1 font-weight-bold">{q.label}</p>
                            <p className="mb-0 small text-muted">{q.companie?.label ?? '—'}</p>
                            <p className="mb-0 small text-muted">{q.contact?.name ?? '—'}</p>
                        </div>
                        <div className="card-footer py-1 px-2 d-flex justify-content-between align-items-center">
                            <small className="text-muted">{q.created_at}</small>
                            <a href={q.url} className="btn btn-xs btn-info">
                                <i className="fas fa-eye" />
                            </a>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Kanban Board (simple column per status)
// ---------------------------------------------------------------------------

function KanbanBoard({ quotes, trans }) {
    const byStatus = {};
    ALL_STATUSES.forEach(s => { byStatus[s] = []; });
    quotes.forEach(q => { if (byStatus[q.statu]) byStatus[q.statu].push(q); });

    return (
        <div className="d-flex gap-2" style={{ overflowX: 'auto', paddingBottom: 8 }}>
            {ALL_STATUSES.map(statu => {
                const cfg   = STATUS_CONFIG[statu];
                const items = byStatus[statu];
                return (
                    <div key={statu} className="card" style={{ minWidth: 220, flex: '0 0 220px' }}>
                        <div className={`card-header py-1 px-2 ${cfg.badge.replace('badge-', 'bg-')} text-white`}>
                            <strong>{trans[cfg.label]}</strong>
                            <span className="badge badge-light ml-1">{items.length}</span>
                        </div>
                        <div className="card-body p-1" style={{ maxHeight: 500, overflowY: 'auto' }}>
                            {items.map(q => (
                                <div key={q.id} className="card mb-1 shadow-sm">
                                    <div className="card-body py-1 px-2">
                                        <p className="mb-0 small font-weight-bold">{q.label}</p>
                                        <p className="mb-0 small text-muted">{q.companie?.label ?? '—'}</p>
                                        <a href={q.url} className="btn btn-xs btn-info mt-1">
                                            <i className="fas fa-eye" />
                                        </a>
                                    </div>
                                </div>
                            ))}
                            {items.length === 0 && <p className="text-muted small text-center py-2">—</p>}
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
// Sub-modal: Create Address
// ---------------------------------------------------------------------------

function CreateAddressSubModal({ show, onClose, companiesId, storeUrl, onCreated, trans }) {
    const empty = { ordre: '', label: '', adress: '', zipcode: '', city: '', country: '', number: '', mail: '' };
    const [form, setForm]     = useState(empty);
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    useEffect(() => { if (show) { setForm(empty); setErrors({}); } }, [show]);

    const set = (f) => (e) => setForm(prev => ({ ...prev, [f]: e.target.value }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const created = await apiFetch(storeUrl, {
                method: 'POST',
                body:   JSON.stringify({ ...form, companies_id: companiesId }),
            });
            onCreated(created);
            onClose();
        } catch (err) {
            setErrors(err.errors ?? {});
        } finally {
            setSaving(false);
        }
    };

    if (!show) return null;
    const fe = (f) => errors[f] ? <span className="text-danger small d-block">{errors[f][0]}</span> : null;

    return (
        <div className="modal fade show d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,.6)', zIndex: 1060 }}>
            <div className="modal-dialog modal-dialog-centered modal-lg">
                <div className="modal-content">
                    <div className="modal-header bg-primary">
                        <h5 className="modal-title text-white">{trans.new_address}</h5>
                        <button type="button" className="close text-white" onClick={onClose}>&times;</button>
                    </div>
                    <form onSubmit={handleSubmit}>
                        <div className="modal-body">
                            <div className="form-row">
                                <div className="form-group col-md-2">
                                    <label>{trans.ordre} *</label>
                                    <input type="number" className="form-control" value={form.ordre} onChange={set('ordre')} min="1" />
                                    {fe('ordre')}
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{trans.adress_label} *</label>
                                    <input className="form-control" value={form.label} onChange={set('label')} />
                                    {fe('label')}
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{trans.adress} *</label>
                                    <input className="form-control" value={form.adress} onChange={set('adress')} />
                                    {fe('adress')}
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group col-md-3">
                                    <label>{trans.postal_code} *</label>
                                    <input className="form-control" value={form.zipcode} onChange={set('zipcode')} />
                                    {fe('zipcode')}
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{trans.city} *</label>
                                    <input className="form-control" value={form.city} onChange={set('city')} />
                                    {fe('city')}
                                </div>
                                <div className="form-group col-md-5">
                                    <label>{trans.country} *</label>
                                    <input className="form-control" value={form.country} onChange={set('country')} />
                                    {fe('country')}
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{trans.phone}</label>
                                    <input className="form-control" value={form.number} onChange={set('number')} />
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{trans.email}</label>
                                    <input type="email" className="form-control" value={form.mail} onChange={set('mail')} />
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary" onClick={onClose}>{trans.cancel}</button>
                            <button type="submit" className="btn btn-primary" disabled={saving}>
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
// Sub-modal: Create Contact
// ---------------------------------------------------------------------------

function CreateContactSubModal({ show, onClose, companiesId, storeUrl, onCreated, trans }) {
    const empty = { ordre: '', civility: '', first_name: '', name: '', function: '', number: '', mobile: '', mail: '' };
    const [form, setForm]     = useState(empty);
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    useEffect(() => { if (show) { setForm(empty); setErrors({}); } }, [show]);

    const set = (f) => (e) => setForm(prev => ({ ...prev, [f]: e.target.value }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const created = await apiFetch(storeUrl, {
                method: 'POST',
                body:   JSON.stringify({ ...form, companies_id: companiesId }),
            });
            onCreated(created);
            onClose();
        } catch (err) {
            setErrors(err.errors ?? {});
        } finally {
            setSaving(false);
        }
    };

    if (!show) return null;
    const fe = (f) => errors[f] ? <span className="text-danger small d-block">{errors[f][0]}</span> : null;

    return (
        <div className="modal fade show d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,.6)', zIndex: 1060 }}>
            <div className="modal-dialog modal-dialog-centered modal-lg">
                <div className="modal-content">
                    <div className="modal-header bg-info">
                        <h5 className="modal-title text-white">{trans.new_contact}</h5>
                        <button type="button" className="close text-white" onClick={onClose}>&times;</button>
                    </div>
                    <form onSubmit={handleSubmit}>
                        <div className="modal-body">
                            <div className="form-row">
                                <div className="form-group col-md-2">
                                    <label>{trans.ordre} *</label>
                                    <input type="number" className="form-control" value={form.ordre} onChange={set('ordre')} min="1" />
                                    {fe('ordre')}
                                </div>
                                <div className="form-group col-md-2">
                                    <label>{trans.civility}</label>
                                    <select className="form-control" value={form.civility} onChange={set('civility')}>
                                        <option value="">—</option>
                                        <option value="M.">M.</option>
                                        <option value="Mme">Mme</option>
                                        <option value="Dr">Dr</option>
                                    </select>
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{trans.first_name} *</label>
                                    <input className="form-control" value={form.first_name} onChange={set('first_name')} />
                                    {fe('first_name')}
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{trans.name} *</label>
                                    <input className="form-control" value={form.name} onChange={set('name')} />
                                    {fe('name')}
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group col-md-4">
                                    <label>{trans.function}</label>
                                    <input className="form-control" value={form.function} onChange={set('function')} />
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{trans.phone}</label>
                                    <input className="form-control" value={form.number} onChange={set('number')} />
                                </div>
                                <div className="form-group col-md-4">
                                    <label>{trans.mobile}</label>
                                    <input className="form-control" value={form.mobile} onChange={set('mobile')} />
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{trans.email}</label>
                                    <input type="email" className="form-control" value={form.mail} onChange={set('mail')} />
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary" onClick={onClose}>{trans.cancel}</button>
                            <button type="submit" className="btn btn-info" disabled={saving}>
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
// Create Quote Modal
// ---------------------------------------------------------------------------

function CreateModal({ show, onClose, endpoints, trans }) {
    const [selectData, setSelectData]         = useState(null);
    const [addresses, setAddresses]           = useState([]);
    const [contacts, setContacts]             = useState([]);
    const [errors, setErrors]                 = useState({});
    const [saving, setSaving]                 = useState(false);
    const [showAddrModal, setShowAddrModal]   = useState(false);
    const [showCtctModal, setShowCtctModal]   = useState(false);

    const [form, setForm] = useState({
        code:                             '',
        label:                            '',
        customer_reference:               '',
        companies_id:                     '',
        companies_contacts_id:            '',
        companies_addresses_id:           '',
        accounting_payment_conditions_id: '',
        accounting_payment_methods_id:    '',
        accounting_deliveries_id:         '',
        user_id:                          '',
        validity_date:                    '',
        comment:                          '',
    });

    useEffect(() => {
        if (!show || selectData) return;
        apiFetch(endpoints.selectData).then(data => {
            setSelectData(data);
            setForm(f => ({
                ...f,
                code:                             data.next_code ?? '',
                label:                            data.next_code ?? '',
                accounting_payment_conditions_id: data.payment_conditions.find(x => x.default)?.id ?? '',
                accounting_payment_methods_id:    data.payment_methods.find(x => x.default)?.id ?? '',
                accounting_deliveries_id:         data.deliveries.find(x => x.default)?.id ?? '',
            }));
        });
    }, [show]);

    const reloadAddressesContacts = (companyId) => {
        const addrUrl = endpoints.addresses.replace('__ID__', companyId);
        const ctctUrl = endpoints.contacts.replace('__ID__', companyId);
        Promise.all([apiFetch(addrUrl), apiFetch(ctctUrl)]).then(([addr, ctct]) => {
            setAddresses(addr);
            setContacts(ctct);
        });
    };

    const handleCompanyChange = (companyId) => {
        setForm(f => ({ ...f, companies_id: companyId, companies_addresses_id: '', companies_contacts_id: '' }));
        setAddresses([]);
        setContacts([]);
        if (companyId) reloadAddressesContacts(companyId);
    };

    const handleAddressCreated = (newAddr) => {
        setAddresses(prev => [...prev, newAddr]);
        setForm(f => ({ ...f, companies_addresses_id: String(newAddr.id) }));
    };

    const handleContactCreated = (newCtct) => {
        setContacts(prev => [...prev, newCtct]);
        setForm(f => ({ ...f, companies_contacts_id: String(newCtct.id) }));
    };

    const set = (field) => (e) => setForm(f => ({ ...f, [field]: e.target.value }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const res = await apiFetch(endpoints.store, {
                method: 'POST',
                body:   JSON.stringify(form),
            });
            window.location.href = res.redirect;
        } catch (err) {
            setErrors(err.errors ?? {});
            setSaving(false);
        }
    };

    if (!show) return null;

    const fieldError = (field) =>
        errors[field] ? <span className="text-danger small">{errors[field][0]}</span> : null;

    const hasCompany = !!form.companies_id;

    return (
        <>
            <div className="modal fade show d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,.5)' }}>
                <div className="modal-dialog modal-dialog-centered modal-xl">
                    <div className="modal-content">
                        <div className="modal-header bg-success">
                            <h5 className="modal-title text-white">{trans.new_quote}</h5>
                            <button type="button" className="close text-white" onClick={onClose}>&times;</button>
                        </div>
                        <form onSubmit={handleSubmit}>
                            <div className="modal-body">
                                {!selectData && <div className="text-center py-4"><i className="fas fa-spinner fa-spin fa-2x" /></div>}
                                {selectData && (
                                    <div className="card card-body">
                                        <div className="form-row">
                                            <div className="form-group col-md-4">
                                                <label>{trans.external_id}</label>
                                                <input className="form-control" value={form.code} onChange={set('code')} />
                                                {fieldError('code')}
                                            </div>
                                            <div className="form-group col-md-4">
                                                <label>{trans.label}</label>
                                                <input className="form-control" value={form.label} onChange={set('label')} />
                                                {fieldError('label')}
                                            </div>
                                            <div className="form-group col-md-4">
                                                <label>{trans.customer_reference}</label>
                                                <input className="form-control" value={form.customer_reference} onChange={set('customer_reference')} />
                                            </div>
                                        </div>
                                        <div className="form-row">
                                            {/* Company */}
                                            <div className="form-group col-md-4">
                                                <label>{trans.company} *</label>
                                                <select className="form-control" value={form.companies_id} onChange={e => handleCompanyChange(e.target.value)}>
                                                    <option value="">—</option>
                                                    {selectData.companies.map(c => (
                                                        <option key={c.id} value={c.id}>{c.code} — {c.label}</option>
                                                    ))}
                                                </select>
                                                {fieldError('companies_id')}
                                            </div>
                                            {/* Address + button */}
                                            <div className="form-group col-md-4">
                                                <label className="d-flex justify-content-between align-items-center">
                                                    <span>{trans.address} *</span>
                                                    {hasCompany && (
                                                        <button type="button" className="btn btn-xs btn-outline-primary py-0 px-1" onClick={() => setShowAddrModal(true)}>
                                                            <i className="fas fa-plus" />
                                                        </button>
                                                    )}
                                                </label>
                                                <select className="form-control" value={form.companies_addresses_id} onChange={set('companies_addresses_id')} disabled={!hasCompany}>
                                                    <option value="">—</option>
                                                    {addresses.map(a => (
                                                        <option key={a.id} value={a.id}>{a.label} — {a.adress}</option>
                                                    ))}
                                                </select>
                                                {fieldError('companies_addresses_id')}
                                            </div>
                                            {/* Contact + button */}
                                            <div className="form-group col-md-4">
                                                <label className="d-flex justify-content-between align-items-center">
                                                    <span>{trans.contact} *</span>
                                                    {hasCompany && (
                                                        <button type="button" className="btn btn-xs btn-outline-info py-0 px-1" onClick={() => setShowCtctModal(true)}>
                                                            <i className="fas fa-plus" />
                                                        </button>
                                                    )}
                                                </label>
                                                <select className="form-control" value={form.companies_contacts_id} onChange={set('companies_contacts_id')} disabled={!hasCompany}>
                                                    <option value="">—</option>
                                                    {contacts.map(c => (
                                                        <option key={c.id} value={c.id}>{c.name}</option>
                                                    ))}
                                                </select>
                                                {fieldError('companies_contacts_id')}
                                            </div>
                                        </div>
                                        <div className="form-row">
                                            <div className="form-group col-md-4">
                                                <label>{trans.payment_condition} *</label>
                                                <select className="form-control" value={form.accounting_payment_conditions_id} onChange={set('accounting_payment_conditions_id')}>
                                                    <option value="">—</option>
                                                    {selectData.payment_conditions.map(x => (
                                                        <option key={x.id} value={x.id}>{x.code} — {x.label}</option>
                                                    ))}
                                                </select>
                                                {fieldError('accounting_payment_conditions_id')}
                                            </div>
                                            <div className="form-group col-md-4">
                                                <label>{trans.payment_method} *</label>
                                                <select className="form-control" value={form.accounting_payment_methods_id} onChange={set('accounting_payment_methods_id')}>
                                                    <option value="">—</option>
                                                    {selectData.payment_methods.map(x => (
                                                        <option key={x.id} value={x.id}>{x.code} — {x.label}</option>
                                                    ))}
                                                </select>
                                                {fieldError('accounting_payment_methods_id')}
                                            </div>
                                            <div className="form-group col-md-4">
                                                <label>{trans.delivery} *</label>
                                                <select className="form-control" value={form.accounting_deliveries_id} onChange={set('accounting_deliveries_id')}>
                                                    <option value="">—</option>
                                                    {selectData.deliveries.map(x => (
                                                        <option key={x.id} value={x.id}>{x.code} — {x.label}</option>
                                                    ))}
                                                </select>
                                                {fieldError('accounting_deliveries_id')}
                                            </div>
                                        </div>
                                        <div className="form-row">
                                            <div className="form-group col-md-4">
                                                <label>{trans.assignee} *</label>
                                                <select className="form-control" value={form.user_id} onChange={set('user_id')}>
                                                    <option value="">—</option>
                                                    {selectData.users.map(u => (
                                                        <option key={u.id} value={u.id}>{u.name}</option>
                                                    ))}
                                                </select>
                                                {fieldError('user_id')}
                                            </div>
                                            <div className="form-group col-md-4">
                                                <label>{trans.validity_date}</label>
                                                <input type="date" className="form-control" value={form.validity_date} onChange={set('validity_date')} />
                                            </div>
                                        </div>
                                        <div className="form-group">
                                            <label>{trans.comment}</label>
                                            <textarea className="form-control" rows="2" value={form.comment} onChange={set('comment')} />
                                        </div>
                                    </div>
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

            {/* Sub-modals rendered on top */}
            <CreateAddressSubModal
                show={showAddrModal}
                onClose={() => setShowAddrModal(false)}
                companiesId={form.companies_id}
                storeUrl={endpoints.storeAddress}
                onCreated={handleAddressCreated}
                trans={trans}
            />
            <CreateContactSubModal
                show={showCtctModal}
                onClose={() => setShowCtctModal(false)}
                companiesId={form.companies_id}
                storeUrl={endpoints.storeContact}
                onCreated={handleContactCreated}
                trans={trans}
            />
        </>
    );
}

// ---------------------------------------------------------------------------
// LocalStorage helpers
// ---------------------------------------------------------------------------

const LS_KEY = 'quotes_list_filters';

function loadFilters() {
    try {
        const raw = localStorage.getItem(LS_KEY);
        if (!raw) return null;
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function saveFilters(filters) {
    try { localStorage.setItem(LS_KEY, JSON.stringify(filters)); } catch {}
}

// ---------------------------------------------------------------------------
// List Tab
// ---------------------------------------------------------------------------

function ListTab({ endpoints, trans }) {
    const saved = loadFilters();

    const [quotes, setQuotes]         = useState([]);
    const [meta, setMeta]             = useState(null);
    const [loading, setLoading]       = useState(false);
    const [search, setSearch]         = useState(saved?.search   ?? '');
    const [statuses, setStatuses]     = useState(saved?.statuses ?? [1]);
    const [sortField, setSortField]   = useState(saved?.sortField ?? 'created_at');
    const [sortAsc, setSortAsc]       = useState(saved?.sortAsc   ?? false);
    const [page, setPage]             = useState(1);
    const [viewType, setViewType]     = useState(saved?.viewType  ?? 'table');
    const [showModal, setShowModal]   = useState(false);

    const searchTimeout = useRef(null);

    const fetchQuotes = useCallback((opts = {}) => {
        setLoading(true);
        const params = new URLSearchParams({
            search:   opts.search   ?? search,
            sort:     opts.sort     ?? sortField,
            asc:      opts.asc      ?? sortAsc ? '1' : '0',
            page:     opts.page     ?? page,
        });
        (opts.statuses ?? statuses).forEach(s => params.append('statuses[]', s));

        apiFetch(`${endpoints.list}?${params}`)
            .then(data => { setQuotes(data.data); setMeta(data.meta); })
            .finally(() => setLoading(false));
    }, [search, sortField, sortAsc, page, statuses, endpoints.list]);

    useEffect(() => { fetchQuotes(); }, [sortField, sortAsc, page, statuses]);

    const handleSearch = (val) => {
        setSearch(val);
        clearTimeout(searchTimeout.current);
        searchTimeout.current = setTimeout(() => {
            setPage(1);
            fetchQuotes({ search: val, page: 1 });
        }, 400);
    };

    // Persist filters whenever they change
    useEffect(() => {
        saveFilters({ search, statuses, sortField, sortAsc, viewType });
    }, [search, statuses, sortField, sortAsc, viewType]);

    const handleSort = (field) => {
        const asc = field === sortField ? !sortAsc : true;
        setSortField(field);
        setSortAsc(asc);
    };

    const handleStatusChange = (next) => {
        setStatuses(next);
        setPage(1);
    };

    return (
        <div>
            {/* Toolbar — all controls on one line */}
            <div className="d-flex flex-wrap align-items-center mb-2" style={{ gap: '0.5rem' }}>
                {/* Search */}
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

                {/* Status filter */}
                <StatusFilter selected={statuses} onChange={handleStatusChange} trans={trans} />

                {/* Spacer */}
                <div className="flex-grow-1" />

                {/* View toggle */}
                <div className="btn-group btn-group-sm flex-shrink-0">
                    {[
                        { key: 'table',  icon: 'fa-list' },
                        { key: 'card',   icon: 'fa-th' },
                        { key: 'kanban', icon: 'fa-columns' },
                    ].map(v => (
                        <button
                            key={v.key}
                            className={`btn ${viewType === v.key ? 'btn-primary' : 'btn-secondary'}`}
                            onClick={() => setViewType(v.key)}
                            title={v.key}
                        >
                            <i className={`fas ${v.icon}`} />
                        </button>
                    ))}
                </div>

                {/* New quote */}
                <button className="btn btn-sm btn-success flex-shrink-0" onClick={() => setShowModal(true)}>
                    <i className="fas fa-plus mr-1" />{trans.new_quote}
                </button>
            </div>

            {/* Loading indicator */}
            {loading && (
                <div className="text-center py-2">
                    <i className="fas fa-spinner fa-spin text-secondary" />
                </div>
            )}

            {/* Quote views */}
            {!loading && viewType === 'table' && (
                <QuotesTable quotes={quotes} sortField={sortField} sortAsc={sortAsc} onSort={handleSort} trans={trans} />
            )}
            {!loading && viewType === 'card' && (
                <QuoteCards quotes={quotes} trans={trans} />
            )}
            {!loading && viewType === 'kanban' && (
                <KanbanBoard quotes={quotes} trans={trans} />
            )}

            <Pagination meta={meta} onPageChange={setPage} />

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
// Root Component
// ---------------------------------------------------------------------------

export default function QuotesIndex({ kpi, chartData, topCustomers, quotesByUser, endpoints, trans }) {
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
                            {trans.quotes_list}
                        </a>
                    </li>
                </ul>
            </div>
            <div className="card-body p-3">
                {activeTab === 'dashboard' && (
                    <DashboardTab
                        kpi={kpi}
                        chartData={chartData}
                        topCustomers={topCustomers}
                        quotesByUser={quotesByUser}
                        trans={trans}
                    />
                )}
                {activeTab === 'list' && (
                    <ListTab endpoints={endpoints} trans={trans} />
                )}
            </div>
        </div>
    );
}
