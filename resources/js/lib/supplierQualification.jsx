// Qualification fournisseur (App\Services\Purchases\SupplierQualification) : badge des
// sélecteurs, et fenêtre « fournisseur non qualifié » ouverte quand le serveur refuse
// une commande d'achat (422 portant `supplier_qualification`).
//
// Les textes viennent du serveur (`supplier_qualification` des données de l'écran pour
// les badges, de la réponse pour la fenêtre) : la fenêtre s'ouvre depuis plusieurs
// écrans qui n'ont pas les mêmes traductions.
//
//   const result = await sendWithSupplierOverride(url, payload);
//   if (!result.ok) afficher result.data.message (fenêtre refermée : result.refused).

import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { apiRequest } from './http';

export const QUALIFIED      = 'qualified';
export const REVIEW_OVERDUE = 'review_overdue';
export const UNQUALIFIED    = 'unqualified';

const BADGES = {
    [QUALIFIED]:      { className: 'text-bg-success', icon: 'fa-circle-check' },
    [REVIEW_OVERDUE]: { className: 'text-bg-warning', icon: 'fa-clock' },
    [UNQUALIFIED]:    { className: 'text-bg-danger',  icon: 'fa-ban' },
};

/** Le badge est-il à afficher ? Option inactive ou statut inconnu : non. */
export function showsBadge(config, status) {
    return !!config?.enabled && !!BADGES[status];
}

/** Libellé d'option de sélecteur : un <option> ne peut pas porter de badge. */
export function supplierOptionLabel(label, status, config) {
    if (!showsBadge(config, status) || status === QUALIFIED) return label;
    const text = config.texts?.[status] ?? status;
    return `${label} — ${text}`;
}

/** Configuration de fenêtre portée par une réponse d'erreur, ou null. */
export function qualificationConfigFrom(body) {
    return body && typeof body === 'object' && body.supplier_qualification ? body.supplier_qualification : null;
}

/** Le motif est-il suffisant ? (même seuil que le serveur) */
export function canSubmitOverride(reason) {
    return (reason ?? '').trim().length >= 3;
}

/** Champs renvoyés avec la requête quand l'utilisateur force. */
export function overridePayload(reason) {
    return { supplier_override: 1, supplier_override_reason: (reason ?? '').trim() };
}

export function SupplierQualificationBadge({ status, config, className = '' }) {
    if (!showsBadge(config, status)) return null;
    const badge = BADGES[status];
    return (
        <span className={`badge ${badge.className} ${className}`.trim()}>
            <i className={`fas ${badge.icon} me-1`} />
            {config.texts?.[status] ?? status}
        </span>
    );
}

export function SupplierOverrideDialog({ config, onClose }) {
    const [reason, setReason] = useState('');
    const texts = config.texts ?? {};
    const valid = canSubmitOverride(reason);

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1060 }} />
            <div className="modal show d-block" tabIndex="-1" role="dialog" style={{ zIndex: 1070 }}>
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header text-bg-danger">
                            <h5 className="modal-title">
                                <i className="fas fa-user-slash me-2" />
                                {texts.title}
                            </h5>
                            <button type="button" className="btn-close btn-close-white" aria-label={texts.cancel} onClick={() => onClose(null)} />
                        </div>
                        <div className="modal-body">
                            <p>{texts.message}</p>
                            <ul className="list-unstyled mb-3">
                                {(config.suppliers ?? []).map(s => (
                                    <li key={s.id} className="mb-1">
                                        <strong>{s.label}</strong>{' '}
                                        <SupplierQualificationBadge status={s.status} config={{ enabled: true, texts: { [s.status]: s.status_label } }} />
                                        <div className="small text-body-secondary">{s.detail}</div>
                                    </li>
                                ))}
                            </ul>
                            {config.can_override ? (
                                <div>
                                    <label className="form-label fw-bold" htmlFor="supplier-override-reason">{texts.reason_label} *</label>
                                    <textarea
                                        id="supplier-override-reason"
                                        className="form-control"
                                        rows={3}
                                        maxLength={2000}
                                        value={reason}
                                        onChange={e => setReason(e.target.value)}
                                        autoFocus
                                    />
                                    <div className="form-text">{texts.reason_help}</div>
                                </div>
                            ) : (
                                <div className="alert alert-secondary mb-0">{texts.no_permission}</div>
                            )}
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-outline-secondary" onClick={() => onClose(null)}>
                                {texts.cancel}
                            </button>
                            {config.can_override && (
                                <button
                                    type="button"
                                    className="btn btn-danger"
                                    disabled={!valid}
                                    onClick={() => onClose(overridePayload(reason))}
                                >
                                    {texts.override}
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

export function askSupplierOverride(config) {
    if (!config) return Promise.resolve(null);

    return new Promise(resolve => {
        const host = document.createElement('div');
        document.body.appendChild(host);
        const root = createRoot(host);
        const close = (result) => {
            root.unmount();
            host.remove();
            resolve(result);
        };
        root.render(<SupplierOverrideDialog config={config} onClose={close} />);
    });
}

/**
 * Envoie la requête ; si le serveur la refuse pour un fournisseur non qualifié, ouvre
 * la fenêtre et la renvoie avec le passe-droit. Résout { ok, cancelled, status, data }.
 * `send` (facultatif) remplace l'envoi JSON par défaut (ex. lossReason).
 */
export async function sendWithSupplierOverride(url, payload, { method = 'POST', send = null } = {}) {
    const doSend = send ?? (async (body) => {
        const res = await apiRequest(url, { method, body: JSON.stringify(body) });
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, data };
    });

    let result = await doSend(payload);
    const config = result.status === 422 ? qualificationConfigFrom(result.data) : null;
    if (!config) return { ...result, cancelled: result.cancelled ?? false };

    // Fenêtre refermée : `refused` pour que l'écran affiche quand même le refus du serveur
    const extra = await askSupplierOverride(config);
    if (!extra) return { ...result, ok: false, cancelled: true, refused: true };

    result = await doSend({ ...payload, ...extra });
    return { ...result, cancelled: result.cancelled ?? false };
}
