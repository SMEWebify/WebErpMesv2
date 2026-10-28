import React, { useState, useEffect, useCallback } from 'react';
import { DataTable, Pagination } from '../table';

// Journal des consultations (Admin) : lecture seule, filtres serveur, export CSV des
// mêmes filtres.

const LS_COL_ORDER   = 'access_log_table_col_order';
const LS_HIDDEN_COLS = 'access_log_table_hidden_cols';

export const EMPTY_FILTERS = { user_id: '', type: '', subject_id: '', from: '', to: '' };

// Paramètres de requête : seuls les filtres renseignés partent.
export function filterParams(filters, page = null) {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        const v = String(value ?? '').trim();
        if (v !== '') params.append(key, v);
    });
    if (page) params.append('page', page);
    return params;
}

function accessColumns(trans) {
    return [
        { key: 'created_at', label: trans.date, bold: true, mobile: 'subtitle' },
        { key: 'user', label: trans.user, filter: 'text', mobile: 'title' },
        { key: 'type_label', label: trans.subject_type, mobile: 'badge',
          render: r => <span className="badge text-bg-secondary">{r.type_label}</span> },
        { key: 'subject_label', label: trans.subject, filter: 'text', mobile: 'subtitle',
          render: r => (
              <>
                  {r.subject_url ? <a href={r.subject_url}>{r.subject_label}</a> : r.subject_label}
                  <span className="text-secondary ms-1">#{r.subject_id}</span>
              </>
          ),
          filterValue: r => `${r.subject_label ?? ''} ${r.subject_id}` },
        { key: 'route', label: trans.route, render: r => r.route ? <code>{r.route}</code> : '—' },
        { key: 'ip', label: trans.ip, render: r => r.ip ?? '—' },
    ];
}

export default function AccessLogApp({ endpoints, trans, users = [], types = {} }) {
    const [rows, setRows]       = useState([]);
    const [meta, setMeta]       = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError]     = useState('');
    const [page, setPage]       = useState(1);
    const [filters, setFilters] = useState(EMPTY_FILTERS);

    const fetchData = useCallback(async () => {
        setLoading(true);
        setError('');
        try {
            const res = await fetch(`${endpoints.list}?${filterParams(filters, page)}`, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            setRows(json.data ?? []);
            setMeta(json.meta ?? null);
        } catch (err) {
            setError(err.message);
            setRows([]);
        } finally {
            setLoading(false);
        }
    }, [endpoints.list, filters, page]);

    useEffect(() => { fetchData(); }, [fetchData]);

    const setFilter = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value }));
        setPage(1);
    };

    return (
        <div className="card card-outline card-primary mb-3">
            <div className="card-header py-2">
                <div className="row g-2 align-items-end">
                    <div className="col-12 col-md-3">
                        <label className="form-label small mb-1">{trans.user}</label>
                        <select className="form-select form-select-sm" value={filters.user_id} onChange={e => setFilter('user_id', e.target.value)}>
                            <option value="">{trans.all_users}</option>
                            {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select>
                    </div>
                    <div className="col-6 col-md-2">
                        <label className="form-label small mb-1">{trans.subject_type}</label>
                        <select className="form-select form-select-sm" value={filters.type} onChange={e => setFilter('type', e.target.value)}>
                            <option value="">{trans.all_types}</option>
                            {Object.entries(types).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                        </select>
                    </div>
                    <div className="col-6 col-md-2">
                        <label className="form-label small mb-1">{trans.subject_id}</label>
                        <input type="number" min="1" className="form-control form-control-sm" value={filters.subject_id} onChange={e => setFilter('subject_id', e.target.value)} />
                    </div>
                    <div className="col-6 col-md-2">
                        <label className="form-label small mb-1">{trans.from}</label>
                        <input type="date" className="form-control form-control-sm" value={filters.from} onChange={e => setFilter('from', e.target.value)} />
                    </div>
                    <div className="col-6 col-md-2">
                        <label className="form-label small mb-1">{trans.to}</label>
                        <input type="date" className="form-control form-control-sm" value={filters.to} onChange={e => setFilter('to', e.target.value)} />
                    </div>
                    <div className="col-12 col-md-1 d-flex gap-1">
                        <button type="button" className="btn btn-sm btn-outline-secondary" title={trans.reset} onClick={() => { setFilters(EMPTY_FILTERS); setPage(1); }}>
                            <i className="fas fa-eraser" />
                        </button>
                        <a className="btn btn-sm btn-outline-primary" title={trans.export_csv} href={`${endpoints.export}?${filterParams(filters)}`}>
                            <i className="fas fa-file-csv" />
                        </a>
                    </div>
                </div>
            </div>

            {error && <div className="alert alert-danger m-2 py-2">{error}</div>}

            <DataTable
                rows={rows}
                columns={accessColumns(trans)}
                loading={loading}
                trans={trans}
                storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
                unsortableIcon={false}
                unsortableCursor="default"
                rowClickable={false}
                actionsColumn={false}
                tableClassName="table table-hover table-sm mb-0"
                loadingContent={<><i className="fas fa-spinner fa-spin" /> {trans.loading}</>}
                emptyText={trans.no_data}
            />

            <Pagination meta={meta} around={1} boundaries showTotal navClassName="d-flex justify-content-between align-items-center px-3 py-2" ulClassName="pagination pagination-sm mb-0" onPage={p => setPage(p)} />
        </div>
    );
}
