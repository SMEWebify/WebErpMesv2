import React, { useState } from 'react';
import { apiFetch, csrfToken } from '../../lib/http';
import { toDrafts, toPayload, appendText, fill } from '../../lib/quoteDictation.js';
import useVoiceRecorder, { extensionFor } from '../../hooks/useVoiceRecorder.js';
import { askCreditOverride } from '../../lib/creditLimit';

function formatDuration(seconds) {
    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
}

// Dictée de lignes de devis ou de commande : texte (micro ou clavier) → lignes en
// brouillon proposées par l'IA → correction → création. Rien n'est enregistré avant
// « Créer ». Commande : un plafond d'encours dépassé (409) ouvre la fenêtre de passe-droit.
export default function QuoteDictationModal({ endpoints, units = [], trans: t = {}, onCreated, onClose }) {
    const [text, setText]       = useState('');
    const [step, setStep]       = useState('text'); // text | drafts
    const [drafts, setDrafts]   = useState([]);
    const [busy, setBusy]       = useState(null);   // transcribing | proposing | creating
    const [error, setError]     = useState(null);
    const [pending, setPending] = useState(null);   // enregistrement dont l'envoi a échoué

    const transcribe = async (memo) => {
        setBusy('transcribing');
        setError(null);
        try {
            const form = new FormData();
            form.append('audio', new File([memo.blob], `dictee.${extensionFor(memo.mime)}`, { type: memo.mime }));
            const res = await fetch(endpoints.dictationTranscribe, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: form,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw data;
            setPending(null);
            setText((prev) => appendText(prev, data.text));
        } catch (e) {
            setPending(memo);
            setError(e?.message ?? t.failed);
        } finally {
            setBusy(null);
        }
    };

    const voice = useVoiceRecorder({
        serverTranscription: !!endpoints.dictationTranscribe,
        onRecorded: transcribe,
        onDictated: (said) => setText((prev) => appendText(prev, said)),
        locale: t.locale,
    });

    const propose = async () => {
        setBusy('proposing');
        setError(null);
        try {
            const data = await apiFetch(endpoints.dictationPropose, { method: 'POST', body: JSON.stringify({ text }) });
            setDrafts(toDrafts(data.lines));
            setStep('drafts');
        } catch (e) {
            setError(e?.message ?? t.failed);
        } finally {
            setBusy(null);
        }
    };

    const create = async (creditOverride = null) => {
        setBusy('creating');
        setError(null);
        try {
            const data = await apiFetch(endpoints.dictationStore, { method: 'POST', body: JSON.stringify({ lines: toPayload(drafts), ...creditOverride }) });
            onCreated(data.lines ?? []);
        } catch (e) {
            if (e?.credit_limit) {
                const override = await askCreditOverride(e.credit_limit);
                if (override) return create(override);
                setBusy(null);
                return;
            }
            setError(e?.message ?? t.failed);
            setBusy(null);
        }
    };

    const update = (key, field, value) =>
        setDrafts((ds) => ds.map((d) => (d.key === key ? { ...d, [field]: value, ...(field === 'methods_units_id' ? { unit_text: null } : {}) } : d)));

    const kept = toPayload(drafts).length;
    const locked = busy !== null || voice.recording;
    const privacy = t.provider === 'claude' ? t.privacy_claude : t.privacy_ovh;

    return (
        <div className="modal fade show d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,.5)' }}>
            <div className="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title"><i className="fas fa-microphone me-2" />{t.title}</h5>
                        <button type="button" onClick={onClose} aria-label="Close" className="btn-close" disabled={busy === 'creating'}></button>
                    </div>
                    <div className="modal-body">
                        {error && <div className="alert alert-danger py-2">{error}</div>}

                        {step === 'text' && (
                            <>
                                <p className="mb-1">{t.intro}</p>
                                <p className="text-muted small">{t.example}</p>

                                <div className="d-flex flex-wrap gap-2 mb-2">
                                    {voice.canRecord && (!voice.recording ? (
                                        <button type="button" className="btn btn-danger" disabled={locked} onClick={voice.start}>
                                            <i className="fas fa-microphone me-2" />{t.record}
                                        </button>
                                    ) : (
                                        <button type="button" className="btn btn-dark" onClick={voice.stop}>
                                            <span className="spinner-grow spinner-grow-sm text-danger me-2" />{t.stop} — {formatDuration(voice.seconds)}
                                        </button>
                                    ))}
                                    {voice.canDictate && (
                                        <button type="button" className={`btn ${voice.dictating ? 'btn-dark' : 'btn-danger'}`}
                                            disabled={busy !== null} onClick={voice.toggleDictation}>
                                            {voice.dictating
                                                ? <><span className="spinner-grow spinner-grow-sm text-danger me-2" />{t.stop_dictation}</>
                                                : <><i className="fas fa-microphone me-2" />{t.dictate}</>}
                                        </button>
                                    )}
                                    {pending && busy === null && (
                                        <>
                                            <button type="button" className="btn btn-outline-primary" onClick={() => transcribe(pending)}>{t.retry}</button>
                                            <button type="button" className="btn btn-outline-danger" onClick={() => { setPending(null); setError(null); }}>{t.discard}</button>
                                        </>
                                    )}
                                </div>
                                {voice.denied && <div className="alert alert-warning py-2">{t.microphone_denied}</div>}
                                {!voice.canRecord && !voice.canDictate && <p className="small text-muted">{t.no_microphone}</p>}
                                {busy === 'transcribing' && (
                                    <div className="alert alert-info py-2"><span className="spinner-border spinner-border-sm me-2" />{t.transcribing}</div>
                                )}

                                <label className="small fw-bold mb-1" htmlFor="quote-dictation-text">{t.text}</label>
                                <textarea id="quote-dictation-text" className="form-control" rows="6" maxLength={5000}
                                    value={text} onChange={(e) => setText(e.target.value)} disabled={busy !== null} />

                                {busy === 'proposing' && (
                                    <div className="alert alert-info py-2 mt-2"><span className="spinner-border spinner-border-sm me-2" />{t.proposing}</div>
                                )}
                                <p className="small text-muted mt-2 mb-0">
                                    {voice.canRecord && <>{t.privacy_audio} </>}
                                    {voice.canDictate && <>{t.privacy_browser} </>}
                                    {privacy}
                                </p>
                            </>
                        )}

                        {step === 'drafts' && (
                            <>
                                <h6 className="mb-1">{t.drafts}</h6>
                                <p className="small text-muted">{t.price_hint}</p>
                                {drafts.map((d) => (
                                    <div key={d.key} className={`card mb-2 ${d.keep ? '' : 'opacity-50'}`}>
                                        <div className="card-body py-2">
                                            <div className="row g-2 align-items-end">
                                                <div className="col-12 col-md-auto">
                                                    <div className="form-check mb-md-2">
                                                        <input className="form-check-input" type="checkbox" id={`draft-keep-${d.key}`}
                                                            checked={d.keep} onChange={(e) => update(d.key, 'keep', e.target.checked)} />
                                                        <label className="form-check-label small" htmlFor={`draft-keep-${d.key}`}>{t.keep}</label>
                                                    </div>
                                                </div>
                                                <div className="col-12 col-md">
                                                    <label className="small mb-0">{t.label}</label>
                                                    <input type="text" className="form-control form-control-sm" maxLength={255}
                                                        value={d.label} onChange={(e) => update(d.key, 'label', e.target.value)} disabled={!d.keep} />
                                                </div>
                                                <div className="col-4 col-md-1">
                                                    <label className="small mb-0">{t.qty}</label>
                                                    <input type="number" className="form-control form-control-sm" min="0" step="any"
                                                        value={d.qty} onChange={(e) => update(d.key, 'qty', e.target.value)} disabled={!d.keep} />
                                                </div>
                                                <div className="col-8 col-md-2">
                                                    <label className="small mb-0">{t.unit}</label>
                                                    <select className={`form-select form-select-sm ${d.unit_text ? 'is-invalid' : ''}`}
                                                        value={d.methods_units_id} onChange={(e) => update(d.key, 'methods_units_id', e.target.value)} disabled={!d.keep}>
                                                        <option value="">—</option>
                                                        {units.map((u) => <option key={u.id} value={u.id}>{u.code} - {u.label}</option>)}
                                                    </select>
                                                </div>
                                                <div className="col-6 col-md-2">
                                                    <label className="small mb-0">{t.price}</label>
                                                    <input type="number" className="form-control form-control-sm" min="0" step="0.01" placeholder="0"
                                                        value={d.selling_price} onChange={(e) => update(d.key, 'selling_price', e.target.value)} disabled={!d.keep} />
                                                </div>
                                                <div className="col-6 col-md-2">
                                                    <label className="small mb-0">{t.material}</label>
                                                    <input type="text" className="form-control form-control-sm" maxLength={255}
                                                        value={d.material} onChange={(e) => update(d.key, 'material', e.target.value)} disabled={!d.keep} />
                                                </div>
                                                <div className="col-12">
                                                    <input type="text" className="form-control form-control-sm" maxLength={2000} placeholder={t.note}
                                                        value={d.internal_comment} onChange={(e) => update(d.key, 'internal_comment', e.target.value)} disabled={!d.keep} />
                                                    {d.unit_text && <div className="small text-danger">{fill(t.unit_unknown, { unit: d.unit_text })}</div>}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </>
                        )}
                    </div>
                    <div className="modal-footer">
                        {step === 'text' ? (
                            <>
                                <button type="button" className="btn btn-secondary btn-sm" onClick={onClose}>{t.cancel ?? 'Annuler'}</button>
                                <button type="button" className="btn btn-primary btn-sm" disabled={locked || !text.trim()} onClick={propose}>
                                    {busy === 'proposing' ? <i className="fas fa-spinner fa-spin me-1" /> : <i className="fas fa-magic me-1" />}
                                    {t.propose}
                                </button>
                            </>
                        ) : (
                            <>
                                <button type="button" className="btn btn-outline-secondary btn-sm me-auto" disabled={busy !== null} onClick={() => setStep('text')}>
                                    <i className="fas fa-arrow-left me-1" />{t.back}
                                </button>
                                <button type="button" className="btn btn-primary btn-sm" disabled={busy !== null || kept === 0} onClick={() => create()}>
                                    {busy === 'creating' ? <i className="fas fa-spinner fa-spin me-1" /> : <i className="fas fa-check me-1" />}
                                    {fill(t.create, { count: kept })}
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
