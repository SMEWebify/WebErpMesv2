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

function formatDeliveryDate(dateStr) {
    if (!dateStr) return null;
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        const date = new Date(y, m - 1, d);
        const today = new Date(); today.setHours(0, 0, 0, 0);
        const isPast = date < today;
        const formatted = `${String(d).padStart(2, '0')}/${String(m).padStart(2, '0')}/${y}`;
        return { formatted, isPast };
    } catch {
        return null;
    }
}

// ---------------------------------------------------------------------------
// DeliveryDateCell
// ---------------------------------------------------------------------------

function DeliveryDateCell({ date }) {
    const parsed = formatDeliveryDate(date);
    if (!parsed) return <span className="text-muted">-</span>;
    if (parsed.isPast) {
        return (
            <span className="badge badge-danger">
                <i className="fas fa-exclamation-triangle mr-1" />
                {parsed.formatted}
            </span>
        );
    }
    return <span className="badge badge-success">{parsed.formatted}</span>;
}

// ---------------------------------------------------------------------------
// WaitingLines Table
// ---------------------------------------------------------------------------

function WaitingLinesTable({ lines, selectedIds, onToggle, trans }) {
    if (!lines.length) {
        return (
            <tr>
                <td colSpan={11} className="text-center text-muted py-3">
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
            {/* Order qty */}
            <td>
                {line.order_line_qty != null
                    ? <>{line.order_line_qty} x</>
                    : <span className="text-muted">{trans.generic ?? 'Générique'}</span>
                }
            </td>
            {/* Order label */}
            <td>
                {line.order_line_label ?? <span className="text-muted">{trans.generic ?? 'Générique'}</span>}
            </td>
            {/* Task / Label */}
            <td>
                {line.tasks_id ? (
                    <>
                        {line.task_url && (
                            <a href={line.task_url} className="btn btn-sm btn-success mr-1">
                                {trans.view ?? 'Voir'}
                            </a>
                        )}
                        #{line.task_id} - {line.task_label}
                        {line.component_label && <> - {line.component_label}</>}
                    </>
                ) : (
                    line.label
                )}
            </td>
            {/* Product */}
            <td>
                {line.product_url && (
                    <a href={line.product_url} className="btn btn-xs btn-info">
                        <i className="fas fa-eye" />
                    </a>
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
            {/* Supplier */}
            <td>{line.supplier_label}</td>
            {/* Description */}
            <td>{line.code} {line.label}</td>
            {/* Qty */}
            <td>{new Intl.NumberFormat('fr-FR').format(line.qty)}</td>
            {/* Delivery date */}
            <td><DeliveryDateCell date={line.delivery_date} /></td>
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

export default function PurchasesWaitingReceipt({ endpoints, trans, initialCode }) {
    const [companies, setCompanies]     = useState([]);
    const [users, setUsers]             = useState([]);
    const [lines, setLines]             = useState([]);
    const [loading, setLoading]         = useState(true);
    const [linesLoading, setLinesLoading] = useState(false);
    const [submitting, setSubmitting]   = useState(false);
    const [errors, setErrors]           = useState({});
    const [flashError, setFlashError]   = useState(null);

    // Form state
    const [companiesId, setCompaniesId]             = useState('');
    const [code, setCode]                           = useState(initialCode ?? '');
    const [label, setLabel]                         = useState(initialCode ?? '');
    const [deliveryNoteNumber, setDeliveryNoteNumber] = useState('');
    const [userId, setUserId]                       = useState('');
    const [selectedIds, setSelectedIds]             = useState([]);

    // Initial load
    useEffect(() => {
        apiFetch(endpoints.init)
            .then(data => {
                setCompanies(data.companies ?? []);
                setUsers(data.users ?? []);
                setLines(data.lines ?? []);
                if (!code) {
                    setCode(data.initial_code ?? '');
                    setLabel(data.initial_code ?? '');
                }
            })
            .catch(() => setFlashError(trans.error ?? 'Erreur de chargement'))
            .finally(() => setLoading(false));
    }, []);

    // Reload lines on company filter change
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

    const buildPayload = () => ({
        code,
        label,
        companies_id: companiesId || undefined,
        delivery_note_number: deliveryNoteNumber || undefined,
        user_id: userId || undefined,
    });

    const handleStore = async (e) => {
        e.preventDefault();
        setErrors({});
        setFlashError(null);
        setSubmitting(true);

        try {
            const data = await apiFetch(endpoints.store, {
                method: 'POST',
                body: JSON.stringify({ ...buildPayload(), line_ids: selectedIds }),
            });
            window.location.href = data.redirect;
        } catch (err) {
            if (err.errors) setErrors(err.errors);
            else setFlashError(err.message ?? (trans.error ?? 'Erreur'));
            setSubmitting(false);
        }
    };

    const handleStoreEmpty = async (e) => {
        e.preventDefault();
        setErrors({});
        setFlashError(null);
        setSubmitting(true);

        try {
            const data = await apiFetch(endpoints.storeEmpty, {
                method: 'POST',
                body: JSON.stringify(buildPayload()),
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

                        {/* Code */}
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
                                    onChange={e => { setCode(e.target.value); setLabel(e.target.value); }}
                                    placeholder={trans.external_id ?? 'ID externe'}
                                />
                            </div>
                            {errors.code && (
                                <span className="text-danger">{errors.code[0]}</span>
                            )}
                        </div>

                        {/* Delivery note */}
                        <div className="form-group col-md-3">
                            <label>{trans.delivery_note_number ?? 'N° bon de livraison'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-external-link-square-alt" /></span>
                                </div>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={deliveryNoteNumber}
                                    onChange={e => setDeliveryNoteNumber(e.target.value)}
                                    placeholder={trans.delivery_note_number ?? 'N° bon de livraison'}
                                />
                            </div>
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

                    {/* Buttons */}
                    <div className="row">
                        <div className="card-footer">
                            <div className="input-group">
                                <button
                                    type="submit"
                                    className="btn btn-success"
                                    disabled={submitting}
                                >
                                    {submitting
                                        ? <><i className="fas fa-spinner fa-spin mr-1" />{trans.loading ?? 'Chargement…'}</>
                                        : trans.new_receipt ?? 'Nouveau reçu'
                                    }
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary ml-2"
                                    disabled={submitting}
                                    onClick={handleStoreEmpty}
                                >
                                    {trans.new_empty_receipt ?? 'Nouveau reçu vierge'}
                                </button>
                            </div>
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
                                <th>{trans.qty ?? 'Qté'}</th>
                                <th>{trans.order ?? 'Commande'} {trans.order_label ?? 'Libellé'}</th>
                                <th>{trans.label ?? 'Libellé'}</th>
                                <th>{trans.product ?? 'Produit'}</th>
                                <th>{trans.purchase_order ?? 'Commande achat'}</th>
                                <th>{trans.supplier ?? 'Fournisseur'}</th>
                                <th>{trans.description ?? 'Description'}</th>
                                <th>{trans.qty ?? 'Qté'}</th>
                                <th>{trans.delivery_date ?? 'Date livraison'}</th>
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
                                <th>{trans.qty ?? 'Qté'}</th>
                                <th>{trans.order ?? 'Commande'} {trans.order_label ?? 'Libellé'}</th>
                                <th>{trans.label ?? 'Libellé'}</th>
                                <th>{trans.product ?? 'Produit'}</th>
                                <th>{trans.purchase_order ?? 'Commande achat'}</th>
                                <th>{trans.supplier ?? 'Fournisseur'}</th>
                                <th>{trans.description ?? 'Description'}</th>
                                <th>{trans.qty ?? 'Qté'}</th>
                                <th>{trans.delivery_date ?? 'Date livraison'}</th>
                                <th>{trans.action ?? 'Action'}</th>
                            </tr>
                        </tfoot>
                    </table>
                )}
            </div>
        </div>
    );
}
