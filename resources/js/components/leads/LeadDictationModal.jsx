import React, { useEffect, useState } from 'react';
import { apiFetch, csrfToken } from '../../lib/http';
import { appendText } from '../../lib/quoteDictation.js';
import useVoiceRecorder, { extensionFor } from '../../hooks/useVoiceRecorder.js';
import { leadPayload } from '../../lib/leadDictation.js';

function formatDuration(seconds) {
    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
}

// Dictée d'une piste : texte (micro ou clavier) → fiche proposée par l'IA (société
// et contact rapprochés de la base par le serveur) → correction → création.
// Rien n'est enregistré avant « Créer la piste ». Endpoints posés seulement si
// l'envoi de données personnelles est autorisé (carte IA).
export default function LeadDictationModal({ endpoints, trans: t = {}, onClose }) {
    const [text, setText]         = useState('');
    const [step, setStep]         = useState('text'); // text | draft
    const [draft, setDraft]       = useState(null);
    const [contacts, setContacts] = useState([]);
    const [busy, setBusy]         = useState(null);   // transcribing | proposing | creating
    const [error, setError]       = useState(null);
    const [pending, setPending]   = useState(null);   // enregistrement dont l'envoi a échoué

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

    // Contacts de la société choisie (existante) : la liste suit le choix.
    const companyId = draft?.companies_id ?? null;
    useEffect(() => {
        if (!companyId) { setContacts([]); return; }
        let alive = true;
        fetch(endpoints.contacts.replace('__ID__', companyId), { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((list) => { if (alive) setContacts(Array.isArray(list) ? list : []); })
            .catch(() => { if (alive) setContacts([]); });
        return () => { alive = false; };
    }, [companyId]);

    const propose = async () => {
        setBusy('proposing');
        setError(null);
        try {
            const data = await apiFetch(endpoints.dictationPropose, { method: 'POST', body: JSON.stringify({ text }) });
            const d = data.draft ?? {};
            setDraft({
                ...d,
                companies_id: d.companies_id ? String(d.companies_id) : '',
                companies_contacts_id: d.companies_contacts_id ? String(d.companies_contacts_id) : '',
                company_label: d.company ?? '',
            });
            setStep('draft');
        } catch (e) {
            setError(e?.message ?? t.failed);
        } finally {
            setBusy(null);
        }
    };

    const create = async () => {
        setBusy('creating');
        setError(null);
        try {
            const data = await apiFetch(endpoints.dictationStore, { method: 'POST', body: JSON.stringify(leadPayload(draft)) });
            window.location.href = data.redirect;
        } catch (e) {
            const first = e?.errors ? Object.values(e.errors)[0]?.[0] : null;
            setError(first ?? e?.message ?? t.failed);
            setBusy(null);
        }
    };

    const set = (field, value) => setDraft((d) => ({
        ...d,
        [field]: value,
        // Changer de société oublie le contact choisi dans l'autre.
        ...(field === 'companies_id' ? { companies_contacts_id: '' } : {}),
    }));

    const locked = busy !== null || voice.recording;
    const privacy = t.provider === 'claude' ? t.privacy_claude : t.privacy_ovh;
    const candidates = draft?.candidates ?? [];
    const newCompany = draft && !draft.companies_id;
    const newContact = draft && !draft.companies_contacts_id;
    const field = (name, label, props = {}) => (
        <div className={props.col ?? 'col-12 col-md-6'}>
            <label className="small mb-0" htmlFor={`lead-dictation-${name}`}>{label}</label>
            <input id={`lead-dictation-${name}`} type={props.type ?? 'text'} className="form-control form-control-sm"
                maxLength={props.max ?? 255} value={draft[name] ?? ''} onChange={(e) => set(name, e.target.value)}
                disabled={busy !== null} />
        </div>
    );

    return (
        <div className="modal fade show d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,.5)' }}>
            <div className="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
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

                                <label className="small fw-bold mb-1" htmlFor="lead-dictation-text">{t.text}</label>
                                <textarea id="lead-dictation-text" className="form-control" rows="6" maxLength={5000}
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

                        {step === 'draft' && draft && (
                            <>
                                <h6 className="mb-2">{t.draft}</h6>

                                <fieldset className="border rounded p-2 mb-2">
                                    <legend className="float-none w-auto px-1 small fw-bold mb-0">{t.company}</legend>
                                    <select className="form-select form-select-sm mb-1" value={draft.companies_id}
                                        onChange={(e) => set('companies_id', e.target.value)} disabled={busy !== null}>
                                        <option value="">{t.company_new}</option>
                                        {candidates.map((c) => (
                                            <option key={c.id} value={String(c.id)}>{c.code ? `${c.code} - ` : ''}{c.label}</option>
                                        ))}
                                    </select>
                                    {draft.companies_id
                                        ? <div className="small text-success"><i className="fas fa-check-circle me-1" />{t.company_matched}</div>
                                        : (
                                            <>
                                                {candidates.length > 0 && <div className="small text-warning mb-1">{t.company_hint}</div>}
                                                <div className="row g-2">{field('company_label', t.company_label, { col: 'col-12' })}</div>
                                            </>
                                        )}
                                </fieldset>

                                <fieldset className="border rounded p-2 mb-2">
                                    <legend className="float-none w-auto px-1 small fw-bold mb-0">{t.contact}</legend>
                                    {!newCompany && (
                                        <select className="form-select form-select-sm mb-1" value={draft.companies_contacts_id}
                                            onChange={(e) => set('companies_contacts_id', e.target.value)} disabled={busy !== null}>
                                            <option value="">{t.contact_new}</option>
                                            {contacts.map((c) => <option key={c.id} value={String(c.id)}>{c.name}</option>)}
                                        </select>
                                    )}
                                    {newContact && (
                                        <div className="row g-2">
                                            {field('contact_first_name', t.first_name)}
                                            {field('contact_name', `${t.name} *`)}
                                            {field('contact_function', t.function)}
                                            {field('contact_mail', t.mail, { type: 'email' })}
                                            {field('contact_number', t.number, { max: 50 })}
                                        </div>
                                    )}
                                </fieldset>

                                <div className="row g-2">
                                    {field('subject', t.subject, { col: 'col-12' })}
                                    <div className="col-12">
                                        <label className="small mb-0" htmlFor="lead-dictation-comment">{t.comment}</label>
                                        <textarea id="lead-dictation-comment" className="form-control form-control-sm" rows="4" maxLength={5000}
                                            value={draft.comment ?? ''} onChange={(e) => set('comment', e.target.value)} disabled={busy !== null} />
                                    </div>
                                    {field('source', t.source)}
                                    <div className="col-12 col-md-6">
                                        <label className="small mb-0" htmlFor="lead-dictation-priority">{t.priority}</label>
                                        <select id="lead-dictation-priority" className="form-select form-select-sm" value={draft.priority ?? 3}
                                            onChange={(e) => set('priority', Number(e.target.value))} disabled={busy !== null}>
                                            {[1, 2, 3, 4].map((p) => <option key={p} value={p}>{t.priorities?.[p] ?? p}</option>)}
                                        </select>
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                    <div className="modal-footer">
                        {step === 'text' ? (
                            <>
                                <button type="button" className="btn btn-secondary btn-sm" onClick={onClose}>{t.cancel}</button>
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
                                <button type="button" className="btn btn-success btn-sm" disabled={busy !== null} onClick={create}>
                                    {busy === 'creating' ? <i className="fas fa-spinner fa-spin me-1" /> : <i className="fas fa-check me-1" />}
                                    {t.create}
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
