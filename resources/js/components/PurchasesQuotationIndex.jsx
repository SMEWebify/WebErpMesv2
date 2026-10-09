import React, { useState, useEffect, useCallback } from 'react';
import { DataTable, StatusFilter, MobileFilters } from './table';
import { apiFetchWithStatus as apiFetch } from '../lib/http';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

const STATUS_LABELS = {
    1: { label: 'En cours',              badge: 'badge-info' },
    2: { label: 'Envoyé',               badge: 'badge-primary' },
    3: { label: 'Partiellement reçu',   badge: 'badge-secondary' },
    4: { label: 'Reçu',                 badge: 'badge-info' },
    5: { label: 'BC partiellement créé',badge: 'badge-warning' },
    6: { label: 'BC créé',              badge: 'badge-success' },
};

const LS_COL_ORDER   = 'purchases_quotation_table_col_order';
const LS_HIDDEN_COLS = 'purchases_quotation_table_hidden_cols';

const CHART_COLORS = [
    'rgba(23, 162, 184, 1)',
    'rgba(255, 193, 7, 1)',
    'rgba(40, 167, 69, 1)',
    'rgba(220, 53, 69, 1)',
    'rgba(108, 117, 125, 1)',
    'rgba(0, 123, 255, 1)',
];

// ---------------------------------------------------------------------------
// DonutChart (inline SVG)
// ---------------------------------------------------------------------------

