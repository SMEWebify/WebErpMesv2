import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-info',    label: 'in_progress'     },
    2: { badge: 'badge-warning', label: 'ordered'         },
    3: { badge: 'badge-primary', label: 'partly_received' },
    4: { badge: 'badge-success', label: 'received'        },
    5: { badge: 'badge-dark',    label: 'canceled'        },
};

const STATUS_COLORS = { 1: '#17a2b8', 2: '#ffc107', 3: '#007bff', 4: '#28a745', 5: '#343a40' };

const LS_COL_ORDER   = 'purchases_table_col_order';
const LS_HIDDEN_COLS = 'purchases_table_hidden_cols';
const LS_FILTERS     = 'purchases_list_filters';

const DEFAULT_COL_ORDER = ['code', 'label', 'companie', 'contact', 'statu', 'created_at', 'purchase_lines_count', 'total_amount'];
const TEXT_FILTER_COLS  = new Set(['code', 'label', 'companie', 'contact']);
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

function KPICards({ kpi, trans, currency, locale }) {
    return (
        <div className="row">
            <div className="col-lg-4">
                <div className="small-box bg-teal">
                    <div className="inner">
                        <h3>{formatCurrency(kpi.averageAmount ?? 0, currency, locale)}</h3>
                        <p>{trans.average_purchase_price ?? 'Average purchase price'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-chart-bar" /></div>
                </div>
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-danger">
                    <div className="inner">
                        <h3>{formatCurrency(kpi.totalPurchasesAmount ?? 0, currency, locale)}</h3>
                        <p>{trans.total_price ?? 'Total purchases'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-shopping-cart" /></div>
                </div>
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-purple">
                    <div className="inner">
                        <h3>{kpi.totalPurchaseLineCount ?? 0}</h3>
                        <p>{trans.lines_count ?? 'Purchase lines'}</p>
                    </div>
                    <div className="icon"><i className="fas fa-list" /></div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// PieChart — pur React SVG
// ---------------------------------------------------------------------------

function PieChart({ data, trans }) {
    const [hovered, setHovered] = useState(null);

    const items = (data ?? []).filter(d => (d.PurchaseCountRate ?? 0) > 0);
    const total = items.reduce((s, d) => s + Number(d.PurchaseCountRate), 0);

    if (!items.length) return <p className="text-muted text-center small py-3">—</p>;

    const R = 80, r = 44, cx = 110, cy = 110, size = 220;
    let angle = -Math.PI / 2;

    const slices = items.map(item => {
        const value = Number(item.PurchaseCountRate);
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
                    {hov ? hov.label : (trans.purchases ?? 'achats')}
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
// Line Chart — pur React SVG
// ---------------------------------------------------------------------------

const CHART_BLUE   = 'rgba(60,141,188,0.9)';
const CHART_ORANGE = 'rgba(240,173,78,0.85)';

const MONTH_KEYS_ORDERED = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];

function buildMonthlyData(items, key = 'purchaseSum', startMonth = 1) {
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

    const current = buildMonthlyData(data.purchaseMonthlyRecap, 'purchaseSum', startMonth);

    const W = 560, H = 260;
    const PAD = { top: 16, right: 16, bottom: 36, left: 52 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top - PAD.bottom;

    const maxVal  = niceMax(Math.max(...current, 1));
    const Y_TICKS = 4;

    const xPos = i => PAD.left + (i / 11) * plotW;
    const yPos = v => PAD.top + plotH - Math.min(v / maxVal, 1) * plotH;

    const linePath = d => d.map((v, i) => `${i === 0 ? 'M' : 'L'} ${xPos(i).toFixed(1)} ${yPos(v).toFixed(1)}`).join(' ');
    const areaPath = d => `${linePath(d)} L ${xPos(11).toFixed(1)} ${(PAD.top + plotH).toFixed(1)} L ${xPos(0).toFixed(1)} ${(PAD.top + plotH).toFixed(1)} Z`;

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
                <path d={linePath(current)} fill="none" stroke={CHART_BLUE} strokeWidth="2.5" />
                {MONTHS.map((m, i) => (
                    <g key={i}
                        onMouseEnter={() => setHovered(i)}
                        onMouseLeave={() => setHovered(null)}
                        style={{ cursor: 'default' }}>
                        <rect x={xPos(i) - plotW / 24} y={PAD.top} width={plotW / 12} height={plotH + 24} fill="transparent" />
                        <text x={xPos(i)} y={H - 6} textAnchor="middle" fontSize="10" fill="#666">
                            {(m ?? '').substring(0, 3)}
                        </text>
                        <circle cx={xPos(i)} cy={yPos(current[i])} r={hovered === i ? 5 : 3} fill={CHART_BLUE} stroke="#fff" strokeWidth="1.5" style={{ transition: 'r 0.1s' }} />
                        {hovered === i && (
                            <g pointerEvents="none">
                                <line x1={xPos(i)} y1={PAD.top} x2={xPos(i)} y2={PAD.top + plotH} stroke="#ccc" strokeWidth="1" strokeDasharray="4,2" />
                                <rect x={i > 8 ? xPos(i) - 88 : xPos(i) + 8} y={PAD.top + 4} width={80} height={30} rx="4"
                                    fill="white" stroke="#ddd" strokeWidth="1" style={{ filter: 'drop-shadow(0 1px 3px rgba(0,0,0,.12))' }} />
                                <text x={i > 8 ? xPos(i) - 80 : xPos(i) + 16} y={PAD.top + 16} fontSize="10" fontWeight="700" fill="#333">{MONTHS[i]}</text>
                                <circle cx={i > 8 ? xPos(i) - 72 : xPos(i) + 18} cy={PAD.top + 26} r={4} fill={CHART_BLUE} />
                                <text x={i > 8 ? xPos(i) - 64 : xPos(i) + 26} y={PAD.top + 30} fontSize="10" fill="#333">{shortAmount(current[i])}</text>
                            </g>
                        )}
                    </g>
                ))}
            </svg>
            <div className="d-flex justify-content-center mt-1" style={{ gap: '1.5rem' }}>
                <div className="d-flex align-items-center" style={{ gap: '6px', fontSize: '0.78rem', color: '#555' }}>
                    <svg width="22" height="10"><line x1="0" y1="5" x2="22" y2="5" stroke={CHART_BLUE} strokeWidth="2" /></svg>
                    {trans.purchase_forecast ?? 'Achats (année)'}
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Top Suppliers card
// ---------------------------------------------------------------------------

function TopSuppliersCard({ suppliers, trans }) {
    if (!suppliers?.length) return <p className="text-muted small">—</p>;
    return (
        <div>
            {suppliers.map((s, i) => (
                <div key={i} className="d-flex align-items-center mb-2" style={{ gap: '0.5rem' }}>
                    <span style={{ width: 22, textAlign: 'center' }}>
                        {[1, 2, 3, 4, 5].map(star => (
                            <i key={star} className={`fas fa-star`}
                                style={{ fontSize: '0.6rem', color: star <= Math.round(s.avg_rating) ? '#ffc107' : '#dee2e6' }} />
                        ))}
                    </span>
                    <span style={{ fontSize: '0.82rem', flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={s.label}>
                        {s.label}
                    </span>
                    <span className="badge badge-warning" style={{ fontSize: '0.7rem' }}>{s.avg_rating}</span>
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Delivery times table
// ---------------------------------------------------------------------------

function DeliveryTimesCard({ suppliers, trans }) {
    if (!suppliers?.length) return <p className="text-muted small">—</p>;
    return (
        <table className="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{trans.supplier ?? 'Fournisseur'}</th>
                    <th className="text-right">{trans.delivery_time ?? 'Délai (j)'}</th>
                </tr>
            </thead>
            <tbody>
                {suppliers.map((s, i) => (
                    <tr key={i}>
                        <td style={{ fontSize: '0.82rem' }}>{s.supplier_name}</td>
                        <td className="text-right" style={{ fontSize: '0.82rem' }}>{s.avg_reception_delay}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

// ---------------------------------------------------------------------------
// Top Products table
// ---------------------------------------------------------------------------

function TopProductsCard({ products, trans }) {
    if (!products?.length) return <p className="text-muted small">—</p>;
    return (
        <table className="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{trans.product ?? 'Produit'}</th>
                    <th className="text-right">{trans.qty_total ?? 'Qté'}</th>
                </tr>
            </thead>
            <tbody>
                {products.map((p, i) => (
                    <tr key={i}>
                        <td style={{ fontSize: '0.82rem' }}>{p.label}</td>
                        <td className="text-right" style={{ fontSize: '0.82rem' }}>{p.total_quantity}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

// ---------------------------------------------------------------------------
// Composite Indicators table
// ---------------------------------------------------------------------------

function CompositeIndicatorsCard({ indicators, trans }) {
    if (!indicators?.length) return <p className="text-muted small">—</p>;
    return (
        <table className="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{trans.supplier ?? 'Fournisseur'}</th>
                    <th>{trans.composite_score ?? 'Score'}</th>
                    <th>{trans.next_review_at ?? 'Révision'}</th>
                </tr>
            </thead>
            <tbody>
                {indicators.map((s, i) => (
                    <tr key={i}>
                        <td style={{ fontSize: '0.82rem' }}>{s.label}</td>
                        <td style={{ fontSize: '0.82rem' }}>{s.composite_score ?? '—'}</td>
                        <td style={{ fontSize: '0.82rem' }}>{s.next_review_at ?? '—'}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

// ---------------------------------------------------------------------------
// Suppliers to requalify table
// ---------------------------------------------------------------------------

function SuppliersToRequalifyCard({ suppliers, trans }) {
    if (!suppliers?.length) return <p className="text-muted small">—</p>;
    return (
        <table className="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{trans.supplier ?? 'Fournisseur'}</th>
                    <th>{trans.evaluation_status ?? 'Statut'}</th>
                    <th>{trans.next_review_at ?? 'Révision'}</th>
                </tr>
            </thead>
            <tbody>
                {suppliers.map((s, i) => (
                    <tr key={i}>
                        <td style={{ fontSize: '0.82rem' }}>{s.label}</td>
                        <td style={{ fontSize: '0.82rem' }}>{s.evaluation_status ? s.evaluation_status.replace(/_/g, ' ') : '—'}</td>
                        <td style={{ fontSize: '0.82rem' }}>{s.next_review_at ?? '—'}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

// ---------------------------------------------------------------------------
// Dashboard Tab
// ---------------------------------------------------------------------------

function DashboardTab({ kpi, chartData, topSuppliers, fastestSuppliers, slowestSuppliers, compositeIndicators, suppliersToRequalify, topProducts, trans, currency, locale }) {
    return (
        <div>
            <KPICards kpi={kpi} trans={trans} currency={currency} locale={locale} />

            <div className="row">
                {/* Pie chart */}
                <div className="col-md-3">
                    <div className="card card-teal">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-pie mr-1" />
                                {trans.purchases_by_status ?? 'Par statut'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <PieChart data={chartData.purchasesDataRate ?? []} trans={trans} />
                        </div>
                    </div>
                </div>

                {/* Line chart */}
                <div className="col-md-6">
                    <div className="card card-purple">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar mr-1" />
                                {trans.monthly_recap ?? 'Récap mensuel'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <LineChart data={chartData} trans={trans} />
                        </div>
                    </div>
                </div>

                {/* Top rated suppliers */}
                <div className="col-md-3">
                    <div className="card card-primary">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-star mr-1" />
                                {trans.top_rated_supplier ?? 'Top fournisseurs'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <TopSuppliersCard suppliers={topSuppliers} trans={trans} />
                        </div>
                    </div>
                </div>
            </div>

            <div className="row">
                {/* Fastest */}
                <div className="col-md-3">
                    <div className="card card-secondary">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-bolt mr-1" />
                                {trans.suppliers_fastest ?? 'Délais les plus courts'}
                            </h3>
                        </div>
                        <div className="card-body p-2">
                            <DeliveryTimesCard suppliers={fastestSuppliers} trans={trans} />
                        </div>
                    </div>
                </div>

                {/* Slowest */}
                <div className="col-md-3">
                    <div className="card card-dark">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-hourglass-half mr-1" />
                                {trans.suppliers_slowest ?? 'Délais les plus longs'}
                            </h3>
                        </div>
                        <div className="card-body p-2">
                            <DeliveryTimesCard suppliers={slowestSuppliers} trans={trans} />
                        </div>
                    </div>
                </div>

                {/* Top products */}
                <div className="col-md-3">
                    <div className="card card-success">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-box mr-1" />
                                {trans.most_purchased_products ?? 'Produits les + achetés'}
                            </h3>
                        </div>
                        <div className="card-body p-2">
                            <TopProductsCard products={topProducts} trans={trans} />
                        </div>
                    </div>
                </div>

                {/* Composite indicators */}
                <div className="col-md-3">
                    <div className="card card-info">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-line mr-1" />
                                {trans.composite_indicators ?? 'Indicateurs composites'}
                            </h3>
                        </div>
                        <div className="card-body p-2">
                            <CompositeIndicatorsCard indicators={compositeIndicators} trans={trans} />
                        </div>
                    </div>
                </div>
            </div>

            {/* Suppliers to requalify */}
            {suppliersToRequalify?.length > 0 && (
                <div className="row">
                    <div className="col-md-6">
                        <div className="card card-danger">
                            <div className="card-header">
                                <h3 className="card-title">
                                    <i className="fas fa-exclamation-triangle mr-1" />
                                    {trans.suppliers_to_requalify ?? 'Fournisseurs à requalifier'}
                                </h3>
                            </div>
                            <div className="card-body p-2">
                                <SuppliersToRequalifyCard suppliers={suppliersToRequalify} trans={trans} />
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// PurchasesTable
// ---------------------------------------------------------------------------

function colDefs(trans) {
    return {
        code:                  { label: trans.code             ?? 'Code',       sortField: 'code',                  align: '',       bold: true },
        label:                 { label: trans.label            ?? 'Label',      sortField: 'label',                 align: ''                   },
        companie:              { label: trans.company          ?? 'Fournisseur', sortField: 'companie',             align: ''                   },
        contact:               { label: trans.contact          ?? 'Contact',    sortField: 'contact',               align: ''                   },
        statu:                 { label: trans.status           ?? 'Statut',     sortField: 'statu',                 align: 'center'             },
        created_at:            { label: trans.created_at       ?? 'Créé le',    sortField: 'created_at',            align: 'center'             },
        purchase_lines_count:  { label: trans.lines            ?? 'Lignes',     sortField: 'purchase_lines_count',  align: 'center'             },
        total_amount:          { label: trans.total_amount     ?? 'Total',      sortField: 'total_amount',          align: 'right',  bold: true  },
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

function matchesColFilter(p, colId, value) {
    if (DATE_RANGE_COLS.has(colId)) {
        const { from, to } = value ?? {};
        const iso = dmyToISO(p.created_at ?? '');
        if (!iso) return true;
        if (from && iso < from) return false;
        if (to   && iso > to)   return false;
        return true;
    }
    const v = (value ?? '').toLowerCase().trim();
    if (!v) return true;
    switch (colId) {
        case 'code':     return (p.code ?? '').toLowerCase().includes(v);
        case 'label':    return (p.label ?? '').toLowerCase().includes(v);
        case 'companie': return (p.companie?.label ?? '').toLowerCase().includes(v);
        case 'contact':  return (p.contact?.name ?? '').toLowerCase().includes(v);
        default:         return true;
    }
}

function PurchasesTable({ purchases, trans, onSort, sortField, sortAsc, currency, locale }) {
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

    const filtered  = purchases.filter(p => visibleCols.every(colId => matchesColFilter(p, colId, colFilters[colId] ?? '')));
    const pageTotal = filtered.reduce((s, p) => s + (p.total_amount ?? 0), 0);
    const totalIdx  = visibleCols.indexOf('total_amount');
    const inputStyle = { fontSize: '0.72rem', height: '24px', padding: '1px 4px' };

    const cellRender = (p, colId) => {
        switch (colId) {
            case 'code':                 return <code>{p.code}</code>;
            case 'label':                return p.label;
            case 'companie':             return p.companie?.label ?? '—';
            case 'contact':              return p.contact?.name ?? '—';
            case 'statu':                return <StatusBadge statu={p.statu} trans={trans} />;
            case 'created_at':           return p.created_at;
            case 'purchase_lines_count': return <span className="badge badge-secondary">{p.purchase_lines_count}</span>;
            case 'total_amount':         return p.total_amount > 0
                ? formatCurrency(p.total_amount, currency, locale)
                : <span className="text-muted">—</span>;
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
                                        style={{
                                            cursor: 'pointer', whiteSpace: 'nowrap', userSelect: 'none',
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
                            <tr><td colSpan={visibleCols.length + 1} className="text-center text-muted py-3">{trans.no_results ?? 'Aucun résultat'}</td></tr>
                        )}
                        {filtered.map(p => (
                            <tr key={p.id}>
                                {visibleCols.map(colId => {
                                    const col      = COLS[colId];
                                    const alignCls = col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '';
                                    return (
                                        <td key={colId}
                                            className={`${alignCls}${col.bold ? ' font-weight-bold' : ''}`}
                                            style={(col.align === 'right' || col.bold) ? { whiteSpace: 'nowrap' } : {}}
                                        >
                                            {cellRender(p, colId)}
                                        </td>
                                    );
                                })}
                                <td>
                                    <a href={p.url} className="btn btn-xs btn-info">
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
        { k: 'ordre',   label: trans.ordre      ?? 'Ordre',       type: 'number' },
        { k: 'label',   label: trans.adress_label ?? 'Label'                      },
        { k: 'adress',  label: trans.adress      ?? 'Adresse'                     },
        { k: 'zipcode', label: trans.postal_code ?? 'Code postal'                 },
        { k: 'city',    label: trans.city        ?? 'Ville'                       },
        { k: 'country', label: trans.country     ?? 'Pays'                        },
        { k: 'number',  label: trans.phone       ?? 'Téléphone'                   },
        { k: 'mail',    label: trans.email       ?? 'Email',       type: 'email'  },
    ];

    return (
        <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1060 }}>
            <div className="modal-dialog modal-lg">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title">{trans.new_address ?? 'Nouvelle adresse'}</h5>
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
                        <button className="btn btn-secondary btn-sm" onClick={onClose}>{trans.cancel ?? 'Annuler'}</button>
                        <button className="btn btn-primary btn-sm" onClick={save} disabled={saving}>
                            {saving ? (trans.saving ?? 'Enregistrement…') : (trans.save ?? 'Enregistrer')}
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
        { k: 'ordre',      label: trans.ordre      ?? 'Ordre',      type: 'number' },
        { k: 'civility',   label: trans.civility   ?? 'Civilité'                   },
        { k: 'first_name', label: trans.first_name ?? 'Prénom'                     },
        { k: 'name',       label: trans.name       ?? 'Nom'                        },
        { k: 'function',   label: trans.function   ?? 'Fonction'                   },
        { k: 'number',     label: trans.phone      ?? 'Téléphone'                  },
        { k: 'mobile',     label: trans.mobile     ?? 'Mobile'                     },
        { k: 'mail',       label: trans.email      ?? 'Email',      type: 'email'  },
    ];

    return (
        <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1060 }}>
            <div className="modal-dialog modal-lg">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title">{trans.new_contact ?? 'Nouveau contact'}</h5>
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
                        <button className="btn btn-secondary btn-sm" onClick={onClose}>{trans.cancel ?? 'Annuler'}</button>
                        <button className="btn btn-primary btn-sm" onClick={save} disabled={saving}>
                            {saving ? (trans.saving ?? 'Enregistrement…') : (trans.save ?? 'Enregistrer')}
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
    const [selectData, setSelectData]   = useState(null);
    const [loadingData, setLoadingData] = useState(true);
    const [form, setForm] = useState({
        code: '', label: '', comment: '',
        companies_id: '', companies_contacts_id: '', companies_addresses_id: '',
    });
    const [errors, setErrors]               = useState({});
    const [saving, setSaving]               = useState(false);
    const [addressOptions, setAddressOptions] = useState([]);
    const [contactOptions, setContactOptions] = useState([]);
    const [showAddressModal, setShowAddressModal] = useState(false);
    const [showContactModal, setShowContactModal] = useState(false);

    useEffect(() => {
        apiFetch(endpoints.selectData)
            .then(data => {
                setSelectData(data);
                setForm(f => ({ ...f, code: data.next_code ?? '', label: data.next_code ?? '' }));
            })
            .catch(() => {})
            .finally(() => setLoadingData(false));
    }, []);

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
            const data = await apiFetch(endpoints.store, { method: 'POST', body: JSON.stringify(form) });
            if (data.redirect) window.location.href = data.redirect;
        } catch (e) { setErrors(e.errors ?? {}); }
        finally { setSaving(false); }
    };

    if (loadingData) return (
        <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1050 }}>
            <div className="modal-dialog"><div className="modal-content"><div className="modal-body text-center py-4"><i className="fas fa-spinner fa-spin fa-2x"></i></div></div></div>
        </div>
    );

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1040 }} onClick={onClose}></div>
            <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1050 }}>
                <div className="modal-dialog modal-lg">
                    <div className="modal-content">
                        <div className="modal-header bg-success">
                            <h5 className="modal-title">{trans.new_purchase ?? 'Nouvel achat'}</h5>
                            <button className="close" onClick={onClose}><span>×</span></button>
                        </div>
                        <div className="modal-body">
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
                                    <label className="mb-0 small">{trans.label ?? 'Libellé'} *</label>
                                    <input className={`form-control form-control-sm ${errors.label ? 'is-invalid' : ''}`}
                                        value={form.label} onChange={e => set('label', e.target.value)} />
                                    {errors.label && <div className="invalid-feedback">{errors.label[0]}</div>}
                                </div>
                                {/* Supplier */}
                                <div className="col-md-6 mb-2">
                                    <label className="mb-0 small">{trans.company ?? 'Fournisseur'} *</label>
                                    <select className={`form-control form-control-sm ${errors.companies_id ? 'is-invalid' : ''}`}
                                        value={form.companies_id} onChange={e => set('companies_id', e.target.value)}>
                                        <option value="">—</option>
                                        {(selectData?.suppliers ?? []).map(c => (
                                            <option key={c.id} value={c.id}>{c.label ?? c.code}</option>
                                        ))}
                                    </select>
                                    {errors.companies_id && <div className="invalid-feedback">{errors.companies_id[0]}</div>}
                                </div>
                                {/* Contact */}
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
                                {/* Address */}
                                <div className="col-md-6 mb-2">
                                    <label className="mb-0 small">{trans.address ?? 'Adresse'} *</label>
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
                                {/* Comment */}
                                <div className="col-12 mb-2">
                                    <label className="mb-0 small">{trans.comment ?? 'Commentaire'}</label>
                                    <textarea className="form-control form-control-sm" rows={2}
                                        value={form.comment} onChange={e => set('comment', e.target.value)} />
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button className="btn btn-secondary btn-sm" onClick={onClose}>{trans.cancel ?? 'Annuler'}</button>
                            <button className="btn btn-success btn-sm" onClick={save} disabled={saving}>
                                {saving ? (trans.saving ?? 'Enregistrement…') : (trans.save ?? 'Enregistrer')}
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

function ListTab({ endpoints, trans, currency, locale, companieId }) {
    const [purchases, setPurchases] = useState([]);
    const [meta, setMeta]           = useState(null);
    const [loading, setLoading]     = useState(false);
    const [search, setSearch]       = useState('');
    const [statuses, setStatuses]   = useState([1, 2]);
    const [sortField, setSortField] = useState('created_at');
    const [sortAsc, setSortAsc]     = useState(false);
    const [page, setPage]           = useState(1);
    const [showModal, setShowModal] = useState(false);
    const debounceRef = useRef(null);

    // Restore filters
    useEffect(() => {
        try {
            const saved = localStorage.getItem(LS_FILTERS);
            if (saved) {
                const f = JSON.parse(saved);
                if (f.search    !== undefined) setSearch(f.search);
                if (f.statuses  !== undefined) setStatuses(f.statuses);
                if (f.sortField !== undefined) setSortField(f.sortField);
                if (f.sortAsc   !== undefined) setSortAsc(f.sortAsc);
            }
        } catch {}
    }, []);

    // Persist filters
    useEffect(() => {
        localStorage.setItem(LS_FILTERS, JSON.stringify({ search, statuses, sortField, sortAsc }));
    }, [search, statuses, sortField, sortAsc]);

    const fetchPurchases = useCallback(async (overrides = {}) => {
        setLoading(true);
        const p  = overrides.page      ?? page;
        const q  = overrides.search    ?? search;
        const s  = overrides.statuses  ?? statuses;
        const sf = overrides.sortField ?? sortField;
        const sa = overrides.sortAsc   ?? sortAsc;

        const params = new URLSearchParams({ search: q, sort: sf, asc: sa ? '1' : '0', page: p });
        s.forEach(id => params.append('statuses[]', id));
        if (companieId) params.set('company_id', companieId);

        try {
            const data = await apiFetch(`${endpoints.list}?${params}`);
            setPurchases(data.data ?? []);
            setMeta(data.meta ?? null);
        } catch {}
        finally { setLoading(false); }
    }, [endpoints.list, page, search, statuses, sortField, sortAsc, companieId]);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => fetchPurchases(), 200);
        return () => clearTimeout(debounceRef.current);
    }, [fetchPurchases]);

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

    return (
        <div>
            <div className="d-flex flex-wrap align-items-center mb-2" style={{ gap: '0.5rem' }}>
                {/* Search */}
                <div className="input-group input-group-sm flex-shrink-0" style={{ width: 200 }}>
                    <div className="input-group-prepend">
                        <span className="input-group-text"><i className="fas fa-search" /></span>
                    </div>
                    <input type="text" className="form-control"
                        placeholder={trans.search ?? 'Rechercher…'}
                        value={search}
                        onChange={e => handleSearch(e.target.value)}
                    />
                </div>

                {/* Status filter */}
                <StatusFilter active={statuses} onToggle={handleStatusToggle} trans={trans} />

                <div className="flex-grow-1" />

                {/* New purchase */}
                <button className="btn btn-sm btn-success flex-shrink-0" onClick={() => setShowModal(true)}>
                    <i className="fas fa-plus mr-1" />{trans.new_purchase ?? 'Nouvel achat'}
                </button>
            </div>

            {loading && <div className="text-center py-3"><i className="fas fa-spinner fa-spin fa-lg"></i></div>}

            {!loading && (
                <PurchasesTable
                    purchases={purchases}
                    trans={trans}
                    onSort={handleSort}
                    sortField={sortField}
                    sortAsc={sortAsc}
                    currency={currency}
                    locale={locale}
                />
            )}

            {meta && (
                <div className="d-flex justify-content-between align-items-center mt-2">
                    <small className="text-muted">
                        {meta.total} {trans.purchases ?? 'achats'} — {trans.page ?? 'Page'} {meta.current_page}/{meta.last_page}
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

export default function PurchasesIndex({
    kpi, chartData, topSuppliers, fastestSuppliers, slowestSuppliers,
    compositeIndicators, suppliersToRequalify, topProducts,
    endpoints, trans, companieId = null,
}) {
    const [activeTab, setActiveTab] = useState(companieId ? 'list' : 'dashboard');
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
                            {trans.purchases_list ?? 'Liste achats'}
                        </a>
                    </li>
                </ul>
            </div>
            <div className="card-body">
                {activeTab === 'dashboard' && (
                    <DashboardTab
                        kpi={kpi}
                        chartData={chartData}
                        topSuppliers={topSuppliers}
                        fastestSuppliers={fastestSuppliers}
                        slowestSuppliers={slowestSuppliers}
                        compositeIndicators={compositeIndicators}
                        suppliersToRequalify={suppliersToRequalify}
                        topProducts={topProducts}
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
