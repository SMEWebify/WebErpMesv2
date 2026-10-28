// Fenêtre « opérations obligatoires manquantes », ouverte quand le serveur refuse
// la conversion d'un devis en commande (réponse portant `required_operations`,
// voir App\Services\Quotes\RequiredOperationsGuard).
//
// Les textes viennent de la réponse du serveur, comme pour la fenêtre d'encours.
//
//   const extra = await askRequiredOperationsOverride(body.required_operations);
//   if (extra) renvoyer la requête avec { ...payload, ...extra };
//
// Résout `null` si l'utilisateur annule ou n'a pas le droit de forcer.

import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';

/** Libellé court d'une ligne de devis : « 10 — CODE Libellé ». */
export function lineCaption(line) {
    return [line.ordre, [line.code, line.label].filter(Boolean).join(' ')].filter(v => v !== null && v !== undefined && v !== '').join(' — ');
}

/** Tableau ligne → opérations manquantes, partagé par la fenêtre et le bandeau du devis. */
export function MissingOperationsTable({ missing, texts }) {
    return (
        <table className="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{texts.line}</th>
                    <th>{texts.missing}</th>
                </tr>
            </thead>
            <tbody>
                {(missing ?? []).map(line => (
                    <tr key={line.line_id}>
                        <td>{lineCaption(line)}</td>
                        <td>
                            {line.services.map(s => (
                                <span key={s.id} className="badge text-bg-warning me-1" title={s.label}>{s.code} — {s.label}</span>
                            ))}
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

function RequiredOperationsDialog({ data, onClose }) {
    const [reason, setReason] = useState('');
    const texts = data.texts ?? {};
    const valid = reason.trim().length >= 3;

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1060 }} />
            <div className="modal show d-block" tabIndex="-1" role="dialog" style={{ zIndex: 1070 }}>
                <div className="modal-dialog modal-lg">
                    <div className="modal-content">
                        <div className="modal-header text-bg-danger">
                            <h5 className="modal-title">
                                <i className="fas fa-tools me-2" />
                                {texts.title}
                            </h5>
                            <button type="button" className="btn-close btn-close-white" aria-label="Close" onClick={() => onClose(null)} />
                        </div>
                        <div className="modal-body">
                            <p>{texts.message}</p>
                            <div className="table-responsive mb-3">
                                <MissingOperationsTable missing={data.missing} texts={texts} />
                            </div>
                            {data.can_override ? (
                                <div>
                                    <label className="form-label fw-bold" htmlFor="required-operations-reason">{texts.reason_label} *</label>
                                    <textarea
                                        id="required-operations-reason"
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
                            {data.can_override && (
                                <button
                                    type="button"
                                    className="btn btn-danger"
                                    disabled={!valid}
                                    onClick={() => onClose({ required_operations_override: 1, required_operations_override_reason: reason.trim() })}
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

export function askRequiredOperationsOverride(data) {
    if (!data) return Promise.resolve(null);

    return new Promise(resolve => {
        const host = document.createElement('div');
        document.body.appendChild(host);
        const root = createRoot(host);
        const close = (result) => {
            root.unmount();
            host.remove();
            resolve(result);
        };
        root.render(<RequiredOperationsDialog data={data} onClose={close} />);
    });
}
