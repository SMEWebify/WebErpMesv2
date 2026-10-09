import React, { useState, useEffect, useRef } from 'react';
import { DataTable, Pagination, StatusBadge, StatusFilter, MobileFilters, useIndexTab } from './table';
import { apiFetch } from '../lib/http';
import { formatDate, formatCurrency } from '../utils';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-secondary',  label: 'draft' },
    2: { badge: 'badge-primary',    label: 'send' },
    3: { badge: 'badge-warning',    label: 'pending' },
    4: { badge: 'badge-danger',     label: 'unpaid' },
    5: { badge: 'badge-success',    label: 'paid' },
};

const STATUS_COLORS = {
    1: '#17a2b8',
    2: '#007bff',
    3: '#ffc107',
    4: '#dc3545',
    5: '#28a745',
};

const ALL_STATUSES = [1, 2, 3, 4, 5];

// ---------------------------------------------------------------------------
// KPI Cards — 3 small-box AdminLTE avec mini top-clients dessous
// ---------------------------------------------------------------------------

const RANK_STYLES = [
    { bg: '#ffc107', color: '#000' },
    { bg: '#adb5bd', color: '#fff' },
    { bg: '#cd7f32', color: '#fff' },
];

function ClientMini({ client, rank, trans }) {
    if (!client) return null;
    const { bg, color } = RANK_STYLES[rank];
    const name = client.companie?.label ?? '—';
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
            <span className="badge badge-secondary" style={{ fontSize: '0.72rem' }}>
                {formatCurrency(client.total_amount, trans.currency, trans.locale)}
            </span>
        </div>
    );
}

