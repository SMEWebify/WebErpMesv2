import React, { useCallback, useEffect, useRef, useState } from 'react';
import { DataTable, Pagination, useIndexTab } from '../table';
import { apiFetch } from '../../lib/http';
import PlanThumbnail from './PlanThumbnail.jsx';

const TABS = ['needs', 'draft', 'approved', 'in_progress', 'closed'];
const TAB_ICONS = {
    needs: 'fa-circle-dot', draft: 'fa-pen-ruler', approved: 'fa-circle-check',
    in_progress: 'fa-ruler-combined', closed: 'fa-flag-checkered',
};
const DOSSIER_TABS = ['in_progress', 'closed'];

const url = (template, id) => template.replace('__ID__', id);

function formatDate(value, locale) {
    if (!value) return '—';
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString(locale);
}

function ItemCell({ row }) {
    return (
        <div className="d-flex align-items-center gap-3">
            <div className="flex-grow-1" style={{ minWidth: 0 }}>
                <div className="fw-semibold">{row.product_code}</div>
                <div className="text-secondary small text-truncate">{row.product_label}</div>
            </div>
        </div>
    );
}

function needsColumns(trans, locale) {
    return [
        { key: 'item', label: trans.item, hideable: false, render: r => <ItemCell row={r} />,
          mobile: 'title', mobileRender: r => `${r.product_code} — ${r.product_label ?? ''}`,
          filter: 'text', filterValue: r => `${r.product_code} ${r.product_label ?? ''}` },
        { key: 'plan', label: trans.plan, render: r => <PlanThumbnail file={r.file} title={r.file?.name ?? trans.no_plan_file} />, mobile: 'hidden' },
        { key: 'revision', label: trans.revision, render: r => r.revision ?? '—', mobile: 'badge' },
        { key: 'company', label: trans.customer, filter: 'text',
          render: r => (
              <div>
                  <div className="fw-semibold">{r.company}</div>
                  <div className="small text-secondary">
                      {r.order_code}{r.orders_count > 1 ? ` (+${r.orders_count - 1})` : ''}
                  </div>
              </div>
          ),
          mobile: 'subtitle', mobileRender: r => `${r.company} · ${r.order_code}` },
        { key: 'needed_at', label: trans.needed_at, nowrap: true, filter: 'date',
          render: r => formatDate(r.needed_at, locale), mobile: 'subtitle', mobileRender: r => formatDate(r.needed_at, locale) },
    ];
}

function planColumns(tab, trans, locale) {
    return [
        { key: 'item', label: trans.item, hideable: false, render: r => <ItemCell row={r} />,
          mobile: 'title', mobileRender: r => `${r.product_code} — ${r.product_label ?? ''}`,
          filter: 'text', filterValue: r => `${r.product_code} ${r.product_label ?? ''}` },
        { key: 'plan', label: trans.plan, render: r => <PlanThumbnail url={r.thumbnail_url} file={r.file} title={r.file?.name ?? ''} />, mobile: 'hidden' },
        { key: 'revision', label: trans.revision, render: r => r.revision ?? '—', mobile: 'badge' },
        { key: 'points', label: trans.characteristics,
          render: r => (
              <span>
                  {r.points_count ?? 0}
                  {r.critical_count > 0 && <span className="badge text-bg-warning ms-2">{r.critical_count} {trans.critical}</span>}
              </span>
          ),
          mobile: 'subtitle', mobileRender: r => `${r.points_count ?? 0} ${trans.characteristics}` },
        tab === 'approved'
            ? { key: 'approved_at', label: trans.approved_on, nowrap: true, filter: 'date', filterValue: r => r.approved_at,
                render: r => <span>{formatDate(r.approved_at, locale)}<div className="small text-secondary">{r.approved_by}</div></span>,
                mobile: 'subtitle', mobileRender: r => formatDate(r.approved_at, locale) }
            : { key: 'created_at', label: trans.created_on, nowrap: true, filter: 'date', filterValue: r => r.created_at,
                render: r => <span>{formatDate(r.created_at, locale)}<div className="small text-secondary">{r.created_by}</div></span>,
                mobile: 'subtitle', mobileRender: r => formatDate(r.created_at, locale) },
    ];
}

