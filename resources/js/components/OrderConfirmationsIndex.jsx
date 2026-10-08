import React, { useState, useEffect, useCallback } from 'react';
import { DataTable, Pagination, StatusFilter, MobileFilters } from './table';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const ARC_STATUS = {
    1: { badge: 'badge-secondary', key: 'status_draft' },
    2: { badge: 'badge-primary',   key: 'status_sent' },
    3: { badge: 'badge-success',   key: 'status_accepted' },
    4: { badge: 'badge-light',     key: 'status_superseded' },
};

const STATUS_DRAFT = 1;

// Même table au format attendu par StatusFilter ({ badge, label } = clé de traduction).
const STATUS_FILTER_CONFIG = Object.fromEntries(
    Object.entries(ARC_STATUS).map(([id, cfg]) => [id, { badge: cfg.badge, label: cfg.key }]),
);

const LS_COL_ORDER   = 'order_confirmations_table_col_order';
const LS_HIDDEN_COLS = 'order_confirmations_table_hidden_cols';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function apiHeaders() {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
    };
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, { headers: apiHeaders(), ...options });
    if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw { status: res.status, errors: body.errors ?? {}, message: body.message ?? `HTTP ${res.status}` };
    }
    return res.json();
}

function endpointFor(template, id) {
    return template.replace('__ID__', id);
}

// ---------------------------------------------------------------------------
// Alert flash
// ---------------------------------------------------------------------------

