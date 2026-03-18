import React, { useState, useEffect, useRef, useCallback } from 'react'; // useRef used by OrdersTable drag-and-drop + ListTab debounce

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-info',      label: 'open' },
    2: { badge: 'badge-warning',   label: 'in_progress' },
    3: { badge: 'badge-success',   label: 'delivered' },
    4: { badge: 'badge-primary',   label: 'partly_delivered' },
    5: { badge: 'badge-danger',    label: 'stopped' },
    6: { badge: 'badge-dark',      label: 'canceled' },
};

const LS_COL_ORDER   = 'orders_table_col_order';
const LS_HIDDEN_COLS = 'orders_table_hidden_cols';
const LS_FILTERS     = 'orders_list_filters';

const DEFAULT_COL_ORDER = ['code', 'label', 'customer_reference', 'companie', 'contact', 'statu', 'validity_date', 'created_at', 'order_lines_count', 'total_amount'];
const TEXT_FILTER_COLS  = new Set(['code', 'label', 'customer_reference', 'companie', 'contact']);
const DATE_RANGE_COLS   = new Set(['validity_date', 'created_at']);

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

function formatCurrency(amount, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style: 'currency',
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
        const body = await res.json().catch(() => ({}));
        const err  = new Error(body.message || `HTTP ${res.status}`);
        err.errors = body.errors ?? {};
        err.status = res.status;
        throw err;
    }
    return res.json();
}

function dmyToISO(str) {
    if (!str) return null;
    const parts = str.split('/');
    if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
    return str;
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
// CustomerMini — un client par colonne (sous sa card), style identique aux devis
// ---------------------------------------------------------------------------

const RANK_STYLES = [
    { bg: '#ffc107', color: '#000' },
    { bg: '#adb5bd', color: '#fff' },
    { bg: '#cd7f32', color: '#fff' },
];

function CustomerMini({ customer, rank }) {
    if (!customer) return null;
    const { bg, color } = RANK_STYLES[rank];
    const name = customer.companie?.label ?? 'Internal';
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
            <span className="badge badge-secondary" style={{ fontSize: '0.72rem' }}>{customer.order_count}</span>
        </div>
    );
}

// ---------------------------------------------------------------------------
// KPI Cards row
// ---------------------------------------------------------------------------

