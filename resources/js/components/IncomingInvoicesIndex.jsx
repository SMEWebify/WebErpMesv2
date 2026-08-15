import React, { useState, useEffect, useRef } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    received:           { badge: 'badge-info',      label: 'Reçue' },
    supplier_unmatched: { badge: 'badge-warning',   label: 'Fournisseur inconnu' },
    converted:          { badge: 'badge-success',   label: 'Convertie' },
    rejected:           { badge: 'badge-secondary', label: 'Refusée' },
    unreadable:         { badge: 'badge-danger',    label: 'Illisible' },
};

const ALL_STATUSES = ['received', 'supplier_unmatched', 'converted', 'rejected', 'unreadable'];

// Provenance du document. Rendue en texte et non en badge : `badge-light` du
// thème AdminLTE s'affiche en blanc sur blanc dans ce tableau.
const PROVIDER_LABELS = {
    superpdp: 'SUPER PDP',
    qonto:    'Qonto',
    manual:   'Dépôt manuel',
};

function providerLabel(provider) {
    return PROVIDER_LABELS[provider] ?? provider;
}

// Statuts que l'acheteur doit déclarer au fournisseur (AFNOR XP Z12-012).
// Un motif est exigé sur les statuts défavorables : sans lui, le fournisseur
// n'a aucun moyen de corriger sa facture.
const OUTGOING_STATUSES = [
    { code: 'fr:204', label: 'Prise en charge',   icon: 'fa-inbox' },
    { code: 'fr:205', label: 'Approuvée',         icon: 'fa-check' },
    { code: 'fr:207', label: 'En litige',         icon: 'fa-exclamation-triangle', reason: true },
    { code: 'fr:210', label: 'Refusée',           icon: 'fa-times',                reason: true },
    { code: 'fr:211', label: 'Paiement transmis', icon: 'fa-money-check' },
];

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function formatCurrency(amount, currency, locale) {
    if (amount === null || amount === undefined) return '—';
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style: 'currency', currency: currency || 'EUR',
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        }).format(amount);
    } catch {
        return `${Number(amount).toFixed(2)} ${currency ?? '€'}`;
    }
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            'Accept':       'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
            ...options.headers,
        },
        ...options,
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok) {
        const err = new Error(body.message || `HTTP ${res.status}`);
        err.status = res.status;
        throw err;
    }
    return body;
}

function StatusBadge({ status }) {
    const cfg = STATUS_CONFIG[status] ?? { badge: 'badge-secondary', label: status };
    return <span className={`badge ${cfg.badge}`}>{cfg.label}</span>;
}

// ---------------------------------------------------------------------------
// Pagination (aligné sur les autres index)
// ---------------------------------------------------------------------------

