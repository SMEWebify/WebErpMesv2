import React, { useState, useEffect, useRef, useCallback } from 'react'; // useRef used by ListTab debounce
import { DataTable, Pagination, StatusBadge, StatusFilter, MobileFilters, useIndexTab, IndexCard, IndexCardGrid, todayIso, daysPast } from './table';
import { apiFetchOrThrow as apiFetch, apiRequest } from '../lib/http';
import { askCreditOverride } from '../lib/creditLimit';
import { formatDate, formatCurrency } from '../utils';
import { CreateAddressSubModal, CreateContactSubModal } from './company/CompanySubModals';
import { affairCategoryColumn, AffairCategoryFilter, AffairCategoryField } from './affairs/affairCategory.jsx';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    0: { badge: 'text-bg-secondary', label: 'awaiting_payment' },
    1: { badge: 'text-bg-info',      label: 'open' },
    2: { badge: 'text-bg-warning',   label: 'in_progress' },
    3: { badge: 'text-bg-success',   label: 'delivered' },
    4: { badge: 'text-bg-primary',   label: 'partly_delivered' },
    5: { badge: 'text-bg-danger',    label: 'stopped' },
    6: { badge: 'text-bg-dark',      label: 'canceled' },
};

const LS_COL_ORDER   = 'orders_table_col_order';
const LS_HIDDEN_COLS = 'orders_table_hidden_cols';
const LS_FILTERS     = 'orders_list_filters';


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
            <span className="badge text-bg-secondary" style={{ fontSize: '0.72rem' }}>{customer.order_count}</span>
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
                <div className="small-box text-bg-success">
                    <div className="inner">
                        <h3>{kpi.deliveredOrdersPercentage ?? 0}%</h3>
                        <p>{trans.order_delivered ?? 'Delivered'}</p>
                    </div>
                    <i className="small-box-icon fas fa-shipping-fast" aria-hidden="true" />
                </div>
                <CustomerMini customer={customers[0]} rank={0} />
            </div>
            <div className="col-lg-4">
                <div className="small-box text-bg-info">
                    <div className="inner">
                        <h3>{kpi.invoicedOrdersPercentage ?? 0}%</h3>
                        <p>{trans.order_invoiced ?? 'Invoiced'}</p>
                    </div>
                    <i className="small-box-icon fas fa-file-invoice-dollar" aria-hidden="true" />
                </div>
                <CustomerMini customer={customers[1]} rank={1} />
            </div>
            <div className="col-lg-4">
                <div className="small-box text-bg-primary">
                    <div className="inner">
                        <h3>{kpi.serviceRate ?? 0}%</h3>
                        <p>{trans.service_rate ?? 'Service rate'}</p>
                    </div>
                    <i className="small-box-icon fas fa-chart-line" aria-hidden="true" />
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

const MONTH_KEYS_ORDERED = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];

function buildMonthlyData(items, key = 'orderSum', startMonth = 1) {
    return Array.from({ length: 12 }, (_, i) => {
        const calMonth = ((startMonth - 1 + i) % 12) + 1;
        const found = (items ?? []).find(d => d.month === calMonth);
        return found ? parseFloat(found[key]) || 0 : 0;
    });
}

