// Notation rapide du fournisseur à la réception (fiche réception d'achat).
//
// Fenêtre facultative : elle s'ouvre seule juste après la création de la réception
// (`?rate=1`, voir PurchasesReceiptController::showReceipt), « Plus tard » la ferme sans
// rien enregistrer. Trois critères de 1 à 5 ; le délai est proposé d'après la date
// promise des lignes d'achat (App\Services\Purchases\SupplierReceiptRating).

import React, { useEffect, useState } from 'react';
import { apiRequest } from '../../lib/http';

export const CRITERIA = ['conformity', 'delay', 'documents'];

/** Remplace les :clés d'un texte traduit. */
export function fillText(template, params = {}) {
    return Object.entries(params).reduce(
        (text, [key, value]) => text.split(`:${key}`).join(String(value)),
        template ?? '',
    );
}

/** Notes de départ : la note enregistrée, sinon le délai proposé (les autres à choisir). */
export function initialScores(state) {
    if (state?.scores) {
        return { ...state.scores };
    }
    return { conformity: null, delay: state?.suggested_delay ?? null, documents: null };
}

/** Les trois critères sont-ils notés de 1 à 5 ? */
export function canSaveRating(scores) {
    return CRITERIA.every((key) => Number.isInteger(scores?.[key]) && scores[key] >= 1 && scores[key] <= 5);
}

/** URL sans le paramètre `rate` : recharger la page ne rouvre pas la fenêtre. */
export function withoutRateParam(href) {
    const url = new URL(href);
    url.searchParams.delete('rate');
    return url.toString();
}

function delayHint(state, texts) {
    if (state.days_late === null || state.days_late === undefined) return texts.no_promised;
    if (state.days_late === 0) return texts.on_time;
    return fillText(texts.days_late, { days: state.days_late });
}

function Stars({ value, label, onChange }) {
    return (
        <div className="d-flex gap-1" role="radiogroup" aria-label={label}>
            {[1, 2, 3, 4, 5].map((n) => (
                <button
                    key={n}
                    type="button"
                    role="radio"
                    aria-checked={value === n}
                    aria-label={`${label} ${n}/5`}
                    className="btn btn-link p-0 fs-4 lh-1 text-decoration-none"
                    onClick={() => onChange?.(n)}
                    disabled={!onChange}
                >
                    <i className={`fa-star ${value && n <= value ? 'fas text-warning' : 'far text-body-tertiary'}`} />
                </button>
            ))}
        </div>
    );
}