function Pagination({ meta, onPage }) {
    if (!meta || meta.last_page <= 1) return null;
    const { current_page, last_page } = meta;
    const pages = [];
    for (let p = Math.max(1, current_page - 2); p <= Math.min(last_page, current_page + 2); p++) pages.push(p);

    return (
        <nav>
            <ul className="pagination pagination-sm justify-content-center mb-0">
                <li className={`page-item ${current_page === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page - 1)}>&laquo;</button>
                </li>
                {pages.map(p => (
                    <li key={p} className={`page-item ${p === current_page ? 'active' : ''}`}>
                        <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                    </li>
                ))}
                <li className={`page-item ${current_page === last_page ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page + 1)}>&raquo;</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// Upload card
// ---------------------------------------------------------------------------

function fileSize(bytes) {
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} Ko`;
    return `${(bytes / 1024 / 1024).toFixed(1)} Mo`;
}

/**
 * Dépôt manuel d'une facture reçue hors plateforme (courriel, portail client).
 *
 * Reprend la zone de dépôt du gestionnaire de fichiers (`wem-dropzone`) plutôt
 * qu'un champ « Choisir un fichier » : c'est le geste attendu pour un document
 * qui arrive en pièce jointe, et l'apparence reste celle du reste de l'ERP.
 */
function UploadCard({ uploadUrl, onUploaded }) {
    const [busy, setBusy] = useState(false);
    const [msg, setMsg]   = useState(null);
    const [err, setErr]   = useState(null);
    const [file, setFile] = useState(null);
    const [over, setOver] = useState(false);
    const fileRef = useRef(null);

    const choose = (files) => {
        const picked = Array.from(files ?? [])[0];
        if (!picked) return;
        setFile(picked);
        setMsg(null);
        setErr(null);
    };

    const reset = () => {
        setFile(null);
        if (fileRef.current) fileRef.current.value = '';
    };

    const submit = () => {
        if (!file || busy) return;

        const form = new FormData();
        form.append('document', file);

        setBusy(true); setMsg(null); setErr(null);
        apiFetch(uploadUrl, { method: 'POST', body: form })
            .then(res => { setMsg(res.message); reset(); onUploaded(); })
            .catch(e => setErr(e.message))
            .finally(() => setBusy(false));
    };

    return (
        <div className="card">
            <div className="card-header">
                <h3 className="card-title">
                    <i className="fas fa-inbox mr-2 text-muted" />
                    Déposer une facture électronique reçue
                </h3>
            </div>
            <div className="card-body">
                <div
                    className={`wem-dropzone ${over ? 'is-over' : ''} ${busy ? 'is-busy' : ''}`}
                    onDragOver={(e) => { e.preventDefault(); setOver(true); }}
                    onDragLeave={() => setOver(false)}
                    onDrop={(e) => { e.preventDefault(); setOver(false); if (!busy) choose(e.dataTransfer.files); }}
                    onClick={() => !busy && fileRef.current?.click()}
                    role="button"
                    tabIndex={0}
                    onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') fileRef.current?.click(); }}
                >
                    <input
                        ref={fileRef}
                        type="file"
                        name="document"
                        accept=".pdf,.xml"
                        className="d-none"
                        onChange={(e) => choose(e.target.files)}
                    />

                    {busy ? (
                        <>
                            <i className="fas fa-spinner fa-spin fa-2x mb-2" />
                            <div>Lecture du document…</div>
                        </>
                    ) : file ? (
                        <>
                            <i className="fas fa-file-invoice fa-2x mb-2 text-primary" />
                            <div className="font-weight-bold">{file.name}</div>
                            <div className="small text-muted mt-1">{fileSize(file.size)}</div>
                        </>
                    ) : (
                        <>
                            <i className="fas fa-cloud-upload-alt fa-2x mb-2" />
                            <div>Glissez le document ici, ou cliquez pour le choisir</div>
                            <div className="small text-muted mt-1">PDF Factur-X ou XML (CII, UBL) — 10 Mo maximum</div>
                        </>
                    )}
                </div>

                {file && ! busy && (
                    <div className="d-flex align-items-center mt-3" style={{ gap: '.5rem' }}>
                        <button type="button" className="btn btn-primary" onClick={submit}>
                            <i className="fas fa-upload mr-1" />Importer
                        </button>
                        <button type="button" className="btn btn-link text-muted" onClick={reset}>
                            Choisir un autre fichier
                        </button>
                    </div>
                )}

                {msg && <div className="alert alert-success mt-3 mb-0 py-2">{msg}</div>}
                {err && <div className="alert alert-danger mt-3 mb-0 py-2">{err}</div>}

                <small className="text-muted d-block mt-3">
                    <i className="fas fa-info-circle mr-1" />
                    Le vendeur est rapproché automatiquement par n° de TVA intracommunautaire ou SIREN.
                    Les factures transmises par la plateforme arrivent seules, sans dépôt manuel.
                </small>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function IncomingInvoicesIndex({ endpoints = {}, locale, currency }) {
    const [rows,    setRows]    = useState([]);
    const [meta,    setMeta]    = useState(null);
    const [loading, setLoading] = useState(false);
    const [error,   setError]   = useState(null);
    const [status,  setStatus]  = useState('');
    const [page,    setPage]    = useState(1);
    const [acting,  setActing]  = useState(null);
    const [directory, setDirectory] = useState(null);

    const fetchData = (opts = {}) => {
        const params = new URLSearchParams();
        const st = opts.status ?? status;
        if (st) params.set('status', st);
        params.set('page', opts.page ?? page);

        setLoading(true); setError(null);
        apiFetch(`${endpoints.list}?${params}`)
            .then(res => { setRows(res.data); setMeta(res.meta); setDirectory(res.directory ?? null); })
            .catch(e => setError(e.message))
            .finally(() => setLoading(false));
    };

    useEffect(() => { fetchData(); }, []);

    const handleStatus = (st) => { setStatus(st); setPage(1); fetchData({ status: st, page: 1 }); };
    const handlePage   = (p)  => { setPage(p); fetchData({ page: p }); };

    /**
     * Déclare un statut au fournisseur. Le motif est demandé — et exigé — sur
     * les statuts défavorables : la plateforme le transmet tel quel au vendeur.
     */
    const declareStatus = (row, status) => {
        let note = null;

        if (status.reason) {
            note = window.prompt(`Motif — « ${status.label} » pour ${row.seller_name}\n\nCe texte est transmis au fournisseur.`);
            if (note === null) return;
            if (!note.trim()) {
                setError('Un motif est obligatoire pour ce statut.');
                return;
            }
        }

        setActing(row.id);
        setError(null);
        apiFetch(row.status_url, {
            method: 'POST',
            body: JSON.stringify({ status: status.code, note: note?.trim() || undefined }),
        })
            .then(() => fetchData())
            .catch(e => setError(e.message))
            .finally(() => setActing(null));
    };

    const doAction = (row, url, confirmMsg) => {
        if (confirmMsg && !window.confirm(confirmMsg)) return;
        setActing(row.id);
        apiFetch(url, { method: 'POST' })
            .then(res => {
                if (res.redirect) { window.location.href = res.redirect; return; }
                fetchData();
            })
            .catch(e => { setError(e.message); setActing(null); });
    };

    return (
        <div>
            {/* Sans ligne d'annuaire, la boîte reste vide exactement comme si
                personne n'avait encore facturé : l'ambiguïté est le vrai danger. */}
            {directory && !directory.reachable && (
                <div className="alert alert-warning">
                    <h5><i className="icon fas fa-exclamation-triangle" /> Vous n'êtes pas joignable</h5>
                    Aucune ligne d'annuaire n'est ouverte : vos fournisseurs ne peuvent pas
                    vous adresser de facture électronique, et cet écran restera vide.
                    <br />
                    Ouvrez-la depuis votre compte sur la plateforme — c'est votre inscription
                    à l'annuaire officiel, en général sous votre numéro SIREN.
                </div>
            )}

            <UploadCard uploadUrl={endpoints.upload} onUploaded={() => fetchData({ page: 1 })} />

            <div className="card">
                <div className="card-body pb-2">
                    <div className="btn-group btn-group-sm" role="group">
                        <button className={`btn ${status === '' ? 'btn-secondary' : 'btn-outline-secondary'}`} onClick={() => handleStatus('')}>
                            Toutes
                        </button>
                        {ALL_STATUSES.map(st => (
                            <button key={st}
                                className={`btn ${status === st ? STATUS_CONFIG[st].badge.replace('badge-', 'btn-') : 'btn-outline-secondary'}`}
                                onClick={() => handleStatus(st)}>
                                {STATUS_CONFIG[st].label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="card-body p-0">
                    {error && <div className="alert alert-danger mx-3">{error}</div>}
                    {loading ? (
                        <div className="text-center py-5"><i className="fas fa-spinner fa-spin fa-2x text-muted" /></div>
                    ) : (
                        <div className="table-responsive">
                            <table className="table table-hover table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th>Vendeur</th>
                                        <th>N° facture</th>
                                        <th>Date</th>
                                        <th className="text-right">TTC</th>
                                        <th>Fournisseur WEM</th>
                                        <th className="text-center">Statut</th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.length === 0 ? (
                                        <tr><td colSpan={8} className="text-center text-muted py-4">Aucune facture reçue.</td></tr>
                                    ) : rows.map(row => (
                                        <tr key={row.id}>
                                            <td><span className="text-muted">{providerLabel(row.provider)}</span></td>
                                            <td>{row.seller_name}<br /><small className="text-muted">{row.seller_vat}</small></td>
                                            <td>{row.invoice_number}</td>
                                            <td>{row.issue_date ?? '—'}</td>
                                            <td className="text-right">{formatCurrency(row.total_ttc, row.currency, locale)}</td>
                                            <td>{row.supplier?.label ?? <span className="text-muted">—</span>}</td>
                                            <td className="text-center"><StatusBadge status={row.status} /></td>
                                            <td className="text-right text-nowrap">
                                                {row.purchase_invoice_url && (
                                                    <a href={row.purchase_invoice_url} className="btn btn-sm btn-outline-success">
                                                        Facture d'achat
                                                    </a>
                                                )}
                                                {row.reconcile_url && (
                                                    <a className="btn btn-sm btn-success" href={row.reconcile_url}
                                                       title="Sélectionner les réceptions couvertes par cette facture">
                                                        <i className="fas fa-link mr-1" />Rapprocher
                                                    </a>
                                                )}
                                                {row.convert_url && (
                                                    <button className="btn btn-sm btn-outline-success ml-1" disabled={acting === row.id}
                                                        onClick={() => doAction(row, row.convert_url, 'Créer une facture d\'achat sans rapprochement ? À réserver aux factures sans commande ni réception (frais, abonnements).')}>
                                                        Sans rapprochement
                                                    </button>
                                                )}
                                                {row.status_url && (
                                                    <div className="btn-group ml-1">
                                                        <button type="button"
                                                                className="btn btn-sm btn-outline-primary dropdown-toggle"
                                                                data-toggle="dropdown"
                                                                disabled={acting === row.id}>
                                                            Déclarer
                                                        </button>
                                                        <div className="dropdown-menu dropdown-menu-right">
                                                            <h6 className="dropdown-header">Statut renvoyé au fournisseur</h6>
                                                            {OUTGOING_STATUSES.map(s => (
                                                                <button key={s.code} type="button" className="dropdown-item"
                                                                        onClick={() => declareStatus(row, s)}>
                                                                    <i className={`fas ${s.icon} fa-fw mr-2 text-muted`} />
                                                                    {s.label}
                                                                </button>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                                {row.reject_url && (
                                                    <button className="btn btn-sm btn-outline-secondary ml-1" disabled={acting === row.id}
                                                        onClick={() => doAction(row, row.reject_url, 'Refuser cette facture ?')}>
                                                        Refuser
                                                    </button>
                                                )}
                                                {row.status === 'supplier_unmatched' && (
                                                    <span className="text-muted small ml-1">TVA {row.seller_vat}</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {meta && (
                    <div className="card-footer d-flex align-items-center justify-content-between">
                        <small className="text-muted">{meta.total} résultats</small>
                        <Pagination meta={meta} onPage={handlePage} />
                    </div>
                )}
            </div>
        </div>
    );
}
