// Fenêtre « motif d'annulation » : annulation d'une ligne de commande
// (App\Services\Workflow\OrderLineCancellation) ou d'une ligne de BL
// (App\Services\Delivery\DeliveryLineCancellation). Le serveur exige le motif.
//
//   const reason = await askCancelReason({ title, message, reason, reason_help, confirm, close });
//   if (reason) envoyer { reason };
//
// Résout `null` si l'utilisateur ferme la fenêtre.

import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';

export const MIN_REASON_LENGTH = 3;

/** Remplace les `:cle` d'un texte de traduction Laravel. */
export function fillPlaceholders(text, values = {}) {
    return Object.entries(values).reduce(
        (out, [key, value]) => out.replaceAll(`:${key}`, value ?? ''),
        String(text ?? ''),
    );
}

function CancelReasonDialog({ texts, onClose }) {
    const [reason, setReason] = useState('');
    const valid = reason.trim().length >= MIN_REASON_LENGTH;

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1060 }} />
            <div className="modal show d-block" tabIndex="-1" role="dialog" style={{ zIndex: 1070 }}>
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header text-bg-danger">
                            <h5 className="modal-title">
                                <i className="fas fa-ban me-2" />
                                {texts.title}
                            </h5>
                            <button type="button" className="btn-close btn-close-white" aria-label="Close" onClick={() => onClose(null)} />
                        </div>
                        <div className="modal-body">
                            {texts.message && <p>{texts.message}</p>}
                            <label className="form-label fw-bold" htmlFor="cancel-reason">{texts.reason} *</label>
                            <textarea
                                id="cancel-reason"
                                className="form-control"
                                rows={3}
                                maxLength={255}
                                value={reason}
                                onChange={e => setReason(e.target.value)}
                                autoFocus
                            />
                            {texts.reason_help && <div className="form-text">{texts.reason_help}</div>}
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-outline-secondary" onClick={() => onClose(null)}>
                                {texts.close}
                            </button>
                            <button
                                type="button"
                                className="btn btn-danger"
                                disabled={!valid}
                                onClick={() => onClose(reason.trim())}
                            >
                                {texts.confirm}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

export function askCancelReason(texts) {
    return new Promise(resolve => {
        const host = document.createElement('div');
        document.body.appendChild(host);
        const root = createRoot(host);
        const close = (result) => {
            root.unmount();
            host.remove();
            resolve(result);
        };
        root.render(<CancelReasonDialog texts={texts ?? {}} onClose={close} />);
    });
}
