// Fenêtre « plafond d'encours dépassé », ouverte quand le serveur refuse une
// commande ou un BL (réponse 409 portant `credit_limit`, voir
// App\Services\Receivables\CreditLimitGuard).
//
// Elle s'ouvre depuis plusieurs écrans qui n'ont pas les mêmes traductions :
// les textes viennent donc de la réponse du serveur.
//
//   const extra = await askCreditOverride(body.credit_limit);
//   if (extra) renvoyer la requête avec { ...payload, ...extra };
//
// Résout `null` si l'utilisateur annule ou n'a pas le droit de forcer.

import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';

function formatMoney(value, currency) {
    if (value === null || value === undefined) return '—';
    const locale = document.documentElement.lang || 'fr';
    try {
        return new Intl.NumberFormat(locale, { style: 'currency', currency: currency || 'EUR' }).format(value);
    } catch {
        return String(value);
    }
}

function CreditLimitDialog({ credit, onClose }) {
    const [reason, setReason] = useState('');
    const texts = credit.texts ?? {};
    const valid = reason.trim().length >= 3;
    const money = (value) => formatMoney(value, credit.currency);

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1060 }} />
            <div className="modal show d-block" tabIndex="-1" role="dialog" style={{ zIndex: 1070 }}>
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header text-bg-danger">
                            <h5 className="modal-title">
                                <i className="fas fa-hand-paper me-2" />
                                {texts.title}
                            </h5>
                            <button type="button" className="btn-close btn-close-white" aria-label="Close" onClick={() => onClose(null)} />
                        </div>
                        <div className="modal-body">
                            <p>{texts.message}</p>
                            <table className="table table-sm mb-3">
                                <tbody>
                                    <tr><td>{texts.outstanding}</td><td className="text-end">{money(credit.outstanding)}</td></tr>
                                    {credit.additional > 0 && (
                                        <tr><td>{texts.document}</td><td className="text-end">{money(credit.additional)}</td></tr>
                                    )}
                                    <tr className="fw-bold"><td>{texts.projected}</td><td className="text-end text-danger">{money(credit.projected)}</td></tr>
                                    <tr><td>{texts.limit}</td><td className="text-end">{money(credit.limit)}</td></tr>
                                </tbody>
                            </table>
                            {credit.can_override ? (
                                <div>
                                    <label className="form-label fw-bold" htmlFor="credit-override-reason">{texts.reason_label} *</label>
                                    <textarea
                                        id="credit-override-reason"
                                        className="form-control"
                                        rows={3}
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
                            {credit.can_override && (
                                <button
                                    type="button"
                                    className="btn btn-danger"
                                    disabled={!valid}
                                    onClick={() => onClose({ credit_override: 1, credit_override_reason: reason.trim() })}
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

export function askCreditOverride(credit) {
    if (!credit) return Promise.resolve(null);

    return new Promise(resolve => {
        const host = document.createElement('div');
        document.body.appendChild(host);
        const root = createRoot(host);
        const close = (result) => {
            root.unmount();
            host.remove();
            resolve(result);
        };
        root.render(<CreditLimitDialog credit={credit} onClose={close} />);
    });
}
