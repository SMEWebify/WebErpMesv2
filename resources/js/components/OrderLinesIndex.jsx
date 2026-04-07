import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const TASKS_STATUS_CONFIG = {
    1: { badge: 'badge-info',    label: 'no_task' },
    2: { badge: 'badge-warning', label: 'created' },
    3: { badge: 'badge-success', label: 'in_progress' },
    4: { badge: 'badge-danger',  label: 'finished_task' },
};

const DELIVERY_STATUS_CONFIG = {
    1: { badge: 'badge-info',    label: 'not_delivered' },
    2: { badge: 'badge-warning', label: 'partly_delivered' },
    3: { badge: 'badge-success', label: 'delivered' },
    4: { badge: 'badge-primary', label: 'delivered_without_dn' },
};

const INVOICE_STATUS_CONFIG = {
    1: { badge: 'badge-info',    label: 'not_invoiced' },
    2: { badge: 'badge-warning', label: 'partly_invoiced' },
    3: { badge: 'badge-success', label: 'invoiced' },
};

const LS_COL_ORDER   = 'order_lines_table_col_order';
const LS_HIDDEN_COLS = 'order_lines_table_hidden_cols';
const LS_FILTERS     = 'order_lines_list_filters';

const DEFAULT_COL_ORDER = ['order_code', 'ordre', 'code', 'product', 'label', 'qty', 'unit_label', 'selling_price', 'discount', 'vat_label', 'delivery_date', 'tasks_status', 'delivery_status', 'invoice_status', 'actions'];
const TEXT_FILTER_COLS  = new Set(['order_code', 'code', 'label']);
const DATE_RANGE_COLS   = new Set(['delivery_date']);

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
    } catch { return dateStr; }
}

function formatCurrency(amount, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style: 'currency', currency: currency || 'EUR',
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        }).format(amount);
    } catch { return `${Number(amount).toFixed(2)} ${currency ?? '€'}`; }
}

function lsGet(key, fallback) {
    try { const v = localStorage.getItem(key); return v ? JSON.parse(v) : fallback; } catch { return fallback; }
}
function lsSet(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); } catch {}
}

// ---------------------------------------------------------------------------
// SortIcon — same style as OrdersIndex
// ---------------------------------------------------------------------------

