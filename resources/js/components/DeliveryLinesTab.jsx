import React, { useState } from 'react';
import { formatQty } from '../utils';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const INVOICE_STATUS = {
    1: { badge: 'badge-info',    label: 'Facturable' },
    2: { badge: 'badge-danger',  label: 'Non facturable' },
    3: { badge: 'badge-warning', label: 'Partiellement' },
    4: { badge: 'badge-success', label: 'Facturé' },
};

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// ---------------------------------------------------------------------------
// ReturnDrawer — slide-in depuis la droite (pattern QuoteLinesPage)
// ---------------------------------------------------------------------------

function ReturnDrawer({ open, line, deliveryId, nonConformities, endpoints, trans, onClose, onSuccess }) {
    const [qty, setQty]             = useState(1);
    const [ncId, setNcId]           = useState('');
    const [issue, setIssue]         = useState('');
    const [errors, setErrors]       = useState({});
    const [saving, setSaving]       = useState(false);

    // Reset quand on ouvre sur une nouvelle ligne
    React.useEffect(() => {
        if (open) {
            setQty(1);
            setNcId('');
            setIssue('');
            setErrors({});
        }
    }, [open, line?.id]);

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const res = await fetch(endpoints.store, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    label: `Retour - ${line?.label ?? line?.code ?? ''}`,
                    deliverys_id: deliveryId,
                    quality_non_conformity_id: ncId || null,
                    lines: [{
                        delivery_line_id: line?.id,
                        qty: Number(qty) || 1,
                        issue_description: issue || null,
                    }],
                }),
            });
            const data = await res.json();
            if (!res.ok) {
                setErrors(data.errors ?? { _global: data.message ?? 'Erreur' });
            } else {
                onSuccess(data.message);
                onClose();
            }
        } catch (err) {
            setErrors({ _global: err.message });
        } finally {
            setSaving(false);
        }
    }

    return (
        <>
            {/* Backdrop */}
            <div
                onClick={onClose}
                style={{
                    position: 'fixed', inset: 0,
                    background: 'rgba(0,0,0,0.35)',
                    zIndex: 1040,
                    opacity: open ? 1 : 0,
                    pointerEvents: open ? 'auto' : 'none',
                    transition: 'opacity 0.2s',
                }}
            />

            {/* Drawer panel */}
            <div style={{
                position: 'fixed', top: 0, right: 0, bottom: 0,
                width: 460, maxWidth: '95vw',
                background: '#fff', zIndex: 1050,
                display: 'flex', flexDirection: 'column',
                transform: open ? 'translateX(0)' : 'translateX(100%)',
                transition: 'transform 0.25s ease',
                boxShadow: '-4px 0 24px rgba(0,0,0,0.15)',
            }}>

                {/* Header */}
                <div className="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                     style={{ background: '#fff3cd' }}>
                    <strong>
                        <i className="fas fa-undo mr-2 text-warning" />
                        {trans.add_return ?? 'Nouveau retour'}
                    </strong>
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={onClose}>
                        <i className="fas fa-times" />
                    </button>
                </div>

                {/* Body */}
                <div className="flex-grow-1 overflow-auto p-3">
                    {errors._global && (
                        <div className="alert alert-danger py-2">{errors._global}</div>
                    )}

                    <form id="return-drawer-form" onSubmit={handleSubmit}>

                        {/* Ligne de livraison — lecture seule */}
                        <div className="form-group mb-3">
                            <label className="mb-1 small font-weight-bold">
                                {trans.delivery_line ?? 'Ligne de livraison'}
                            </label>
                            <div className="input-group input-group-sm">
                                <div className="input-group-prepend">
                                    <span className="input-group-text bg-light">
                                        <i className="fas fa-box" />
                                    </span>
                                </div>
                                <input
                                    type="text"
                                    className="form-control bg-light"
                                    value={line ? `${line.code ? line.code + ' — ' : ''}${line.label ?? ''}` : ''}
                                    readOnly
                                />
                            </div>
                        </div>

                        {/* Quantité */}
                        <div className="form-group mb-3">
                            <label className="mb-1 small font-weight-bold">
                                {trans.qty ?? 'Quantité'}
                            </label>
                            <input
                                type="number"
                                min="1"
                                className="form-control form-control-sm"
                                value={qty}
                                onChange={e => setQty(e.target.value)}
                            />
                        </div>

                        {/* Non-conformité */}
                        <div className="form-group mb-3">
                            <label className="mb-1 small font-weight-bold">
                                {trans.non_conformity ?? 'Non-conformité'}
                            </label>
                            <select
                                className="form-control form-control-sm"
                                value={ncId}
                                onChange={e => setNcId(e.target.value)}
                            >
                                <option value="">{trans.choose ?? 'Choisir…'}</option>
                                {nonConformities.map(nc => (
                                    <option key={nc.id} value={nc.id}>{nc.code}</option>
                                ))}
                            </select>
                        </div>

                        {/* Description du problème */}
                        <div className="form-group mb-3">
                            <label className="mb-1 small font-weight-bold">
                                {trans.issue ?? 'Description du problème'}
                            </label>
                            <textarea
                                className={`form-control form-control-sm ${errors['lines.0.issue_description'] ? 'is-invalid' : ''}`}
                                rows={4}
                                placeholder={`${trans.issue ?? 'Décrire le problème'}…`}
                                value={issue}
                                onChange={e => setIssue(e.target.value)}
                            />
                            {errors['lines.0.issue_description'] && (
                                <div className="invalid-feedback">{errors['lines.0.issue_description'][0]}</div>
                            )}
                        </div>

                    </form>
                </div>

                {/* Footer */}
                <div className="px-3 py-2 border-top d-flex justify-content-end" style={{ gap: 8, background: '#f8f9fa' }}>
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={onClose}>
                        {trans.cancel ?? 'Annuler'}
                    </button>
                    <button
                        type="submit"
                        form="return-drawer-form"
                        className="btn btn-sm btn-warning"
                        disabled={saving}
                    >
                        <i className="fas fa-save mr-1" />
                        {saving ? '…' : (trans.save ?? 'Enregistrer')}
                    </button>
                </div>

            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function DeliveryLinesTab({ lines: initialLines, deliveryId, nonConformities, endpoints, trans }) {
    const [lines, setLines]           = useState(initialLines ?? []);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [activeLine, setActiveLine] = useState(null);
    const [flash, setFlash]           = useState('');
    const [error, setError]           = useState('');
    const [busyId, setBusyId]         = useState(null);

    function openDrawer(line) {
        setActiveLine(line);
        setDrawerOpen(true);
    }

    function handleSuccess(msg) {
        setFlash(msg);
        setTimeout(() => setFlash(''), 4000);
    }

    async function markNotChargeable(line) {
        if (!endpoints.markNotChargeable) return;
        if (!window.confirm(trans.confirm_not_chargeable ?? 'Marquer cette ligne comme non facturable ?')) return;

        setBusyId(line.id);
        setError('');
        try {
            const url = endpoints.markNotChargeable.replace('__LINE__', line.id);
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
            const data = await res.json();
            if (!res.ok) {
                setError(data.error ?? data.message ?? 'Erreur');
            } else {
                setLines(prev => prev.map(l => l.id === line.id ? { ...l, invoice_status: 2 } : l));
                handleSuccess(trans.mark_not_chargeable ?? 'Ligne marquée non facturable');
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setBusyId(null);
        }
    }

    return (
        <div>
            {/* Flash success */}
            {flash && (
                <div className="alert alert-success alert-dismissible py-2 mb-2" role="alert">
                    <i className="fas fa-check-circle mr-1" />{flash}
                    <button type="button" className="close py-1" onClick={() => setFlash('')}>
                        <span>&times;</span>
                    </button>
                </div>
            )}

            {/* Flash error */}
            {error && (
                <div className="alert alert-danger alert-dismissible py-2 mb-2" role="alert">
                    <i className="fas fa-exclamation-triangle mr-1" />{error}
                    <button type="button" className="close py-1" onClick={() => setError('')}>
                        <span>&times;</span>
                    </button>
                </div>
            )}

            <div className="table-responsive">
                <table className="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>{trans.order ?? 'Commande'}</th>
                            <th>{trans.code ?? 'Code'}</th>
                            <th>{trans.description ?? 'Description'}</th>
                            <th>{trans.qty ?? 'Qté'}</th>
                            <th>{trans.unit ?? 'Unité'}</th>
                            <th>{trans.delivered_qty ?? 'Livré'}</th>
                            <th>{trans.remaining_qty ?? 'Restant'}</th>
                            <th>{trans.invoice_status ?? 'Facturation'}</th>
                            <th style={{ width: 110 }} className="text-right">{trans.return ?? 'Retour'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {lines.length === 0 ? (
                            <tr>
                                <td colSpan={9} className="text-center text-muted py-3">
                                    {trans.no_data ?? 'Aucune ligne'}
                                </td>
                            </tr>
                        ) : lines.map(line => {
                            const inv = INVOICE_STATUS[line.invoice_status];
                            return (
                                <tr key={line.id}>
                                    <td>
                                        <a href={line.order_url} className="btn btn-xs btn-outline-secondary">
                                            {line.order_code}
                                        </a>
                                    </td>
                                    <td><small>{line.code}</small></td>
                                    <td>{line.label}</td>
                                    <td>{formatQty(line.qty)}</td>
                                    <td><small>{line.unit}</small></td>
                                    <td>{formatQty(line.delivered_qty)}</td>
                                    <td>{formatQty(line.remaining_qty)}</td>
                                    <td>
                                        {inv && (
                                            <span className={`badge ${inv.badge}`}>{inv.label}</span>
                                        )}
                                    </td>
                                    <td className="text-right text-nowrap">
                                        {(line.invoice_status === 1 || line.invoice_status === 3) && (
                                            <button
                                                type="button"
                                                className="btn btn-xs btn-outline-danger mr-1"
                                                title={trans.mark_not_chargeable ?? 'Marquer non facturable'}
                                                disabled={busyId === line.id}
                                                onClick={() => markNotChargeable(line)}
                                            >
                                                <i className={busyId === line.id ? 'fas fa-spinner fa-spin' : 'fas fa-ban'} />
                                            </button>
                                        )}
                                        <button
                                            type="button"
                                            className="btn btn-xs btn-outline-warning"
                                            title={trans.add_return ?? 'Nouveau retour'}
                                            onClick={() => openDrawer(line)}
                                        >
                                            <i className="fas fa-undo" />
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{trans.order ?? 'Commande'}</th>
                            <th>{trans.code ?? 'Code'}</th>
                            <th>{trans.description ?? 'Description'}</th>
                            <th>{trans.qty ?? 'Qté'}</th>
                            <th>{trans.unit ?? 'Unité'}</th>
                            <th>{trans.delivered_qty ?? 'Livré'}</th>
                            <th>{trans.remaining_qty ?? 'Restant'}</th>
                            <th>{trans.invoice_status ?? 'Facturation'}</th>
                            <th />
                        </tr>
                    </tfoot>
                </table>
            </div>

            <ReturnDrawer
                open={drawerOpen}
                line={activeLine}
                deliveryId={deliveryId}
                nonConformities={nonConformities}
                endpoints={endpoints}
                trans={trans}
                onClose={() => setDrawerOpen(false)}
                onSuccess={handleSuccess}
            />
        </div>
    );
}