export function RatingDialog({ state, texts, endpoint, onClose, onSaved }) {
    const [scores, setScores]   = useState(() => initialScores(state));
    const [comment, setComment] = useState(state.comment ?? '');
    const [saving, setSaving]   = useState(false);
    const [error, setError]     = useState(null);

    const set = (key) => (value) => setScores((s) => ({ ...s, [key]: value }));

    const save = async () => {
        setSaving(true);
        setError(null);
        try {
            const res = await apiRequest(endpoint, {
                method: 'POST',
                body: JSON.stringify({ ...scores, comment: comment.trim() === '' ? null : comment.trim() }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setError(data.message ?? texts.error);
                return;
            }
            onSaved(data.state);
        } catch {
            setError(texts.error);
        } finally {
            setSaving(false);
        }
    };

    const row = (key, help, hint) => (
        <div className="mb-3" key={key}>
            <div className="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div className="fw-bold">{texts[key]}</div>
                    <div className="small text-body-secondary">{help}</div>
                </div>
                <Stars value={scores[key]} label={texts[key]} onChange={set(key)} />
            </div>
            {hint && <div className="small text-body-secondary mt-1">{hint}</div>}
        </div>
    );

    const suggested = state.suggested_delay
        ? ` — ${texts.suggested} : ${state.suggested_delay}/5`
        : '';

    return (
        <>
            <div className="modal-backdrop fade show" />
            <div className="modal show d-block" tabIndex="-1" role="dialog" aria-modal="true" aria-labelledby="receipt-rating-title">
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="receipt-rating-title">
                                <i className="fas fa-star-half-alt me-2 text-warning" />
                                {texts.title}
                            </h5>
                            <button type="button" className="btn-close" aria-label={texts.later} onClick={onClose} />
                        </div>
                        <div className="modal-body">
                            <p className="text-body-secondary small">{texts.intro}</p>
                            {error && <div className="alert alert-danger py-2">{error}</div>}
                            {state.has_nc && (
                                <div className="alert alert-warning py-2 small">
                                    <i className="fas fa-exclamation-triangle me-1" />
                                    {fillText(texts.has_nc, { score: state.nc_quality_score })}
                                </div>
                            )}
                            {row('conformity', texts.conformity_help)}
                            {row('delay', texts.delay_help, delayHint(state, texts) + suggested)}
                            {row('documents', texts.documents_help)}
                            <label className="form-label" htmlFor="receipt-rating-comment">{texts.comment}</label>
                            <textarea
                                id="receipt-rating-comment"
                                className="form-control"
                                rows={2}
                                maxLength={2000}
                                value={comment}
                                onChange={(e) => setComment(e.target.value)}
                            />
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-outline-secondary" onClick={onClose} disabled={saving}>
                                {texts.later}
                            </button>
                            <button type="button" className="btn btn-primary" onClick={save} disabled={saving || !canSaveRating(scores)}>
                                {saving
                                    ? <><i className="fas fa-spinner fa-spin me-1" />{texts.saving}</>
                                    : <><i className="fas fa-save me-1" />{texts.save}</>}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

export default function ReceiptSupplierRating({ endpoint, autoOpen = false, state: initialState, texts }) {
    const [state, setState] = useState(initialState);
    const [open, setOpen]   = useState(Boolean(autoOpen));

    // Le paramètre ?rate=1 ne sert qu'une fois.
    useEffect(() => {
        if (autoOpen && typeof window !== 'undefined' && window.history?.replaceState) {
            window.history.replaceState(null, '', withoutRateParam(window.location.href));
        }
    }, [autoOpen]);

    const rolling = state.rolling ?? {};

    return (
        <div>
            {state.rated ? (
                <>
                    {CRITERIA.map((key) => (
                        <div key={key} className="d-flex justify-content-between align-items-center">
                            <span className="small">{texts[key]}</span>
                            <Stars value={state.scores?.[key]} label={texts[key]} />
                        </div>
                    ))}
                    {state.comment && <p className="small fst-italic mt-2 mb-0">{state.comment}</p>}
                </>
            ) : (
                <p className="small text-body-secondary mb-2">{state.can_rate ? texts.not_rated : texts.no_lines}</p>
            )}

            {state.has_nc && (
                <div className="small text-warning-emphasis mt-2">
                    <i className="fas fa-exclamation-triangle me-1" />
                    {fillText(texts.has_nc, { score: state.nc_quality_score })}
                </div>
            )}

            {state.can_rate && (
                <button type="button" className="btn btn-sm btn-outline-primary mt-2" onClick={() => setOpen(true)}>
                    <i className={`fas ${state.rated ? 'fa-edit' : 'fa-star'} me-1`} />
                    {state.rated ? texts.edit : texts.rate}
                </button>
            )}

            <hr className="my-2" />
            <div className="small">
                <strong>{fillText(texts.rolling, { months: rolling.window_months })}</strong>{' : '}
                {rolling.rating != null
                    ? <>
                        <span className="fw-bold">{Number(rolling.rating).toFixed(1)}/5</span>
                        <div className="text-body-secondary">
                            {fillText(texts.rolling_detail, { count: rolling.ratings_count, nc: rolling.nc_receipts })}
                        </div>
                      </>
                    : <span className="text-body-secondary">{fillText(texts.no_rolling, { months: rolling.window_months })}</span>}
            </div>

            {open && (
                <RatingDialog
                    state={state}
                    texts={texts}
                    endpoint={endpoint}
                    onClose={() => setOpen(false)}
                    onSaved={(next) => { setState(next); setOpen(false); }}
                />
            )}
        </div>
    );
}
