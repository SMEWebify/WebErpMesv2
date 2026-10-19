import React, { useState } from 'react';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, method, body) {
    const res = await fetch(url, {
        method,
        headers: {
            'Accept':       'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: body ? JSON.stringify(body) : undefined,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw { status: res.status, data };
    return data;
}

function formatCurrency(value, currency) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: currency ?? 'EUR' }).format(value);
}

function LineTotal({ qty, unitPrice, discount }) {
    const total = qty * unitPrice * (1 - discount / 100);
    return <span>{formatCurrency(isNaN(total) ? 0 : total)}</span>;
}

/**
 * Première erreur de validation renvoyée par Laravel, à défaut le message brut.
 */
function firstError(e, fallback) {
    const errors = e?.data?.errors;
    if (errors) {
        const first = Object.values(errors)[0];
        if (Array.isArray(first) && first.length) return first[0];
    }
    return e?.data?.message ?? fallback;
}

const EMPTY_LINE = {
    label: '', code: '', qty: '1', unit_price: '', discount: '0',
    methods_units_id: '', accounting_vats_id: '',
};

/**
 * Saisie d'une ligne libre : une ligne de facture sans ligne de commande en
 * face. Sert au frais oublié au moment de la commande — port, frais de dossier,
 * prestation ponctuelle — que l'on veut porter sur la facture existante plutôt
 * que sur un second document.
 */
function NewLineForm({ vats, units, currency, onSubmit, saving }) {
    const defaultVat  = vats.find(v => Number(v.default) === 1);
    const defaultUnit = units.find(u => Number(u.default) === 1);

    const blank = () => ({
        ...EMPTY_LINE,
        methods_units_id:   defaultUnit ? String(defaultUnit.id) : '',
        accounting_vats_id: defaultVat  ? String(defaultVat.id)  : '',
    });

    const [form, setForm] = useState(blank);

    const set = (field) => (e) => setForm(prev => ({ ...prev, [field]: e.target.value }));

    const qty      = parseFloat(form.qty) || 0;
    const price    = parseFloat(form.unit_price) || 0;
    const discount = parseFloat(form.discount) || 0;
    const total    = qty * price * (1 - discount / 100);

    function handleSubmit(e) {
        e.preventDefault();
        onSubmit({
            label:              form.label,
            code:               form.code || null,
            qty,
            unit_price:         price,
            discount,
            methods_units_id:   form.methods_units_id   ? Number(form.methods_units_id)   : null,
            accounting_vats_id: form.accounting_vats_id ? Number(form.accounting_vats_id) : null,
        }, () => setForm(blank()));
    }

    return (
        <form className="card card-outline card-primary mt-3 mb-3" onSubmit={handleSubmit}>
            <div className="card-header py-2">
                <h3 className="card-title">
                    <i className="fas fa-plus-circle me-2"></i>Ajouter une ligne
                </h3>
                <span className="text-muted small float-end">
                    Frais de port, frais de dossier, prestation non commandée
                </span>
            </div>
            <div className="card-body py-2">
                <div className="row gx-2 align-items-end">
                    <div className="mb-3 col-md-3 mb-2">
                        <label className="small mb-1">Désignation *</label>
                        <input type="text" className="form-control form-control-sm" required maxLength={255}
                               placeholder="Frais de port" value={form.label} onChange={set('label')} />
                    </div>
                    <div className="mb-3 col-md-1 mb-2">
                        <label className="small mb-1">Réf.</label>
                        <input type="text" className="form-control form-control-sm" maxLength={255}
                               value={form.code} onChange={set('code')} />
                    </div>
                    <div className="mb-3 col-md-1 mb-2">
                        <label className="small mb-1">Qté *</label>
                        <input type="number" min="0" step="0.001" className="form-control form-control-sm text-end"
                               required value={form.qty} onChange={set('qty')} />
                    </div>
                    <div className="mb-3 col-md-1 mb-2">
                        <label className="small mb-1">Unité</label>
                        <select className="form-select form-select-sm" value={form.methods_units_id} onChange={set('methods_units_id')}>
                            {units.map(u => <option key={u.id} value={u.id}>{u.label}</option>)}
                        </select>
                    </div>
                    <div className="mb-3 col-md-2 mb-2">
                        <label className="small mb-1">P.U HT *</label>
                        <input type="number" min="0" step="0.01" className="form-control form-control-sm text-end"
                               required value={form.unit_price} onChange={set('unit_price')} />
                    </div>
                    <div className="mb-3 col-md-1 mb-2">
                        <label className="small mb-1">Remise %</label>
                        <input type="number" min="0" max="100" step="0.01" className="form-control form-control-sm text-end"
                               value={form.discount} onChange={set('discount')} />
                    </div>
                    <div className="mb-3 col-md-1 mb-2">
                        <label className="small mb-1">TVA</label>
                        <select className="form-select form-select-sm" value={form.accounting_vats_id} onChange={set('accounting_vats_id')}>
                            {vats.map(v => <option key={v.id} value={v.id}>{v.label}</option>)}
                        </select>
                    </div>
                    <div className="mb-3 col-md-2 mb-2 text-end">
                        <div className="small text-muted mb-1">{formatCurrency(total, currency)} HT</div>
                        <button type="submit" className="btn btn-primary btn-sm w-100" disabled={saving}>
                            {saving
                                ? <><i className="fas fa-spinner fa-spin me-1"></i>Ajout…</>
                                : <><i className="fas fa-plus me-1"></i>Ajouter</>}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    );
}

