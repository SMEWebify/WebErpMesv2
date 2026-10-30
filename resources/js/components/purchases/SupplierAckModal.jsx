import React, { useEffect, useState } from 'react';
import { apiRequest } from '../../lib/http';
import { uploadFiles } from '../files/api';
import { ackDraftLines, ackPayload, gapDays, gapBadgeClass, formatGap } from '../../lib/supplierAck';

function today() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

/**
 * Fenêtre « AR reçu » de la fiche commande d'achat : date et référence de l'AR, date
 * confirmée par ligne (par défaut la date demandée), dépôt facultatif du document dans
 * la GED de la commande (rôle `ar_fournisseur`).
 *
 * `onlyLineId` : ouverte depuis le menu d'une ligne, seule celle-ci est cochée.
 */
export default function SupplierAckModal({ open, lines, onlyLineId, ack, onClose, onSaved }) {
    const t = ack.trans ?? {};
    const [draft, setDraft]         = useState([]);
    const [ackAt, setAckAt]         = useState(today());
    const [reference, setReference] = useState('');
    const [file, setFile]           = useState(null);
    const [saving, setSaving]       = useState(false);
    const [error, setError]         = useState(null);

    useEffect(() => {
        if (!open) return;
        setDraft(ackDraftLines(lines, onlyLineId ?? null));
        const first = lines.find((l) => l.id === onlyLineId) ?? lines.find((l) => l.supplier_ack_reference);
        setReference(first?.supplier_ack_reference ?? '');
        setAckAt(today());
        setFile(null);
        setError(null);
    }, [open, onlyLineId]);

    if (!open) return null;

    const byId = Object.fromEntries(lines.map((l) => [l.id, l]));
    const checkedCount = draft.filter((l) => l.checked).length;
    const allChecked = draft.length > 0 && checkedCount === draft.length;

    const update = (id, patch) => setDraft((d) => d.map((l) => (l.id === id ? { ...l, ...patch } : l)));
    const toggleAll = () => setDraft((d) => d.map((l) => ({ ...l, checked: !allChecked })));

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (checkedCount === 0) { setError(t.no_line); return; }
        setSaving(true);
        setError(null);
        try {
            const res  = await apiRequest(ack.endpoints.acknowledge, {
                method: 'POST',
                body: JSON.stringify(ackPayload(draft, ackAt, reference)),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setError(data.message || Object.values(data.errors ?? {}).flat().join(' ') || t.error);
                return;
            }

            // Document de l'AR : l'AR est déjà enregistré, un échec du dépôt est seulement signalé
            let fileError = null;
            if (file) {
                try {
                    await uploadFiles(
                        { store: ack.endpoints.fileStore },
                        { type: 'purchase', id: ack.purchaseId },
                        [file],
                        { role: 'ar_fournisseur' },
                    );
                } catch (err) {
                    fileError = `${t.file_error} ${err.message ?? ''}`.trim();
                }
            }

            onSaved(data.lines ?? [], fileError);
        } catch {
            setError(t.error);
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <div className="modal-backdrop fade show" />
            <div className="modal show d-block" tabIndex="-1" role="dialog" aria-modal="true" aria-labelledby="supplier-ack-title">
                <div className="modal-dialog modal-lg modal-dialog-scrollable">
                    <form className="modal-content" onSubmit={handleSubmit}>
                        <div className="modal-header">
                            <h5 className="modal-title" id="supplier-ack-title">
                                <i className="fas fa-handshake me-2" />{t.title}
                            </h5>
                            <button type="button" className="btn-close" aria-label={t.cancel} onClick={onClose} />
                        </div>
                        <div className="modal-body">
                            {error && <div className="alert alert-danger py-2">{error}</div>}

                            <div className="row g-2 mb-3">
                                <div className="col-sm-4">
                                    <label className="form-label small fw-bold" htmlFor="supplier-ack-at">{t.ack_at}</label>
                                    <input id="supplier-ack-at" type="date" className="form-control form-control-sm" required
                                        value={ackAt} onChange={(e) => setAckAt(e.target.value)} />
                                </div>
                                <div className="col-sm-4">
                                    <label className="form-label small fw-bold" htmlFor="supplier-ack-reference">{t.reference}</label>
                                    <input id="supplier-ack-reference" type="text" className="form-control form-control-sm" maxLength={100}
                                        value={reference} onChange={(e) => setReference(e.target.value)} />
                                </div>
                                <div className="col-sm-4">
                                    <label className="form-label small fw-bold" htmlFor="supplier-ack-file">{t.file}</label>
                                    <input id="supplier-ack-file" type="file" className="form-control form-control-sm"
                                        accept=".pdf,.png,.jpg,.jpeg"
                                        onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
                                </div>
                            </div>

                            <p className="small text-body-secondary mb-2">{t.hint}</p>

                            <div className="table-responsive">
                                <table className="table table-sm align-middle mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th style={{ width: 32 }}>
                                                <input type="checkbox" className="form-check-input" checked={allChecked} onChange={toggleAll} aria-label={t.all_lines} />
                                            </th>
                                            <th>{t.line}</th>
                                            <th>{t.requested}</th>
                                            <th style={{ width: 170 }}>{t.confirmed}</th>
                                            <th className="text-end">{t.gap}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {draft.map((d) => {
                                            const line = byId[d.id] ?? {};
                                            const gap  = gapDays(line.delivery_date, d.confirmed_delivery_date);
                                            const gapClass = gapBadgeClass(gap);
                                            return (
                                                <tr key={d.id} className={d.checked ? '' : 'text-body-secondary'}>
                                                    <td>
                                                        <input type="checkbox" className="form-check-input" checked={d.checked}
                                                            onChange={() => update(d.id, { checked: !d.checked })} aria-label={line.label} />
                                                    </td>
                                                    <td>
                                                        <span className="small text-body-secondary me-1">{line.ordre}</span>
                                                        {line.label}
                                                        {line.supplier_ack_at && <span className="badge text-bg-secondary ms-1">{t.already}</span>}
                                                    </td>
                                                    <td className="small">{line.delivery_date ? line.delivery_date.slice(0, 10).split('-').reverse().join('/') : '—'}</td>
                                                    <td>
                                                        <input type="date" className="form-control form-control-sm" disabled={!d.checked}
                                                            value={d.confirmed_delivery_date}
                                                            onChange={(e) => update(d.id, { confirmed_delivery_date: e.target.value })} />
                                                    </td>
                                                    <td className="text-end">
                                                        {gapClass && <span className={`badge ${gapClass}`}>{formatGap(gap, t.day_unit)}</span>}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-sm btn-outline-secondary" onClick={onClose}>{t.cancel}</button>
                            <button type="submit" className="btn btn-sm btn-primary" disabled={saving || checkedCount === 0}>
                                {saving
                                    ? <><i className="fas fa-spinner fa-spin me-1" />{t.saving}</>
                                    : <><i className="fas fa-check me-1" />{t.save} ({checkedCount})</>}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
