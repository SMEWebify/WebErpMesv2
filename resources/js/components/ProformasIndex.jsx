import React, { useState, useEffect, useCallback } from 'react';
import { SortIcon } from './table';

// Textes de l'écran (resources/lang/*/billing_ui.php), posés par le composant principal
// avant le rendu de ses enfants ; repli sur le texte français.
let L = {};

const STATUSES = [
    { value: 1, get label() { return L.draft ?? 'Brouillon'; },  badge: 'secondary' },
    { value: 2, get label() { return L.proforma_sent ?? 'Envoyée'; },    badge: 'primary'   },
    { value: 3, get label() { return L.accepted ?? 'Acceptée'; },   badge: 'warning'   },
    { value: 4, get label() { return L.refused ?? 'Refusée'; },    badge: 'danger'    },
    { value: 5, get label() { return L.converted ?? 'Convertie'; },  badge: 'success'   },
];

function StatusBadge({ statu }) {
    const s = STATUSES.find(x => x.value === statu);
    if (!s) return null;
    return <span className={`badge text-bg-${s.badge}`}>{s.label}</span>;
}

export default function ProformasIndex({ endpoints, trans = {} }) {
    L = trans?.ui ?? {};
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

    return (
        <div>
            {/* Header */}
            <div className="d-flex justify-content-between align-items-center mb-3">
                <div>
                    {STATUSES.map(s => (
                        <button
                            key={s.value}
                            onClick={() => toggleStatus(s.value)}
                            className={`btn btn-sm me-1 ${statuses.includes(s.value) ? `btn-${s.badge}` : 'btn-outline-secondary'}`}
                        >
                            {s.label}
                        </button>
                    ))}
                </div>
                <a href={endpoints.request} className="btn btn-success btn-sm">
                    <i className="fas fa-plus me-1" /> {L.new_proforma ?? 'Nouvelle proforma'}
                </a>
            </div>

            {/* Search */}
            <div className="mb-3">
                <input
                    type="text"
                    className="form-control"
                    placeholder={L.search ?? 'Rechercher...'}
                    value={search}
                    onChange={e => { setSearch(e.target.value); setPage(1); }}
                />
            </div>

            {/* Table */}
            <div className="table-responsive">
                <table className="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th style={{cursor:'pointer'}} onClick={() => handleSort('code')}>{L.code ?? 'Code'} <SortIcon field="code" sortField={sort} sortAsc={asc} /></th>
                            <th style={{cursor:'pointer'}} onClick={() => handleSort('label')}>{L.label ?? 'Libellé'} <SortIcon field="label" sortField={sort} sortAsc={asc} /></th>
                            <th style={{cursor:'pointer'}} onClick={() => handleSort('companie')}>{L.customer ?? 'Client'} <SortIcon field="companie" sortField={sort} sortAsc={asc} /></th>
                            <th>{L.contact ?? 'Contact'}</th>
                            <th style={{cursor:'pointer'}} onClick={() => handleSort('created_at')}>{L.date ?? 'Date'} <SortIcon field="created_at" sortField={sort} sortAsc={asc} /></th>
                            <th>{L.lines ?? 'Lignes'}</th>
                            <th>{L.amount_ht ?? 'Montant HT'}</th>
                            <th>{L.status ?? 'Statut'}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading && (
                            <tr><td colSpan="9" className="text-center"><i className="fas fa-spinner fa-spin" /></td></tr>
                        )}
                        {!loading && rows.length === 0 && (
                            <tr><td colSpan="9" className="text-center text-muted">{L.no_result ?? 'Aucun résultat'}</td></tr>
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
                                    <a href={row.url} className="btn btn-sm btn-info me-1" title={L.view ?? 'Voir'}>
                                        <i className="fas fa-eye" />
                                    </a>
                                    <a href={row.url_pdf} className="btn btn-sm btn-secondary" title="PDF" target="_blank" rel="noreferrer">
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
                    <small className="text-muted">{meta.total} {L.results_suffix ?? 'résultat(s)'}</small>
                    <div>
                        <button className="btn btn-sm btn-outline-secondary me-1" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>
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