function KPICards({ kpi, topCustomers, trans }) {
    const customers = topCustomers ?? [];
    return (
        <div className="row">
            <div className="col-lg-4">
                <div className="small-box bg-success">
                    <div className="inner">
                        <h3>{kpi.deliveredOrdersPercentage ?? 0}%</h3>
                        <p>{trans.order_delivered ?? 'Delivered'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-shipping-fast" /></div>
                </div>
                <CustomerMini customer={customers[0]} rank={0} />
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-info">
                    <div className="inner">
                        <h3>{kpi.invoicedOrdersPercentage ?? 0}%</h3>
                        <p>{trans.order_invoiced ?? 'Invoiced'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-file-invoice-dollar" /></div>
                </div>
                <CustomerMini customer={customers[1]} rank={1} />
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-primary">
                    <div className="inner">
                        <h3>{kpi.serviceRate ?? 0}%</h3>
                        <p>{trans.service_rate ?? 'Service rate'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-chart-line" /></div>
                </div>
                <CustomerMini customer={customers[2]} rank={2} />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// PieChart — pur React SVG, style identique aux devis
// ---------------------------------------------------------------------------

const STATUS_COLORS = { 1: '#17a2b8', 2: '#ffc107', 3: '#28a745', 4: '#007bff', 5: '#dc3545', 6: '#343a40' };

function PieChart({ data, trans }) {
    const [hovered, setHovered] = useState(null);

    const items = (data ?? []).filter(d => (d.OrderCountRate ?? 0) > 0);
    const total = items.reduce((s, d) => s + Number(d.OrderCountRate), 0);

    if (!items.length) return <p className="text-muted text-center small py-3">—</p>;

    const R = 80, r = 44, cx = 110, cy = 110, size = 220;
    let angle = -Math.PI / 2;

    const slices = items.map(item => {
        const value  = Number(item.OrderCountRate);
        const sweep  = (value / total) * 2 * Math.PI;
        const x1 = cx + R * Math.cos(angle),  y1 = cy + R * Math.sin(angle);
        angle += sweep;
        const x2 = cx + R * Math.cos(angle),  y2 = cy + R * Math.sin(angle);
        const ix1 = cx + r * Math.cos(angle),  iy1 = cy + r * Math.sin(angle);
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
                    {hov ? hov.label : (trans.orders ?? 'orders')}
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
// Line Chart — pur React SVG (pas de Chart.js)
// ---------------------------------------------------------------------------

const CHART_BLUE   = 'rgba(60,141,188,0.9)';
const CHART_ORANGE = 'rgba(240,173,78,0.85)';

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

function buildMonthlyData(items, key = 'orderSum') {
    return Array.from({ length: 12 }, (_, i) => {
        const found = (items ?? []).find(d => d.month === i + 1);
        return found ? parseFloat(found[key]) || 0 : 0;
    });
}

function LineChart({ data, trans }) {
    const [hovered, setHovered] = useState(null);

    const MONTHS = [
        trans.jan, trans.feb, trans.mar, trans.apr, trans.may, trans.jun,
        trans.jul, trans.aug, trans.sep, trans.oct, trans.nov, trans.dec,
    ];

    const current  = buildMonthlyData(data.orderMonthlyRecap);
    const previous = buildMonthlyData(data.orderMonthlyRecapPreviousYear);

    const W = 560, H = 260;
    const PAD = { top: 16, right: 16, bottom: 36, left: 52 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top - PAD.bottom;

    const maxVal  = niceMax(Math.max(...current, ...previous, 1));
    const Y_TICKS = 4;

    const xPos = i => PAD.left + (i / 11) * plotW;
    const yPos = v => PAD.top + plotH - Math.min(v / maxVal, 1) * plotH;

    const linePath = d => d.map((v, i) => `${i === 0 ? 'M' : 'L'} ${xPos(i).toFixed(1)} ${yPos(v).toFixed(1)}`).join(' ');
    const areaPath = d => `${linePath(d)} L ${xPos(11).toFixed(1)} ${(PAD.top + plotH).toFixed(1)} L ${xPos(0).toFixed(1)} ${(PAD.top + plotH).toFixed(1)} Z`;

    const renderTooltip = () => {
        if (hovered === null) return null;
        const cv = current[hovered], pv = previous[hovered];
        const tipW = 92, tipH = 52;
        const tx = hovered > 8 ? xPos(hovered) - tipW - 8 : xPos(hovered) + 10;
        const ty = PAD.top + 4;
        return (
            <g pointerEvents="none">
                <line x1={xPos(hovered)} y1={PAD.top} x2={xPos(hovered)} y2={PAD.top + plotH}
                    stroke="#ccc" strokeWidth="1" strokeDasharray="4,2" />
                <rect x={tx} y={ty} width={tipW} height={tipH} rx="4"
                    fill="white" stroke="#ddd" strokeWidth="1"
                    style={{ filter: 'drop-shadow(0 1px 3px rgba(0,0,0,.12))' }} />
                <text x={tx + 8} y={ty + 14} fontSize="10" fontWeight="700" fill="#333">{MONTHS[hovered]}</text>
                <circle cx={tx + 10} cy={ty + 28} r={4} fill={CHART_BLUE} />
                <text x={tx + 18} y={ty + 32} fontSize="10" fill="#333">{shortAmount(cv)}</text>
                <circle cx={tx + 10} cy={ty + 42} r={4} fill={CHART_ORANGE} />
                <text x={tx + 18} y={ty + 46} fontSize="10" fill="#333">{shortAmount(pv)}</text>
            </g>
        );
    };

    return (
        <div>
            <svg viewBox={`0 0 ${W} ${H}`} style={{ width: '100%', display: 'block' }}>
                {Array.from({ length: Y_TICKS + 1 }, (_, i) => {
                    const v = (maxVal / Y_TICKS) * i;
                    const y = yPos(v);
                    return (
                        <g key={i}>
                            <line x1={PAD.left} y1={y} x2={PAD.left + plotW} y2={y}
                                stroke={i === 0 ? '#ccc' : '#efefef'} strokeWidth="1" />
                            <text x={PAD.left - 6} y={y + 4} textAnchor="end" fontSize="10" fill="#999">
                                {shortAmount(v)}
                            </text>
                        </g>
                    );
                })}
                <path d={areaPath(current)} fill="rgba(60,141,188,0.08)" />
                <path d={linePath(previous)} fill="none" stroke={CHART_ORANGE} strokeWidth="2" strokeDasharray="6,3" />
                <path d={linePath(current)}  fill="none" stroke={CHART_BLUE}   strokeWidth="2.5" />
                {MONTHS.map((m, i) => (
                    <g key={i}
                        onMouseEnter={() => setHovered(i)}
                        onMouseLeave={() => setHovered(null)}
                        style={{ cursor: 'default' }}>
                        <rect x={xPos(i) - plotW / 24} y={PAD.top} width={plotW / 12} height={plotH + 24} fill="transparent" />
                        <text x={xPos(i)} y={H - 6} textAnchor="middle" fontSize="10" fill="#666">
                            {(m ?? '').substring(0, 3)}
                        </text>
                        <circle cx={xPos(i)} cy={yPos(current[i])}  r={hovered === i ? 5 : 3} fill={CHART_BLUE}   stroke="#fff" strokeWidth="1.5" style={{ transition: 'r 0.1s' }} />
                        <circle cx={xPos(i)} cy={yPos(previous[i])} r={hovered === i ? 5 : 3} fill={CHART_ORANGE} stroke="#fff" strokeWidth="1.5" style={{ transition: 'r 0.1s' }} />
                    </g>
                ))}
                {renderTooltip()}
            </svg>
            <div className="d-flex justify-content-center mt-1" style={{ gap: '1.5rem' }}>
                {[
                    { color: CHART_BLUE,   dash: false, label: trans.order_forecast  ?? 'Order forecast' },
                    { color: CHART_ORANGE, dash: true,  label: trans.order_last_year ?? 'Last year' },
                ].map(({ color, dash, label }) => (
                    <div key={label} className="d-flex align-items-center" style={{ gap: '6px', fontSize: '0.78rem', color: '#555' }}>
                        <svg width="22" height="10">
                            <line x1="0" y1="5" x2="22" y2="5" stroke={color} strokeWidth="2" strokeDasharray={dash ? '5,3' : undefined} />
                        </svg>
                        {label}
                    </div>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Dashboard Tab
// ---------------------------------------------------------------------------

function DashboardTab({ kpi, chartData, topCustomers, trans }) {
    return (
        <div>
            {/* Row 1 : KPI cards + podium client sous chaque card */}
            <KPICards kpi={kpi} topCustomers={topCustomers} trans={trans} />

            {/* Row 2 : camembert | graphique ligne | KPIs secondaires */}
            <div className="row">
                <div className="col-md-3">
                    <div className="card card-teal">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-pie mr-1" />
                                {trans.orders_by_status ?? 'By status'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <PieChart data={chartData.ordersDataRate ?? []} trans={trans} />
                        </div>
                    </div>
                </div>

                <div className="col-lg-6">
                    <div className="card card-purple">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar mr-1" />
                                {trans.monthly_recap ?? 'Monthly recap'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <LineChart data={chartData} trans={trans} />
                        </div>
                    </div>
                </div>

                <div className="col-md-3">
                    <div className="card card-orange">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-info-circle mr-1" />
                                {trans.orders ?? 'Orders'}
                            </h3>
                        </div>
                        <div className="card-body p-2">
                            {[
                                { value: kpi.remainingDeliveryOrder,  label: trans.remaining_month            ?? 'Remaining to deliver',  icon: 'fas fa-truck',                color: 'danger'  },
                                { value: kpi.remainingInvoiceOrder,   label: trans.remaining_invoice_month    ?? 'Remaining to invoice',   icon: 'fas fa-file-invoice-dollar',  color: 'warning' },
                                { value: kpi.lateOrdersCount,         label: trans.late_orders                ?? 'Late orders',            icon: 'fas fa-exclamation-triangle', color: 'danger'  },
                                { value: kpi.pendingDeliveries,       label: trans.order_waiting              ?? 'Waiting orders',         icon: 'fas fa-hourglass-half',       color: 'warning' },
                                { value: `${kpi.averageProcessingTime ?? 0} ${trans.day ?? 'j'}`, label: trans.average_order_processing_time ?? 'Avg time', icon: 'fas fa-clock', color: 'info' },
                            ].map(({ value, label, icon, color }) => (
                                <div key={label} className={`d-flex align-items-center border-left border-${color} pl-2 mb-2`} style={{ borderLeftWidth: '3px !important' }}>
                                    <i className={`${icon} text-${color} mr-2`} style={{ width: 16 }} />
                                    <div style={{ flex: 1, fontSize: '0.78rem' }}>
                                        <div className="font-weight-bold">{value ?? '—'}</div>
                                        <div className="text-muted" style={{ fontSize: '0.7rem' }}>{label}</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// OrdersTable
// ---------------------------------------------------------------------------

function colDefs(trans) {
    return {
        code:               { label: trans.code                ?? 'Code',       sortField: 'code',              align: '',       bold: true  },
        label:              { label: trans.label               ?? 'Label',      sortField: 'label',             align: ''                   },
        customer_reference: { label: trans.customer_reference  ?? 'Ref.',       sortField: null,                align: ''                   },
        companie:           { label: trans.company             ?? 'Company',    sortField: 'companie',          align: ''                   },
        contact:            { label: trans.contact             ?? 'Contact',    sortField: 'contact',           align: ''                   },
        statu:              { label: trans.status              ?? 'Status',     sortField: 'statu',             align: 'center'             },
        validity_date:      { label: trans.validity_date       ?? 'Delivery',   sortField: 'validity_date',     align: 'center'             },
        created_at:         { label: trans.created_at          ?? 'Created',    sortField: 'created_at',        align: 'center'             },
        order_lines_count:  { label: trans.lines               ?? 'Lines',      sortField: 'order_lines_count', align: 'center'             },
        total_amount:       { label: trans.total_amount        ?? 'Total',      sortField: 'total_amount',      align: 'right',  bold: true  },
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

function matchesColFilter(o, colId, value) {
    if (DATE_RANGE_COLS.has(colId)) {
        const { from, to } = value ?? {};
        const iso = colId === 'validity_date' ? (o.validity_date ?? '') : dmyToISO(o.created_at ?? '');
        if (!iso) return true;
        if (from && iso < from) return false;
        if (to   && iso > to)   return false;
        return true;
    }
    const v = (value ?? '').toLowerCase().trim();
    if (!v) return true;
    switch (colId) {
        case 'code':               return (o.code ?? '').toLowerCase().includes(v);
        case 'label':              return (o.label ?? '').toLowerCase().includes(v);
        case 'customer_reference': return (o.customer_reference ?? '').toLowerCase().includes(v);
        case 'companie':           return (o.companie?.label ?? '').toLowerCase().includes(v);
        case 'contact':            return (o.contact?.name ?? '').toLowerCase().includes(v);
        default:                   return true;
    }
}

function OrdersTable({ orders, trans, onSort, sortField, sortAsc, currency, locale }) {
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

    const filtered  = orders.filter(o => visibleCols.every(colId => matchesColFilter(o, colId, colFilters[colId] ?? '')));
    const pageTotal = filtered.reduce((s, o) => s + (o.total_amount ?? 0), 0);
    const totalIdx  = visibleCols.indexOf('total_amount');
    const inputStyle = { fontSize: '0.72rem', height: '24px', padding: '1px 4px' };

    const cellRender = (o, colId) => {
        switch (colId) {
            case 'code':               return <code>{o.code}</code>;
            case 'label':              return o.label;
            case 'customer_reference': return o.customer_reference ?? '—';
            case 'companie':           return o.companie?.label ?? '—';
            case 'contact':            return o.contact?.name ?? '—';
            case 'statu':              return <StatusBadge statu={o.statu} trans={trans} />;
            case 'validity_date':      return formatDate(o.validity_date, locale);
            case 'created_at':         return o.created_at;
            case 'order_lines_count':  return <span className="badge badge-secondary">{o.order_lines_count}</span>;
            case 'total_amount':       return o.total_amount > 0
                ? formatCurrency(o.total_amount, currency, locale)
                : <span className="text-muted">—</span>;
            default: return '—';
        }
    };

    return (
        <div>
            {/* hidden-column restore chips */}
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
                        {/* row 1 : column headers */}
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
                                            borderLeft:  dropping ? '3px solid #007bff' : undefined,
                                            background:  dropping ? '#e8f0fe'           : undefined,
                                        }}
                                        onDragStart={() => onDragStart(colId)}
                                        onDragOver={e => onDragOver(e, colId)}
                                        onDragLeave={onDragLeave}
                                        onDrop={() => onDrop(colId)}
                                        onClick={() => col.sortField && onSort(col.sortField)}
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
                                        >×</span>
                                    </th>
                                );
                            })}
                            <th style={{ width: 36 }} />
                        </tr>
                        {/* row 2 : per-column filter inputs */}
                        <tr>
                            {visibleCols.map(colId => (
                                <th key={colId} style={{ padding: '2px 4px', fontWeight: 'normal' }}>
                                    {TEXT_FILTER_COLS.has(colId) && (
                                        <input type="text" className="form-control form-control-sm"
                                            style={inputStyle}
                                            placeholder="⌕"
                                            value={colFilters[colId] ?? ''}
                                            onChange={e => setColFilters(prev => ({ ...prev, [colId]: e.target.value }))}
                                        />
                                    )}
                                    {DATE_RANGE_COLS.has(colId) && (
                                        <div style={{ display: 'flex', gap: '2px', minWidth: '200px' }}>
                                            <input type="date" className="form-control form-control-sm"
                                                style={{ ...inputStyle, flex: 1, minWidth: 0 }}
                                                title="Du"
                                                value={(colFilters[colId] ?? {}).from ?? ''}
                                                onChange={e => setColFilters(prev => ({ ...prev, [colId]: { ...(prev[colId] ?? {}), from: e.target.value } }))}
                                            />
                                            <input type="date" className="form-control form-control-sm"
                                                style={{ ...inputStyle, flex: 1, minWidth: 0 }}
                                                title="Au"
                                                value={(colFilters[colId] ?? {}).to ?? ''}
                                                onChange={e => setColFilters(prev => ({ ...prev, [colId]: { ...(prev[colId] ?? {}), to: e.target.value } }))}
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
                            <tr><td colSpan={visibleCols.length + 1} className="text-center text-muted py-3">{trans.no_results ?? 'No results'}</td></tr>
                        )}
                        {filtered.map(o => (
                            <tr key={o.id}>
                                {visibleCols.map(colId => {
                                    const col      = COLS[colId];
                                    const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                    return (
                                        <td key={colId}
                                            className={`${alignCls}${col.bold ? ' font-weight-bold' : ''}`}
                                            style={(col.align === 'right' || col.bold) ? { whiteSpace: 'nowrap' } : {}}
                                        >
                                            {cellRender(o, colId)}
                                        </td>
                                    );
                                })}
                                <td>
                                    <a href={o.url} className="btn btn-xs btn-info">
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
                                    if (colId === 'total_amount') return (
                                        <td key={colId} className="text-right" style={{ whiteSpace: 'nowrap' }}>
                                            {formatCurrency(pageTotal, currency, locale)}
                                        </td>
                                    );
                                    if (i === totalIdx - 1) return <td key={colId} className="text-right">{trans.total ?? 'Total'}</td>;
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
// OrderCards (card view)
// ---------------------------------------------------------------------------

function OrderCards({ orders, trans, currency, locale }) {
    if (orders.length === 0) {
        return <div className="text-center text-muted py-4">{trans.no_results ?? 'No results'}</div>;
    }
    return (
        <div className="row">
            {orders.map(o => (
                <div key={o.id} className="col-md-3 mb-3">
                    <div className="card h-100">
                        <div className="card-body p-2">
                            <div className="d-flex justify-content-between align-items-start mb-1">
                                <a href={o.url} className="font-weight-bold text-primary"><code>{o.code}</code></a>
                                <StatusBadge statu={o.statu} trans={trans} />
                            </div>
                            <div className="text-truncate small" title={o.label}>{o.label}</div>
                            {o.companie && <div className="text-muted" style={{ fontSize: '0.72rem' }}>{o.companie.label}</div>}
                            {o.contact  && <div className="text-muted" style={{ fontSize: '0.72rem' }}>{o.contact.name}</div>}
                            <div className="mt-2 d-flex justify-content-between">
                                <span className="text-muted small">{formatDate(o.validity_date, locale)}</span>
                                <span className="font-weight-bold small">{formatCurrency(o.total_amount, currency, locale)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// KanbanBoard
// ---------------------------------------------------------------------------

function KanbanBoard({ statuses, trans, currency, locale }) {
    return (
        <div className="d-flex" style={{ overflowX: 'auto', gap: '0.75rem', paddingBottom: '0.5rem' }}>
            {statuses.map(col => {
                const cfg = STATUS_CONFIG[col.id] ?? {};
                const bg  = cfg.badge?.replace('badge-', 'bg-') ?? 'bg-secondary';
                return (
                    <div key={col.id} className="card flex-shrink-0" style={{ minWidth: 220, maxWidth: 260, width: '100%' }}>
                        <div className={`card-header py-1 d-flex justify-content-between align-items-center ${bg}`}>
                            <span className="text-white font-weight-bold" style={{ fontSize: '0.82rem' }}>
                                {trans[cfg.label] ?? col.id}
                            </span>
                            <span className="badge badge-light">{col.items?.length ?? 0}</span>
                        </div>
                        <div className="card-body p-2" style={{ maxHeight: 420, overflowY: 'auto' }}>
                            {(col.items ?? []).map(o => (
                                <div key={o.id} className="card mb-2 shadow-sm">
                                    <div className="card-body p-2">
                                        <a href={o.url} className="font-weight-bold text-primary d-block mb-1" style={{ fontSize: '0.8rem' }}>
                                            <code>{o.code}</code>
                                        </a>
                                        <div className="text-truncate" style={{ fontSize: '0.75rem' }} title={o.label}>{o.label}</div>
                                        {o.companie && <div className="text-muted" style={{ fontSize: '0.68rem' }}>{o.companie.label}</div>}
                                        <div className="d-flex justify-content-between mt-1">
                                            <span className="text-muted" style={{ fontSize: '0.68rem' }}>{formatDate(o.validity_date, locale)}</span>
                                            <span className="font-weight-bold" style={{ fontSize: '0.68rem' }}>{formatCurrency(o.total_amount, currency, locale)}</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                            {(!col.items || col.items.length === 0) && (
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
// CreateAddressSubModal
// ---------------------------------------------------------------------------

function CreateAddressSubModal({ companyId, endpoints, trans, onCreated, onClose }) {
    const [form, setForm]     = useState({ ordre: '1', label: '', adress: '', zipcode: '', city: '', country: '', number: '', mail: '' });
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    const set  = (k, v) => setForm(f => ({ ...f, [k]: v }));
    const save = async () => {
        setSaving(true); setErrors({});
        try {
            const data = await apiFetch(endpoints.storeAddress, { method: 'POST', body: JSON.stringify({ ...form, companies_id: companyId }) });
            onCreated(data);
        } catch (e) { setErrors(e.errors ?? {}); }
        finally { setSaving(false); }
    };

    const fields = [
        { k: 'ordre',   label: trans.ordre       ?? 'Order',       type: 'number' },
        { k: 'label',   label: trans.adress_label ?? 'Label'                      },
        { k: 'adress',  label: trans.adress       ?? 'Address'                    },
        { k: 'zipcode', label: trans.postal_code  ?? 'Postal code'                },
        { k: 'city',    label: trans.city         ?? 'City'                       },
        { k: 'country', label: trans.country      ?? 'Country'                    },
        { k: 'number',  label: trans.phone        ?? 'Phone'                      },
        { k: 'mail',    label: trans.email        ?? 'Email',       type: 'email' },
    ];

    return (
        <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1060 }}>
            <div className="modal-dialog modal-lg">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title">{trans.new_address ?? 'New address'}</h5>
                        <button className="close" onClick={onClose}><span>×</span></button>
                    </div>
                    <div className="modal-body">
                        <div className="row">
                            {fields.map(({ k, label, type }) => (
                                <div className="col-md-6 mb-2" key={k}>
                                    <label className="mb-0 small">{label}</label>
                                    <input className={`form-control form-control-sm ${errors[k] ? 'is-invalid' : ''}`}
                                        type={type ?? 'text'} value={form[k]} onChange={e => set(k, e.target.value)} />
                                    {errors[k] && <div className="invalid-feedback">{errors[k][0]}</div>}
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button className="btn btn-secondary btn-sm" onClick={onClose}>{trans.cancel ?? 'Cancel'}</button>
                        <button className="btn btn-primary btn-sm" onClick={save} disabled={saving}>
                            {saving ? (trans.saving ?? 'Saving…') : (trans.save ?? 'Save')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// CreateContactSubModal
// ---------------------------------------------------------------------------

function CreateContactSubModal({ companyId, endpoints, trans, onCreated, onClose }) {
    const [form, setForm]     = useState({ ordre: '1', civility: '', first_name: '', name: '', function: '', number: '', mobile: '', mail: '' });
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    const set  = (k, v) => setForm(f => ({ ...f, [k]: v }));
    const save = async () => {
        setSaving(true); setErrors({});
        try {
            const data = await apiFetch(endpoints.storeContact, { method: 'POST', body: JSON.stringify({ ...form, companies_id: companyId }) });
            onCreated(data);
        } catch (e) { setErrors(e.errors ?? {}); }
        finally { setSaving(false); }
    };

    const fields = [
        { k: 'ordre',      label: trans.ordre      ?? 'Order',     type: 'number' },
        { k: 'civility',   label: trans.civility   ?? 'Civility'                  },
        { k: 'first_name', label: trans.first_name ?? 'First name'                },
        { k: 'name',       label: trans.name       ?? 'Name'                      },
        { k: 'function',   label: trans.function   ?? 'Function'                  },
        { k: 'number',     label: trans.phone      ?? 'Phone'                     },
        { k: 'mobile',     label: trans.mobile     ?? 'Mobile'                    },
        { k: 'mail',       label: trans.email      ?? 'Email',     type: 'email'  },
    ];

    return (
        <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1060 }}>
            <div className="modal-dialog modal-lg">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title">{trans.new_contact ?? 'New contact'}</h5>
                        <button className="close" onClick={onClose}><span>×</span></button>
                    </div>
                    <div className="modal-body">
                        <div className="row">
                            {fields.map(({ k, label, type }) => (
                                <div className="col-md-6 mb-2" key={k}>
                                    <label className="mb-0 small">{label}</label>
                                    <input className={`form-control form-control-sm ${errors[k] ? 'is-invalid' : ''}`}
                                        type={type ?? 'text'} value={form[k]} onChange={e => set(k, e.target.value)} />
                                    {errors[k] && <div className="invalid-feedback">{errors[k][0]}</div>}
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button className="btn btn-secondary btn-sm" onClick={onClose}>{trans.cancel ?? 'Cancel'}</button>
                        <button className="btn btn-primary btn-sm" onClick={save} disabled={saving}>
                            {saving ? (trans.saving ?? 'Saving…') : (trans.save ?? 'Save')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// CreateModal
// ---------------------------------------------------------------------------

function CreateModal({ endpoints, trans, onClose }) {
    const [selectData, setSelectData]     = useState(null);
    const [loadingData, setLoadingData]   = useState(true);
    const [type, setType]                 = useState(1);
    const [form, setForm] = useState({
        code: '', label: '', customer_reference: '', comment: '',
        companies_id: '', companies_contacts_id: '', companies_addresses_id: '',
        accounting_payment_conditions_id: '', accounting_payment_methods_id: '', accounting_deliveries_id: '',
        user_id: '', validity_date: '',
    });
    const [errors, setErrors]             = useState({});
    const [saving, setSaving]             = useState(false);
    const [addressOptions, setAddressOptions] = useState([]);
    const [contactOptions, setContactOptions] = useState([]);
    const [showAddressModal, setShowAddressModal] = useState(false);
    const [showContactModal, setShowContactModal] = useState(false);

    useEffect(() => {
        apiFetch(endpoints.selectData)
            .then(data => {
                setSelectData(data);
                setForm(f => ({
                    ...f,
                    code:   data.next_code_external ?? '',
                    label:  data.next_code_external ?? '',
                    user_id: String(data.users?.[0]?.id ?? ''),
                    accounting_payment_conditions_id: String(data.payment_conditions?.find(c => c.default)?.id ?? data.payment_conditions?.[0]?.id ?? ''),
                    accounting_payment_methods_id:    String(data.payment_methods?.find(m => m.default)?.id    ?? data.payment_methods?.[0]?.id    ?? ''),
                    accounting_deliveries_id:         String(data.deliveries?.find(d => d.default)?.id         ?? data.deliveries?.[0]?.id         ?? ''),
                }));
            })
            .catch(() => {})
            .finally(() => setLoadingData(false));
    }, []);

    useEffect(() => {
        if (!selectData) return;
        const code = type === 1 ? (selectData.next_code_external ?? '') : (selectData.next_code_internal ?? '');
        setForm(f => ({ ...f, code, label: code }));
    }, [type, selectData]);

    useEffect(() => {
        if (!form.companies_id) { setAddressOptions([]); setContactOptions([]); return; }
        const addrUrl = endpoints.addresses.replace('__ID__', form.companies_id);
        const contUrl = endpoints.contacts.replace('__ID__', form.companies_id);
        Promise.all([apiFetch(addrUrl), apiFetch(contUrl)])
            .then(([addr, cont]) => {
                setAddressOptions(addr ?? []);
                setContactOptions(cont ?? []);
                setForm(f => ({
                    ...f,
                    companies_addresses_id: addr?.[0]?.id ? String(addr[0].id) : '',
                    companies_contacts_id:  cont?.[0]?.id ? String(cont[0].id) : '',
                }));
            })
            .catch(() => {});
    }, [form.companies_id]);

    const set  = (k, v) => setForm(f => ({ ...f, [k]: v }));
    const save = async () => {
        setSaving(true); setErrors({});
        try {
            const data = await apiFetch(endpoints.store, { method: 'POST', body: JSON.stringify({ ...form, type }) });
            if (data.redirect) window.location.href = data.redirect;
        } catch (e) { setErrors(e.errors ?? {}); }
        finally { setSaving(false); }
    };

    if (loadingData) return (
        <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1050 }}>
            <div className="modal-dialog"><div className="modal-content"><div className="modal-body text-center py-4"><i className="fas fa-spinner fa-spin fa-2x"></i></div></div></div>
        </div>
    );

    const isExternal = type === 1;

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1040 }} onClick={onClose}></div>
            <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1050 }}>
                <div className="modal-dialog modal-lg">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title">{trans.new_order ?? 'New order'}</h5>
                            <button className="close" onClick={onClose}><span>×</span></button>
                        </div>
                        <div className="modal-body">

                            {/* Type selector */}
                            <div className="form-group mb-3">
                                <label className="d-block mb-1 small font-weight-bold">{trans.order_type ?? 'Order type'}</label>
                                <div className="btn-group btn-group-sm">
                                    <button type="button" className={`btn ${type === 1 ? 'btn-primary' : 'btn-outline-primary'}`} onClick={() => setType(1)}>
                                        {trans.customer_type ?? 'Customer order'}
                                    </button>
                                    <button type="button" className={`btn ${type === 2 ? 'btn-primary' : 'btn-outline-primary'}`} onClick={() => setType(2)}>
                                        {trans.internal_type ?? 'Internal order'}
                                    </button>
                                </div>
                            </div>

                            <div className="row">
                                {/* Code */}
                                <div className="col-md-4 mb-2">
                                    <label className="mb-0 small">{trans.code ?? 'Code'} *</label>
                                    <input className={`form-control form-control-sm ${errors.code ? 'is-invalid' : ''}`}
                                        value={form.code} onChange={e => set('code', e.target.value)} />
                                    {errors.code && <div className="invalid-feedback">{errors.code[0]}</div>}
                                </div>
                                {/* Label */}
                                <div className="col-md-8 mb-2">
                                    <label className="mb-0 small">{trans.label ?? 'Label'} *</label>
                                    <input className={`form-control form-control-sm ${errors.label ? 'is-invalid' : ''}`}
                                        value={form.label} onChange={e => set('label', e.target.value)} />
                                    {errors.label && <div className="invalid-feedback">{errors.label[0]}</div>}
                                </div>
                                {/* Delivery date */}
                                <div className="col-md-4 mb-2">
                                    <label className="mb-0 small">{trans.validity_date ?? 'Delivery date'} *</label>
                                    <input type="date" className={`form-control form-control-sm ${errors.validity_date ? 'is-invalid' : ''}`}
                                        value={form.validity_date} onChange={e => set('validity_date', e.target.value)} />
                                    {errors.validity_date && <div className="invalid-feedback">{errors.validity_date[0]}</div>}
                                </div>
                                {/* Assignee */}
                                <div className="col-md-4 mb-2">
                                    <label className="mb-0 small">{trans.assignee ?? 'Assignee'} *</label>
                                    <select className={`form-control form-control-sm ${errors.user_id ? 'is-invalid' : ''}`}
                                        value={form.user_id} onChange={e => set('user_id', e.target.value)}>
                                        <option value="">—</option>
                                        {(selectData?.users ?? []).map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                                    </select>
                                    {errors.user_id && <div className="invalid-feedback">{errors.user_id[0]}</div>}
                                </div>
                                {/* Customer reference (external only) */}
                                {isExternal && (
                                    <div className="col-md-4 mb-2">
                                        <label className="mb-0 small">{trans.customer_reference ?? 'Customer ref.'}</label>
                                        <input className="form-control form-control-sm"
                                            value={form.customer_reference} onChange={e => set('customer_reference', e.target.value)} />
                                    </div>
                                )}
                                {/* Company */}
                                {isExternal && (
                                    <div className="col-md-6 mb-2">
                                        <label className="mb-0 small">{trans.company ?? 'Company'} *</label>
                                        <select className={`form-control form-control-sm ${errors.companies_id ? 'is-invalid' : ''}`}
                                            value={form.companies_id} onChange={e => set('companies_id', e.target.value)}>
                                            <option value="">—</option>
                                            {(selectData?.companies ?? []).map(c => (
                                                <option key={c.id} value={c.id}>{c.label ?? c.code}</option>
                                            ))}
                                        </select>
                                        {errors.companies_id && <div className="invalid-feedback">{errors.companies_id[0]}</div>}
                                    </div>
                                )}
                                {/* Contact */}
                                {isExternal && (
                                    <div className="col-md-6 mb-2">
                                        <label className="mb-0 small">{trans.contact ?? 'Contact'} *</label>
                                        <div className="input-group input-group-sm">
                                            <select className={`form-control form-control-sm ${errors.companies_contacts_id ? 'is-invalid' : ''}`}
                                                value={form.companies_contacts_id}
                                                onChange={e => set('companies_contacts_id', e.target.value)}
                                                disabled={!form.companies_id}>
                                                <option value="">—</option>
                                                {contactOptions.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                            </select>
                                            {form.companies_id && (
                                                <div className="input-group-append">
                                                    <button className="btn btn-outline-secondary btn-sm" type="button" onClick={() => setShowContactModal(true)}>+</button>
                                                </div>
                                            )}
                                        </div>
                                        {errors.companies_contacts_id && <div className="invalid-feedback d-block">{errors.companies_contacts_id[0]}</div>}
                                    </div>
                                )}
                                {/* Address */}
                                {isExternal && (
                                    <div className="col-md-6 mb-2">
                                        <label className="mb-0 small">{trans.address ?? 'Address'} *</label>
                                        <div className="input-group input-group-sm">
                                            <select className={`form-control form-control-sm ${errors.companies_addresses_id ? 'is-invalid' : ''}`}
                                                value={form.companies_addresses_id}
                                                onChange={e => set('companies_addresses_id', e.target.value)}
                                                disabled={!form.companies_id}>
                                                <option value="">—</option>
                                                {addressOptions.map(a => <option key={a.id} value={a.id}>{a.label} — {a.adress}</option>)}
                                            </select>
                                            {form.companies_id && (
                                                <div className="input-group-append">
                                                    <button className="btn btn-outline-secondary btn-sm" type="button" onClick={() => setShowAddressModal(true)}>+</button>
                                                </div>
                                            )}
                                        </div>
                                        {errors.companies_addresses_id && <div className="invalid-feedback d-block">{errors.companies_addresses_id[0]}</div>}
                                    </div>
                                )}
                                {/* Payment condition */}
                                {isExternal && (
                                    <div className="col-md-6 mb-2">
                                        <label className="mb-0 small">{trans.payment_condition ?? 'Payment condition'} *</label>
                                        <select className={`form-control form-control-sm ${errors.accounting_payment_conditions_id ? 'is-invalid' : ''}`}
                                            value={form.accounting_payment_conditions_id}
                                            onChange={e => set('accounting_payment_conditions_id', e.target.value)}>
                                            <option value="">—</option>
                                            {(selectData?.payment_conditions ?? []).map(c => <option key={c.id} value={c.id}>{c.label}</option>)}
                                        </select>
                                        {errors.accounting_payment_conditions_id && <div className="invalid-feedback">{errors.accounting_payment_conditions_id[0]}</div>}
                                    </div>
                                )}
                                {/* Payment method */}
                                {isExternal && (
                                    <div className="col-md-6 mb-2">
                                        <label className="mb-0 small">{trans.payment_method ?? 'Payment method'} *</label>
                                        <select className={`form-control form-control-sm ${errors.accounting_payment_methods_id ? 'is-invalid' : ''}`}
                                            value={form.accounting_payment_methods_id}
                                            onChange={e => set('accounting_payment_methods_id', e.target.value)}>
                                            <option value="">—</option>
                                            {(selectData?.payment_methods ?? []).map(m => <option key={m.id} value={m.id}>{m.label}</option>)}
                                        </select>
                                        {errors.accounting_payment_methods_id && <div className="invalid-feedback">{errors.accounting_payment_methods_id[0]}</div>}
                                    </div>
                                )}
                                {/* Delivery */}
                                {isExternal && (
                                    <div className="col-md-6 mb-2">
                                        <label className="mb-0 small">{trans.delivery ?? 'Delivery'} *</label>
                                        <select className={`form-control form-control-sm ${errors.accounting_deliveries_id ? 'is-invalid' : ''}`}
                                            value={form.accounting_deliveries_id}
                                            onChange={e => set('accounting_deliveries_id', e.target.value)}>
                                            <option value="">—</option>
                                            {(selectData?.deliveries ?? []).map(d => <option key={d.id} value={d.id}>{d.label}</option>)}
                                        </select>
                                        {errors.accounting_deliveries_id && <div className="invalid-feedback">{errors.accounting_deliveries_id[0]}</div>}
                                    </div>
                                )}
                                {/* Comment */}
                                <div className="col-12 mb-2">
                                    <label className="mb-0 small">{trans.comment ?? 'Comment'}</label>
                                    <textarea className="form-control form-control-sm" rows={2}
                                        value={form.comment} onChange={e => set('comment', e.target.value)} />
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button className="btn btn-secondary btn-sm" onClick={onClose}>{trans.cancel ?? 'Cancel'}</button>
                            <button className="btn btn-success btn-sm" onClick={save} disabled={saving}>
                                {saving ? (trans.saving ?? 'Saving…') : (trans.save ?? 'Save')}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {showAddressModal && (
                <CreateAddressSubModal
                    companyId={form.companies_id}
                    endpoints={endpoints}
                    trans={trans}
                    onCreated={addr => {
                        setAddressOptions(a => [...a, addr]);
                        set('companies_addresses_id', String(addr.id));
                        setShowAddressModal(false);
                    }}
                    onClose={() => setShowAddressModal(false)}
                />
            )}
            {showContactModal && (
                <CreateContactSubModal
                    companyId={form.companies_id}
                    endpoints={endpoints}
                    trans={trans}
                    onCreated={contact => {
                        setContactOptions(c => [...c, contact]);
                        set('companies_contacts_id', String(contact.id));
                        setShowContactModal(false);
                    }}
                    onClose={() => setShowContactModal(false)}
                />
            )}
        </>
    );
}

// ---------------------------------------------------------------------------
// ListTab
// ---------------------------------------------------------------------------

function ListTab({ endpoints, trans, currency, locale }) {
    const [orders, setOrders]       = useState([]);
    const [meta, setMeta]           = useState(null);
    const [loading, setLoading]     = useState(false);
    const [search, setSearch]       = useState('');
    const [statuses, setStatuses]   = useState([1, 2]);
    const [sortField, setSortField] = useState('created_at');
    const [sortAsc, setSortAsc]     = useState(false);
    const [page, setPage]           = useState(1);
    const [viewType, setViewType]   = useState('table');
    const [showModal, setShowModal] = useState(false);
    const debounceRef = useRef(null);

    // Restore filters from localStorage
    useEffect(() => {
        try {
            const saved = localStorage.getItem(LS_FILTERS);
            if (saved) {
                const f = JSON.parse(saved);
                if (f.search    !== undefined) setSearch(f.search);
                if (f.statuses  !== undefined) setStatuses(f.statuses);
                if (f.sortField !== undefined) setSortField(f.sortField);
                if (f.sortAsc   !== undefined) setSortAsc(f.sortAsc);
                if (f.viewType  !== undefined) setViewType(f.viewType);
            }
        } catch {}
    }, []);

    // Persist filters
    useEffect(() => {
        localStorage.setItem(LS_FILTERS, JSON.stringify({ search, statuses, sortField, sortAsc, viewType }));
    }, [search, statuses, sortField, sortAsc, viewType]);

    const fetchOrders = useCallback(async (overrides = {}) => {
        setLoading(true);
        const p  = overrides.page      ?? page;
        const q  = overrides.search    ?? search;
        const s  = overrides.statuses  ?? statuses;
        const sf = overrides.sortField ?? sortField;
        const sa = overrides.sortAsc   ?? sortAsc;

        const params = new URLSearchParams({ search: q, sort: sf, asc: sa ? '1' : '0', page: p });
        s.forEach(id => params.append('statuses[]', id));

        try {
            const data = await apiFetch(`${endpoints.list}?${params}`);
            setOrders(data.data ?? []);
            setMeta(data.meta ?? null);
        } catch {}
        finally { setLoading(false); }
    }, [endpoints.list, page, search, statuses, sortField, sortAsc]);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => fetchOrders(), 200);
        return () => clearTimeout(debounceRef.current);
    }, [fetchOrders]);

    const handleSort = field => {
        const newAsc = sortField === field ? !sortAsc : true;
        setSortField(field);
        setSortAsc(newAsc);
        setPage(1);
    };
    const handleStatusToggle = sid => {
        setStatuses(s => s.includes(sid) ? s.filter(x => x !== sid) : [...s, sid]);
        setPage(1);
    };
    const handleSearch = val => { setSearch(val); setPage(1); };
    const handlePage   = p   => setPage(p);

    const kanbanCols = Object.entries(STATUS_CONFIG).map(([id, cfg]) => ({
        id:    Number(id),
        label: trans[cfg.label] ?? cfg.label,
        badge: cfg.badge,
        items: orders.filter(o => o.statu === Number(id)),
    }));

    return (
        <div>
            {/* Toolbar — all controls on one line */}
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

                {/* Spacer */}
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

                {/* New order */}
                <button className="btn btn-sm btn-success flex-shrink-0" onClick={() => setShowModal(true)}>
                    <i className="fas fa-plus mr-1" />{trans.new_order ?? 'New order'}
                </button>
            </div>

            {loading && <div className="text-center py-3"><i className="fas fa-spinner fa-spin fa-lg"></i></div>}

            {!loading && viewType === 'table' && (
                <OrdersTable
                    orders={orders}
                    trans={trans}
                    onSort={handleSort}
                    sortField={sortField}
                    sortAsc={sortAsc}
                    currency={currency}
                    locale={locale}
                />
            )}
            {!loading && viewType === 'card' && (
                <OrderCards orders={orders} trans={trans} currency={currency} locale={locale} />
            )}
            {!loading && viewType === 'kanban' && (
                <KanbanBoard statuses={kanbanCols} trans={trans} currency={currency} locale={locale} />
            )}

            {meta && viewType !== 'kanban' && (
                <div className="d-flex justify-content-between align-items-center mt-2">
                    <small className="text-muted">
                        {meta.total} {trans.orders ?? 'orders'} — {trans.page ?? 'Page'} {meta.current_page}/{meta.last_page}
                    </small>
                    <Pagination meta={meta} onPage={handlePage} />
                </div>
            )}

            {showModal && (
                <CreateModal
                    endpoints={endpoints}
                    trans={trans}
                    currency={currency}
                    locale={locale}
                    onClose={() => setShowModal(false)}
                />
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function OrdersIndex({ kpi, chartData, topCustomers, endpoints, trans }) {
    const [activeTab, setActiveTab] = useState('dashboard');
    const currency = trans.currency ?? 'EUR';
    const locale   = trans.locale   ?? 'fr-FR';

    return (
        <div className="card">
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
                            {trans.orders_list ?? 'Orders list'}
                        </a>
                    </li>
                </ul>
            </div>
            <div className="card-body">
                {activeTab === 'dashboard' && (
                    <DashboardTab
                        kpi={kpi}
                        chartData={chartData}
                        topCustomers={topCustomers}
                        trans={trans}
                        currency={currency}
                        locale={locale}
                    />
                )}
                {activeTab === 'list' && (
                    <ListTab
                        endpoints={endpoints}
                        trans={trans}
                        currency={currency}
                        locale={locale}
                    />
                )}
            </div>
        </div>
    );
}
