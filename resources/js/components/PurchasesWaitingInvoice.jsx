import React, { useState, useEffect, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            'Accept':       'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...options.headers,
        },
        ...options,
    });
    const body = await res.json().catch(() => ({ message: res.statusText }));
    if (!res.ok) throw body;
    return body;
}

// ---------------------------------------------------------------------------
// WaitingLinesTable
// ---------------------------------------------------------------------------

function WaitingLinesTable({ lines, selectedIds, onToggle, trans }) {
    if (!lines.length) {
        return (
            <tr>
                <td colSpan={7} className="text-center text-muted py-3">
                    {trans.no_data ?? 'Aucune donnée'}
                </td>
            </tr>
        );
    }

    return lines.map(line => (
        <tr key={line.id}>
            {/* Order */}
            <td>
                {line.order_url ? (
                    <a href={line.order_url} className="btn btn-xs btn-secondary">
                        <i className="fas fa-folder mr-1" />
                        {line.order_code}
                    </a>
                ) : (
                    <span className="text-muted">{trans.generic ?? 'Générique'}</span>
                )}
            </td>

            {/* Purchase order */}
            <td>
                {line.purchase_url ? (
                    <a href={line.purchase_url} className="btn btn-primary btn-sm">
                        <i className="fas fa-folder mr-1" />
                        {line.purchase_code}
                    </a>
                ) : '—'}
            </td>

            {/* Purchase receipt */}
            <td>
                {line.receipt_url ? (
                    <a href={line.receipt_url} className="btn btn-primary btn-sm">
                        <i className="fas fa-folder mr-1" />
                        {line.receipt_code}
                    </a>
                ) : '—'}
            </td>

            {/* Supplier */}
            <td>
                {line.supplier_code && line.supplier_label
                    ? line.supplier_label
                    : <span className="text-muted">—</span>
                }
            </td>

            {/* Description */}
            <td>
                {line.tasks_id ? (
                    <>
                        {line.task_url && (
                            <a href={line.task_url} className="btn btn-sm btn-success mr-1">
                                {trans.view ?? 'Voir'}
                            </a>
                        )}
                        #{line.task_id} {line.line_code} {line.line_label}
                        {line.component_label && <> - {line.component_label}</>}
                    </>
                ) : (
                    line.line_label
                )}
            </td>

            {/* Receipt qty */}
            <td>{new Intl.NumberFormat('fr-FR').format(line.receipt_qty)}</td>

            {/* Checkbox */}
            <td>
                <div className="custom-control custom-checkbox">
                    <input
                        className="custom-control-input"
                        type="checkbox"
                        id={`line-${line.id}`}
                        checked={selectedIds.includes(line.id)}
                        onChange={() => onToggle(line.id)}
                    />
                    <label className="custom-control-label" htmlFor={`line-${line.id}`}>
                        {trans.add_to_document ?? 'Ajouter au document'}
                    </label>
                </div>
            </td>
        </tr>
    ));
}

// ---------------------------------------------------------------------------
// Root Component
// ---------------------------------------------------------------------------


/**
 * Facture électronique reçue, affichée en regard des réceptions à cocher.
 *
 * Ses lignes ne sont pas recopiables : une ligne de facture d'achat référence
 * une commande et une réception, seule garantie qu'on ne paie que ce qui a été
 * commandé et reçu. Le rôle de ce panneau est donc la confrontation — et
 * l'écart de total est ce qu'on ne voit pas à l'œil sur trente lignes.
 */
