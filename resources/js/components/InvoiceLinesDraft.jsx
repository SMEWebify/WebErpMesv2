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

export default function InvoiceLinesDraft({ invoiceId, statu: initialStatu, lines: initialLines, endpoints, currency, trans }) {
    const [lines, setLines]   = useState(initialLines);
    const [statu, setStatu]   = useState(initialStatu);
    const [saving, setSaving] = useState({});
    const [emitting, setEmitting] = useState(false);
    const [error, setError]   = useState(null);

    const isDraft = statu === 1;

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
            setError(e.data?.message ?? 'Erreur lors de la sauvegarde.');
        } finally {
            setSaving(prev => ({ ...prev, [lineId]: false }));
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
            setError(e.data?.message ?? 'Erreur lors de l\'émission.');
            setEmitting(false);
        }
    }

    return (
        <div>
            {isDraft && (
                <div className="alert alert-warning d-flex justify-content-between align-items-center py-2 mb-3">
                    <span>
                        <i className="fas fa-edit mr-2"></i>
                        <strong>Brouillon</strong> — Les prix, quantités et remises sont modifiables. La facture sera verrouillée après émission.
                    </span>
                    <button className="btn btn-success btn-sm ml-3" onClick={handleEmit} disabled={emitting}>
                        {emitting
                            ? <><i className="fas fa-spinner fa-spin mr-1"></i>Émission…</>
                            : <><i className="fas fa-paper-plane mr-1"></i>Émettre la facture</>}
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
                            <th className="text-right">{trans.qty ?? 'Qté'}</th>
                            <th>{trans.unit ?? 'U.'}</th>
                            <th className="text-right">{trans.price ?? 'P.U HT'}</th>
                            <th className="text-right">{trans.discount ?? 'Remise'}</th>
                            <th className="text-right">{trans.vat ?? 'TVA'}</th>
                            <th className="text-right">{trans.total ?? 'Montant HT'}</th>
                            <th>{trans.status ?? 'Statut'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {lines.map(line => (
                            <tr key={line.id}>
                                <td>
                                    <a href={line.order_url} className="btn btn-xs btn-default">
                                        <i className="fas fa-external-link-alt mr-1"></i>{line.order_code}
                                    </a>
                                </td>
                                <td>
                                    {line.delivery_url
                                        ? <a href={line.delivery_url} className="btn btn-xs btn-default">
                                            <i className="fas fa-external-link-alt mr-1"></i>{line.delivery_code}
                                          </a>
                                        : <span className="text-muted small">Sans BL</span>}
                                </td>
                                <td className="small">{line.order_line_code}</td>
                                <td>{line.order_line_label}</td>

                                {isDraft ? (
                                    <>
                                        <td>
                                            <input
                                                type="number" min="0" step="0.001"
                                                className="form-control form-control-sm text-right"
                                                style={{ width: '80px' }}
                                                defaultValue={line.qty}
                                                onBlur={e => handleLineChange(line.id, 'qty', e.target.value)}
                                            />
                                        </td>
                                        <td className="small">{line.unit_label}</td>
                                        <td>
                                            <input
                                                type="number" min="0" step="0.01"
                                                className="form-control form-control-sm text-right"
                                                style={{ width: '100px' }}
                                                defaultValue={line.unit_price}
                                                onBlur={e => handleLineChange(line.id, 'unit_price', e.target.value)}
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="number" min="0" max="100" step="0.01"
                                                className="form-control form-control-sm text-right"
                                                style={{ width: '70px' }}
                                                defaultValue={line.discount}
                                                onBlur={e => handleLineChange(line.id, 'discount', e.target.value)}
                                            />
                                        </td>
                                        <td className="text-right small">{line.vat_rate} %</td>
                                        <td className="text-right">
                                            {saving[line.id]
                                                ? <i className="fas fa-spinner fa-spin text-muted"></i>
                                                : <LineTotal qty={line.qty} unitPrice={line.unit_price} discount={line.discount} />}
                                        </td>
                                    </>
                                ) : (
                                    <>
                                        <td className="text-right">{line.qty}</td>
                                        <td className="small">{line.unit_label}</td>
                                        <td className="text-right">{formatCurrency(line.unit_price, currency)}</td>
                                        <td className="text-right">{line.discount} %</td>
                                        <td className="text-right small">{line.vat_rate} %</td>
                                        <td className="text-right">
                                            {formatCurrency(line.qty * line.unit_price * (1 - line.discount / 100), currency)}
                                        </td>
                                    </>
                                )}

                                <td>
                                    <InvoiceStatusBadge status={line.invoice_status} trans={trans} />
                                </td>
                            </tr>
                        ))}
                        {lines.length === 0 && (
                            <tr><td colSpan="11" className="text-center text-muted py-3">Aucune ligne</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function InvoiceStatusBadge({ status, trans }) {
    const map = {
        1: 'badge-info',
        2: 'badge-primary',
        3: 'badge-warning',
        4: 'badge-danger',
        5: 'badge-success',
    };
    const labels = {
        1: trans.in_progress ?? 'En cours',
        2: trans.send        ?? 'Envoyée',
        3: trans.pending     ?? 'En attente',
        4: trans.unpaid      ?? 'Impayée',
        5: trans.paid        ?? 'Payée',
    };
    return <span className={`badge ${map[status] ?? 'badge-secondary'}`}>{labels[status] ?? status}</span>;
}
