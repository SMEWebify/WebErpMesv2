import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-info',      label: 'undefined' },
    2: { badge: 'badge-success',   label: 'sold' },
    3: { badge: 'badge-secondary', label: 'shipped' },
    4: { badge: 'badge-warning',   label: 'returned' },
    5: { badge: 'badge-primary',   label: 'in_stock' },
};

const ALL_STATUSES = [1, 2, 3, 4, 5];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
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
// KPI Cards
// ---------------------------------------------------------------------------

function KPICards({ kpi, trans }) {
    const total = kpi.totalCount ?? 0;
    return (
        <div className="row">
            <div className="col-lg-3">
                <div className="small-box bg-info">
                    <div className="inner">
                        <h3>{total}</h3>
                        <p>{trans.total_serial_numbers}</p>
                    </div>
                    <div className="icon"><i className="fas fa-barcode" /></div>
                </div>
            </div>
            <div className="col-lg-3">
                <div className="small-box bg-success">
                    <div className="inner">
                        <h3>{kpi.soldCount ?? 0}</h3>
                        <p>{trans.sold}</p>
                    </div>
                    <div className="icon"><i className="fas fa-tag" /></div>
                </div>
            </div>
            <div className="col-lg-3">
                <div className="small-box bg-secondary">
                    <div className="inner">
                        <h3>{kpi.shippedCount ?? 0}</h3>
                        <p>{trans.shipped}</p>
                    </div>
                    <div className="icon"><i className="fas fa-truck" /></div>
                </div>
            </div>
            <div className="col-lg-3">
                <div className="small-box bg-primary">
                    <div className="inner">
                        <h3>{kpi.inStockCount ?? 0}</h3>
                        <p>{trans.in_stock}</p>
                    </div>
                    <div className="icon"><i className="fas fa-warehouse" /></div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Status Badge
// ---------------------------------------------------------------------------

function StatusBadge({ status, trans }) {
    const cfg = STATUS_CONFIG[status] ?? { badge: 'badge-secondary', label: 'unknown' };
    return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? status}</span>;
}

// ---------------------------------------------------------------------------
// Status Filter
// ---------------------------------------------------------------------------

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
// Sort Icon
// ---------------------------------------------------------------------------

function SortIcon({ field, sortField, sortAsc }) {
    if (field !== sortField) return <i className="fas fa-sort text-muted ml-1" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

// ---------------------------------------------------------------------------
// Serial Numbers Table
// ---------------------------------------------------------------------------

function SerialNumbersTable({ items, sortField, sortAsc, onSort, trans }) {
    const cols = [
        { id: 'serial_number', label: trans.serial_number, sortField: 'serial_number' },
        { id: 'product',       label: trans.product,       sortField: null },
        { id: 'order',         label: trans.order,         sortField: null },
        { id: 'task',          label: trans.task,          sortField: null },
        { id: 'receipt',       label: trans.po_receipt,    sortField: null },
        { id: 'status',        label: trans.status,        sortField: 'status' },
        { id: 'created_at',    label: trans.created_at,    sortField: 'created_at' },
        { id: 'trace',         label: trans.trace,         sortField: null },
    ];

    return (
        <div className="table-responsive">
            <table className="table table-hover table-sm">
                <thead>
                    <tr>
                        {cols.map(col => (
                            <th
                                key={col.id}
                                style={col.sortField ? { cursor: 'pointer', whiteSpace: 'nowrap', userSelect: 'none' } : { whiteSpace: 'nowrap' }}
                                onClick={col.sortField ? () => onSort(col.sortField) : undefined}
                            >
                                {col.label}
                                {col.sortField && (
                                    <SortIcon field={col.sortField} sortField={sortField} sortAsc={sortAsc} />
                                )}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {items.length === 0 && (
                        <tr>
                            <td colSpan={cols.length} className="text-center text-muted py-3">
                                {trans.no_results}
                            </td>
                        </tr>
                    )}
                    {items.map(sn => (
                        <tr key={sn.id}>
                            <td><code>{sn.serial_number}</code></td>
                            <td>
                                {sn.product ? (
                                    <span>
                                        <a href={sn.product.url} className="btn btn-xs btn-info mr-1">
                                            <i className="fas fa-eye" />
                                        </a>
                                        {sn.product.label}
                                    </span>
                                ) : '—'}
                            </td>
                            <td>
                                {sn.order ? (
                                    <span>
                                        <a href={sn.order.url} className="btn btn-xs btn-primary mr-1">
                                            <i className="fas fa-folder" />
                                        </a>
                                        <code>{sn.order.code}</code>
                                    </span>
                                ) : '—'}
                            </td>
                            <td>
                                {sn.task ? (
                                    <a href={sn.task.url} className="btn btn-xs btn-success">
                                        {trans.view}
                                    </a>
                                ) : '—'}
                            </td>
                            <td>
                                {sn.receipt ? (
                                    <a href={sn.receipt.url} className="btn btn-xs btn-primary">
                                        <i className="fas fa-folder mr-1" />
                                        <code>{sn.receipt.code}</code>
                                    </a>
                                ) : '—'}
                            </td>
                            <td>
                                <StatusBadge status={sn.status} trans={trans} />
                            </td>
                            <td style={{ whiteSpace: 'nowrap' }}>{sn.created_at}</td>
                            <td>
                                <a href={sn.trace_url} className="btn btn-xs btn-primary">
                                    {trans.trace}
                                </a>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

function Pagination({ meta, onPageChange }) {
    if (!meta || meta.last_page <= 1) return null;

    const pages = [];
    const current = meta.current_page;
    const last    = meta.last_page;

    for (let p = 1; p <= last; p++) {
        if (p === 1 || p === last || (p >= current - 2 && p <= current + 2)) {
            pages.push(p);
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...');
        }
    }

    return (
        <nav>
            <ul className="pagination pagination-sm justify-content-end">
                <li className={`page-item ${current === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPageChange(current - 1)}>«</button>
                </li>
                {pages.map((p, i) => (
                    p === '...'
                        ? <li key={`dots-${i}`} className="page-item disabled"><span className="page-link">…</span></li>
                        : <li key={p} className={`page-item ${p === current ? 'active' : ''}`}>
                            <button className="page-link" onClick={() => onPageChange(p)}>{p}</button>
                        </li>
                ))}
                <li className={`page-item ${current === last ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPageChange(current + 1)}>»</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// Dashboard Tab
// ---------------------------------------------------------------------------

function DashboardTab({ kpi, trans }) {
    return (
        <div>
            <KPICards kpi={kpi} trans={trans} />
            <div className="row">
                {ALL_STATUSES.map(id => {
                    const cfg   = STATUS_CONFIG[id];
                    const count = kpi.byStatus?.[id] ?? 0;
                    const total = kpi.totalCount ?? 1;
                    const pct   = total > 0 ? Math.round(count / total * 100) : 0;
                    return (
                        <div key={id} className="col-md-4 mb-3">
                            <div className="card">
                                <div className="card-body py-2">
                                    <div className="d-flex justify-content-between align-items-center mb-1">
                                        <span className={`badge ${cfg.badge}`}>{trans[cfg.label]}</span>
                                        <strong>{count}</strong>
                                    </div>
                                    <div className="progress" style={{ height: 6 }}>
                                        <div
                                            className={`progress-bar ${cfg.badge.replace('badge-', 'bg-')}`}
                                            style={{ width: `${pct}%` }}
                                        />
                                    </div>
                                    <small className="text-muted">{pct}%</small>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// List Tab
// ---------------------------------------------------------------------------

const LS_KEY = 'serial_numbers_list_filters';

function loadFilters() {
    try { return JSON.parse(localStorage.getItem(LS_KEY)); } catch { return null; }
}
function saveFilters(f) {
    try { localStorage.setItem(LS_KEY, JSON.stringify(f)); } catch {}
}

function ListTab({ endpoints, trans, productId = null }) {
    const saved = productId ? null : loadFilters();

    const [items, setItems]       = useState([]);
    const [meta, setMeta]         = useState(null);
    const [loading, setLoading]   = useState(false);
    const [search, setSearch]     = useState(saved?.search    ?? '');
    const [statuses, setStatuses] = useState(saved?.statuses  ?? ALL_STATUSES);
    const [sortField, setSortField] = useState(saved?.sortField ?? 'created_at');
    const [sortAsc, setSortAsc]   = useState(saved?.sortAsc    ?? false);
    const [page, setPage]         = useState(1);

    const searchTimer = useRef(null);

    const fetchItems = useCallback((opts = {}) => {
        setLoading(true);
        const params = new URLSearchParams({
            search: opts.search    ?? search,
            sort:   opts.sort      ?? sortField,
            asc:    (opts.asc      ?? sortAsc) ? '1' : '0',
            page:   String(opts.page ?? page),
        });
        (opts.statuses ?? statuses).forEach(s => params.append('statuses[]', s));
        if (productId) params.set('product_id', productId);

        apiFetch(`${endpoints.list}?${params}`)
            .then(data => { setItems(data.data); setMeta(data.meta); })
            .finally(() => setLoading(false));
    }, [search, sortField, sortAsc, page, statuses, endpoints.list]);

    useEffect(() => { fetchItems(); }, [sortField, sortAsc, page, statuses]);

    useEffect(() => {
        saveFilters({ search, statuses, sortField, sortAsc });
    }, [search, statuses, sortField, sortAsc]);

    const handleSearch = (val) => {
        setSearch(val);
        clearTimeout(searchTimer.current);
        searchTimer.current = setTimeout(() => {
            setPage(1);
            fetchItems({ search: val, page: 1 });
        }, 400);
    };

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
            {/* Toolbar */}
            <div className="d-flex flex-wrap align-items-center mb-2" style={{ gap: '0.5rem' }}>
                <div className="input-group input-group-sm flex-shrink-0" style={{ width: 220 }}>
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
                <StatusFilter selected={statuses} onChange={handleStatusChange} trans={trans} />
                <div className="flex-grow-1" />
                {meta && (
                    <small className="text-muted">{meta.total} {trans.total_serial_numbers}</small>
                )}
            </div>

            {loading && (
                <div className="text-center py-3">
                    <i className="fas fa-spinner fa-spin text-secondary fa-lg" />
                </div>
            )}

            {!loading && (
                <SerialNumbersTable
                    items={items}
                    sortField={sortField}
                    sortAsc={sortAsc}
                    onSort={handleSort}
                    trans={trans}
                />
            )}

            <Pagination meta={meta} onPageChange={setPage} />
        </div>
    );
}

// ---------------------------------------------------------------------------
// Root Component
// ---------------------------------------------------------------------------

export default function SerialNumbersIndex({ kpi, endpoints, trans, productId = null }) {
    const [activeTab, setActiveTab] = useState('dashboard');

    if (productId) {
        return (
            <ListTab endpoints={endpoints} trans={trans} productId={productId} />
        );
    }

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
                            {trans.serial_numbers_list}
                        </a>
                    </li>
                </ul>
            </div>
            <div className="card-body p-3">
                {activeTab === 'dashboard' && (
                    <DashboardTab kpi={kpi} trans={trans} />
                )}
                {activeTab === 'list' && (
                    <ListTab endpoints={endpoints} trans={trans} />
                )}
            </div>
        </div>
    );
}
