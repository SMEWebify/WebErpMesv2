import React, { useState, useEffect, useRef, useCallback } from 'react';
import { formatQty } from '../utils';
import { DataTable, Pagination, StatusFilter } from './table';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-info',      label: 'open' },
    2: { badge: 'badge-warning',   label: 'send' },
    3: { badge: 'badge-success',   label: 'win' },
    4: { badge: 'badge-danger',    label: 'lost' },
    5: { badge: 'badge-secondary', label: 'closed' },
    6: { badge: 'badge-primary',   label: 'obsolete' },
};

const LS_COL_ORDER   = 'quote_lines_table_col_order';
const LS_HIDDEN_COLS = 'quote_lines_table_hidden_cols';
const LS_FILTERS     = 'quote_lines_list_filters';


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
// Table — colonnes déclarées, rendu par le DataTable partagé
// ---------------------------------------------------------------------------

const lineAmount = l => (Number(l.selling_price) || 0) * (Number(l.qty) || 0) * (1 - (Number(l.discount) || 0) / 100);

function quoteLineColumns(trans, currency, locale) {
    return [
        { key: 'quote_code',    label: trans.quote,         sortable: 'quotes_id',
          render: l => <strong>{l.quote_code ?? l.quotes_id}</strong>, filterValue: l => l.quote_code, filter: 'text',
          mobile: 'subtitle', mobileOrder: 1, mobileRender: l => l.quote_code ?? l.quotes_id },
        { key: 'ordre',         label: trans.ordre,         sortable: true },
        { key: 'code',          label: trans.code,          sortable: true,
          render: l => <code>{l.code}</code>, filter: 'text', mobile: 'subtitle', mobileRender: l => l.code },
        { key: 'product',       label: trans.product,
          render: l => (l.product_url ? <a href={l.product_url} className="btn btn-xs btn-info"><i className="fas fa-eye" /></a> : '—') },
        { key: 'label',         label: trans.label,         sortable: true, bold: true, nowrap: false,
          filter: 'text', mobile: 'title' },
        { key: 'qty',           label: trans.qty,           sortable: true, align: 'right', nowrap: false,
          render: l => formatQty(l.qty) },
        { key: 'unit_label',    label: trans.unit,
          render: l => l.unit_label ?? '—' },
        { key: 'selling_price', label: trans.price,         sortable: true, align: 'right', nowrap: false,
          render: l => (
              <strong>
                  {formatCurrency(l.selling_price, currency, locale)}
                  {l.use_calculated_price && <i className="fas fa-calculator text-warning ml-1" title={trans.calculated_price} />}
              </strong>
          ),
          total: { value: lineAmount, format: sum => formatCurrency(sum, currency, locale) },
          mobile: 'amount', mobileRender: l => formatCurrency(lineAmount(l), currency, locale) },
        { key: 'discount',      label: trans.discount,      align: 'right', nowrap: false,
          render: l => (l.discount ? `${l.discount}%` : '—') },
        { key: 'vat_label',     label: trans.vat,
          render: l => l.vat_label ?? '—' },
        { key: 'delivery_date', label: trans.delivery_date, sortable: true,
          render: l => formatDate(l.delivery_date, locale), filter: 'date' },
        { key: 'statu',         label: trans.status,
          render: l => {
              const cfg = STATUS_CONFIG[l.statu];
              if (!cfg) return '—';
              return (
                  <>
                      <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span>
                      {l.statu === 3 && l.order_url && (
                          <a href={l.order_url} className="badge badge-primary ml-1" target="_blank" rel="noreferrer">
                              <i className="fas fa-file-alt" /> {l.order_code}
                          </a>
                      )}
                  </>
              );
          },
          mobile: 'badge', mobileRender: l => {
              const cfg = STATUS_CONFIG[l.statu];
              return cfg ? <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span> : null;
          } },
        { key: 'actions',       label: trans.action,        hideable: false,
          render: l => (
              <div className="btn-group btn-group-sm">
                  <a href={l.quote_url} className="btn btn-xs btn-info" title={trans.view_quote}>
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

// Les filtres de colonne restent enregistrés à plat dans quote_lines_list_filters
// ({ code, label, delivery_date_from, delivery_date_to }), comme avant la migration.
const toTableFilters = f => ({
    ...f,
    delivery_date: { from: f.delivery_date_from ?? '', to: f.delivery_date_to ?? '' },
});
function fromTableFilters(next) {
    const { delivery_date: range, ...rest } = next;
    return { ...rest, delivery_date_from: range?.from ?? '', delivery_date_to: range?.to ?? '' };
}

function QuoteLinesTable({ lines, sortField, sortAsc, onSort, trans, currency, locale, colFilters, onColFiltersChange }) {
    return (
        <DataTable
            rows={lines}
            columns={quoteLineColumns(trans, currency, locale)}
            trans={trans}
            sortField={sortField}
            sortAsc={sortAsc}
            onSort={onSort}
            storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
            unsortableCursor="grab"
            colFilters={toTableFilters(colFilters)}
            onColFiltersChange={next => onColFiltersChange(fromTableFilters(next))}
            actionsColumn={false}
            rowHref={l => l.quote_url}
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

export default function QuoteLinesIndex({ endpoints, trans }) {
    const currency = trans.currency ?? 'EUR';
    const locale   = trans.locale   ?? 'fr-FR';

    const savedFilters = lsGet(LS_FILTERS, {});
    const [search,     setSearch]     = useState(savedFilters.search     ?? '');
    const [statuses,   setStatuses]   = useState(savedFilters.statuses   ?? []);
    const [sortField,  setSortField]  = useState(savedFilters.sortField  ?? 'label');
    const [sortAsc,    setSortAsc]    = useState(savedFilters.sortAsc    ?? true);
    const [colFilters, setColFilters] = useState(savedFilters.colFilters ?? {});
    const [page,       setPage]       = useState(1);
    const [lines,      setLines]      = useState([]);
    const [meta,       setMeta]       = useState(null);
    const [loading,    setLoading]    = useState(false);

    useEffect(() => {
        lsSet(LS_FILTERS, { search, statuses, sortField, sortAsc, colFilters });
    }, [search, statuses, sortField, sortAsc, colFilters]);

    const fetchLines = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({ search, sort: sortField, asc: sortAsc ? 1 : 0, page });
        statuses.forEach(s => params.append('statuses[]', s));
        fetch(`${endpoints.list}?${params}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(r => r.json())
            .then(json => { setLines(json.data ?? []); setMeta(json.meta ?? null); })
            .finally(() => setLoading(false));
    }, [endpoints.list, search, statuses, sortField, sortAsc, page]);

    useEffect(() => { fetchLines(); }, [fetchLines]);

    // Debounce search → reset page
    const searchTimer = useRef(null);
    function handleSearch(val) {
        setSearch(val);
        clearTimeout(searchTimer.current);
        searchTimer.current = setTimeout(() => setPage(1), 300);
    }

    function handleStatusToggle(sid) {
        setStatuses(prev => prev.includes(sid) ? prev.filter(s => s !== sid) : [...prev, sid]);
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
        <div className="card">
            <div className="card-body">
                {/* Search + status filters */}
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

                    <StatusFilter config={STATUS_CONFIG} selected={statuses} onToggle={handleStatusToggle} trans={trans} />

                    <div className="flex-grow-1" />

                    {loading && <span className="text-muted"><i className="fas fa-spinner fa-spin mr-1" /></span>}
                    {meta && <small className="text-muted">{meta.total} lignes</small>}
                </div>

                <QuoteLinesTable
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