/** Dossiers de contrôle tenus depuis l'écran tâche (en cours / terminés). */
function dossierColumns(tab, trans, locale) {
    return [
        { key: 'item', label: trans.item, hideable: false, render: r => <ItemCell row={r} />,
          mobile: 'title', mobileRender: r => `${r.product_code} — ${r.product_label ?? ''}`,
          filter: 'text', filterValue: r => `${r.product_code} ${r.product_label ?? ''}` },
        { key: 'plan', label: trans.plan, render: r => <PlanThumbnail url={r.thumbnail_url} file={r.file} title={r.code} />, mobile: 'hidden' },
        { key: 'revision', label: trans.revision, render: r => r.revision ?? '—', mobile: 'badge' },
        { key: 'company', label: trans.customer, filter: 'text',
          render: r => <div><div className="fw-semibold">{r.company}</div><div className="small text-secondary">{r.order_code}</div></div>,
          mobile: 'subtitle', mobileRender: r => `${r.company ?? ''} · ${r.order_code ?? ''}` },
        { key: 'kind', label: trans.status,
          render: r => <span className={`badge ${r.is_fai ? 'text-bg-warning' : 'text-bg-secondary'}`}>{r.is_fai ? trans.control_fai : trans.control_routine}</span>,
          mobile: 'badge', mobileRender: r => (r.is_fai ? trans.control_fai : trans.control_routine) },
        { key: 'measures', label: trans.characteristics,
          render: r => (
              <span>
                  {r.measures} {trans.measured}
                  {r.nok > 0 && <span className="badge text-bg-danger ms-2">{r.nok} {trans.nok_count}</span>}
              </span>
          ) },
        tab === 'closed'
            ? { key: 'closed_at', label: trans.closed_badge, nowrap: true, filter: 'date', filterValue: r => r.closed_at, render: r => formatDate(r.closed_at, locale) }
            : { key: 'created_at', label: trans.created_on, nowrap: true, filter: 'date', filterValue: r => r.created_at, render: r => formatDate(r.created_at, locale) },
    ];
}

/**
 * « Nouveau plan de contrôle » : n'importe quel article, sans attendre qu'une
 * commande le fasse apparaître dans « À bullé ». Ouvre le bulleur sur le
 * brouillon créé (ou rouvert) à l'indice courant de l'article.
 */