function LineChart({ data, trans }) {
    const [hovered, setHovered] = useState(null);
    const startMonth = data.fiscalYearStartMonth ?? 1;

    const MONTHS = Array.from({ length: 12 }, (_, i) => {
        const key = MONTH_KEYS_ORDERED[((startMonth - 1) + i) % 12];
        return trans[key] ?? key;
    });

    const current  = buildMonthlyData(data.orderMonthlyRecap, 'orderSum', startMonth);
    const previous = buildMonthlyData(data.orderMonthlyRecapPreviousYear, 'orderSum', startMonth);

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
                    <div key={label} className="d-flex align-items-center" style={{ gap: '6px', fontSize: '0.78rem', color: 'var(--bs-body-color)' }}>
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
                    <div className="card card-teal mb-3">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-pie me-1" />
                                {trans.orders_by_status ?? 'By status'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <PieChart data={chartData.ordersDataRate ?? []} trans={trans} />
                        </div>
                    </div>
                </div>

                <div className="col-lg-6">
                    <div className="card card-violet mb-3">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar me-1" />
                                {trans.monthly_recap ?? 'Monthly recap'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <LineChart data={chartData} trans={trans} />
                        </div>
                    </div>
                </div>

                <div className="col-md-3">
                    <div className="card card-orange mb-3">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-info-circle me-1" />
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
                                <div key={label} className={`d-flex align-items-center border-start border-${color} ps-2 mb-2`} style={{ borderLeftWidth: '3px !important' }}>
                                    <i className={`${icon} text-${color} me-2`} style={{ width: 16 }} />
                                    <div style={{ flex: 1, fontSize: '0.78rem' }}>
                                        <div className="fw-bold">{value ?? '—'}</div>
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
// OrdersTable — colonnes déclarées, rendu par le DataTable partagé
// ---------------------------------------------------------------------------

function orderColumns(trans, currency, locale) {
    return [
        { key: 'code',               label: trans.code               ?? 'Code',     sortable: true,                bold: true,
          render: o => <code>{o.code}</code>, filter: 'text', mobile: 'title', mobileRender: o => o.code },
        { key: 'label',              label: trans.label              ?? 'Label',    sortable: true,
          // Repères qui ont fait remonter la commande dans la recherche.
          render: o => (o.matched_equipment_tags?.length ? (
              <>
                  {o.label}
                  <div className="small" title={trans.matched_equipment_tag}>
                      <i className="fas fa-tag text-body-secondary me-1" />
                      {o.matched_equipment_tags.map(tag => (
                          <span key={tag} className="badge bg-body-tertiary text-body border me-1">{tag}</span>
                      ))}
                  </div>
              </>
          ) : o.label),
          filter: 'text', mobile: 'subtitle', mobileRender: o => o.label },
        { key: 'customer_reference', label: trans.customer_reference ?? 'Ref.',
          render: o => o.customer_reference ?? '—', filter: 'text' },
        { key: 'companie',           label: trans.company            ?? 'Company',  sortable: true,
          render: o => o.companie?.label ?? '—', filterValue: o => o.companie?.label, filter: 'text', mobile: 'subtitle', mobileOrder: 1 },
        { key: 'contact',            label: trans.contact            ?? 'Contact',  sortable: true,
          render: o => o.contact?.name ?? '—', filterValue: o => o.contact?.name, filter: 'text' },
        { key: 'statu',              label: trans.status             ?? 'Status',   sortable: true,                align: 'center',
          render: o => <StatusBadge statu={o.statu} config={STATUS_CONFIG} trans={trans} />, mobile: 'badge' },
        { key: 'validity_date',      label: trans.validity_date      ?? 'Delivery', sortable: true,                align: 'center',
          render: o => formatDate(o.validity_date, locale), filter: 'date' },
        { key: 'created_at',         label: trans.created_at         ?? 'Created',  sortable: true,                align: 'center',
          filter: 'date' },
        { key: 'order_lines_count',  label: trans.lines              ?? 'Lines',    sortable: true,                align: 'center',
          render: o => <span className="badge text-bg-secondary">{o.order_lines_count}</span> },
        { key: 'total_amount',       label: trans.total_amount       ?? 'Total',    sortable: true,                align: 'right', bold: true,
          render: o => (o.total_amount > 0 ? formatCurrency(o.total_amount, currency, locale) : <span className="text-muted">—</span>),
          total: { value: o => o.total_amount ?? 0, format: sum => formatCurrency(sum, currency, locale) },
          mobile: 'amount', mobileRender: o => (o.total_amount > 0 ? formatCurrency(o.total_amount, currency, locale) : null) },
    ];
}

function OrdersTable({ orders, loading, trans, onSort, sortField, sortAsc, currency, locale, withCategory }) {
    const columns = orderColumns(trans, currency, locale);
    if (withCategory) columns.push(affairCategoryColumn(trans));
    return (
        <DataTable
            rows={orders}
            columns={columns}
            loading={loading}
            trans={trans}
            sortField={sortField}
            sortAsc={sortAsc}
            onSort={onSort}
            storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
            rowHref={o => o.url}
            emptyText={trans.no_results ?? 'No results'}
            totalLabel={trans.total ?? 'Total'}
        />
    );
}

// ---------------------------------------------------------------------------
// OrderCards (card view)
// ---------------------------------------------------------------------------

// Commande pas encore entièrement livrée dont la date de livraison est passée.
const UNDELIVERED_STATUSES = [1, 2, 4];

function OrderCards({ orders, loading, trans, currency, locale }) {
    const today = todayIso();
    return (
        <IndexCardGrid items={orders} loading={loading} emptyText={trans.no_results ?? 'No results'} renderCard={o => {
            const late = UNDELIVERED_STATUSES.includes(o.statu) ? daysPast(o.validity_date, today) : 0;
            return (
                <IndexCard
                    href={o.url}
                    status={STATUS_CONFIG[o.statu]?.badge}
                    code={o.code}
                    badges={<StatusBadge statu={o.statu} config={STATUS_CONFIG} trans={trans} />}
                    title={o.label}
                    details={[
                        { icon: 'fa-building', text: o.companie?.label ?? '—', title: o.companie?.label },
                        { icon: 'fa-user',     text: o.contact?.name, title: o.contact?.name },
                        { icon: 'fa-hashtag',  text: o.customer_reference, title: trans.customer_reference },
                    ]}
                    meta={[
                        { icon: late ? 'fa-exclamation-triangle' : 'fa-truck', danger: late > 0, title: trans.validity_date,
                          text: <>{o.validity_date ? formatDate(o.validity_date, locale) : '—'}{late > 0 && trans.days_late && <span className="ms-1">({trans.days_late.replace(':days', late)})</span>}</> },
                        { icon: 'fa-list', text: String(o.order_lines_count ?? 0), title: trans.lines },
                    ]}
                    amount={o.total_amount > 0 ? formatCurrency(o.total_amount, currency, locale) : null}
                />
            );
        }} />
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
                const bg  = cfg.badge?.replace(/^(badge|text-bg)-/, 'bg-') ?? 'bg-secondary';
                return (
                    <div key={col.id} className="card flex-shrink-0 mb-3" style={{ minWidth: 220, maxWidth: 260, width: '100%' }}>
                        <div className={`card-header py-1 d-flex justify-content-between align-items-center ${bg}`}>
                            <span className="text-white fw-bold" style={{ fontSize: '0.82rem' }}>
                                {trans[cfg.label] ?? col.id}
                            </span>
                            <span className="badge text-bg-light">{col.items?.length ?? 0}</span>
                        </div>
                        <div className="card-body p-2" style={{ maxHeight: 420, overflowY: 'auto' }}>
                            {(col.items ?? []).map(o => (
                                <div key={o.id} className="card mb-2 shadow-sm">
                                    <div className="card-body p-2">
                                        <a href={o.url} className="fw-bold text-primary d-block mb-1" style={{ fontSize: '0.8rem' }}>
                                            <code>{o.code}</code>
                                        </a>
                                        <div className="text-truncate" style={{ fontSize: '0.75rem' }} title={o.label}>{o.label}</div>
                                        {o.companie && <div className="text-muted" style={{ fontSize: '0.68rem' }}>{o.companie.label}</div>}
                                        <div className="d-flex justify-content-between mt-1">
                                            <span className="text-muted" style={{ fontSize: '0.68rem' }}>{formatDate(o.validity_date, locale)}</span>
                                            <span className="fw-bold" style={{ fontSize: '0.68rem' }}>{formatCurrency(o.total_amount, currency, locale)}</span>
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
        user_id: '', validity_date: '', affair_category_id: '',
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
                    label:  '-',
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
        setForm(f => ({ ...f, code, label: '-' }));
    }, [type, selectData]);

    useEffect(() => {
        if (!form.companies_id) { setAddressOptions([]); setContactOptions([]); return; }
        const addrUrl = endpoints.addresses.replace('__ID__', form.companies_id);
        const contUrl = endpoints.contacts.replace('__ID__', form.companies_id);
        Promise.all([apiFetch(addrUrl), apiFetch(contUrl)])
            .then(([addrData, cont]) => {
                const addr      = addrData?.addresses ?? addrData ?? [];
                const docAddrId = addrData?.default_address_id ? String(addrData.default_address_id) : null;
                const docCtctId = addrData?.default_contact_id ? String(addrData.default_contact_id) : null;
                setAddressOptions(addr);
                setContactOptions(cont ?? []);

                const resolveId = (docId, list) => {
                    if (docId && list.some(x => String(x.id) === docId)) return docId;
                    const byDefault = list.find(x => x.default == 1);
                    if (byDefault) return String(byDefault.id);
                    if (list.length === 1) return String(list[0].id);
                    return '';
                };

                setForm(f => ({
                    ...f,
                    companies_addresses_id: resolveId(docAddrId, addr),
                    companies_contacts_id:  resolveId(docCtctId, cont ?? []),
                }));
            })
            .catch(() => {});
    }, [form.companies_id]);

    const set  = (k, v) => setForm(f => ({ ...f, [k]: v }));
    const submit = async (extra) => {
        setSaving(true); setErrors({});
        try {
            const res  = await apiRequest(endpoints.store, { method: 'POST', body: JSON.stringify({ ...form, type, ...extra }) });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                if (data.redirect) window.location.href = data.redirect;
                return;
            }
            // Plafond d'encours dépassé : passe-droit motivé, puis nouvel envoi.
            const override = data.credit_limit ? await askCreditOverride(data.credit_limit) : null;
            if (override) return submit(override);
            setErrors(data.errors ?? {});
        } catch { setErrors({}); }
        finally { setSaving(false); }
    };
    const save = () => submit({});

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
                            <button onClick={onClose} aria-label="Close" className="btn-close float-end"></button>
                        </div>
                        <div className="modal-body">

                            {/* Type selector */}
                            <div className="mb-3 mb-3">
                                <label className="d-block mb-1 small fw-bold">{trans.order_type ?? 'Order type'}</label>
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
                                    <select className={`form-select form-select-sm ${errors.user_id ? 'is-invalid' : ''}`}
                                        value={form.user_id} onChange={e => set('user_id', e.target.value)}>
                                        <option value="">—</option>
                                        {(selectData?.users ?? []).map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                                    </select>
                                    {errors.user_id && <div className="invalid-feedback">{errors.user_id[0]}</div>}
                                </div>
                                {/* Affair category */}
                                {selectData?.affair_categories?.length > 0 && (
                                    <div className="col-md-4 mb-2">
                                        <AffairCategoryField
                                            categories={selectData.affair_categories}
                                            value={form.affair_category_id}
                                            onChange={v => set('affair_category_id', v)}
                                            trans={trans}
                                            error={errors.affair_category_id?.[0]}
                                            className="form-select form-select-sm"
                                            labelClassName="mb-0 small"
                                        />
                                    </div>
                                )}
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
                                        <select className={`form-select form-select-sm ${errors.companies_id ? 'is-invalid' : ''}`}
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
                                            <select className={`form-select form-select-sm ${errors.companies_contacts_id ? 'is-invalid' : ''}`}
                                                value={form.companies_contacts_id}
                                                onChange={e => set('companies_contacts_id', e.target.value)}
                                                disabled={!form.companies_id}>
                                                <option value="">—</option>
                                                {contactOptions.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                            </select>
                                            {form.companies_id && (
                                                <button className="btn btn-outline-secondary btn-sm" type="button" onClick={() => setShowContactModal(true)}>+</button>
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
                                            <select className={`form-select form-select-sm ${errors.companies_addresses_id ? 'is-invalid' : ''}`}
                                                value={form.companies_addresses_id}
                                                onChange={e => set('companies_addresses_id', e.target.value)}
                                                disabled={!form.companies_id}>
                                                <option value="">—</option>
                                                {addressOptions.map(a => <option key={a.id} value={a.id}>{a.label} — {a.adress}</option>)}
                                            </select>
                                            {form.companies_id && (
                                                <button className="btn btn-outline-secondary btn-sm" type="button" onClick={() => setShowAddressModal(true)}>+</button>
                                            )}
                                        </div>
                                        {errors.companies_addresses_id && <div className="invalid-feedback d-block">{errors.companies_addresses_id[0]}</div>}
                                    </div>
                                )}
                                {/* Payment condition */}
                                {isExternal && (
                                    <div className="col-md-6 mb-2">
                                        <label className="mb-0 small">{trans.payment_condition ?? 'Payment condition'} *</label>
                                        <select className={`form-select form-select-sm ${errors.accounting_payment_conditions_id ? 'is-invalid' : ''}`}
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
                                        <select className={`form-select form-select-sm ${errors.accounting_payment_methods_id ? 'is-invalid' : ''}`}
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
                                        <select className={`form-select form-select-sm ${errors.accounting_deliveries_id ? 'is-invalid' : ''}`}
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
                    show
                    companiesId={form.companies_id}
                    storeUrl={endpoints.storeAddress}
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
                    show
                    companiesId={form.companies_id}
                    storeUrl={endpoints.storeContact}
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

function ListTab({ endpoints, trans, currency, locale, companieId }) {
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
    const [category, setCategory]   = useState('');
    const [categories, setCategories] = useState([]);
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
                if (f.affairCategory !== undefined) setCategory(f.affairCategory);
            }
        } catch {}
    }, []);

    // Persist filters
    useEffect(() => {
        try {
            localStorage.setItem(LS_FILTERS, JSON.stringify({ search, statuses, sortField, sortAsc, viewType, affairCategory: category }));
        } catch {}
    }, [search, statuses, sortField, sortAsc, viewType, category]);

    const fetchOrders = useCallback(async (overrides = {}) => {
        setLoading(true);
        const p  = overrides.page      ?? page;
        const q  = overrides.search    ?? search;
        const s  = overrides.statuses  ?? statuses;
        const sf = overrides.sortField ?? sortField;
        const sa = overrides.sortAsc   ?? sortAsc;

        const params = new URLSearchParams({ search: q, sort: sf, asc: sa ? '1' : '0', page: p });
        s.forEach(id => params.append('statuses[]', id));
        if (companieId) params.set('company_id', companieId);
        if (category) params.set('affair_category_id', category);

        try {
            const data = await apiFetch(`${endpoints.list}?${params}`);
            setOrders(data.data ?? []);
            setMeta(data.meta ?? null);
            setCategories(data.affair_categories ?? []);
        } catch {}
        finally { setLoading(false); }
    }, [endpoints.list, page, search, statuses, sortField, sortAsc, category, companieId]);

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
                    <span className="input-group-text"><i className="fas fa-search" /></span>
                    <input type="text" className="form-control"
                        placeholder={trans.search ?? 'Search…'}
                        value={search}
                        onChange={e => handleSearch(e.target.value)}
                    />
                </div>

                {/* Status filter */}
                <MobileFilters count={statuses.length + (category ? 1 : 0)} trans={trans}>
                    <StatusFilter config={STATUS_CONFIG} selected={statuses} onToggle={handleStatusToggle} trans={trans} />
                    <AffairCategoryFilter categories={categories} value={category}
                        onChange={v => { setCategory(v); setPage(1); }} trans={trans} />
                </MobileFilters>

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
                    <i className="fas fa-plus me-1" />{trans.new_order ?? 'New order'}
                </button>
            </div>

            {viewType === 'table' && (
                <OrdersTable
                    orders={orders}
                    loading={loading}
                    trans={trans}
                    withCategory={categories.length > 0}
                    onSort={handleSort}
                    sortField={sortField}
                    sortAsc={sortAsc}
                    currency={currency}
                    locale={locale}
                />
            )}
            {viewType === 'card' && (
                <OrderCards orders={orders} loading={loading} trans={trans} currency={currency} locale={locale} />
            )}
            {!loading && viewType === 'kanban' && (
                <KanbanBoard statuses={kanbanCols} trans={trans} currency={currency} locale={locale} />
            )}

            {meta && viewType !== 'kanban' && (
                <div className="d-flex justify-content-between align-items-center mt-2">
                    <small className="text-muted">
                        {meta.total} {trans.orders ?? 'orders'} — {trans.page ?? 'Page'} {meta.current_page}/{meta.last_page}
                    </small>
                    <Pagination meta={meta} around={2} jumpButtons ulClassName="pagination pagination-sm mb-0" onPage={handlePage} />
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

export default function OrdersIndex({ kpi, chartData, topCustomers, endpoints, trans, companieId = null }) {
    const [activeTab, setActiveTab] = useIndexTab('orders_index_tab', { forced: companieId ? 'list' : null });
    const currency = trans.currency ?? 'EUR';
    const locale   = trans.locale   ?? 'fr-FR';

    return (
        <div className="card card-outline card-warning mb-3">
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
                        companieId={companieId}
                    />
                )}
            </div>
        </div>
    );
}