function Flash({ msg, type, onClose }) {
    if (!msg) return null;
    return (
        <div className={`alert alert-${type} alert-dismissible`} role="alert">
            {msg}
            <button type="button" className="close" onClick={onClose}><span>&times;</span></button>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Table — colonnes déclarées, rendu par le DataTable partagé
// ---------------------------------------------------------------------------

function formatAmount(amount, trans) {
    try {
        return new Intl.NumberFormat(trans.locale || 'fr-FR', { style: 'currency', currency: trans.currency || 'EUR' }).format(amount);
    } catch {
        return `${Number(amount).toFixed(2)} ${trans.currency ?? '€'}`;
    }
}

function confirmationColumns(trans) {
    return [
        { key: 'code',       label: trans.code,       sortable: true,
          render: r => <code>{r.code}</code>, filter: 'text', mobile: 'title', mobileRender: r => r.code },
        { key: 'revision',   label: trans.revision,   sortable: true,
          render: r => (
              <>
                  <span className="badge badge-dark">{r.revision}</span>
                  {r.is_current && <i className="fas fa-check-circle text-success ml-1" title="Indice en vigueur" />}
              </>
          ),
          mobile: 'title', mobileRender: r => `ind. ${r.revision}` },
        { key: 'order',      label: trans.order,
          render: r => (r.order ? <a href={r.order_url}>{r.order.code}</a> : '—'), filterValue: r => r.order?.code, filter: 'text' },
        { key: 'customer',   label: trans.customer,
          render: r => r.customer ?? '—', filterValue: r => r.customer, filter: 'text', mobile: 'subtitle', mobileOrder: 1 },
        { key: 'label',      label: trans.label,      sortable: true,
          filter: 'text', mobile: 'subtitle' },
        { key: 'total',      label: trans.total,      align: 'right', nowrap: false,
          total: { value: r => Number(r.total_amount) || 0, format: sum => formatAmount(sum, trans) },
          mobile: 'amount' },
        { key: 'statu',      label: trans.status,     sortable: true,
          render: r => {
              const cfg = ARC_STATUS[r.statu] ?? { badge: 'badge-secondary', key: '' };
              return <span className={`badge ${cfg.badge}`}>{trans[cfg.key] ?? r.statu}</span>;
          },
          mobile: 'badge' },
        { key: 'created_at', label: trans.created_at, sortable: true,
          filter: 'date' },
        { key: 'sent_at',    label: trans.sent_at,
          render: r => r.sent_at ?? '—', filter: 'date' },
    ];
}

export default function OrderConfirmationsIndex({ endpoints, trans }) {
    const [rows, setRows]             = useState([]);
    const [meta, setMeta]             = useState(null);
    const [loading, setLoading]       = useState(true);
    const [fetchError, setFetchError] = useState('');
    const [page, setPage]             = useState(1);
    const [search, setSearch]         = useState('');
    const [statuses, setStatuses]     = useState([]);
    const [sort, setSort]             = useState({ field: 'created_at', asc: false });
    const [flash, setFlash]           = useState({ msg: '', type: 'success' });

    const showFlash = (msg, type = 'success') => {
        setFlash({ msg, type });
        setTimeout(() => setFlash({ msg: '', type: 'success' }), 4000);
    };

    const fetchData = useCallback(async () => {
        if (!endpoints?.list) {
            console.error('OrderConfirmationsIndex: endpoints.list is missing', endpoints);
            setLoading(false);
            setFetchError('Configuration manquante : endpoint list non défini');
            return;
        }
        setLoading(true);
        try {
            setFetchError('');
            const params = new URLSearchParams({ search, sort: sort.field, asc: sort.asc ? '1' : '0', page });
            statuses.forEach(s => params.append('statuses[]', s));
            const json = await apiFetch(`${endpoints.list}?${params}`);
            setRows(json.data ?? []);
            setMeta(json.meta ?? null);
        } catch (err) {
            console.error('OrderConfirmationsIndex fetch error:', err);
            setFetchError(err?.message ?? 'Erreur de chargement');
            setRows([]);
        } finally {
            setLoading(false);
        }
    }, [endpoints?.list, search, statuses, sort, page]);

    useEffect(() => { fetchData(); }, [fetchData]);

    function handleSort(field) {
        setSort(prev => ({ field, asc: prev.field === field ? !prev.asc : false }));
        setPage(1);
    }

    async function handleSend(row) {
        try {
            const res = await apiFetch(endpointFor(endpoints.send, row.id), { method: 'POST' });
            showFlash(res.message);
            fetchData();
        } catch (err) {
            showFlash(err.message, 'danger');
        }
    }

    function handleStatusToggle(id) {
        setStatuses(prev => (prev.includes(id) ? prev.filter(s => s !== id) : [...prev, id]));
        setPage(1);
    }

    return (
        <div>
            <Flash msg={flash.msg} type={flash.type} onClose={() => setFlash({ msg: '', type: 'success' })} />
            {fetchError && (
                <div className="alert alert-danger alert-dismissible py-2">
                    <i className="fas fa-exclamation-triangle mr-1" />{fetchError}
                    <button type="button" className="close py-1" onClick={() => setFetchError('')}><span>&times;</span></button>
                </div>
            )}

            <div className="card card-outline card-primary">
                <div className="card-header py-2">
                    <div className="d-flex flex-wrap align-items-center" style={{ gap: 8 }}>
                        <div className="input-group" style={{ maxWidth: 240 }}>
                            <div className="input-group-prepend">
                                <span className="input-group-text"><i className="fas fa-search" /></span>
                            </div>
                            <input
                                type="text"
                                className="form-control form-control-sm"
                                placeholder={trans.search}
                                value={search}
                                onChange={e => { setSearch(e.target.value); setPage(1); }}
                            />
                        </div>

                        <MobileFilters count={statuses.length} trans={trans}>
                            <StatusFilter config={STATUS_FILTER_CONFIG} selected={statuses} onToggle={handleStatusToggle} trans={trans} buttonType="button" />
                        </MobileFilters>
                    </div>
                </div>

                <DataTable
                    rows={rows}
                    columns={confirmationColumns(trans)}
                    loading={loading}
                    trans={trans}
                    sortField={sort.field}
                    sortAsc={sort.asc}
                    onSort={handleSort}
                    storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
                    unsortableIcon={false}
                    tableClassName="table table-hover table-sm mb-0"
                    loadingContent={<><i className="fas fa-spinner fa-spin" /> {trans.loading}</>}
                    emptyText={trans.no_data}
                    actionsHeader={trans.actions}
                    actionsWidth={90}
                    actionsCellStyle={{ whiteSpace: 'nowrap' }}
                    rowHref={r => r.url}
                    rowClickable
                    rowActions={row => (
                        <div className="d-flex" style={{ gap: 4 }}>
                            {row.statu === STATUS_DRAFT && (
                                <button
                                    className="btn btn-xs btn-outline-success"
                                    title={trans.send}
                                    onClick={() => handleSend(row)}
                                >
                                    <i className="fas fa-paper-plane" />
                                </button>
                            )}
                            <a href={row.url} className="btn btn-xs btn-outline-primary" title={trans.view}>
                                <i className="fas fa-eye" />
                            </a>
                        </div>
                    )}
                    mobileActions={row => row.statu === STATUS_DRAFT && (
                        <button type="button" className="btn btn-outline-success" style={{ minHeight: 44 }} onClick={() => handleSend(row)}>
                            <i className="fas fa-paper-plane mr-1" />{trans.send}
                        </button>
                    )}
                />

                <Pagination meta={meta} around={1} boundaries showTotal navClassName="d-flex justify-content-between align-items-center px-3 pb-2" ulClassName="pagination pagination-sm mb-0" onPage={p => setPage(p)} />
            </div>
        </div>
    );
}