export default function InvoiceLinesDraft({ invoiceId, statu: initialStatu, lines: initialLines, endpoints, currency, vats = [], units = [], trans }) {
    const [lines, setLines]   = useState(initialLines);
    const [statu, setStatu]   = useState(initialStatu);
    const [saving, setSaving] = useState({});
    const [adding, setAdding] = useState(false);
    const [emitting, setEmitting] = useState(false);
    const [error, setError]   = useState(null);

    const isDraft  = statu === 1;
    const colCount = isDraft ? 12 : 11;

    async function handleLineChange(lineId, field, rawValue) {
        const value = parseFloat(rawValue);
        if (isNaN(value)) return;

        setLines(prev => prev.map(l => l.id === lineId ? { ...l, [field]: value } : l));
        setSaving(prev => ({ ...prev, [lineId]: true }));
        setError(null);

        const url = endpoints.updateLine.replace('__LINE_ID__', lineId);
        try {
            await apiFetch(url, 'PATCH', { [field]: value });
        } catch (e) {
            setError(firstError(e, 'Erreur lors de la sauvegarde.'));
        } finally {
            setSaving(prev => ({ ...prev, [lineId]: false }));
        }
    }

    async function handleAddLine(payload, reset) {
        setAdding(true);
        setError(null);
        try {
            const data = await apiFetch(endpoints.storeLine, 'POST', payload);
            setLines(prev => [...prev, data.line]);
            reset();
        } catch (e) {
            setError(firstError(e, "Erreur lors de l'ajout de la ligne."));
        } finally {
            setAdding(false);
        }
    }

    async function handleDeleteLine(line) {
        const message = line.is_free_line
            ? `Supprimer la ligne « ${line.order_line_label} » ?`
            : `Supprimer la ligne « ${line.order_line_label} » ? Les quantités seront rendues à la commande ${line.order_code ?? ''}.`;
        if (!confirm(message)) return;

        setSaving(prev => ({ ...prev, [line.id]: true }));
        setError(null);
        try {
            await apiFetch(endpoints.deleteLine.replace('__LINE_ID__', line.id), 'DELETE');
            setLines(prev => prev.filter(l => l.id !== line.id));
        } catch (e) {
            setError(firstError(e, 'Erreur lors de la suppression.'));
            setSaving(prev => ({ ...prev, [line.id]: false }));
        }
    }

    async function handleEmit() {
        if (!confirm('Émettre la facture ? Elle ne sera plus modifiable.')) return;
        setEmitting(true);
        setError(null);
        try {
            await apiFetch(endpoints.emit, 'PATCH');
            setStatu(2);
            setLines(prev => prev.map(l => ({ ...l, invoice_status: 2 })));
            window.location.reload();
        } catch (e) {
            setError(firstError(e, "Erreur lors de l'émission."));
            setEmitting(false);
        }
    }

    return (
        <div>
            {isDraft && (
                <div className="alert alert-warning d-flex justify-content-between align-items-center py-2 mb-3">
                    <span>
                        <i className="fas fa-edit me-2"></i>
                        <strong>Brouillon</strong> — Les lignes, prix, quantités et remises sont modifiables. La facture sera verrouillée après émission.
                    </span>
                    <button className="btn btn-success btn-sm ms-3" onClick={handleEmit} disabled={emitting}>
                        {emitting
                            ? <><i className="fas fa-spinner fa-spin me-1"></i>Émission…</>
                            : <><i className="fas fa-paper-plane me-1"></i>Émettre la facture</>}
                    </button>
                </div>
            )}

            {error && <div className="alert alert-danger py-2">{error}</div>}

            <div className="table-responsive">
                <table className="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>{trans.order ?? 'Commande'}</th>
                            <th>{trans.delivery ?? 'BL'}</th>
                            <th>{trans.ref ?? 'Réf.'}</th>
                            <th>{trans.description ?? 'Désignation'}</th>
                            <th className="text-end">{trans.qty ?? 'Qté'}</th>
                            <th>{trans.unit ?? 'U.'}</th>
                            <th className="text-end">{trans.price ?? 'P.U HT'}</th>
                            <th className="text-end">{trans.discount ?? 'Remise'}</th>
                            <th className="text-end">{trans.vat ?? 'TVA'}</th>
                            <th className="text-end">{trans.total ?? 'Montant HT'}</th>
                            <th>{trans.status ?? 'Statut'}</th>
                            {isDraft && <th></th>}
                        </tr>
                    </thead>
                    <tbody>
                        {lines.map(line => (
                            <tr key={line.id}>
                                <td>
                                    {line.order_url
                                        ? <a href={line.order_url} className="btn btn-sm btn-outline-secondary">
                                            <i className="fas fa-external-link-alt me-1"></i>{line.order_code}
                                          </a>
                                        : <span className="badge text-bg-light border">Ligne libre</span>}
                                </td>
                                <td>
                                    {line.delivery_url
                                        ? <a href={line.delivery_url} className="btn btn-sm btn-outline-secondary">
                                            <i className="fas fa-external-link-alt me-1"></i>{line.delivery_code}
                                          </a>
                                        : <span className="text-muted small">Sans BL</span>}
                                </td>
                                <td className="small">{line.order_line_code}{line.order_line_ind ? <span className="badge text-bg-light ms-1" title="Indice du plan">ind. {line.order_line_ind}</span> : null}</td>
                                <td>{line.order_line_label}</td>

                                {isDraft ? (
                                    <>
                                        <td>
                                            <input
                                                type="number" min="0" step="0.001"
                                                className="form-control form-control-sm text-end"
                                                style={{ width: '80px' }}
                                                defaultValue={line.qty}
                                                onBlur={e => handleLineChange(line.id, 'qty', e.target.value)}
                                            />
                                        </td>
                                        <td className="small">{line.unit_label}</td>
                                        <td>
                                            <input
                                                type="number" min="0" step="0.01"
                                                className="form-control form-control-sm text-end"
                                                style={{ width: '100px' }}
                                                defaultValue={line.unit_price}
                                                onBlur={e => handleLineChange(line.id, 'unit_price', e.target.value)}
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="number" min="0" max="100" step="0.01"
                                                className="form-control form-control-sm text-end"
                                                style={{ width: '70px' }}
                                                defaultValue={line.discount}
                                                onBlur={e => handleLineChange(line.id, 'discount', e.target.value)}
                                            />
                                        </td>
                                        <td className="text-end small">{line.vat_rate} %</td>
                                        <td className="text-end">
                                            {saving[line.id]
                                                ? <i className="fas fa-spinner fa-spin text-muted"></i>
                                                : <LineTotal qty={line.qty} unitPrice={line.unit_price} discount={line.discount} />}
                                        </td>
                                    </>
                                ) : (
                                    <>
                                        <td className="text-end">{line.qty}</td>
                                        <td className="small">{line.unit_label}</td>
                                        <td className="text-end">{formatCurrency(line.unit_price, currency)}</td>
                                        <td className="text-end">{line.discount} %</td>
                                        <td className="text-end small">{line.vat_rate} %</td>
                                        <td className="text-end">
                                            {formatCurrency(line.qty * line.unit_price * (1 - line.discount / 100), currency)}
                                        </td>
                                    </>
                                )}

                                <td>
                                    <InvoiceStatusBadge status={line.invoice_status} trans={trans} />
                                </td>

                                {isDraft && (
                                    <td className="text-end">
                                        <button
                                            type="button"
                                            className="btn btn-sm btn-outline-danger"
                                            title="Supprimer la ligne"
                                            disabled={saving[line.id]}
                                            onClick={() => handleDeleteLine(line)}
                                        >
                                            <i className="fas fa-trash"></i>
                                        </button>
                                    </td>
                                )}
                            </tr>
                        ))}
                        {lines.length === 0 && (
                            <tr><td colSpan={colCount} className="text-center text-muted py-3">Aucune ligne</td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            {isDraft && (
                <NewLineForm
                    vats={vats}
                    units={units}
                    currency={currency}
                    saving={adding}
                    onSubmit={handleAddLine}
                />
            )}
        </div>
    );
}

function InvoiceStatusBadge({ status, trans }) {
    const map = {
        1: 'text-bg-info',
        2: 'text-bg-primary',
        3: 'text-bg-warning',
        4: 'text-bg-danger',
        5: 'text-bg-success',
    };
    const labels = {
        1: trans.in_progress ?? 'En cours',
        2: trans.send        ?? 'Envoyée',
        3: trans.pending     ?? 'En attente',
        4: trans.unpaid      ?? 'Impayée',
        5: trans.paid        ?? 'Payée',
    };
    return <span className={`badge ${map[status] ?? 'text-bg-secondary'}`}>{labels[status] ?? status}</span>;
}
