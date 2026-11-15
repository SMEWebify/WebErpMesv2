import React, { useState } from 'react';
import { formatAddress, formatSiret } from '../../lib/companyRegistry';

/**
 * Recherche dans le répertoire Sirene (SIRET, SIREN ou nom) et reprise d'un
 * résultat. N'enregistre rien : `onSelect(result)` remplit le formulaire
 * appelant, que l'utilisateur relit.
 */
export default function CompanyRegistrySearch({ url, trans = {}, onSelect, initialQuery = '' }) {
    const [query,   setQuery]   = useState(initialQuery);
    const [status,  setStatus]  = useState('idle'); // idle | busy | done | error
    const [message, setMessage] = useState('');
    const [results, setResults] = useState([]);

    const search = async () => {
        const q = query.trim();
        if (!q) return;
        setStatus('busy');
        setMessage('');
        setResults([]);
        try {
            const res  = await fetch(`${url}?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setStatus('error');
                setMessage(data.message ?? trans.network_error);
                return;
            }
            const found = data.results ?? [];
            setResults(found);
            setStatus('done');
            if (!found.length) setMessage(trans.no_result);
        } catch {
            setStatus('error');
            setMessage(trans.network_error);
        }
    };

    const pick = (result) => {
        setResults([]);
        setStatus('idle');
        onSelect(result);
    };

    return (
        <div className="mb-2">
            <label className="mb-1 small text-body-secondary">
                <i className="fas fa-building-circle-check me-1" />{trans.title}
            </label>
            <div className="input-group">
                <input
                    type="text"
                    className="form-control"
                    value={query}
                    placeholder={trans.placeholder}
                    onChange={e => setQuery(e.target.value)}
                    // Dans un <form> : Entrée lance la recherche, pas l'enregistrement.
                    onKeyDown={e => { if (e.key === 'Enter') { e.preventDefault(); search(); } }}
                />
                <button type="button" className="btn btn-outline-secondary" onClick={search}
                        disabled={status === 'busy' || !query.trim()}>
                    <i className={`fas ${status === 'busy' ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'} me-1`} />
                    {status === 'busy' ? trans.searching : trans.search}
                </button>
            </div>
            {message && (
                <small className={`form-text ${status === 'error' ? 'text-danger' : 'text-body-secondary'}`}>{message}</small>
            )}
            {results.length > 0 && (
                <div className="list-group mt-2" style={{ maxHeight: 280, overflowY: 'auto' }}>
                    {results.map(r => (
                        <button
                            key={r.siret ?? r.siren}
                            type="button"
                            className="list-group-item list-group-item-action"
                            onClick={() => pick(r)}
                        >
                            <div className="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong>{r.label}</strong>
                                    <span className={`badge ms-2 ${r.is_head_office ? 'text-bg-primary' : 'text-bg-secondary'}`}>
                                        {r.is_head_office ? trans.head_office : trans.establishment}
                                    </span>
                                    {!r.active && <span className="badge text-bg-danger ms-1">{trans.closed}</span>}
                                    <div className="small text-body-secondary">
                                        {formatSiret(r.siret)}{r.naf_code ? ` · NAF ${r.naf_code}` : ''}
                                    </div>
                                    <div className="small">{formatAddress(r.address)}</div>
                                </div>
                                <span className="btn btn-sm btn-success flex-shrink-0">{trans.use}</span>
                            </div>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
