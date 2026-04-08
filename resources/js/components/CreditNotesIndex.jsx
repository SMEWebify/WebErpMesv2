import React, { useState, useEffect, useRef, useCallback } from 'react';

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

const DEFAULT_COL_ORDER = ['code', 'label', 'company', 'lines_count', 'total_price', 'statu', 'user', 'created_at'];

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function formatCurrency(amount, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style:                'currency',
            currency:             currency || 'EUR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    } catch {
        return `${Number(amount).toFixed(0)} ${currency ?? '€'}`;
    }
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
// SortIcon
// ---------------------------------------------------------------------------

function SortIcon({ field, sortField, sortAsc }) {
    if (field !== sortField) return <i className="fas fa-sort text-muted ml-1" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

// ---------------------------------------------------------------------------
// Column definitions
// ---------------------------------------------------------------------------

function colDefs(trans, currency, locale) {
    return {
        code:        { label: trans.code        ?? 'Code',       sortField: 'code',          render: r => <code>{r.code}</code> },
        label:       { label: trans.label       ?? 'Libellé',    sortField: 'label',          render: r => r.label },
        company:     { label: trans.company     ?? 'Client',     sortField: 'companies_id',   render: r => r.companie
            ? <a href={`/fr/companies/${r.companie.id}`} className="btn btn-outline-secondary btn-xs" style={{ fontSize: 11 }}>{r.companie.label}</a>
            : '—' },
        lines_count: { label: trans.lines       ?? 'Lignes',     sortField: 'lines_count',    render: r => <span className="badge badge-secondary">{r.lines_count}</span> },
        total_price: { label: trans.total_price ?? 'Montant',    sortField: null,             render: r => formatCurrency(r.total_price ?? 0, currency, locale) },
        statu:       { label: trans.status      ?? 'Statut',     sortField: 'statu',          render: r => {
            const cfg = CREDIT_NOTE_STATUS[r.statu] ?? { badge: 'badge-secondary', label: '?' };
            return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span>;
        }},
        user:        { label: trans.user        ?? 'Utilisateur', sortField: null,            render: r => r.user?.name ?? '—' },
        created_at:  { label: trans.created_at  ?? 'Créé le',    sortField: 'created_at',     render: r => r.created_at },
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

// ---------------------------------------------------------------------------
// Table
// ---------------------------------------------------------------------------

function CreditNotesTable({ rows, loading, sort, onSort, trans, currency, locale }) {
    const [colOrder,   setColOrder]   = useState(readSavedColOrder);
    const [hiddenCols, setHiddenCols] = useState(readSavedHiddenCols);
    const [dragOver,   setDragOver]   = useState(null);
    const dragCol = useRef(null);

    const COLS        = colDefs(trans, currency, locale);
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

    return (
        <div>
            {hiddenCols.size > 0 && (
                <div className="mb-2 d-flex flex-wrap px-3 pt-2" style={{ gap: '4px' }}>
                    {colOrder.filter(c => hiddenCols.has(c)).map(colId => (
                        <button
                            key={colId}
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            style={{ fontSize: '0.72rem', padding: '1px 8px' }}
                            onClick={() => showCol(colId)}
                            title="Réafficher la colonne"
                        >
                            + {COLS[colId]?.label}
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
                                const dropping = dragOver === colId;
                                return (
                                    <th
                                        key={colId}
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
                                        onClick={() => col.sortField && onSort(col.sortField)}
                                    >
                                        <i className="fas fa-grip-vertical text-muted mr-1" style={{ fontSize: '0.65rem', opacity: 0.4 }} />
                                        {col.label}
                                        {col.sortField && (
                                            <SortIcon field={col.sortField} sortField={sort.field} sortAsc={sort.asc} />
                                        )}
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
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={visibleCols.length + 1} className="text-center py-4">
                                    <i className="fas fa-spinner fa-spin" /> {trans.loading ?? 'Chargement…'}
                                </td>
                            </tr>
                        ) : rows.length === 0 ? (
                            <tr>
                                <td colSpan={visibleCols.length + 1} className="text-center py-4 text-muted">
                                    {trans.no_results ?? 'Aucun résultat'}
                                </td>
                            </tr>
                        ) : rows.map(row => (
                            <tr key={row.id}>
                                {visibleCols.map(colId => (
                                    <td key={colId}>{COLS[colId]?.render(row)}</td>
                                ))}
                                <td>
                                    <a href={row.url} className="btn btn-xs btn-info" title={trans.view ?? 'Voir'}>
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

function Pagination({ meta, onPage }) {
    if (!meta || meta.last_page <= 1) return null;

    const cur  = meta.current_page;
    const last = meta.last_page;

    const visible = new Set([1, last, cur - 1, cur, cur + 1, cur - 2, cur + 2]
        .filter(p => p >= 1 && p <= last));
    const sorted = [...visible].sort((a, b) => a - b);

    return (
        <nav className="d-flex justify-content-between align-items-center px-3 pb-2">
            <small className="text-muted">
                {meta.total} {meta.total > 1 ? 'résultats' : 'résultat'}
            </small>
            <ul className="pagination pagination-sm mb-0">
                <li className={`page-item ${cur === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(cur - 1)}>«</button>
                </li>
                {sorted.map((p, i) => (
                    <React.Fragment key={p}>
                        {i > 0 && sorted[i - 1] < p - 1 && (
                            <li className="page-item disabled"><span className="page-link">…</span></li>
                        )}
                        <li className={`page-item ${p === cur ? 'active' : ''}`}>
                            <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                        </li>
                    </React.Fragment>
                ))}
                <li className={`page-item ${cur === last ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(cur + 1)}>»</button>
                </li>
            </ul>
        </nav>
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
                <div className="col-md-9">
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

                        <Pagination meta={meta} onPage={p => setPage(p)} />
                    </div>
                </div>
            </div>
        </div>
    );
}
