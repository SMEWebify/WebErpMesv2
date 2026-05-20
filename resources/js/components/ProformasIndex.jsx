import React, { useState, useEffect, useCallback } from 'react';

const STATUSES = [
    { value: 1, label: 'Brouillon',  badge: 'secondary' },
    { value: 2, label: 'Envoyée',    badge: 'primary'   },
    { value: 3, label: 'Acceptée',   badge: 'warning'   },
    { value: 4, label: 'Refusée',    badge: 'danger'    },
    { value: 5, label: 'Convertie',  badge: 'success'   },
];

function StatusBadge({ statu }) {
    const s = STATUSES.find(x => x.value === statu);
    if (!s) return null;
    return <span className={`badge badge-${s.badge}`}>{s.label}</span>;
}

export default function ProformasIndex({ endpoints }) {
    const [rows,        setRows]        = useState([]);
    const [meta,        setMeta]        = useState({ total: 0, last_page: 1, current_page: 1 });
    const [search,      setSearch]      = useState('');
    const [statuses,    setStatuses]    = useState([]);
    const [page,        setPage]        = useState(1);
    const [sort,        setSort]        = useState('created_at');
    const [asc,         setAsc]         = useState(false);
    const [loading,     setLoading]     = useState(false);

    const fetchData = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({ search, sort, asc: asc ? '1' : '0', page });
        statuses.forEach(s => params.append('statuses[]', s));

        fetch(`${endpoints.list}?${params}`)
            .then(r => r.json())
            .then(d => { setRows(d.data ?? []); setMeta(d.meta ?? {}); })
            .finally(() => setLoading(false));
    }, [search, statuses, page, sort, asc, endpoints.list]);

    useEffect(() => { fetchData(); }, [fetchData]);

    function toggleStatus(val) {
        setStatuses(prev => prev.includes(val) ? prev.filter(s => s !== val) : [...prev, val]);
        setPage(1);
    }

    function handleSort(field) {
        if (sort === field) setAsc(a => !a);
        else { setSort(field); setAsc(true); }
    }

    function SortIcon({ field }) {
        if (sort !== field) return <i className="fas fa-sort ml-1 text-muted" />;
        return <i className={`fas fa-sort-${asc ? 'up' : 'down'} ml-1`} />;
    }

    return (
        <div>
            {/* Header */}
            <div className="d-flex justify-content-between align-items-center mb-3">
                <div>
                    {STATUSES.map(s => (
                        <button
                            key={s.value}
                            onClick={() => toggleStatus(s.value)}
                            className={`btn btn-sm mr-1 ${statuses.includes(s.value) ? `btn-${s.badge}` : 'btn-outline-secondary'}`}
                        >
                            {s.label}
                        </button>
                    ))}
                </div>
                <a href={endpoints.request} className="btn btn-success btn-sm">
                    <i className="fas fa-plus mr-1" /> Nouvelle proforma
                </a>
            </div>

            {/* Search */}
            <div className="mb-3">
                <input
                    type="text"
                    className="form-control"
                    placeholder="Rechercher..."
                    value={search}
                    onChange={e => { setSearch(e.target.value); setPage(1); }}
                />
            </div>

            {/* Table */}
            <div className="table-responsive">
                <table className="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th style={{cursor:'pointer'}} onClick={() => handleSort('code')}>Code <SortIcon field="code" /></th>
                            <th style={{cursor:'pointer'}} onClick={() => handleSort('label')}>Libellé <SortIcon field="label" /></th>
                            <th style={{cursor:'pointer'}} onClick={() => handleSort('companie')}>Client <SortIcon field="companie" /></th>
                            <th>Contact</th>
                            <th style={{cursor:'pointer'}} onClick={() => handleSort('created_at')}>Date <SortIcon field="created_at" /></th>
                            <th>Lignes</th>
                            <th>Montant HT</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading && (
                            <tr><td colSpan="9" className="text-center"><i className="fas fa-spinner fa-spin" /></td></tr>
                        )}
                        {!loading && rows.length === 0 && (
                            <tr><td colSpan="9" className="text-center text-muted">Aucun résultat</td></tr>
                        )}
                        {!loading && rows.map(row => (
                            <tr key={row.id}>
                                <td><a href={row.url}>{row.code}</a></td>
                                <td>{row.label}</td>
                                <td>{row.companie?.label}</td>
                                <td>{row.contact?.name}</td>
                                <td>{row.created_at}</td>
                                <td>{row.invoice_lines_count}</td>
                                <td>{row.total_amount?.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })}</td>
                                <td><StatusBadge statu={row.statu} /></td>
                                <td>
                                    <a href={row.url} className="btn btn-xs btn-info mr-1" title="Voir">
                                        <i className="fas fa-eye" />
                                    </a>
                                    <a href={row.url_pdf} className="btn btn-xs btn-secondary" title="PDF" target="_blank" rel="noreferrer">
                                        <i className="fas fa-file-pdf" />
                                    </a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {meta.last_page > 1 && (
                <div className="d-flex justify-content-between align-items-center mt-2">
                    <small className="text-muted">{meta.total} résultat(s)</small>
                    <div>
                        <button className="btn btn-sm btn-outline-secondary mr-1" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>
                            <i className="fas fa-chevron-left" />
                        </button>
                        <span className="mx-2">{page} / {meta.last_page}</span>
                        <button className="btn btn-sm btn-outline-secondary" disabled={page >= meta.last_page} onClick={() => setPage(p => p + 1)}>
                            <i className="fas fa-chevron-right" />
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