function KPICards({ kpi, topClients, trans }) {
    const clients = topClients ?? [];
    return (
        <div className="row">
            <div className="col-lg-4">
                <div className="small-box bg-success">
                    <div className="inner">
                        <h3 style={{ fontSize: '1.6rem' }}>{kpi.totalAmountFormatted}</h3>
                        <p>{trans.total_invoiced}</p>
                    </div>
                    <div className="icon"><i className="fas fa-file-invoice-dollar" /></div>
                </div>
                <ClientMini client={clients[0]} rank={0} trans={trans} />
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-info">
                    <div className="inner">
                        <h3>{kpi.paymentRate} <sup style={{ fontSize: '1.2rem' }}>%</sup></h3>
                        <p>{trans.payment_rate}</p>
                    </div>
                    <div className="icon"><i className="fas fa-check-circle" /></div>
                </div>
                <ClientMini client={clients[1]} rank={1} trans={trans} />
            </div>
            <div className="col-lg-4">
                <div className="small-box bg-danger">
                    <div className="inner">
                        <h3>{kpi.unpaidCount}</h3>
                        <p>{trans.unpaid_invoices} / {kpi.latePaymentRate}% {trans.late}</p>
                    </div>
                    <div className="icon"><i className="fas fa-exclamation-triangle" /></div>
                </div>
                <ClientMini client={clients[2]} rank={2} trans={trans} />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Pie Chart — Donut SVG pur React
// ---------------------------------------------------------------------------

function PieChart({ chartData, trans }) {
    const [hovered, setHovered] = useState(null);

    const statusTrans = {
        1: trans.draft ?? 'Brouillon',
        2: trans.send,
        3: trans.pending,
        4: trans.unpaid,
        5: trans.paid,
    };

    const items = (chartData.invoicesDataRate ?? []).filter(i => i.InvoiceCountRate > 0);
    const total = items.reduce((s, i) => s + Number(i.InvoiceCountRate), 0);

    if (!items.length) return <p className="text-muted text-center small py-3">—</p>;

    const R = 80, r = 44, cx = 110, cy = 110, size = 220;
    let angle = -Math.PI / 2;

    const slices = items.map((item) => {
        const value  = Number(item.InvoiceCountRate);
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
        const large = sweep > Math.PI ? 1 : 0;
        const midAngle = angle - sweep / 2;
        return {
            statu: item.statu,
            value,
            color: STATUS_COLORS[item.statu] ?? '#aaa',
            label: statusTrans[item.statu] ?? item.statu,
            path:  `M ${x1} ${y1} A ${R} ${R} 0 ${large} 1 ${x2} ${y2} L ${ix1} ${iy1} A ${r} ${r} 0 ${large} 0 ${ix2} ${iy2} Z`,
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
                    {hoveredItem ? hoveredItem.label : trans.invoices_label}
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
// Revenue Line Chart — SVG pur React, année courante vs N-1
// ---------------------------------------------------------------------------

const CHART_BLUE   = 'rgba(40,167,69,0.9)';
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

function buildMonthlyData(items, startMonth = 1) {
    return Array.from({ length: 12 }, (_, i) => {
        const calMonth = ((startMonth - 1 + i) % 12) + 1;
        const found = (items ?? []).find(d => d.month === calMonth);
        return found ? parseFloat(found.orderSum) : 0;
    });
}

function LineChart({ chartData, trans }) {
    const [hovered, setHovered] = useState(null);
    const startMonth = chartData.fiscalYearStartMonth ?? 1;

    const MONTHS = Array.from({ length: 12 }, (_, i) => {
        const key = MONTH_KEYS_ORDERED[((startMonth - 1) + i) % 12];
        return trans[key] ?? key;
    });

    const current  = buildMonthlyData(chartData.invoiceMonthlyRecap, startMonth);
    const previous = buildMonthlyData(chartData.invoiceMonthlyRecapPreviousYear, startMonth);

    const W = 560, H = 260;
    const PAD = { top: 16, right: 16, bottom: 36, left: 52 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top - PAD.bottom;

    const maxVal  = niceMax(Math.max(...current, ...previous, 1));
    const Y_TICKS = 4;

    const xPos = (i) => PAD.left + (i / 11) * plotW;
    const yPos = (v) => PAD.top + plotH - Math.min(v / maxVal, 1) * plotH;

    const linePath = (data) =>
        data.map((v, i) => `${i === 0 ? 'M' : 'L'} ${xPos(i).toFixed(1)} ${yPos(v).toFixed(1)}`).join(' ');

    const areaPath = (data) =>
        `${linePath(data)} L ${xPos(11).toFixed(1)} ${(PAD.top + plotH).toFixed(1)} L ${xPos(0).toFixed(1)} ${(PAD.top + plotH).toFixed(1)} Z`;

    const renderTooltip = () => {
        if (hovered === null) return null;
        const cv   = current[hovered];
        const pv   = previous[hovered];
        const tipW = 92, tipH = 52;
        const tx   = hovered > 8 ? xPos(hovered) - tipW - 8 : xPos(hovered) + 10;
        const ty   = PAD.top + 4;
        return (
            <g pointerEvents="none">
                <line x1={xPos(hovered)} y1={PAD.top} x2={xPos(hovered)} y2={PAD.top + plotH}
                    stroke="#ccc" strokeWidth="1" strokeDasharray="4,2" />
                <rect x={tx} y={ty} width={tipW} height={tipH} rx="4"
                    fill="white" stroke="#ddd" strokeWidth="1"
                    style={{ filter: 'drop-shadow(0 1px 3px rgba(0,0,0,.12))' }} />
                <text x={tx + 8} y={ty + 14} fontSize="10" fontWeight="700" fill="#333">
                    {MONTHS[hovered]}
                </text>
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

                <path d={areaPath(current)} fill="rgba(40,167,69,0.08)" />
                <path d={linePath(previous)} fill="none" stroke={CHART_ORANGE} strokeWidth="2" strokeDasharray="6,3" />
                <path d={linePath(current)}  fill="none" stroke={CHART_BLUE}   strokeWidth="2.5" />

                {MONTHS.map((m, i) => (
                    <g key={i}
                        onMouseEnter={() => setHovered(i)}
                        onMouseLeave={() => setHovered(null)}
                        style={{ cursor: 'default' }}>
                        <rect x={xPos(i) - plotW / 24} y={PAD.top}
                            width={plotW / 12} height={plotH + 24} fill="transparent" />
                        <text x={xPos(i)} y={H - 6} textAnchor="middle" fontSize="10" fill="#666">
                            {m.substring(0, 3)}
                        </text>
                        <circle cx={xPos(i)} cy={yPos(current[i])} r={hovered === i ? 5 : 3}
                            fill={CHART_BLUE} stroke="#fff" strokeWidth="1.5"
                            style={{ transition: 'r 0.1s' }} />
                        <circle cx={xPos(i)} cy={yPos(previous[i])} r={hovered === i ? 5 : 3}
                            fill={CHART_ORANGE} stroke="#fff" strokeWidth="1.5"
                            style={{ transition: 'r 0.1s' }} />
                    </g>
                ))}

                {renderTooltip()}
            </svg>

            <div className="d-flex justify-content-center mt-1" style={{ gap: '1.5rem' }}>
                {[
                    { color: CHART_BLUE,   dash: false, label: trans.invoice_forecast },
                    { color: CHART_ORANGE, dash: true,  label: trans.invoice_last_year },
                ].map(({ color, dash, label }) => (
                    <div key={label} className="d-flex align-items-center" style={{ gap: '6px', fontSize: '0.78rem', color: '#555' }}>
                        <svg width="22" height="10">
                            <line x1="0" y1="5" x2="22" y2="5"
                                stroke={color} strokeWidth="2"
                                strokeDasharray={dash ? '5,3' : undefined} />
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

function DashboardTab({ kpi, chartData, topClients, trans }) {
    return (
        <div>
            <KPICards kpi={kpi} topClients={topClients} trans={trans} />
            <div className="row">
                <div className="col-md-3">
                    <div className="card card-teal">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-chart-pie mr-1" />{trans.statistiques}</h3>
                        </div>
                        <div className="card-body">
                            <PieChart chartData={chartData} trans={trans} />
                        </div>
                    </div>
                </div>
                <div className="col-md-9">
                    <div className="card card-success">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-chart-line mr-1" />{trans.monthly_recap}</h3>
                        </div>
                        <div className="card-body">
                            <LineChart chartData={chartData} trans={trans} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Invoices Table — colonnes déclarées, rendu par le DataTable partagé
// ---------------------------------------------------------------------------

const LS_COL_ORDER    = 'invoices_table_col_order';
const LS_HIDDEN_COLS  = 'invoices_table_hidden_cols';

// La colonne de statut PDP n'a jamais été affichée : l'ancien tableau figeait l'ordre des
// colonnes avant de recevoir pdp_enabled. Elle reste masquée tant que son libellé (« Qonto »)
// et ses statuts, propres à Qonto, ne sont pas adaptés aux autres plateformes (SUPER PDP).
const PDP_COLUMN_VISIBLE = false;

const QONTO_LIFECYCLE_CONFIG = {
    pending:      { badge: 'badge-secondary', label: 'Non déposée' },
    submitted:    { badge: 'badge-info',      label: 'Émise (à payer)' },
    acknowledged: { badge: 'badge-primary',   label: 'Accusé reçu' },
    rejected:     { badge: 'badge-danger',    label: 'Rejetée' },
    refused:      { badge: 'badge-danger',    label: 'Refusée' },
    accepted:     { badge: 'badge-success',   label: 'Acceptée' },
    paid:         { badge: 'badge-success',   label: 'Payée' },
    canceled:     { badge: 'badge-warning',   label: 'Annulée chez Qonto' },
};

function QontoStatusBadge({ status }) {
    if (!status) return <span className="text-muted small">—</span>;
    const cfg = QONTO_LIFECYCLE_CONFIG[status] ?? { badge: 'badge-secondary', label: status };
    return <span className={`badge ${cfg.badge}`} title="Statut de la facture chez Qonto">{cfg.label}</span>;
}

function invoiceColumns(trans, pdpEnabled) {
    const columns = [
        { key: 'code',       label: trans.code,       sortable: true,
          render: inv => <code>{inv.code}</code>, filter: 'text', mobile: 'title', mobileRender: inv => inv.code },
        { key: 'label',      label: trans.label,      sortable: true,
          filter: 'text', mobile: 'subtitle' },
        { key: 'client',     label: trans.client,     sortable: 'companie',
          render: inv => inv.companie?.label ?? '—', filterValue: inv => inv.companie?.label, filter: 'text', mobile: 'subtitle', mobileOrder: 1 },
        { key: 'contact',    label: trans.contact,    sortable: true,
          render: inv => inv.contact?.name ?? '—', filterValue: inv => inv.contact?.name, filter: 'text' },
        { key: 'due_date',   label: trans.due_date,   sortable: true,
          render: inv => formatDate(inv.due_date, trans.locale), filter: 'date' },
        { key: 'status',     label: trans.status,     sortable: 'statu',
          render: inv => <StatusBadge statu={inv.statu} config={STATUS_CONFIG} trans={trans} fallback="value" />, mobile: 'badge' },
        { key: 'lines',      label: trans.lines,      sortable: 'invoice_lines_count', align: 'center',
          render: inv => <span className="badge badge-secondary">{inv.invoice_lines_count}</span> },
        { key: 'created_at', label: trans.created_at, sortable: true,
          filter: 'date' },
        { key: 'total',      label: trans.total,      sortable: 'total_amount', align: 'right', bold: true,
          render: inv => (inv.total_amount > 0
              ? formatCurrency(inv.total_amount, trans.currency, trans.locale)
              : <span className="text-muted">—</span>),
          total: { value: inv => inv.total_amount ?? 0, format: sum => formatCurrency(sum, trans.currency, trans.locale) },
          mobile: 'amount', mobileRender: inv => (inv.total_amount > 0 ? formatCurrency(inv.total_amount, trans.currency, trans.locale) : null) },
    ];
    if (pdpEnabled && PDP_COLUMN_VISIBLE) {
        columns.push({ key: 'qonto', label: 'Qonto', align: 'center', render: inv => <QontoStatusBadge status={inv.pdp_status} /> });
    }
    return columns;
}

function InvoiceActions({ inv, trans }) {
    return (
        <>
            <a href={inv.url} className="btn btn-xs btn-info mr-1" title={trans.view}>
                <i className="fas fa-eye" />
            </a>
            {/* Un brouillon n'est pas émis : ni PDF légal ni Factur-X.
                Les routes le refusent (403), on n'affiche donc pas
                des boutons qui mènent à une page d'erreur. */}
            {Number(inv.statu) !== 1 && (
                <>
                    <a href={inv.url_pdf} className="btn btn-xs btn-secondary mr-1" title="PDF" target="_blank" rel="noreferrer">
                        <i className="fas fa-file-pdf" />
                    </a>
                    <a href={inv.url_facturex} className="btn btn-xs btn-warning" title="Factur-X" target="_blank" rel="noreferrer">
                        <i className="fas fa-file-invoice" />
                    </a>
                </>
            )}
        </>
    );
}

function InvoicesTable({ invoices, loading, sortField, sortAsc, onSort, trans, qontoEnabled }) {
    return (
        <DataTable
            rows={invoices}
            columns={invoiceColumns(trans, qontoEnabled)}
            loading={loading}
            trans={trans}
            sortField={sortField}
            sortAsc={sortAsc}
            onSort={onSort}
            storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
            rowHref={inv => inv.url}
            rowActions={inv => <InvoiceActions inv={inv} trans={trans} />}
            actionsWidth={90}
            actionsCellStyle={{ whiteSpace: 'nowrap' }}
            mobileActions={inv => Number(inv.statu) !== 1 && (
                <div className="d-flex" style={{ gap: '0.5rem' }}>
                    <a href={inv.url_pdf} className="btn btn-outline-secondary" style={{ minHeight: 44, lineHeight: '30px' }} target="_blank" rel="noreferrer">
                        <i className="fas fa-file-pdf mr-1" />PDF
                    </a>
                    <a href={inv.url_facturex} className="btn btn-outline-secondary" style={{ minHeight: 44, lineHeight: '30px' }} target="_blank" rel="noreferrer">
                        <i className="fas fa-file-invoice mr-1" />Factur-X
                    </a>
                </div>
            )}
        />
    );
}

// ---------------------------------------------------------------------------
// LocalStorage helpers
// ---------------------------------------------------------------------------

const LS_KEY = 'invoices_list_filters';

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

function ListTab({ endpoints, trans, companieId = null }) {
    const saved = loadFilters();

    const [invoices, setInvoices]   = useState([]);
    const [meta, setMeta]           = useState(null);
    const [loading, setLoading]     = useState(false);
    const [qontoEnabled, setQontoEnabled] = useState(false);
    const [search, setSearch]       = useState(saved?.search   ?? '');
    const [statuses, setStatuses]   = useState(saved?.statuses ?? ALL_STATUSES);
    const [sortField, setSortField] = useState(saved?.sortField ?? 'created_at');
    const [sortAsc, setSortAsc]     = useState(saved?.sortAsc   ?? false);
    const [page, setPage]           = useState(1);

    const searchTimeout = useRef(null);

    const fetchInvoices = (params = {}) => {
        const qs = new URLSearchParams({
            search:   params.search   ?? search,
            sort:     params.sortField ?? sortField,
            asc:      params.sortAsc   ?? sortAsc ? '1' : '0',
            page:     params.page     ?? page,
        });
        (params.statuses ?? statuses).forEach(s => qs.append('statuses[]', s));
        if (companieId) qs.set('company_id', companieId);

        setLoading(true);
        apiFetch(`${endpoints.list}?${qs}`)
            .then(res => {
                setInvoices(res.data);
                setMeta(res.meta);
                if (typeof res.pdp_enabled !== 'undefined') {
                    setQontoEnabled(res.pdp_enabled);
                }
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        fetchInvoices();
    }, [page, statuses, sortField, sortAsc]);

    useEffect(() => {
        saveFilters({ search, statuses, sortField, sortAsc });
    }, [search, statuses, sortField, sortAsc]);

    const handleSearchChange = (e) => {
        const val = e.target.value;
        setSearch(val);
        clearTimeout(searchTimeout.current);
        searchTimeout.current = setTimeout(() => {
            setPage(1);
            fetchInvoices({ search: val, page: 1 });
        }, 350);
    };

    const handleStatusChange = (next) => {
        setStatuses(next);
        setPage(1);
    };

    const handleSort = (field) => {
        const nextAsc = sortField === field ? !sortAsc : false;
        setSortField(field);
        setSortAsc(nextAsc);
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
                    <input
                        type="text"
                        className="form-control"
                        placeholder={trans.search}
                        value={search}
                        onChange={handleSearchChange}
                    />
                </div>
                <MobileFilters count={statuses.length === ALL_STATUSES.length ? 0 : statuses.length} trans={trans}>
                    <StatusFilter config={STATUS_CONFIG} ids={ALL_STATUSES} selected={statuses} onChange={handleStatusChange} trans={trans} allowEmpty={false} fallback="value" />
                </MobileFilters>
            </div>

            <InvoicesTable
                invoices={invoices}
                loading={loading}
                sortField={sortField}
                sortAsc={sortAsc}
                onSort={handleSort}
                trans={trans}
                qontoEnabled={qontoEnabled}
            />

            <Pagination meta={meta} ulClassName="pagination pagination-sm justify-content-end" onPage={(p) => setPage(p)} />
        </div>
    );
}

// ---------------------------------------------------------------------------
// Root component
// ---------------------------------------------------------------------------

export default function InvoicesIndex({ kpi, chartData, topClients, endpoints, trans, companieId = null }) {
    const [activeTab, setActiveTab] = useIndexTab('invoices_index_tab', { forced: companieId ? 'list' : null });

    return (
        <div className="card card-outline card-danger">
            <div className="card-header p-2">
                <ul className="nav nav-pills">
                    {!companieId && (
                        <li className="nav-item">
                            <a
                                className={`nav-link ${activeTab === 'dashboard' ? 'active' : ''}`}
                                href="#"
                                onClick={e => { e.preventDefault(); setActiveTab('dashboard'); }}
                            >
                                {trans.dashboard}
                            </a>
                        </li>
                    )}
                    <li className="nav-item">
                        <a
                            className={`nav-link ${activeTab === 'list' ? 'active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('list'); }}
                        >
                            {trans.invoices_list}
                        </a>
                    </li>
                </ul>
            </div>

            <div className="card-body">
                {activeTab === 'dashboard' && (
                    <DashboardTab kpi={kpi} chartData={chartData} topClients={topClients} trans={trans} />
                )}
                {activeTab === 'list' && (
                    <ListTab endpoints={endpoints} trans={trans} companieId={companieId} />
                )}
            </div>
        </div>
    );
}
