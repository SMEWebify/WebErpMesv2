// Fenêtre « motif de perte », ouverte quand un devis passe en Perdu ou une
// opportunité en Perdue (voir App\Services\Workflow\LossReasonRecorder).
//
// La configuration vient du serveur (motifs, textes, motif actuel) : soit posée
// d'avance sur la page (flèches de statut de la fiche), soit renvoyée dans la 422
// qui refuse un changement de statut sans motif (kanban) :
//
//   const result = await sendWithLossReason(url, { statu: 5 }, { method: 'PUT' });
//   if (result.cancelled) remettre la carte à sa place;
//
// askLossReason() résout `null` si l'utilisateur annule.

import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { apiRequest } from './http';

/** Le formulaire peut-il partir ? Le motif n'est obligatoire que si le serveur l'exige. */
export function canSubmitLoss(config, reasonId) {
    return !config?.required || String(reasonId ?? '') !== '';
}

/** Champs envoyés avec le changement de statut. */
export function lossPayload(reasonId, comment) {
    const id = String(reasonId ?? '') === '' ? null : Number(reasonId);
    const text = (comment ?? '').trim();
    return { loss_reason_id: id, loss_comment: text === '' ? null : text };
}

/** Configuration de fenêtre portée par une réponse d'erreur, ou null. */
export function lossConfigFrom(body) {
    return body && typeof body === 'object' && body.loss_reason ? body.loss_reason : null;
}

export function LossReasonDialog({ config, onClose }) {
    const texts = config.texts ?? {};
    const [reasonId, setReasonId] = useState(config.current?.loss_reason_id ?? '');
    const [comment, setComment]   = useState(config.current?.loss_comment ?? '');
    const valid = canSubmitLoss(config, reasonId);

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1060 }} />
            <div className="modal show d-block" tabIndex="-1" role="dialog" style={{ zIndex: 1070 }}>
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header text-bg-danger">
                            <h5 className="modal-title">
                                <i className="fas fa-heart-broken me-2" />
                                {texts.title}
                            </h5>
                            <button type="button" className="btn-close btn-close-white" aria-label={texts.cancel} onClick={() => onClose(null)} />
                        </div>
                        <div className="modal-body">
                            <div className="mb-3">
                                <label className="form-label fw-bold" htmlFor="loss-reason-select">
                                    {texts.reason}{config.required ? ' *' : ''}
                                </label>
                                <select
                                    id="loss-reason-select"
                                    className="form-select"
                                    value={reasonId ?? ''}
                                    onChange={e => setReasonId(e.target.value)}
                                    autoFocus
                                >
                                    <option value="">{texts.choose}</option>
                                    {(config.reasons ?? []).map(r => (
                                        <option key={r.id} value={r.id}>{r.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="form-label" htmlFor="loss-reason-comment">{texts.comment}</label>
                                <textarea
                                    id="loss-reason-comment"
                                    className="form-control"
                                    rows={3}
                                    maxLength={2000}
                                    value={comment ?? ''}
                                    onChange={e => setComment(e.target.value)}
                                />
                                <div className="form-text">{texts.comment_help}</div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-outline-secondary" onClick={() => onClose(null)}>
                                {texts.cancel}
                            </button>
                            <button
                                type="button"
                                className="btn btn-danger"
                                disabled={!valid}
                                onClick={() => onClose(lossPayload(reasonId, comment))}
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

export function askLossReason(config) {
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
        root.render(<LossReasonDialog config={config} onClose={close} />);
    });
}

/**
 * Envoie la requête ; si le serveur la refuse faute de motif, ouvre la fenêtre
 * et la renvoie complétée. Résout { ok, cancelled, status, data }.
 */
export async function sendWithLossReason(url, payload, { method = 'POST' } = {}) {
    const send = async (body) => {
        const res = await apiRequest(url, { method, body: JSON.stringify(body) });
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, data };
    };

    let result = await send(payload);
    const config = result.status === 422 ? lossConfigFrom(result.data) : null;
    if (!config) return { ...result, cancelled: false };

    const extra = await askLossReason(config);
    if (!extra) return { ...result, ok: false, cancelled: true };

    result = await send({ ...payload, ...extra });
    return { ...result, cancelled: false };
}
