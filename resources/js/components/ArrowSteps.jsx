import React, { useState, useEffect, useRef } from 'react';
import { askLossReason, sendWithLossReason } from '../lib/lossReason.jsx';
import { sendWithSupplierOverride } from '../lib/supplierQualification.jsx';

const STYLE_ID = 'arrow-steps-react-v1';

const CSS = `
.as-track {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding: 4px 0 10px;
}
.as-row {
    display: flex;
    width: 100%;
}
.as-btn {
    flex: 1 1 0;
    min-width: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 11px 22px;
    font-size: 12px;
    font-weight: 500;
    color: #fff;
    background: #6c757d;
    border: none;
    outline: none;
    cursor: pointer;
    transition: background 0.18s ease, filter 0.14s ease;
    clip-path: polygon(14px 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%, 14px 50%);
    margin-left: -14px;
    position: relative;
    text-align: center;
    white-space: nowrap;
    line-height: 1.3;
    letter-spacing: 0.01em;
}
.as-btn:first-child {
    clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
    border-radius: 5px 0 0 5px;
    padding-left: 14px;
    margin-left: 0;
}
.as-btn:last-child {
    clip-path: polygon(14px 0, 100% 0, 100% 100%, 0 100%, 14px 50%);
    border-radius: 0 5px 5px 0;
    padding-right: 14px;
}
.as-btn:only-child {
    clip-path: none;
    border-radius: 5px;
    padding: 11px 14px;
    margin-left: 0;
}
.as-btn.as-done    { background: #198754; }
.as-btn.as-current { background: #0d6efd; font-weight: 700; font-size: 13px; }
.as-btn:hover:not(:disabled):not(.as-current) { filter: brightness(1.18); }
.as-btn:disabled { cursor: wait; opacity: 0.72; }
.as-btn:nth-child(1) { z-index: 1; }
.as-btn:nth-child(2) { z-index: 2; }
.as-btn:nth-child(3) { z-index: 3; }
.as-btn:nth-child(4) { z-index: 4; }
.as-btn:nth-child(5) { z-index: 5; }
.as-btn:nth-child(6) { z-index: 6; }
.as-btn:nth-child(7) { z-index: 7; }
.as-btn:nth-child(8) { z-index: 8; }

/* Mobile : une seule ligne qui défile (barre de statut façon Odoo) plutôt
   qu'une pile de boutons pleine largeur ; l'étape courante est ramenée à l'écran. */
@media (max-width: 576px) {
    .as-track {
        scrollbar-width: none;
    }
    .as-track::-webkit-scrollbar {
        display: none;
    }
    .as-btn {
        flex: 0 0 auto;
        min-width: 0;
        padding: 9px 20px;
    }
    .as-btn:first-child {
        padding-left: 12px;
    }
    .as-btn:last-child {
        padding-right: 12px;
    }
    .as-btn.as-current {
        font-size: 12px;
    }
}
`;

function injectStyles() {
    if (document.getElementById(STYLE_ID)) return;
    const el = document.createElement('style');
    el.id = STYLE_ID;
    el.textContent = CSS;
    document.head.appendChild(el);
}

/**
 * `loss` (facultatif) : configuration de la fenêtre « motif de perte »
 * (LossReasonRecorder::dialog() + `endpoint` de saisie après coup). Le passage
 * à l'étape perdue ouvre la fenêtre avant d'envoyer le statut ; une fois perdu,
 * le motif s'affiche sous les flèches avec un bouton pour le renseigner ou le corriger.
 */
export default function ArrowSteps({ steps, currentStatu, endpoint, redirectUrl, loss = null }) {
    const [busy, setBusy]   = useState(false);
    const [error, setError] = useState('');

    useEffect(() => { injectStyles(); }, []);

    // Sur mobile la barre défile : on y ramène l'étape courante
    const trackRef = useRef(null);
    useEffect(() => {
        const track = trackRef.current;
        const current = track?.querySelector('.as-current');
        if (!current || track.scrollWidth <= track.clientWidth) return;
        const offset = current.getBoundingClientRect().left - track.getBoundingClientRect().left;
        track.scrollLeft += offset - (track.clientWidth - current.offsetWidth) / 2;
    }, [currentStatu]);

    const currentIndex = steps.findIndex(s => s.value === currentStatu);

    const hasLossChoices = !!loss && (loss.reasons?.length ?? 0) > 0;

    const go = async (value) => {
        if (busy || value === currentStatu) return;
        setBusy(true);
        setError('');

        let payload = { statu: value };
        if (hasLossChoices && value === loss.status) {
            const extra = await askLossReason(loss);
            if (!extra) { setBusy(false); return; }
            payload = { ...payload, ...extra };
        }

        try {
            // Sans configuration posée sur la page, une 422 « motif manquant » ouvre quand même la fenêtre.
            // Une 422 « fournisseur non qualifié » (validation d'une commande d'achat) ouvre celle du passe-droit.
            const result = await sendWithSupplierOverride(endpoint, payload, {
                send: (body) => sendWithLossReason(endpoint, body),
            });
            if (result.ok) {
                window.location.href = redirectUrl;
                return;
            }
            if (!result.cancelled || result.refused) {
                setError(result.data.error ?? result.data.message ?? 'Erreur lors du changement de statut.');
            }
        } catch {
            setError('Erreur lors du changement de statut.');
        }
        setBusy(false);
    };

    const editLoss = async () => {
        const extra = await askLossReason(loss);
        if (!extra) return;
        setBusy(true);
        setError('');
        try {
            const result = await sendWithLossReason(loss.endpoint, extra);
            if (result.ok) {
                window.location.href = redirectUrl;
                return;
            }
            setError(result.data.message ?? loss.texts?.error ?? '');
        } catch {
            setError(loss.texts?.error ?? '');
        }
        setBusy(false);
    };

    const showLoss = !!loss && currentStatu === loss.status && (hasLossChoices || !!loss.current?.label);

    return (
        <div className="as-track" ref={trackRef}>
            {error && (
                <div className="alert alert-danger py-2 mb-2" role="alert">
                    <i className="fas fa-exclamation-triangle me-1" />{error}
                </div>
            )}
            <div className="as-row">
                {steps.map((s, i) => {
                    const isCurrent = s.value === currentStatu;
                    const isDone    = currentIndex >= 0 && i < currentIndex;
                    return (
                        <button
                            key={s.value}
                            className={`as-btn${isDone ? ' as-done' : ''}${isCurrent ? ' as-current' : ''}`}
                            onClick={() => go(s.value)}
                            disabled={busy}
                            title={s.label}
                        >
                            {s.label}
                        </button>
                    );
                })}
            </div>
            {showLoss && (
                <div className="small mt-1 text-body-secondary">
                    <i className="fas fa-heart-broken me-1 text-danger" />
                    {loss.texts?.label} : <strong className="text-body">{loss.current?.label ?? loss.texts?.none}</strong>
                    {loss.current?.loss_comment && <span className="ms-1">— {loss.current.loss_comment}</span>}
                    {hasLossChoices && (
                        <button type="button" className="btn btn-link btn-sm p-0 ms-2 align-baseline" onClick={editLoss} disabled={busy}>
                            {loss.current?.loss_reason_id ? loss.texts?.edit : loss.texts?.fill}
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
