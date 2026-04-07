import React, { useState } from 'react';

// ---------------------------------------------------------------------------
// Small Box (AdminLTE style)
// ---------------------------------------------------------------------------

function SmallBox({ value, label, icon, theme }) {
    return (
        <div className="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div className={`small-box bg-${theme}`}>
                <div className="inner">
                    <h3 style={{ fontSize: '1.3rem' }}>{value}</h3>
                    <p>{label}</p>
                </div>
                <div className="icon">
                    <i className={`fas ${icon}`} />
                </div>
            </div>
        </div>
    );
}

function InvoicesCard({ paid, unpaid, trans }) {
    return (
        <div className="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div className="card card-outline card-primary" style={{ minHeight: '88px' }}>
                <div className="card-body d-flex flex-column justify-content-center">
                    <p className="card-text mb-1">{trans.bills_paid} : <strong>{paid}</strong></p>
                    <p className="card-text mb-0">{trans.bills_unpaid} : <strong>{unpaid}</strong></p>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Quotes Pie Chart (SVG)
// ---------------------------------------------------------------------------

const QUOTE_STATUS_CONFIG = {
    1: { color: '#17a2b8', key: 'open' },
    2: { color: '#ffc107', key: 'send' },
    3: { color: '#28a745', key: 'win' },
    4: { color: '#dc3545', key: 'lost' },
    5: { color: '#6c757d', key: 'closed' },
    6: { color: '#007bff', key: 'obsolete' },
};

function QuotesPieChart({ data, trans }) {
    const [hovered, setHovered] = useState(null);

    const items = (data ?? [])
        .map(row => ({
            label: trans[QUOTE_STATUS_CONFIG[row.statu]?.key] ?? `Status ${row.statu}`,
            color: QUOTE_STATUS_CONFIG[row.statu]?.color ?? '#adb5bd',
            value: parseInt(row.QuoteCountRate, 10) || 0,
        }))
        .filter(i => i.value > 0);

    const total = items.reduce((s, i) => s + i.value, 0);
    if (!total) return <p className="text-muted text-center small py-3">—</p>;

    const R = 80, r = 44, cx = 110, cy = 110, size = 220;
    let angle = -Math.PI / 2;

    const slices = items.map((item) => {
        const sweep = (item.value / total) * 2 * Math.PI;
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
                    {hov ? hov.label : trans.total ?? ''}
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
// Orders Line Chart (SVG)
// ---------------------------------------------------------------------------

const MONTHS_KEYS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

function niceMax(value) {
    if (value <= 0) return 5;
    const exp = Math.pow(10, Math.floor(Math.log10(value)));
    return Math.ceil(value / exp) * exp;
}

function shortNum(v) {
    if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + 'M';
    if (v >= 1_000)     return Math.round(v / 1_000) + 'k';
    return Math.round(v).toString();
}

function OrdersLineChart({ data, trans }) {
    const [hovered, setHovered] = useState(null);

    const byMonth = {};
    (data ?? []).forEach(row => { byMonth[row.month] = parseFloat(row.orderSum) || 0; });
    const values = MONTHS_KEYS.map((_, i) => byMonth[i + 1] ?? 0);

    const maxVal = niceMax(Math.max(...values, 1));
    const currentMonth = new Date().getMonth();

    const W = 560, H = 240;
    const PAD = { top: 16, right: 16, bottom: 40, left: 52 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top - PAD.bottom;
    const xPos = (i) => PAD.left + (i / 11) * plotW;
    const yPos = (v) => PAD.top + plotH - (v / maxVal) * plotH;
    const Y_TICKS = 4;

    const linePoints = values.map((v, i) => `${xPos(i)},${yPos(v)}`).join(' ');
    const areaPath =
        `M ${xPos(0)},${yPos(values[0])} ` +
        values.slice(1).map((v, i) => `L ${xPos(i + 1)},${yPos(v)}`).join(' ') +
        ` L ${xPos(11)},${PAD.top + plotH} L ${xPos(0)},${PAD.top + plotH} Z`;

    return (
        <svg viewBox={`0 0 ${W} ${H}`} style={{ width: '100%', display: 'block' }}>
            {/* Y grid */}
            {Array.from({ length: Y_TICKS + 1 }, (_, t) => {
                const v  = (maxVal / Y_TICKS) * t;
                const yp = yPos(v);
                return (
                    <g key={t}>
                        <line x1={PAD.left} y1={yp} x2={W - PAD.right} y2={yp} stroke="#e9ecef" strokeWidth="1" />
                        <text x={PAD.left - 4} y={yp + 4} textAnchor="end" fontSize="8" fill="#6c757d">{shortNum(v)}</text>
                    </g>
                );
            })}

            {/* Area fill */}
            <path d={areaPath} fill="rgba(60,141,188,0.15)" />

            {/* Line */}
            <polyline points={linePoints} fill="none" stroke="rgba(60,141,188,0.9)" strokeWidth="2" strokeLinejoin="round" />

            {/* Points */}
            {values.map((v, i) => (
                <circle
                    key={i}
                    cx={xPos(i)} cy={yPos(v)}
                    r={hovered === i ? 5 : 3}
                    fill={i === currentMonth ? '#007bff' : 'rgba(60,141,188,1)'}
                    stroke="#fff" strokeWidth="1.5"
                    style={{ cursor: 'default', transition: 'r 0.1s' }}
                    onMouseEnter={() => setHovered(i)}
                    onMouseLeave={() => setHovered(null)}
                />
            ))}

            {/* Tooltip */}
            {hovered !== null && values[hovered] > 0 && (() => {
                const rawX = xPos(hovered);
                const rawY = yPos(values[hovered]);
                const bw = 72, bh = 16;
                const bx = Math.min(Math.max(rawX - bw / 2, PAD.left), W - PAD.right - bw);
                const locale = trans.locale || 'fr-FR';
                const currency = trans.currency || 'EUR';
                const formatted = new Intl.NumberFormat(locale, { style: 'currency', currency, maximumFractionDigits: 0 }).format(values[hovered]);
                return (
                    <g>
                        <rect x={bx} y={rawY - bh - 6} width={bw} height={bh} rx="3" fill="#343a40" opacity="0.85" />
                        <text x={bx + bw / 2} y={rawY - 10} textAnchor="middle" fontSize="8" fill="#fff">{formatted}</text>
                    </g>
                );
            })()}

            {/* X labels */}
            {MONTHS_KEYS.map((k, i) => (
                <text
                    key={k}
                    x={xPos(i)} y={PAD.top + plotH + 16}
                    textAnchor="middle" fontSize="8"
                    fill={i === currentMonth ? '#007bff' : '#6c757d'}
                    fontWeight={i === currentMonth ? '700' : '400'}
                >
                    {trans[k] ?? k}
                </text>
            ))}
        </svg>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function CompanyDashboard({ kpi, charts, trans }) {
    return (
        <div>
            {/* KPIs */}
            <div className="row">
                <SmallBox value={kpi.orderAverage}           label={trans.order_average}            icon="fa-shopping-cart" theme="orange" />
                <SmallBox value={kpi.remainingInvoiceOrder}  label={trans.remaining_invoice}        icon="fa-file-invoice"  theme="info" />
                <SmallBox value={kpi.pendingOrdersCount}     label={trans.pending_orders}           icon="fa-box-open"      theme="warning" />
                <SmallBox value={kpi.customerProcessingCost} label={trans.customer_processing_cost} icon="fa-cogs"          theme="teal" />
                <SmallBox value={`${kpi.serviceRate ?? 0}%`} label={trans.service_rate}             icon="fa-chart-line"    theme="primary" />
                <InvoicesCard paid={kpi.paidInvoices} unpaid={kpi.unpaidInvoices} trans={trans} />
                <SmallBox value="Since" label={kpi.since}                                           icon="fa-calendar-alt"  theme="warning" />
            </div>

            {/* Charts */}
            <div className="row mt-2">
                <div className="col-lg-3 col-md-12 mb-3">
                    <div className="card card-outline card-teal">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar text-teal mr-1" />
                                {trans.quote_transformation}
                            </h3>
                        </div>
                        <div className="card-body">
                            <QuotesPieChart data={charts.quotesDataRate} trans={trans} />
                        </div>
                    </div>
                </div>

                <div className="col-lg-9 col-md-12 mb-3">
                    <div className="card card-outline card-purple">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar text-purple mr-1" />
                                {trans.monthly_recap}
                            </h3>
                            <div className="card-tools">
                                <span className="badge badge-secondary">{trans.sales_period}</span>
                            </div>
                        </div>
                        <div className="card-body">
                            <OrdersLineChart data={charts.orderMonthlyRecap} trans={trans} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