function SortIcon({ field, sortField, sortAsc }) {
    if (!field || field !== sortField) return <i className="fas fa-sort text-muted ml-1" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

// ---------------------------------------------------------------------------
// DeliveryStatusFilter — filtres par statut de livraison
// ---------------------------------------------------------------------------

function DeliveryStatusFilter({ active, onToggle, trans }) {
    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.25rem' }}>
            {Object.entries(DELIVERY_STATUS_CONFIG).map(([id, cfg]) => {
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
// LinesPopover — livraisons / factures associées
// ---------------------------------------------------------------------------

function LinesPopover({ items, badgeClass, badgeLabel }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        function onClick(e) { if (ref.current && !ref.current.contains(e.target)) setOpen(false); }
        document.addEventListener('mousedown', onClick);
        return () => document.removeEventListener('mousedown', onClick);
    }, [open]);

    if (!items || items.length === 0) return <span className={`badge ${badgeClass}`}>{badgeLabel}</span>;

    return (
        <span ref={ref} style={{ position: 'relative' }}>
            <span className={`badge ${badgeClass}`} style={{ cursor: 'pointer' }} onClick={() => setOpen(o => !o)}>
                {badgeLabel}
            </span>
            {open && (
                <div style={{
                    position: 'absolute', zIndex: 1050, top: '100%', left: 0, minWidth: 200,
                    background: '#fff', border: '1px solid #dee2e6', borderRadius: 4,
                    boxShadow: '0 4px 12px rgba(0,0,0,.15)', padding: '0.5rem',
                }}>
                    <ul className="list-unstyled mb-0">
                        {items.map(item => (
                            <li key={item.id} className="mb-1">
                                <a href={item.url} className="text-primary font-weight-bold">{item.code}</a>
                                {' — '}<small>{item.qty}</small>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </span>
    );
}

function ProgressBar({ value }) {
    const pct = Math.min(100, Math.max(0, value));
    return (
        <div className="progress mt-1" style={{ height: 5 }}>
            <div className="progress-bar bg-teal" style={{ width: `${pct}%` }} />
        </div>
    );
}

// ---------------------------------------------------------------------------
// Column definitions
// ---------------------------------------------------------------------------

function colDefs(trans) {
    return {
        order_code:      { label: trans.order },
        ordre:           { label: trans.ordre },
        code:            { label: trans.code },
        product:         { label: trans.product },
        label:           { label: trans.label,           sortField: 'label',           bold: true },
        qty:             { label: trans.qty,              sortField: 'qty',             align: 'right' },
        unit_label:      { label: trans.unit },
        selling_price:   { label: trans.price,            sortField: 'selling_price',   align: 'right' },
        discount:        { label: trans.discount,                                       align: 'right' },
        vat_label:       { label: trans.vat },
        delivery_date:   { label: trans.delivery_date,   sortField: 'delivery_date' },
        tasks_status:    { label: trans.tasks_status,    sortField: 'tasks_status' },
        delivery_status: { label: trans.delivery_status, sortField: 'delivery_status' },
        invoice_status:  { label: trans.invoice_status,  sortField: 'invoice_status' },
        actions:         { label: trans.action },
    };
}

// ---------------------------------------------------------------------------
// Table
// ---------------------------------------------------------------------------

function OrderLinesTable({ lines, colOrder, hiddenCols, sortField, sortAsc, onSort, onHideCol, colDef, trans, currency, locale, colFilters, onColFilter }) {
    const [dragOver, setDragOver] = useState(null);
    const dragCol = useRef(null);

    const visibleCols = colOrder.filter(c => !hiddenCols.includes(c));

    function handleDragStart(col) { dragCol.current = col; }
    function handleDrop(col) {
        if (!dragCol.current || dragCol.current === col) return;
        const order = [...colOrder];
        const from  = order.indexOf(dragCol.current);
        const to    = order.indexOf(col);
        order.splice(from, 1);
        order.splice(to, 0, dragCol.current);
        dragCol.current = null;
        setDragOver(null);
        onSort('__reorder__', order);
    }

    function renderCell(col, line) {
        switch (col) {
            case 'order_code':
                return <strong>{line.order_code ?? line.orders_id}</strong>;
            case 'ordre':
                return line.ordre;
            case 'code':
                return <code>{line.code}</code>;
            case 'product':
                return line.product_url
                    ? <a href={line.product_url} className="btn btn-xs btn-outline-secondary"><i className="fas fa-eye" /></a>
                    : '—';
            case 'label':
                return line.label;
            case 'qty':
                return line.qty;
            case 'unit_label':
                return line.unit_label ?? '—';
            case 'selling_price':
                return (
                    <strong>
                        {formatCurrency(line.selling_price, currency, locale)}
                        {line.use_calculated_price && <i className="fas fa-calculator text-warning ml-1" title={trans.calculated_price} />}
                    </strong>
                );
            case 'discount':
                return line.discount ? `${line.discount}%` : '—';
            case 'vat_label':
                return line.vat_label ?? '—';
            case 'delivery_date':
                return formatDate(line.delivery_date, locale);
            case 'tasks_status': {
                const cfg = TASKS_STATUS_CONFIG[line.tasks_status];
                if (!cfg) return '—';
                return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span>;
            }
            case 'delivery_status': {
                const cfg = DELIVERY_STATUS_CONFIG[line.delivery_status];
                if (!cfg) return '—';
                const label = `${trans[cfg.label] ?? cfg.label}${line.delivered_qty > 0 ? ` (${line.delivered_qty})` : ''}`;
                if (line.delivery_status === 1 || line.delivery_status === 4) {
                    return <span className={`badge ${cfg.badge}`}>{label}</span>;
                }
                return (
                    <>
                        <LinesPopover
                            items={(line.delivery_lines ?? []).map(dl => ({ id: dl.id, code: dl.delivery_code, qty: dl.qty, url: dl.delivery_url }))}
                            badgeClass={cfg.badge}
                            badgeLabel={label}
                        />
                        <ProgressBar value={line.delivery_progress} />
                    </>
                );
            }
            case 'invoice_status': {
                if (line.order_type === 2) return <span className="text-muted">—</span>;
                const cfg = INVOICE_STATUS_CONFIG[line.invoice_status];
                if (!cfg) return '—';
                const label = `${trans[cfg.label] ?? cfg.label}${line.invoiced_qty > 0 ? ` (${line.invoiced_qty})` : ''}`;
                if (line.invoice_status === 1) {
                    return <span className={`badge ${cfg.badge}`}>{label}</span>;
                }
                return (
                    <>
                        <LinesPopover
                            items={(line.invoice_lines ?? []).map(il => ({ id: il.id, code: il.invoice_code, qty: il.qty, url: il.invoice_url }))}
                            badgeClass={cfg.badge}
                            badgeLabel={label}
                        />
                        <ProgressBar value={line.invoice_progress} />
                    </>
                );
            }
            case 'actions':
                return (
                    <div className="btn-group btn-group-sm">
                        <a href={line.order_url} className="btn btn-info btn-xs" title={trans.view_order}>
                            <i className="fas fa-eye" />
                        </a>
                        <a href={line.task_url} className="btn btn-success btn-xs" title={trans.tasks}>
                            <i className="fas fa-list" />
                            {' '}({line.task_count}) ({line.sub_assembly_count})
                        </a>
                    </div>
                );
            default:
                return null;
        }
    }

    const totalAmount = lines.reduce((sum, l) => {
        return sum + l.selling_price * l.qty * (1 - (l.discount ?? 0) / 100);
    }, 0);

    return (
        <>
            {hiddenCols.length > 0 && (
                <div className="mb-2">
                    {hiddenCols.map(c => (
                        <button key={c} className="btn btn-xs btn-outline-secondary mr-1 mb-1" onClick={() => onHideCol(c, false)}>
                            + {colDef[c]?.label ?? c}
                        </button>
                    ))}
                </div>
            )}
            <div className="table-responsive">
                <table className="table table-hover table-sm">
                    <thead>
                        <tr>
                            {visibleCols.map(col => {
                                const def = colDef[col] ?? {};
                                const sf  = def.sortField;
                                return (
                                    <th
                                        key={col}
                                        draggable
                                        onDragStart={() => handleDragStart(col)}
                                        onDragOver={e => { e.preventDefault(); setDragOver(col); }}
                                        onDrop={() => handleDrop(col)}
                                        onDragEnd={() => setDragOver(null)}
                                        onClick={() => sf && onSort(sf)}
                                        style={{
                                            cursor: sf ? 'pointer' : 'grab',
                                            background: dragOver === col ? '#edf5ff' : undefined,
                                            whiteSpace: 'nowrap',
                                            userSelect: 'none',
                                        }}
                                        className={def.align === 'right' ? 'text-right' : ''}
                                    >
                                        <i className="fas fa-grip-vertical text-muted mr-1" style={{ fontSize: '0.65rem', opacity: 0.4 }} />
                                        {def.label}
                                        <SortIcon field={sf} sortField={sortField} sortAsc={sortAsc} />
                                        {col !== 'actions' && (
                                            <span
                                                role="button"
                                                style={{ marginLeft: 6, opacity: 0.4, fontSize: '0.8rem', lineHeight: 1 }}
                                                className="text-danger"
                                                onClick={e => { e.stopPropagation(); onHideCol(col, true); }}
                                            >×</span>
                                        )}
                                    </th>
                                );
                            })}
                        </tr>
                        <tr>
                            {visibleCols.map(col => (
                                <th key={col} className="p-1">
                                    {TEXT_FILTER_COLS.has(col) && (
                                        <input
                                            className="form-control form-control-sm"
                                            placeholder="⌕"
                                            value={colFilters[col] ?? ''}
                                            onChange={e => onColFilter(col, e.target.value)}
                                        />
                                    )}
                                    {DATE_RANGE_COLS.has(col) && (
                                        <div className="d-flex" style={{ gap: 2 }}>
                                            <input type="date" className="form-control form-control-sm" style={{ minWidth: 0 }}
                                                value={colFilters[col + '_from'] ?? ''}
                                                onChange={e => onColFilter(col + '_from', e.target.value)} />
                                            <input type="date" className="form-control form-control-sm" style={{ minWidth: 0 }}
                                                value={colFilters[col + '_to'] ?? ''}
                                                onChange={e => onColFilter(col + '_to', e.target.value)} />
                                        </div>
                                    )}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {lines.length === 0 && (
                            <tr><td colSpan={visibleCols.length} className="text-center text-muted py-4">{trans.no_results}</td></tr>
                        )}
                        {lines.map(line => (
                            <tr key={line.id}>
                                {visibleCols.map(col => (
                                    <td key={col} className={(colDef[col]?.align === 'right' ? 'text-right ' : '') + (colDef[col]?.bold ? 'font-weight-bold ' : '')}>
                                        {renderCell(col, line)}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                    <tfoot>
                        <tr className="font-weight-bold">
                            {visibleCols.map((col, i) => (
                                <td key={col} className={colDef[col]?.align === 'right' ? 'text-right' : ''}>
                                    {col === 'selling_price'
                                        ? <strong>{formatCurrency(totalAmount, currency, locale)}</strong>
                                        : (i === 0 ? trans.total : '')}
                                </td>
                            ))}
                        </tr>
                    </tfoot>
                </table>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function OrderLinesIndex({ endpoints, trans }) {
    const currency = trans.currency ?? 'EUR';
    const locale   = trans.locale   ?? 'fr-FR';

    const savedFilters = lsGet(LS_FILTERS, {});
    const [search,          setSearch]          = useState(savedFilters.search          ?? '');
    const [deliveryStatuses, setDeliveryStatuses] = useState(savedFilters.deliveryStatuses ?? []);
    const [sortField,       setSortField]       = useState(savedFilters.sortField       ?? 'label');
    const [sortAsc,         setSortAsc]         = useState(savedFilters.sortAsc         ?? true);
    const [colFilters,      setColFilters]      = useState(savedFilters.colFilters      ?? {});
    const [page,            setPage]            = useState(1);
    const [lines,           setLines]           = useState([]);
    const [meta,            setMeta]            = useState(null);
    const [loading,         setLoading]         = useState(false);

    const [colOrder,   setColOrder]   = useState(() => lsGet(LS_COL_ORDER, DEFAULT_COL_ORDER));
    const [hiddenCols, setHiddenCols] = useState(() => lsGet(LS_HIDDEN_COLS, []));

    const defs = colDefs(trans);

    useEffect(() => {
        lsSet(LS_FILTERS, { search, deliveryStatuses, sortField, sortAsc, colFilters });
    }, [search, deliveryStatuses, sortField, sortAsc, colFilters]);

    const fetchLines = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({ search, sort: sortField, asc: sortAsc ? 1 : 0, page });
        deliveryStatuses.forEach(s => params.append('delivery_statuses[]', s));
        fetch(`${endpoints.list}?${params}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(r => r.json())
            .then(json => { setLines(json.data ?? []); setMeta(json.meta ?? null); })
            .finally(() => setLoading(false));
    }, [endpoints.list, search, deliveryStatuses, sortField, sortAsc, page]);

    useEffect(() => { fetchLines(); }, [fetchLines]);

    const searchTimer = useRef(null);
    function handleSearch(val) {
        setSearch(val);
        clearTimeout(searchTimer.current);
        searchTimer.current = setTimeout(() => setPage(1), 300);
    }

    function handleDeliveryStatusToggle(sid) {
        setDeliveryStatuses(prev => prev.includes(sid) ? prev.filter(s => s !== sid) : [...prev, sid]);
        setPage(1);
    }

    function handleSort(field, newOrder) {
        if (field === '__reorder__') {
            setColOrder(newOrder);
            lsSet(LS_COL_ORDER, newOrder);
            return;
        }
        if (sortField === field) setSortAsc(a => !a);
        else { setSortField(field); setSortAsc(true); }
        setPage(1);
    }

    function handleHideCol(col, hide) {
        setHiddenCols(prev => {
            const next = hide ? [...prev, col] : prev.filter(c => c !== col);
            lsSet(LS_HIDDEN_COLS, next);
            return next;
        });
    }

    function handleColFilter(col, value) {
        setColFilters(prev => ({ ...prev, [col]: value }));
        setPage(1);
    }

    const filtered = lines.filter(line => {
        for (const col of TEXT_FILTER_COLS) {
            const f = (colFilters[col] ?? '').toLowerCase();
            if (!f) continue;
            const val = col === 'order_code' ? (line.order_code ?? '') : (line[col] ?? '');
            if (!String(val).toLowerCase().includes(f)) return false;
        }
        for (const col of DATE_RANGE_COLS) {
            const from = colFilters[col + '_from'];
            const to   = colFilters[col + '_to'];
            if (from && line[col] && line[col] < from) return false;
            if (to   && line[col] && line[col] > to)   return false;
        }
        return true;
    });

    return (
        <div className="card">
            <div className="card-body">
                {/* Search + delivery status filters */}
                <div className="d-flex flex-wrap align-items-center mb-2" style={{ gap: '0.5rem' }}>
                    <div className="input-group" style={{ maxWidth: 320 }}>
                        <input
                            className="form-control"
                            placeholder={trans.search}
                            value={search}
                            onChange={e => handleSearch(e.target.value)}
                        />
                        {search && (
                            <div className="input-group-append">
                                <button className="btn btn-outline-secondary" onClick={() => handleSearch('')}>×</button>
                            </div>
                        )}
                    </div>

                    <DeliveryStatusFilter active={deliveryStatuses} onToggle={handleDeliveryStatusToggle} trans={trans} />

                    <div className="flex-grow-1" />

                    {loading && <span className="text-muted"><i className="fas fa-spinner fa-spin mr-1" /></span>}
                    {meta && <small className="text-muted">{meta.total} lignes</small>}
                </div>

                <OrderLinesTable
                    lines={filtered}
                    colOrder={colOrder}
                    hiddenCols={hiddenCols}
                    sortField={sortField}
                    sortAsc={sortAsc}
                    onSort={handleSort}
                    onHideCol={handleHideCol}
                    colDef={defs}
                    trans={trans}
                    currency={currency}
                    locale={locale}
                    colFilters={colFilters}
                    onColFilter={handleColFilter}
                />

                {meta && meta.last_page > 1 && (
                    <nav className="mt-2">
                        <ul className="pagination pagination-sm m-0 flex-wrap">
                            <li className={`page-item ${meta.current_page === 1 ? 'disabled' : ''}`}>
                                <button className="page-link" onClick={() => setPage(p => p - 1)}>&laquo;</button>
                            </li>
                            {Array.from({ length: meta.last_page }, (_, i) => i + 1).map(p => (
                                <li key={p} className={`page-item ${p === meta.current_page ? 'active' : ''}`}>
                                    <button className="page-link" onClick={() => setPage(p)}>{p}</button>
                                </li>
                            ))}
                            <li className={`page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}`}>
                                <button className="page-link" onClick={() => setPage(p => p + 1)}>&raquo;</button>
                            </li>
                        </ul>
                    </nav>
                )}
            </div>
        </div>
    );
}
