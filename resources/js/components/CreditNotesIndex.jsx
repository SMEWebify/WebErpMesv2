import React, { useState, useEffect, useCallback } from 'react';
import { DataTable, Pagination, MobileFilters } from './table';
import { formatCurrencyRounded as formatCurrency } from '../utils';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const CREDIT_NOTE_STATUS = {
    1: { badge: 'badge-danger',  label: 'pending' },
    2: { badge: 'badge-success', label: 'approved' },
    3: { badge: 'badge-warning', label: 'rejected' },
};

const MONTHS_SHORT = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];

const LS_COL_ORDER   = 'credit_notes_col_order';
const LS_HIDDEN_COLS = 'credit_notes_hidden_cols';
const LS_FILTERS     = 'credit_notes_filters';


// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url) {
    const res = await fetch(url, {
        headers: {
            'Accept':       'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

// ---------------------------------------------------------------------------
// DonutChart — pure SVG
// ---------------------------------------------------------------------------

function DonutChart({ data, colors, labels }) {
    const size = 180;
    const cx = size / 2, cy = size / 2;
    const outerR = 70, innerR = 42;
    const total = data.reduce((s, v) => s + v, 0);

    if (total === 0) {
        return (
            <div style={{ textAlign: 'center' }}>
                <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
                    <circle cx={cx} cy={cy} r={outerR} fill="#eee" />
                    <circle cx={cx} cy={cy} r={innerR} fill="white" />
                    <text x={cx} y={cy + 5} textAnchor="middle" fill="#bbb" fontSize="13">—</text>
                </svg>
            </div>
        );
    }

    let angle = -Math.PI / 2;
    const slices = data.map((val, i) => {
        const sweep = (val / total) * 2 * Math.PI;
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
        angle += sweep;
        return { d, color: colors[i % colors.length], label: labels[i], val };
    });

    return (
        <div style={{ textAlign: 'center' }}>
            <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
                {slices.map((s, i) => (
                    <path key={i} d={s.d} fill={s.color} stroke="white" strokeWidth="2">
                        <title>{s.label}: {s.val}</title>
                    </path>
                ))}
                <circle cx={cx} cy={cy} r={innerR} fill="white" />
                <text x={cx} y={cy - 6} textAnchor="middle" fill="#444" fontSize="20" fontWeight="700">{total}</text>
                <text x={cx} y={cy + 12} textAnchor="middle" fill="#999" fontSize="11">total</text>
            </svg>
            <div style={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'center', gap: '6px', marginTop: '6px' }}>
                {slices.map((s, i) => (
                    <span key={i} style={{ display: 'flex', alignItems: 'center', gap: '4px', fontSize: '12px', color: '#555' }}>
                        <span style={{ width: 10, height: 10, background: s.color, borderRadius: 2, display: 'inline-block', flexShrink: 0 }} />
                        {s.label} <strong>({s.val})</strong>
                    </span>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// BarChart — pure SVG (monthly recap)
// ---------------------------------------------------------------------------

function BarChart({ monthlyData, currency, locale }) {
    const months = Array.from({ length: 12 }, (_, i) => {
        const found = monthlyData.find(d => Number(d.month) === i + 1);
        return found ? Number(found.orderSum) : 0;
    });

    const max = Math.max(...months, 1);
    const W = 300, H = 110;
    const padL = 4, padR = 4, padT = 8, padB = 22;
    const innerW = W - padL - padR;
    const innerH = H - padT - padB;
    const barW   = (innerW / 12) - 3;

    const yLines = [0.25, 0.5, 0.75, 1].map(f => ({
        y:   padT + innerH * (1 - f),
        val: max * f,
    }));

    return (
        <svg width="100%" viewBox={`0 0 ${W} ${H}`} style={{ display: 'block' }}>
            {yLines.map((l, i) => (
                <line key={i} x1={padL} x2={W - padR} y1={l.y} y2={l.y}
                    stroke="#e8e8e8" strokeWidth="1" strokeDasharray="3 3" />
            ))}
            {months.map((val, i) => {
                const h  = val > 0 ? Math.max((val / max) * innerH, 3) : 2;
                const x  = padL + i * (innerW / 12) + 1.5;
                const y  = padT + innerH - h;
                return (
                    <g key={i}>
                        <rect x={x} y={y} width={barW} height={h}
                            fill="rgba(60,141,188,0.85)" rx="2">
                            <title>{MONTHS_SHORT[i]}: {formatCurrency(val, currency, locale)}</title>
                        </rect>
                        <text x={x + barW / 2} y={H - 6}
                            textAnchor="middle" fill="#888" fontSize="7.5">
                            {MONTHS_SHORT[i]}
                        </text>
                    </g>
                );
            })}
        </svg>
    );
}

// ---------------------------------------------------------------------------
// KPI cards
// ---------------------------------------------------------------------------

function KpiCards({ kpi, trans }) {
    const cards = [
        { label: trans.total    ?? 'Total',    value: kpi.total,    icon: 'fas fa-file-invoice',  color: '#17a2b8' },
        { label: trans.pending  ?? 'En attente', value: kpi.pending, icon: 'fas fa-clock',         color: '#dc3545' },
        { label: trans.approved ?? 'Approuvé', value: kpi.approved, icon: 'fas fa-check-circle',  color: '#28a745' },
        { label: trans.rejected ?? 'Rejeté',   value: kpi.rejected, icon: 'fas fa-times-circle',  color: '#ffc107' },
    ];

    return (
        <div className="row" style={{ marginBottom: 16 }}>
            {cards.map((c, i) => (
                <div key={i} className="col-6 col-md-3" style={{ marginBottom: 8 }}>
                    <div style={{
                        background: c.color, color: '#fff', borderRadius: 6,
                        padding: '12px 16px', position: 'relative', boxShadow: '0 1px 4px rgba(0,0,0,.15)',
                    }}>
                        <div style={{ fontSize: 26, fontWeight: 700, lineHeight: 1 }}>{c.value}</div>
                        <div style={{ fontSize: 12, opacity: 0.9, marginTop: 4 }}>{c.label}</div>
                        <div style={{ position: 'absolute', right: 12, top: 10, fontSize: 30, opacity: 0.25 }}>
                            <i className={c.icon} />
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Table — colonnes déclarées, rendu par le DataTable partagé
// ---------------------------------------------------------------------------

function creditNoteColumns(trans, currency, locale) {
    return [
        { key: 'code',        label: trans.code        ?? 'Code',        sortable: true,
          render: r => <code>{r.code}</code>, filter: 'text', mobile: 'title', mobileRender: r => r.code },
        { key: 'label',       label: trans.label       ?? 'Libellé',     sortable: true,
          filter: 'text', mobile: 'subtitle' },
        { key: 'company',     label: trans.company     ?? 'Client',      sortable: 'companies_id',
          render: r => (r.companie
              ? <a href={`/fr/companies/${r.companie.id}`} className="btn btn-outline-secondary btn-xs" style={{ fontSize: 11 }}>{r.companie.label}</a>
              : '—'),
          filterValue: r => r.companie?.label, filter: 'text', mobile: 'subtitle', mobileOrder: 1, mobileRender: r => r.companie?.label },
        { key: 'lines_count', label: trans.lines       ?? 'Lignes',      sortable: true,
          render: r => <span className="badge badge-secondary">{r.lines_count}</span> },
        { key: 'total_price', label: trans.total_price ?? 'Montant',     align: 'right',
          render: r => formatCurrency(r.total_price ?? 0, currency, locale),
          total: { value: r => Number(r.total_price) || 0, format: sum => formatCurrency(sum, currency, locale) },
          mobile: 'amount' },
        { key: 'statu',       label: trans.status      ?? 'Statut',      sortable: true,
          render: r => {
              const cfg = CREDIT_NOTE_STATUS[r.statu] ?? { badge: 'badge-secondary', label: '?' };
              return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span>;
          },
          mobile: 'badge' },
        { key: 'user',        label: trans.user        ?? 'Utilisateur',
          render: r => r.user?.name ?? '—', filterValue: r => r.user?.name, filter: 'text' },
        { key: 'created_at',  label: trans.created_at  ?? 'Créé le',     sortable: true,
          filter: 'date', mobile: 'subtitle' },
    ];
}

function CreditNotesTable({ rows, loading, sort, onSort, trans, currency, locale }) {
    return (
        <DataTable
            rows={rows}
            columns={creditNoteColumns(trans, currency, locale)}
            loading={loading}
            trans={trans}
            sortField={sort.field}
            sortAsc={sort.asc}
            onSort={onSort}
            storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
            unsortableIcon={false}
            chipsClassName="mb-2 d-flex flex-wrap px-3 pt-2"
            loadingContent={<><i className="fas fa-spinner fa-spin" /> {trans.loading ?? 'Chargement…'}</>}
            emptyText={trans.no_results ?? 'Aucun résultat'}
            totalLabel={trans.total ?? 'Total'}
            rowHref={r => r.url}
            rowActions={r => (
                <a href={r.url} className="btn btn-xs btn-info" title={trans.view ?? 'Voir'}>
                    <i className="fas fa-eye" />
                </a>
            )}
        />
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function CreditNotesIndex({ chartData, endpoints, trans }) {
    const currency = trans.currency ?? 'EUR';
    const locale   = trans.locale   ?? 'fr-FR';

    // ---- Filters (localStorage) ----
    const defaultFilters = { search: '', statuses: [] };
    const [filters, setFilters] = useState(() => {
        try { return { ...defaultFilters, ...JSON.parse(localStorage.getItem(LS_FILTERS) ?? 'null') }; }
        catch { return defaultFilters; }
    });

    // ---- Sort & page ----
    const [sort, setSort] = useState({ field: 'created_at', asc: false });
    const [page, setPage] = useState(1);

    // ---- Data ----
    const [rows, setRows]       = useState([]);
    const [meta, setMeta]       = useState(null);
    const [loading, setLoading] = useState(true);

    // ---- KPI from chartData ----
    const kpi = React.useMemo(() => {
        const rates   = chartData?.creditNotesDataRate ?? [];
        const pending  = Number(rates.find(r => Number(r.statu) === 1)?.CreditNotesCountRate ?? 0);
        const approved = Number(rates.find(r => Number(r.statu) === 2)?.CreditNotesCountRate ?? 0);
        const rejected = Number(rates.find(r => Number(r.statu) === 3)?.CreditNotesCountRate ?? 0);
        return { total: pending + approved + rejected, pending, approved, rejected };
    }, [chartData]);

    // ---- Fetch ----
    const fetchData = useCallback(async (f, s, p) => {
        if (!endpoints?.list) return;
        setLoading(true);
        try {
            const params = new URLSearchParams({ search: f.search, sort: s.field, asc: s.asc ? '1' : '0', page: p });
            f.statuses.forEach(v => params.append('statuses[]', v));
            const json = await apiFetch(`${endpoints.list}?${params}`);
            setRows(json.data ?? []);
            setMeta(json.meta ?? null);
        } catch (e) {
            console.error('CreditNotesIndex fetch error:', e);
        } finally {
            setLoading(false);
        }
    }, [endpoints?.list]);

    useEffect(() => { fetchData(filters, sort, page); }, [filters, sort, page, fetchData]);
    useEffect(() => { localStorage.setItem(LS_FILTERS, JSON.stringify(filters)); }, [filters]);

    // ---- Handlers ----
    function handleSort(field) {
        setSort(prev => ({ field, asc: prev.field === field ? !prev.asc : false }));
        setPage(1);
    }

    function handleFilterChange(key, value) {
        setFilters(prev => ({ ...prev, [key]: value }));
        setPage(1);
    }

    function toggleStatus(val) {
        setFilters(prev => ({
            ...prev,
            statuses: prev.statuses.includes(val)
                ? prev.statuses.filter(s => s !== val)
                : [...prev.statuses, val],
        }));
        setPage(1);
    }

    // ---- Chart data ----
    const monthlyData = chartData?.creditNoteMonthlyRecap ?? [];

    const pieData   = [kpi.pending, kpi.approved, kpi.rejected];
    const pieColors = ['#dc3545', '#28a745', '#ffc107'];
    const pieLabels = [trans.pending ?? 'En attente', trans.approved ?? 'Approuvé', trans.rejected ?? 'Rejeté'];

    function activeBtn(active, colorClass) {
        return `btn btn-sm ${active ? colorClass : 'btn-outline-secondary'}`;
    }

    return (
        <div>
            {/* KPI row */}
            <KpiCards kpi={kpi} trans={trans} />

            <div className="row">
                {/* ── Left column: charts ── */}
                <div className="col-md-3">
                    <div className="card card-outline card-info">
                        <div className="card-header py-2">
                            <h3 className="card-title" style={{ fontSize: 13 }}>
                                <i className="fas fa-chart-pie mr-1" />
                                {trans.statistiques ?? 'Statistiques'}
                            </h3>
                        </div>
                        <div className="card-body">
                            <DonutChart data={pieData} colors={pieColors} labels={pieLabels} />
                        </div>
                    </div>

                    <div className="card card-outline card-warning">
                        <div className="card-header py-2">
                            <h3 className="card-title" style={{ fontSize: 13 }}>
                                <i className="fas fa-chart-bar mr-1" />
                                {trans.monthly_recap ?? 'Récap mensuel'}
                            </h3>
                        </div>
                        <div className="card-body p-2">
                            <BarChart monthlyData={monthlyData} currency={currency} locale={locale} />
                            {monthlyData.length === 0 && (
                                <p className="text-center text-muted mt-1 mb-0" style={{ fontSize: 12 }}>
                                    {trans.no_data ?? 'Aucune donnée'}
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                {/* ── Right column: table ── */}
                <div className="col-md-9 order-first order-md-0">
                    <div className="card">
                        <div className="card-header py-2">
                            <div className="d-flex flex-wrap align-items-center" style={{ gap: 6 }}>
                                {/* Search */}
                                <div className="input-group" style={{ maxWidth: 240 }}>
                                    <div className="input-group-prepend">
                                        <span className="input-group-text"><i className="fas fa-search" /></span>
                                    </div>
                                    <input
                                        type="text"
                                        className="form-control form-control-sm"
                                        placeholder={trans.search ?? 'Rechercher…'}
                                        value={filters.search}
                                        onChange={e => handleFilterChange('search', e.target.value)}
                                    />
                                </div>

                                <MobileFilters count={filters.statuses.length} trans={trans}>
                                    {/* Status filter */}
                                    <div className="btn-group btn-group-sm">
                                        {Object.entries(CREDIT_NOTE_STATUS).map(([val, cfg]) => (
                                            <button
                                                key={val}
                                                type="button"
                                                className={activeBtn(
                                                    filters.statuses.includes(Number(val)),
                                                    cfg.badge.replace('badge', 'btn')
                                                )}
                                                onClick={() => toggleStatus(Number(val))}
                                            >
                                                {trans[cfg.label] ?? cfg.label}
                                            </button>
                                        ))}
                                    </div>
                                </MobileFilters>

                                <div style={{ flex: 1 }} />
                            </div>
                        </div>

                        <CreditNotesTable
                            rows={rows}
                            loading={loading}
                            sort={sort}
                            onSort={handleSort}
                            trans={trans}
                            currency={currency}
                            locale={locale}
                        />

                        <Pagination meta={meta} around={2} boundaries showTotal navClassName="d-flex justify-content-between align-items-center px-3 pb-2" ulClassName="pagination pagination-sm mb-0" onPage={p => setPage(p)} />
                    </div>
                </div>
            </div>
        </div>
    );
}