function IncomingDocumentPanel({ incoming, selectedTotal, money }) {
    const gap = Number((selectedTotal - incoming.total_ht).toFixed(2));
    const matched = Math.abs(gap) < 0.01;

    return (
        <div className="card bg-light mx-3 mt-3 mb-0">
            <div className="card-header py-2">
                <h3 className="card-title">
                    <i className="fas fa-file-invoice mr-2 text-muted" />
                    Document reçu — {incoming.seller_name}
                    {incoming.invoice_number && <span className="ml-2 text-muted">n° {incoming.invoice_number}</span>}
                </h3>
                {incoming.issue_date && (
                    <span className="float-right text-muted small">
                        Émise le {incoming.issue_date}
                        {incoming.due_date && ` — échéance ${incoming.due_date}`}
                    </span>
                )}
            </div>
            <div className="card-body py-2">
                <div className="table-responsive">
                    <table className="table table-sm mb-2">
                        <thead>
                            <tr>
                                <th>Ce que le fournisseur facture</th>
                                <th className="text-right" style={{ width: 140 }}>Quantité</th>
                                <th className="text-right" style={{ width: 140 }}>Total HT</th>
                            </tr>
                        </thead>
                        <tbody>
                            {incoming.lines.length === 0 && (
                                <tr><td colSpan={3} className="text-muted">Aucune ligne dans le document.</td></tr>
                            )}
                            {incoming.lines.map((line, i) => (
                                <tr key={i}>
                                    <td>{line.name}</td>
                                    <td className="text-right">{line.quantity} {line.unit_code}</td>
                                    <td className="text-right">{money(line.line_total)}</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colSpan={2} className="text-right">Total facturé HT</th>
                                <th className="text-right">{money(incoming.total_ht)}</th>
                            </tr>
                            <tr className="text-muted">
                                <td colSpan={2} className="text-right">TVA / TTC</td>
                                <td className="text-right">{money(incoming.total_vat)} / {money(incoming.total_ttc)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div className={`alert py-2 mb-0 ${matched ? 'alert-success' : 'alert-warning'}`}>
                    <i className={`fas ${matched ? 'fa-check' : 'fa-balance-scale-right'} mr-2`} />
                    Réceptions sélectionnées : <strong>{money(selectedTotal)}</strong>
                    {matched
                        ? ' — le total correspond au document reçu.'
                        : <> — écart de <strong>{money(gap)}</strong> avec le document reçu.</>}
                </div>
            </div>
        </div>
    );
}

export default function PurchasesWaitingInvoice({ endpoints, trans, initialCode }) {
    const [companies, setCompanies]         = useState([]);
    const [users, setUsers]                 = useState([]);
    const [lines, setLines]                 = useState([]);
    const [loading, setLoading]             = useState(true);
    const [linesLoading, setLinesLoading]   = useState(false);
    const [submitting, setSubmitting]       = useState(false);
    const [errors, setErrors]               = useState({});
    const [flashError, setFlashError]       = useState(null);

    // Contexte de rapprochement : la boîte de réception ouvre cet écran avec la
    // facture électronique reçue et son fournisseur déjà désignés.
    const query       = new URLSearchParams(window.location.search);
    const incomingId  = query.get('incoming_id');

    // Form state
    const [incoming, setIncoming]                   = useState(null);
    const [supplierReference, setSupplierReference] = useState('');
    const [companiesId, setCompaniesId] = useState(query.get('companies_id') ?? '');
    const [code, setCode]               = useState(initialCode ?? '');
    const [userId, setUserId]           = useState('');
    const [selectedIds, setSelectedIds] = useState([]);

    // Initial load
    useEffect(() => {
        const params = new URLSearchParams();
        if (companiesId) params.set('companies_id', companiesId);
        if (incomingId)  params.set('incoming_id', incomingId);

        apiFetch(`${endpoints.init}?${params}`)
            .then(data => {
                setCompanies(data.companies ?? []);
                setUsers(data.users ?? []);
                setLines(data.lines ?? []);
                if (!code) setCode(data.initial_code ?? '');
                if (data.incoming) {
                    setIncoming(data.incoming);
                    // La référence fournisseur est le numéro de SA facture :
                    // c'est elle qui permettra de retrouver le document reçu.
                    setSupplierReference(data.incoming.invoice_number ?? '');
                }
            })
            .catch(() => setFlashError(trans.error ?? 'Erreur de chargement'))
            .finally(() => setLoading(false));
    }, []);

    // Reload lines when company filter changes
    const fetchLines = useCallback((cid) => {
        setLinesLoading(true);
        setSelectedIds([]);
        const params = new URLSearchParams();
        if (cid) params.set('companies_id', cid);
        apiFetch(`${endpoints.init}?${params}`)
            .then(data => setLines(data.lines ?? []))
            .catch(() => setFlashError(trans.error ?? 'Erreur'))
            .finally(() => setLinesLoading(false));
    }, [endpoints.init]);

    const handleCompanyChange = (val) => {
        setCompaniesId(val);
        fetchLines(val);
    };

    const toggleLine = (id) => {
        setSelectedIds(prev =>
            prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]
        );
    };

    const handleStore = async (e) => {
        e.preventDefault();
        setErrors({});
        setFlashError(null);
        setSubmitting(true);

        try {
            const data = await apiFetch(endpoints.store, {
                method: 'POST',
                body: JSON.stringify({
                    code,
                    companies_id: companiesId || undefined,
                    user_id:      userId      || undefined,
                    line_ids:     selectedIds,
                    supplier_reference: supplierReference || undefined,
                    incoming_id:        incomingId || undefined,
                }),
            });
            window.location.href = data.redirect;
        } catch (err) {
            if (err.errors) setErrors(err.errors);
            else setFlashError(err.message ?? (trans.error ?? 'Erreur'));
            setSubmitting(false);
        }
    };

    if (loading) {
        return (
            <div className="text-center py-4">
                <i className="fas fa-spinner fa-spin fa-2x text-secondary" />
            </div>
        );
    }

    const money = (value) => new Intl.NumberFormat('fr-FR', {
        style: 'currency', currency: 'EUR',
    }).format(Number(value ?? 0));

    // Ce que représentent les réceptions cochées, au prix de la commande d'achat.
    const selectedTotal = lines
        .filter(l => selectedIds.includes(l.id))
        .reduce((sum, l) => sum + Number(l.line_total ?? 0), 0);

    return (
        <div className="card card-outline card-orange">
            {flashError && (
                <div className="alert alert-danger alert-dismissible mx-3 mt-3 mb-0">
                    {flashError}
                    <button type="button" className="close" onClick={() => setFlashError(null)}>
                        <span>&times;</span>
                    </button>
                </div>
            )}

            {incoming && (
                <IncomingDocumentPanel incoming={incoming} selectedTotal={selectedTotal} money={money} />
            )}

            {/* Form */}
            <div className="card-body">
                <form onSubmit={handleStore}>
                    <div className="form-row">
                        {/* Company filter */}
                        <div className="form-group col-md-3">
                            <label>{trans.sort_company ?? 'Trier par tiers'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-building" /></span>
                                </div>
                                <select
                                    className={`form-control ${errors.companies_id ? 'is-invalid' : ''}`}
                                    value={companiesId}
                                    onChange={e => handleCompanyChange(e.target.value)}
                                >
                                    <option value="">{trans.select_company ?? 'Sélectionner un tiers'}</option>
                                    {companies.length === 0 && (
                                        <option disabled>{trans.no_select_company ?? 'Aucun tiers'}</option>
                                    )}
                                    {companies.map(c => (
                                        <option key={c.id} value={c.id}>{c.label}</option>
                                    ))}
                                </select>
                            </div>
                            {errors.companies_id && (
                                <span className="text-danger">{errors.companies_id[0]}</span>
                            )}
                        </div>

                        {/* Code (external ID) */}
                        <div className="form-group col-md-3">
                            <label>{trans.external_id ?? 'ID externe'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-external-link-square-alt" /></span>
                                </div>
                                <input
                                    type="text"
                                    className={`form-control ${errors.code ? 'is-invalid' : ''}`}
                                    value={code}
                                    onChange={e => setCode(e.target.value)}
                                    placeholder={trans.external_id ?? 'ID externe'}
                                />
                            </div>
                            {errors.code && (
                                <span className="text-danger">{errors.code[0]}</span>
                            )}
                        </div>

                        {/* Numéro de la facture du fournisseur : unique par tiers,
                            c'est lui qui rattache notre écriture à son document. */}
                        <div className="form-group col-md-3">
                            <label>Référence fournisseur</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-hashtag" /></span>
                                </div>
                                <input
                                    type="text"
                                    className={`form-control ${errors.supplier_reference ? 'is-invalid' : ''}`}
                                    value={supplierReference}
                                    onChange={e => setSupplierReference(e.target.value)}
                                    placeholder="N° de facture du fournisseur"
                                />
                            </div>
                            {errors.supplier_reference && (
                                <span className="text-danger">{errors.supplier_reference[0]}</span>
                            )}
                        </div>

                        {/* User */}
                        <div className="form-group col-md-3">
                            <label>{trans.user ?? 'Utilisateur'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-user" /></span>
                                </div>
                                <select
                                    className={`form-control ${errors.user_id ? 'is-invalid' : ''}`}
                                    value={userId}
                                    onChange={e => setUserId(e.target.value)}
                                >
                                    <option value="">{trans.select_user ?? 'Sélectionner un utilisateur'}</option>
                                    {users.map(u => (
                                        <option key={u.id} value={u.id}>{u.name}</option>
                                    ))}
                                </select>
                            </div>
                            {errors.user_id && (
                                <span className="text-danger">{errors.user_id[0]}</span>
                            )}
                        </div>
                    </div>

                    {/* Submit */}
                    <div className="row">
                        <div className="card-footer">
                            <button
                                type="submit"
                                className="btn btn-success btn-block"
                                disabled={submitting}
                            >
                                {submitting
                                    ? <><i className="fas fa-spinner fa-spin mr-1" />{trans.loading ?? 'Chargement…'}</>
                                    : trans.new_invoice ?? 'Nouvelle facture'
                                }
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {/* Table */}
            <div className="card-body table-responsive p-0">
                {linesLoading ? (
                    <div className="text-center py-3">
                        <i className="fas fa-spinner fa-spin text-secondary" />
                    </div>
                ) : (
                    <table className="table table-hover">
                        <thead>
                            <tr>
                                <th>{trans.order ?? 'Commande'}</th>
                                <th>{trans.purchase_order ?? 'Commande achat'}</th>
                                <th>{trans.purchase_receipt ?? 'Réception'}</th>
                                <th>{trans.supplier ?? 'Fournisseur'}</th>
                                <th>{trans.description ?? 'Description'}</th>
                                <th>{trans.qty ?? 'Qté reçue'}</th>
                                <th>{trans.action ?? 'Action'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <WaitingLinesTable
                                lines={lines}
                                selectedIds={selectedIds}
                                onToggle={toggleLine}
                                trans={trans}
                            />
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>{trans.order ?? 'Commande'}</th>
                                <th>{trans.purchase_order ?? 'Commande achat'}</th>
                                <th>{trans.purchase_receipt ?? 'Réception'}</th>
                                <th>{trans.supplier ?? 'Fournisseur'}</th>
                                <th>{trans.description ?? 'Description'}</th>
                                <th>{trans.qty ?? 'Qté reçue'}</th>
                                <th>{trans.action ?? 'Action'}</th>
                            </tr>
                        </tfoot>
                    </table>
                )}
            </div>
        </div>
    );
}
