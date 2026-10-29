import React, { useCallback, useEffect, useState } from 'react';
import { DataTable, Pagination } from '../table';
import { apiFetch } from '../../lib/http';
import DocumentCheckDrawer, { CertificateCell, DecisionBadge, StatusBadge } from './DocumentCheckDrawer.jsx';

// Liste qualité : lignes de réception en attente de visa documentaire (et visas passés).
const FILTERS = ['awaiting', 'non_compliant', 'compliant', 'all'];
const LS_FILTER    = 'receipt_document_check_filter';
const LS_COL_ORDER = 'receipt_document_check_col_order';
const LS_HIDDEN    = 'receipt_document_check_hidden_cols';

function readFilter() {
    try {
        const value = localStorage.getItem(LS_FILTER);
        return FILTERS.includes(value) ? value : 'awaiting';
    } catch { return 'awaiting'; }
}

function formatDate(iso) {
    if (!iso) return '—';
    const [y, m, d] = iso.slice(0, 10).split('-').map(Number);
    return new Intl.DateTimeFormat(document.documentElement.lang || 'fr').format(new Date(y, m - 1, d));
}

function columns(texts) {
    return [
        { key: 'receipt_code', label: texts.receipt, bold: true, mobile: 'title',
          render: r => (r.receipt_url ? <a href={r.receipt_url}>{r.receipt_code}</a> : r.receipt_code),
          mobileRender: r => r.receipt_code },
        { key: 'received_at', label: texts.received_at, mobile: 'subtitle', render: r => formatDate(r.received_at) },
        { key: 'supplier_label', label: texts.supplier, mobile: 'subtitle' },
        { key: 'purchase_code', label: texts.purchase,
          render: r => (r.purchase_url ? <a href={r.purchase_url}>{r.purchase_code}</a> : (r.purchase_code ?? '—')) },
        { key: 'purchase_line_label', label: texts.line, mobile: 'subtitle' },
        { key: 'receipt_qty', label: texts.qty, align: 'right' },
        { key: 'certificate', label: texts.certificate, nowrap: true, render: r => <CertificateCell line={r} texts={texts} /> },
        { key: 'physical_status', label: texts.physical, render: r => <StatusBadge status={r.physical_status} texts={texts} /> },
        { key: 'document_check_status', label: texts.document, mobile: 'badge',
          render: r => <StatusBadge status={r.document_check_status} texts={texts} /> },
        { key: 'final_decision', label: texts.decision, render: r => <DecisionBadge decision={r.final_decision} texts={texts} /> },
        { key: 'nc_code', label: texts.nc, render: r => (r.nc_code ? <span className="badge text-bg-warning">{r.nc_code}</span> : '—') },
    ];
}

export default function ReceiptDocumentCheckApp({ endpoints, config }) {
    const texts = config.texts ?? {};

    const [filter, setFilter]   = useState(readFilter);
    const [search, setSearch]   = useState('');
    const [page, setPage]       = useState(1);
    const [rows, setRows]       = useState([]);
    const [meta, setMeta]       = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError]     = useState(null);
    const [current, setCurrent] = useState(null);
    const [flash, setFlash]     = useState(null);

    const load = useCallback(() => {
        setLoading(true);
        setError(null);
        const params = new URLSearchParams({ filter, page });
        if (search.trim()) params.set('search', search.trim());
        apiFetch(`${endpoints.list}?${params}`)
            .then(data => { setRows(data.data ?? []); setMeta(data.meta ?? null); })
            .catch(err => setError(err?.message ?? texts.error))
            .finally(() => setLoading(false));
    }, [filter, page, search, endpoints.list]);

    useEffect(() => { load(); }, [filter, page]);

    const changeFilter = (value) => {
        setFilter(value);
        setPage(1);
        try { localStorage.setItem(LS_FILTER, value); } catch { /* navigation privée */ }
    };

    const saved = () => {
        setCurrent(null);
        setFlash(texts.saved);
        setTimeout(() => setFlash(null), 4000);
        load();
    };

    return (
        <div>
            {flash && <div className="alert alert-success py-2">{flash}</div>}
            {error && <div className="alert alert-danger py-2">{error}</div>}

            <div className="d-flex flex-wrap align-items-center mb-3" style={{ gap: '0.5rem' }}>
                <div className="btn-group btn-group-sm" role="group">
                    {FILTERS.map(value => (
                        <button key={value} type="button"
                            className={`btn ${filter === value ? 'btn-teal' : 'btn-outline-secondary'}`}
                            onClick={() => changeFilter(value)}>
                            {texts[`filter_${value}`]}
                        </button>
                    ))}
                </div>
                <form className="ms-auto" onSubmit={e => { e.preventDefault(); setPage(1); load(); }}>
                    <div className="input-group input-group-sm">
                        <input type="search" className="form-control" placeholder={texts.search}
                            value={search} onChange={e => setSearch(e.target.value)} />
                        <button type="submit" className="btn btn-outline-secondary"><i className="fas fa-magnifying-glass" /></button>
                    </div>
                </form>
            </div>

            <DataTable
                rows={rows}
                columns={columns(texts)}
                trans={texts}
                loading={loading}
                storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN }}
                rowClickable={false}
                onRowClick={r => setCurrent(r)}
                emptyText={texts.no_data}
                rowActions={r => (
                    <button type="button" className={`btn btn-sm ${config.canCheck ? 'btn-teal' : 'btn-outline-secondary'}`}
                        title={config.canCheck ? texts.check : texts.view} onClick={() => setCurrent(r)}>
                        <i className={`fas ${config.canCheck ? 'fa-stamp' : 'fa-eye'}`} />
                    </button>
                )}
                mobileActions={r => (
                    <button type="button" className="btn btn-teal" style={{ minHeight: 44 }} onClick={() => setCurrent(r)}>
                        <i className="fas fa-stamp me-1" />{config.canCheck ? texts.check : texts.view}
                    </button>
                )}
                actionsWidth={44}
            />

            {meta && meta.last_page > 1 && (
                <Pagination meta={meta} ulClassName="pagination pagination-sm justify-content-end" onPage={p => setPage(p)} />
            )}

            <DocumentCheckDrawer
                line={current}
                endpoint={endpoints.check}
                config={config}
                onClose={() => setCurrent(null)}
                onSaved={saved}
            />
        </div>
    );
}