function NewPlanPicker({ endpoints, trans, onError }) {
    const [open, setOpen]       = useState(false);
    const [query, setQuery]     = useState('');
    const [results, setResults] = useState([]);
    const [busy, setBusy]       = useState(null);
    const timer = useRef(null);
    const input = useRef(null);

    useEffect(() => { if (open) input.current?.focus(); }, [open]);

    const search = (value) => {
        setQuery(value);
        clearTimeout(timer.current);
        if (value.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(() => {
            apiFetch(`${endpoints.productSearch}?${new URLSearchParams({ q: value.trim() })}`)
                .then(data => setResults(data.products ?? []))
                .catch(() => setResults([]));
        }, 250);
    };

    const pick = (product) => {
        setBusy(product.id);
        apiFetch(endpoints.store, { method: 'POST', body: JSON.stringify({ products_id: product.id }) })
            .then(data => { window.location.href = url(endpoints.bubbler, data.plan.id); })
            .catch(err => { onError(err?.message ?? trans.save_error); setBusy(null); });
    };

    if (!open) {
        return (
            <button type="button" className="btn btn-sm btn-primary text-nowrap" onClick={() => setOpen(true)}>
                <i className="fas fa-plus me-1" />{trans.new_plan}
            </button>
        );
    }

    return (
        <div className="position-relative" style={{ minWidth: 280 }}>
            <div className="input-group input-group-sm">
                <span className="input-group-text"><i className="fas fa-box" /></span>
                <input ref={input} type="search" className="form-control" placeholder={trans.new_plan_search}
                       value={query} onChange={e => search(e.target.value)}
                       onKeyDown={e => { if (e.key === 'Escape') setOpen(false); }} />
                <button type="button" className="btn btn-outline-secondary" title={trans.cancel} onClick={() => { setOpen(false); setQuery(''); setResults([]); }}>
                    <i className="fas fa-xmark" />
                </button>
            </div>
            {results.length > 0 && (
                <div className="list-group position-absolute w-100 shadow mt-1" style={{ zIndex: 1050, maxHeight: 320, overflowY: 'auto' }}>
                    {results.map(p => (
                        <button key={p.id} type="button" className="list-group-item list-group-item-action py-1" disabled={busy !== null} onClick={() => pick(p)}>
                            <div className="d-flex align-items-center">
                                <strong className="me-2">{p.code}</strong>
                                {p.ind && <span className="badge text-bg-secondary me-2">{trans.revision} {p.ind}</span>}
                                {busy === p.id && <i className="fas fa-spinner fa-spin ms-auto" />}
                            </div>
                            <div className="small text-secondary text-truncate">{p.label}</div>
                        </button>
                    ))}
                </div>
            )}
            {query.trim().length >= 2 && results.length === 0 && (
                <div className="small text-secondary position-absolute mt-1">{trans.no_product_found}</div>
            )}
        </div>
    );
}

export default function QualityPlansIndex({ endpoints, trans, canEdit = false }) {
    const locale = trans.locale || undefined;
    const [tab, setTabRaw] = useIndexTab('quality_plans_index_tab', { tabs: TABS, fallback: 'needs' });
    const activeTab = TABS.includes(tab) ? tab : 'needs';

    const [rows, setRows]       = useState([]);
    const [meta, setMeta]       = useState(null);
    const [counts, setCounts]   = useState({});
    const [loading, setLoading] = useState(false);
    const [error, setError]     = useState(null);
    const [busyRow, setBusyRow] = useState(null);
    const [search, setSearch]   = useState('');
    const [page, setPage]       = useState(1);
    const timer = useRef(null);

    const load = useCallback((opts = {}) => {
        setLoading(true);
        setError(null);
        const params = new URLSearchParams({ tab: opts.tab ?? activeTab, page: String(opts.page ?? page), search: opts.search ?? search });
        apiFetch(`${endpoints.list}?${params}`)
            .then(data => {
                setRows(data.tab === 'needs'
                    ? data.rows.map(r => ({ ...r, id: `${r.product_id}|${r.revision ?? ''}` }))
                    : data.rows);
                setMeta(data.meta);
                setCounts(data.counts ?? {});
            })
            .catch(err => setError(err?.message ?? trans.load_error))
            .finally(() => setLoading(false));
    }, [activeTab, page, search, endpoints.list]);

    useEffect(() => { load(); }, [activeTab, page]);

    const setTab = (next) => { setTabRaw(next); setPage(1); setRows([]); };

    const onSearch = (value) => {
        setSearch(value);
        clearTimeout(timer.current);
        timer.current = setTimeout(() => { setPage(1); load({ search: value, page: 1 }); }, 350);
    };

    const openBubbler = (row) => {
        setBusyRow(row.id);
        apiFetch(endpoints.store, {
            method: 'POST',
            body: JSON.stringify({ products_id: row.product_id, revision: row.revision }),
        })
            .then(data => { window.location.href = url(endpoints.bubbler, data.plan.id); })
            .catch(err => { setError(err?.message ?? trans.load_error); setBusyRow(null); });
    };

    const rowAction = (row) => {
        if (DOSSIER_TABS.includes(activeTab)) {
            return row.task_url ? (
                <a className="btn btn-sm btn-outline-primary text-nowrap" href={row.task_url}>
                    <i className="fas fa-ruler-combined me-1" />{trans.control_tab}
                </a>
            ) : null;
        }
        if (activeTab !== 'needs') {
            return (
                <a className="btn btn-sm btn-outline-primary text-nowrap" href={url(endpoints.bubbler, row.id)}>
                    <i className={`fas ${activeTab === 'draft' && canEdit ? 'fa-pen-ruler' : 'fa-eye'} me-1`} />
                    {activeTab === 'draft' && canEdit ? trans.open_bubbler : trans.view}
                </a>
            );
        }
        if (!row.file) {
            return (
                <a className="btn btn-sm btn-outline-secondary text-nowrap" href={url(endpoints.productDocuments, row.product_id)} title={trans.no_plan_file_hint}>
                    <i className="fas fa-file-arrow-up me-1" />{trans.add_plan_file}
                </a>
            );
        }
        if (!canEdit) return <span className="text-secondary small">{trans.read_only}</span>;
        return (
            <button type="button" className="btn btn-sm btn-primary text-nowrap" disabled={busyRow === row.id} onClick={() => openBubbler(row)}>
                <i className={`fas ${busyRow === row.id ? 'fa-spinner fa-spin' : 'fa-pen-ruler'} me-1`} />{trans.open_bubbler}
            </button>
        );
    };

    const columns = [
        ...(activeTab === 'needs'
            ? needsColumns(trans, locale)
            : DOSSIER_TABS.includes(activeTab) ? dossierColumns(activeTab, trans, locale) : planColumns(activeTab, trans, locale)),
        { key: 'actions', label: trans.actions, hideable: false, render: rowAction, mobile: 'hidden' },
    ];

    return (
        <div className="card mb-3">
            <div className="card-header d-flex flex-wrap align-items-center gap-2">
                <ul className="nav nav-underline flex-grow-1">
                    {TABS.map(key => (
                        <li className="nav-item" key={key}>
                            <a href="#" className={`nav-link ${activeTab === key ? 'active' : ''}`}
                               onClick={e => { e.preventDefault(); setTab(key); }}>
                                <i className={`fas ${TAB_ICONS[key]} me-1`} />
                                {trans[`tab_${key}`]}
                                <span className={`badge rounded-pill ms-2 ${activeTab === key ? 'text-bg-primary' : 'text-bg-secondary'}`}>
                                    {counts[key] ?? '…'}
                                </span>
                            </a>
                        </li>
                    ))}
                </ul>
                {canEdit && <NewPlanPicker endpoints={endpoints} trans={trans} onError={setError} />}
                <div className="input-group input-group-sm" style={{ maxWidth: 280 }}>
                    <span className="input-group-text"><i className="fas fa-search" /></span>
                    <input type="search" className="form-control" placeholder={trans.search} value={search}
                           onChange={e => onSearch(e.target.value)} />
                </div>
            </div>
            <div className="card-body p-2">
                {activeTab === 'needs' && (
                    <p className="small text-secondary mb-2"><i className="fas fa-circle-info me-1" />{trans.needs_help}</p>
                )}
                {error && <div className="alert alert-danger py-2">{error}</div>}
                <DataTable
                    key={activeTab}
                    rows={rows}
                    columns={columns}
                    trans={trans}
                    loading={loading}
                    storage={{ order: `quality_plans_${activeTab}_col_order`, hidden: `quality_plans_${activeTab}_hidden_cols` }}
                    unsortableIcon={false}
                    unsortableCursor="default"
                    actionsColumn={false}
                    rowClickable={false}
                    mobileActions={rowAction}
                    emptyText={trans[`empty_${activeTab}`]}
                />
                <Pagination meta={meta} around={2} boundaries ulClassName="pagination pagination-sm justify-content-end mb-0" onPage={setPage} />
            </div>
        </div>
    );
}
