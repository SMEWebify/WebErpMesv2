import React, { useState, useEffect, useRef, useCallback } from 'react';
import { formatQty, formatDate, formatCurrency } from '../utils';
import { DataTable, Pagination, MobileFilters } from './table';

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


// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function lsGet(key, fallback) {
    try { const v = localStorage.getItem(key); return v ? JSON.parse(v) : fallback; } catch { return fallback; }
}
function lsSet(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); } catch {}
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
                                {' — '}<small>{formatQty(item.qty)}</small>
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
// Table — colonnes déclarées, rendu par le DataTable partagé
// ---------------------------------------------------------------------------

const lineAmount = l => (Number(l.selling_price) || 0) * (Number(l.qty) || 0) * (1 - (Number(l.discount) || 0) / 100);

function deliveryStatusCell(line, trans) {
    const cfg = DELIVERY_STATUS_CONFIG[line.delivery_status];
    if (!cfg) return '—';
    const label = `${trans[cfg.label] ?? cfg.label}${line.delivered_qty > 0 ? ` (${formatQty(line.delivered_qty)})` : ''}`;
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

function invoiceStatusCell(line, trans) {
    if (line.order_type === 2) return <span className="text-muted">—</span>;
    const cfg = INVOICE_STATUS_CONFIG[line.invoice_status];
    if (!cfg) return '—';
    const label = `${trans[cfg.label] ?? cfg.label}${line.invoiced_qty > 0 ? ` (${formatQty(line.invoiced_qty)})` : ''}`;
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

function orderLineColumns(trans, currency, locale) {
    return [
        { key: 'order_code',      label: trans.order,
          render: l => <strong>{l.order_code ?? l.orders_id}</strong>, filterValue: l => l.order_code, filter: 'text',
          mobile: 'subtitle', mobileOrder: 1, mobileRender: l => l.order_code ?? l.orders_id },
        { key: 'ordre',           label: trans.ordre },
        { key: 'code',            label: trans.code,
          render: l => <code>{l.code}</code>, filter: 'text', mobile: 'subtitle', mobileRender: l => l.code },
        { key: 'product',         label: trans.product,
          render: l => (l.product_url ? <a href={l.product_url} className="btn btn-xs btn-info"><i className="fas fa-eye" /></a> : '—') },
        { key: 'label',           label: trans.label,           sortable: true, bold: true, nowrap: false,
          filter: 'text', mobile: 'title' },
        { key: 'qty',             label: trans.qty,             sortable: true, align: 'right', nowrap: false,
          render: l => formatQty(l.qty) },
        { key: 'unit_label',      label: trans.unit,
          render: l => l.unit_label ?? '—' },
        { key: 'selling_price',   label: trans.price,           sortable: true, align: 'right', nowrap: false,
          render: l => (
              <strong>
                  {formatCurrency(l.selling_price, currency, locale)}
                  {l.use_calculated_price && <i className="fas fa-calculator text-warning ml-1" title={trans.calculated_price} />}
              </strong>
          ),
          total: { value: lineAmount, format: sum => formatCurrency(sum, currency, locale) },
          mobile: 'amount', mobileRender: l => formatCurrency(lineAmount(l), currency, locale) },
        { key: 'discount',        label: trans.discount,        align: 'right', nowrap: false,
          render: l => (l.discount ? `${l.discount}%` : '—') },
        { key: 'vat_label',       label: trans.vat,
          render: l => l.vat_label ?? '—' },
        { key: 'delivery_date',   label: trans.delivery_date,   sortable: true,
          render: l => formatDate(l.delivery_date, locale), filter: 'date' },
        { key: 'tasks_status',    label: trans.tasks_status,    sortable: true,
          render: l => {
              const cfg = TASKS_STATUS_CONFIG[l.tasks_status];
              return cfg ? <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span> : '—';
          } },
        { key: 'delivery_status', label: trans.delivery_status, sortable: true,
          render: l => deliveryStatusCell(l, trans),
          mobile: 'badge', mobileRender: l => {
              const cfg = DELIVERY_STATUS_CONFIG[l.delivery_status];
              return cfg ? <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span> : null;
          } },
        { key: 'invoice_status',  label: trans.invoice_status,  sortable: true,
          render: l => invoiceStatusCell(l, trans) },
        { key: 'actions',         label: trans.action,          hideable: false,
          render: l => (
              <div className="btn-group btn-group-sm">
                  <a href={l.order_url} className="btn btn-xs btn-info" title={trans.view_order}>
                      <i className="fas fa-eye" />
                  </a>
                  <a href={l.task_url} className="btn btn-success btn-xs" title={trans.tasks}>
                      <i className="fas fa-list" />
                      {' '}({l.task_count}) ({l.sub_assembly_count})
                  </a>
              </div>
          ) },
    ];
}

// Les filtres de colonne restent enregistrés à plat dans order_lines_list_filters
// ({ code, label, delivery_date_from, delivery_date_to }), comme avant la migration.
const toTableFilters = f => ({
    ...f,
    delivery_date: { from: f.delivery_date_from ?? '', to: f.delivery_date_to ?? '' },
});
function fromTableFilters(next) {
    const { delivery_date: range, ...rest } = next;
    return { ...rest, delivery_date_from: range?.from ?? '', delivery_date_to: range?.to ?? '' };
}

function OrderLinesTable({ lines, sortField, sortAsc, onSort, trans, currency, locale, colFilters, onColFiltersChange }) {
    return (
        <DataTable
            rows={lines}
            columns={orderLineColumns(trans, currency, locale)}
            trans={trans}
            sortField={sortField}
            sortAsc={sortAsc}
            onSort={onSort}
            storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
            unsortableCursor="grab"
            colFilters={toTableFilters(colFilters)}
            onColFiltersChange={next => onColFiltersChange(fromTableFilters(next))}
            actionsColumn={false}
            rowHref={l => l.order_url}
            mobileActions={l => (
                <a href={l.task_url} className="btn btn-outline-success" style={{ minHeight: 44, lineHeight: '30px' }}>
                    <i className="fas fa-list mr-1" />{trans.tasks} ({l.task_count})
                </a>
            )}
        />
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

    function handleSort(field) {
        if (sortField === field) setSortAsc(a => !a);
        else { setSortField(field); setSortAsc(true); }
        setPage(1);
    }

    function handleColFilters(next) {
        setColFilters(next);
        setPage(1);
    }


    return (
        <div className="card card-outline card-warning">
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

                    <MobileFilters count={deliveryStatuses.length} trans={trans}>
                        <DeliveryStatusFilter active={deliveryStatuses} onToggle={handleDeliveryStatusToggle} trans={trans} />
                    </MobileFilters>

                    <div className="flex-grow-1" />

                    {loading && <span className="text-muted"><i className="fas fa-spinner fa-spin mr-1" /></span>}
                    {meta && <small className="text-muted">{meta.total} lignes</small>}
                </div>

                <OrderLinesTable
                    lines={lines}
                    sortField={sortField}
                    sortAsc={sortAsc}
                    onSort={handleSort}
                    trans={trans}
                    currency={currency}
                    locale={locale}
                    colFilters={colFilters}
                    onColFiltersChange={handleColFilters}
                />

                <Pagination meta={meta} navClassName="mt-2" ulClassName="pagination pagination-sm m-0 flex-wrap" onPage={setPage} />
            </div>
        </div>
    );
}