function DonutChart({ data }) {
    if (!data || data.length === 0) return null;

    const total = data.reduce((s, d) => s + Number(d.count), 0);
    if (total === 0) return null;

    const size = 120;
    const cx = size / 2;
    const cy = size / 2;
    const r  = 45;
    const strokeWidth = 20;

    let cumAngle = -Math.PI / 2;
    const slices = data.map((d, i) => {
        const fraction = Number(d.count) / total;
        const angle    = fraction * 2 * Math.PI;
        const x1 = cx + r * Math.cos(cumAngle);
        const y1 = cy + r * Math.sin(cumAngle);
        cumAngle += angle;
        const x2 = cx + r * Math.cos(cumAngle);
        const y2 = cy + r * Math.sin(cumAngle);
        const large = angle > Math.PI ? 1 : 0;
        return {
            d: `M ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2}`,
            color: CHART_COLORS[i % CHART_COLORS.length],
            label: STATUS_LABELS[d.statu]?.label ?? `Statut ${d.statu}`,
            count: d.count,
        };
    });

    return (
        <div className="text-center">
            <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
                {slices.map((s, i) => (
                    <path key={i} d={s.d} fill="none" stroke={s.color} strokeWidth={strokeWidth} />
                ))}
                <text x={cx} y={cy + 5} textAnchor="middle" fontSize="14" fontWeight="bold">{total}</text>
            </svg>
            <div className="mt-2" style={{ fontSize: '0.78rem' }}>
                {slices.map((s, i) => (
                    <div key={i} className="d-flex align-items-center justify-content-between px-1">
                        <span>
                            <span style={{ display: 'inline-block', width: 10, height: 10, background: s.color, marginRight: 4, borderRadius: 2 }} />
                            {s.label}
                        </span>
                        <strong>{s.count}</strong>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Table — colonnes déclarées, rendu par le DataTable partagé
// ---------------------------------------------------------------------------

function quotationColumns(trans) {
    return [
        { key: 'code',        label: trans.id,          sortable: true, nowrap: true,
          filter: 'text', mobile: 'title' },
        { key: 'label',       label: trans.label,       sortable: true,
          filter: 'text', mobile: 'subtitle' },
        { key: 'rfq_group',   label: trans.rfq_group,
          render: q => (q.rfq_group_code ? (
              <>
                  <span className="badge badge-light">{q.rfq_group_code}</span>
                  {q.rfq_group_label && <div className="text-muted small">{q.rfq_group_label}</div>}
              </>
          ) : <span className="text-muted">—</span>),
          filterValue: q => [q.rfq_group_code, q.rfq_group_label].filter(Boolean).join(' '), filter: 'text' },
        { key: 'companie',    label: trans.supplier,    sortable: 'companies_id',
          render: q => (q.companie_url
              ? <a href={q.companie_url} className="btn btn-outline-secondary btn-sm">{q.companie_label}</a>
              : q.companie_label),
          filterValue: q => q.companie_label, filter: 'text', mobile: 'subtitle', mobileOrder: 1, mobileRender: q => q.companie_label },
        { key: 'lines_count', label: trans.lines_count },
        { key: 'statu',       label: trans.status,
          render: q => STATUS_LABELS[q.statu] && (
              <span className={`badge ${STATUS_LABELS[q.statu].badge}`}>{STATUS_LABELS[q.statu].label}</span>
          ),
          mobile: 'badge' },
        { key: 'created_at',  label: trans.created_at,  sortable: true,
          render: q => q.created_at_human, filterValue: q => q.created_date, filter: 'date' },
    ];
}

function RfqGroupHeader({ quotation: q, trans }) {
    return (
        <div className="d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <strong>{trans.rfq_group}:</strong>{' '}
                {q.rfq_group_label ?? q.rfq_group_code}
                {q.rfq_group_code && <span className="text-muted ml-1">({q.rfq_group_code})</span>}
            </div>
            {q.compare_url && (
                <a href={q.compare_url} className="btn btn-outline-primary btn-sm">
                    <i className="fas fa-balance-scale mr-1" />{trans.compare_rfq}
                </a>
            )}
        </div>
    );
}

export default function PurchasesQuotationIndex({ endpoints, trans, initialKpi }) {
    const [quotations, setQuotations] = useState([]);
    const [meta,       setMeta]       = useState({ current_page: 1, last_page: 1, total: 0 });
    const [kpi,        setKpi]        = useState(initialKpi ?? null);
    const [search,     setSearch]     = useState('');
    const [statuses,   setStatuses]   = useState([]);
    const [sortField,  setSortField]  = useState('created_at');
    const [sortAsc,    setSortAsc]    = useState(false);
    const [page,       setPage]       = useState(1);
    const [loading,    setLoading]    = useState(true);

    // Fetch list
    const fetchList = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({
            search,
            sort: sortField,
            dir: sortAsc ? 'asc' : 'desc',
            page,
        });
        statuses.forEach(s => params.append('statuses[]', s));
        apiFetch(`${endpoints.list}?${params}`)
            .then(data => {
                setQuotations(data.data);
                setMeta(data.meta);
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [search, statuses, sortField, sortAsc, page, endpoints.list]);

    // Fetch KPI once on mount (or use initialKpi passed as prop)
    useEffect(() => {
        if (!initialKpi) {
            apiFetch(endpoints.kpi).then(setKpi).catch(() => {});
        }
    }, []);

    useEffect(() => {
        setPage(1);
    }, [search, statuses, sortField, sortAsc]);

    useEffect(() => {
        fetchList();
    }, [fetchList]);

    function handleSort(field) {
        if (sortField === field) {
            setSortAsc(v => !v);
        } else {
            setSortField(field);
            setSortAsc(true);
        }
    }

    // Render
    return (
        <div className="row">
            {/* Left sidebar */}
            <div className="col-md-3">
                <div className="card card-teal">
                    <div className="card-header">
                        <h3 className="card-title">
                            <i className="fas fa-chart-bar mr-1" />{trans.statistics}
                        </h3>
                    </div>
                    <div className="card-body">
                        {kpi ? <DonutChart data={kpi.chart} /> : (
                            <div className="text-center text-muted py-3"><i className="fas fa-spinner fa-spin" /></div>
                        )}
                    </div>
                </div>

                <div className="small-box bg-info">
                    <div className="inner">
                        <h3>{kpi?.total_count ?? '—'}</h3>
                        <p>{trans.purchase_quotation}</p>
                    </div>
                    <div className="icon"><i className="fas fa-file-signature" /></div>
                </div>

                <div className="small-box bg-purple">
                    <div className="inner">
                        <h3>{kpi?.total_lines ?? '—'}</h3>
                        <p>{trans.lines_count}</p>
                    </div>
                    <div className="icon"><i className="fas fa-stream" /></div>
                </div>

                <div className="small-box bg-success">
                    <div className="inner">
                        <h3 style={{ fontSize: '1.3rem' }}>{kpi?.total_amount ?? '—'}</h3>
                        <p>{trans.total_price}</p>
                    </div>
                    <div className="icon"><i className="fas fa-coins" /></div>
                </div>
            </div>

            {/* Main list */}
            <div className="col-md-9 order-first order-md-0">
                <div className="card">
                    <div className="card-body pb-1">
                        <div className="input-group">
                            <div className="input-group-prepend">
                                <span className="input-group-text"><i className="fas fa-search" /></span>
                            </div>
                            <input
                                type="text"
                                className="form-control"
                                placeholder={trans.search}
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                            />
                        </div>
                        <MobileFilters count={statuses.length} trans={trans}>
                            <div className="mt-2">
                                <StatusFilter
                                    config={STATUS_LABELS}
                                    selected={statuses}
                                    onToggle={id => setStatuses(prev => (prev.includes(id) ? prev.filter(s => s !== id) : [...prev, id]))}
                                    trans={trans}
                                    buttonType="button"
                                />
                            </div>
                        </MobileFilters>
                    </div>

                    <DataTable
                        rows={quotations}
                        columns={quotationColumns(trans)}
                        loading={loading}
                        trans={trans}
                        sortField={sortField}
                        sortAsc={sortAsc}
                        onSort={handleSort}
                        storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
                        unsortableIcon={false}
                        tableClassName="table table-hover"
                        chipsClassName="mb-2 d-flex flex-wrap px-3 pt-2"
                        loadingContent={<><i className="fas fa-spinner fa-spin mr-2" />{trans.loading}</>}
                        emptyText={trans.no_data}
                        actionsHeader={trans.action}
                        actionsWidth={90}
                        actionsCellStyle={{ whiteSpace: 'nowrap' }}
                        rowHref={q => q.show_url}
                        renderGroupHeader={(q, prev) => q.rfq_group_id && q.rfq_group_id !== prev?.rfq_group_id
                            && <RfqGroupHeader quotation={q} trans={trans} />}
                        rowStyle={q => (q.rfq_group_id ? { borderLeft: '4px solid #6c757d' } : undefined)}
                        rowActions={q => (
                            <>
                                <a href={q.show_url} className="btn btn-xs btn-info mr-1">
                                    <i className="fas fa-eye" />
                                </a>
                                {q.pdf_url && (
                                    <a href={q.pdf_url} className="btn btn-outline-danger btn-sm" target="_blank" rel="noreferrer">
                                        <i className="fas fa-file-pdf" />
                                    </a>
                                )}
                            </>
                        )}
                        mobileActions={q => q.pdf_url && (
                            <a href={q.pdf_url} className="btn btn-outline-secondary" style={{ minHeight: 44, lineHeight: '30px' }} target="_blank" rel="noreferrer">
                                <i className="fas fa-file-pdf text-danger mr-1" />PDF
                            </a>
                        )}
                    />

                    {/* Pagination */}
                    {meta.last_page > 1 && (
                        <div className="card-footer d-flex justify-content-between align-items-center">
                            <small className="text-muted">{trans.total}: {meta.total}</small>
                            <div>
                                <button
                                    className="btn btn-sm btn-outline-secondary mr-1"
                                    disabled={meta.current_page <= 1}
                                    onClick={() => setPage(p => p - 1)}
                                >
                                    <i className="fas fa-chevron-left" />
                                </button>
                                <span className="mx-2">{meta.current_page} / {meta.last_page}</span>
                                <button
                                    className="btn btn-sm btn-outline-secondary ml-1"
                                    disabled={meta.current_page >= meta.last_page}
                                    onClick={() => setPage(p => p + 1)}
                                >
                                    <i className="fas fa-chevron-right" />
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
