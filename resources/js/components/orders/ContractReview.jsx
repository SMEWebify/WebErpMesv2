// Onglet « Revue de contrat » de la fiche commande (ISO 19443).
//
// Préparation (check-list, écarts) enregistrable par tous ; visa validé / rejeté
// réservé à la permission `contract-review-approve`, auteur et date posés par le
// serveur, revue figée ensuite. À côté : les avenants. Créer un avenant rouvre
// la revue ; son visa est celui de la revue. L'ancienne demande de changement
// (colonnes de la commande) n'est plus qu'affichée. Rien ici ne bloque la production.

import React, { useCallback, useEffect, useState } from 'react';
import { apiRequest } from '../../lib/http';
import {
    reviewBadge, uncheckedItems, decisionBlocker, draftPayload,
    amendmentBadge, amendmentBlocker, amendmentDelta,
} from '../../lib/contractReview';

function StatusBadge({ status, trans }) {
    const badge = reviewBadge(status);
    if (!badge) return null;
    return (
        <span className={`badge ${badge.className}`}>
            <i className={`fas ${badge.icon} me-1`} />
            {trans.status?.[status] ?? status}
        </span>
    );
}

function openDocumentsTab(event) {
    const link = document.querySelector('a[href="#Documents"][data-bs-toggle="tab"]');
    if (!link || !window.bootstrap?.Tab) return;
    event.preventDefault();
    window.bootstrap.Tab.getOrCreateInstance(link).show();
}

function ReviewCard({ state, trans, endpoints, onState }) {
    const [checklist, setChecklist] = useState(state.checklist ?? []);
    const [gaps, setGaps]           = useState(state.gaps ?? '');
    const [busy, setBusy]           = useState(false);
    const [message, setMessage]     = useState(null);

    useEffect(() => {
        setChecklist(state.checklist ?? []);
        setGaps(state.gaps ?? '');
    }, [state]);

    const frozen = state.frozen;
    const unchecked = uncheckedItems(checklist);

    const send = async (url, method, body, okText) => {
        setBusy(true);
        setMessage(null);
        try {
            const res = await apiRequest(url, { method, body: JSON.stringify(body) });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setMessage({ type: 'danger', text: data.message ?? trans.error });
                // Revue visée entre-temps par quelqu'un d'autre : relire l'état.
                if (res.status === 409) onState(null);
                return;
            }
            onState(data.state);
            if (okText) setMessage({ type: 'success', text: okText });
        } catch {
            setMessage({ type: 'danger', text: trans.error });
        } finally {
            setBusy(false);
        }
    };

    const save = () => send(endpoints.update, 'PUT', draftPayload(checklist, gaps), trans.saved);

    const decide = (decision) => {
        const blocker = decisionBlocker(decision, checklist, gaps);
        if (blocker) {
            setMessage({ type: 'warning', text: trans.errors?.[blocker]?.replace(':items', unchecked.map(i => i.label).join(', ')) });
            return;
        }
        if (!window.confirm(decision === 'approved' ? trans.approve_confirm : trans.reject_confirm)) return;
        send(endpoints.decide, 'POST', { ...draftPayload(checklist, gaps), decision }, null);
    };

    const toggle = (key) => setChecklist(items => items.map(item => (item.key === key ? { ...item, checked: !item.checked } : item)));

    return (
        <div className="card card-outline card-info mb-3">
            <div className="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 className="card-title mb-0 me-auto">
                    <i className="fas fa-clipboard-check me-2" />
                    {trans.title}
                </h3>
                <StatusBadge status={state.status} trans={trans} />
            </div>
            <div className="card-body">
                <p className="small text-body-secondary">{trans.intro}</p>

                {frozen && (
                    <div className={`alert ${state.decision === 'rejected' ? 'alert-danger' : 'alert-success'} py-2`}>
                        <i className="fas fa-lock me-1" />
                        {trans.frozen}
                        {state.reviewed_at && (
                            <div className="small">
                                {trans.visa?.replace(':date', state.reviewed_at).replace(':user', state.reviewed_by ?? '—')}
                            </div>
                        )}
                    </div>
                )}

                {state.pending_amendment && (
                    <div className="alert alert-warning py-2">
                        <i className="fas fa-file-signature me-1" />
                        {trans.amendment?.reopened?.replace(':number', state.pending_amendment.number)}
                    </div>
                )}

                {state.risk_register && !frozen && (
                    // Catégorie d'affaire à registre des risques, aucun risque recensé : jamais bloquant.
                    <div className="alert alert-warning py-2">
                        <i className="fas fa-shield-alt me-1" />
                        {trans.risk_register?.warning}
                        {state.risk_register.opportunity_url ? (
                            <a href={state.risk_register.opportunity_url} className="ms-1">{trans.risk_register?.link}</a>
                        ) : (
                            <div className="small">{trans.risk_register?.no_affair}</div>
                        )}
                    </div>
                )}

                {message && <div className={`alert alert-${message.type} py-2`}>{message.text}</div>}

                <h6 className="fw-bold">{trans.checklist_title}</h6>
                <ul className="list-unstyled mb-3">
                    {checklist.map(item => (
                        <li key={item.key} className="form-check mb-1">
                            <input
                                type="checkbox"
                                className="form-check-input"
                                id={`review-item-${item.key}`}
                                checked={Boolean(item.checked)}
                                disabled={frozen || busy || item.configured === false}
                                onChange={() => toggle(item.key)}
                            />
                            <label className="form-check-label" htmlFor={`review-item-${item.key}`}>
                                {item.label}
                                {item.configured === false && (
                                    <span className="small text-body-secondary ms-1">({trans.checklist_removed})</span>
                                )}
                            </label>
                        </li>
                    ))}
                </ul>

                <label className="form-label fw-bold" htmlFor="review-gaps">{trans.gaps}</label>
                {frozen ? (
                    <p className="mb-3" style={{ whiteSpace: 'pre-line' }}>
                        {state.gaps || <span className="text-body-secondary">{trans.no_gaps}</span>}
                    </p>
                ) : (
                    <>
                        <textarea
                            id="review-gaps"
                            className="form-control"
                            rows={4}
                            maxLength={10000}
                            value={gaps}
                            disabled={busy}
                            onChange={e => setGaps(e.target.value)}
                        />
                        <div className="form-text mb-3">{trans.gaps_help}</div>
                    </>
                )}

                {!frozen && unchecked.length > 0 && gaps.trim() === '' && (
                    <div className="small text-warning-emphasis mb-2">
                        <i className="fas fa-exclamation-triangle me-1" />
                        {trans.unchecked_warning}
                    </div>
                )}
            </div>
            {!frozen && (
                <div className="card-footer d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" className="btn btn-outline-primary btn-sm" onClick={save} disabled={busy}>
                        <i className="fas fa-save me-1" />
                        {busy ? trans.saving : trans.save}
                    </button>
                    <span className="flex-grow-1" />
                    {state.can_approve ? (
                        <>
                            <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => decide('rejected')} disabled={busy}>
                                <i className="fas fa-times me-1" />
                                {trans.reject}
                            </button>
                            <button type="button" className="btn btn-success btn-sm" onClick={() => decide('approved')} disabled={busy}>
                                <i className="fas fa-stamp me-1" />
                                {trans.approve}
                            </button>
                        </>
                    ) : (
                        <span className="small text-body-secondary">
                            <i className="fas fa-user-lock me-1" />
                            {trans.visa_reserved}
                        </span>
                    )}
                </div>
            )}
        </div>
    );
}

function DocumentsCard({ documents, trans }) {
    return (
        <div className="card card-outline card-secondary mb-3">
            <div className="card-header">
                <h3 className="card-title mb-0">
                    <i className="far fa-file-alt me-2" />
                    {trans.documents}
                </h3>
            </div>
            <div className="card-body">
                <p className="small text-body-secondary">{trans.documents_help}</p>
                {documents.length === 0 ? (
                    <p className="small text-warning-emphasis">
                        <i className="fas fa-exclamation-triangle me-1" />
                        {trans.no_documents}
                    </p>
                ) : (
                    <ul className="list-unstyled small">
                        {documents.map(doc => (
                            <li key={doc.id} className="text-truncate">
                                <i className="fas fa-paperclip me-1 text-body-secondary" />
                                <a href={doc.url} target="_blank" rel="noopener">{doc.name}</a>
                            </li>
                        ))}
                    </ul>
                )}
                <a href="#Documents" className="btn btn-outline-secondary btn-sm" onClick={openDocumentsTab}>
                    <i className="far fa-folder-open me-1" />
                    {trans.open_documents}
                </a>
            </div>
        </div>
    );
}

function formatAmount(value, currency) {
    if (value === null || value === undefined) return '—';
    try {
        return new Intl.NumberFormat(document.documentElement.lang || undefined, { style: 'currency', currency }).format(value);
    } catch {
        return Number(value).toFixed(2);
    }
}

function AmendmentForm({ users, trans, endpoint, onState }) {
    const t = trans.amendment ?? {};
    const today = new Date().toISOString().slice(0, 10);
    const [form, setForm]       = useState({ reason: '', requested_by: '', amendment_date: today });
    const [busy, setBusy]       = useState(false);
    const [message, setMessage] = useState(null);

    const set = (key, value) => setForm(f => ({ ...f, [key]: value }));

    const submit = async () => {
        if (form.reason.trim() === '' || !window.confirm(t.create_confirm)) return;
        setBusy(true);
        setMessage(null);
        try {
            const res = await apiRequest(endpoint, {
                method: 'POST',
                body: JSON.stringify({
                    reason: form.reason.trim(),
                    requested_by: form.requested_by || null,
                    amendment_date: form.amendment_date || null,
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setMessage({ type: 'danger', text: data.message ?? trans.error });
                if (res.status === 409) onState(null);
                return;
            }
            setForm({ reason: '', requested_by: '', amendment_date: today });
            onState(data.state, t.created);
        } catch {
            setMessage({ type: 'danger', text: trans.error });
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="border rounded p-2 mb-3 bg-body-tertiary">
            <h6 className="fw-bold">{t.new}</h6>
            {message && <div className={`alert alert-${message.type} py-2`}>{message.text}</div>}
            <div className="mb-2">
                <label className="form-label" htmlFor="amendment-reason">{t.reason} *</label>
                <textarea
                    id="amendment-reason"
                    className="form-control form-control-sm"
                    rows={3}
                    maxLength={10000}
                    value={form.reason}
                    disabled={busy}
                    onChange={e => set('reason', e.target.value)}
                />
            </div>
            <div className="row g-2 mb-2">
                <div className="col-sm-7">
                    <label className="form-label" htmlFor="amendment-requested-by">{t.requested_by}</label>
                    <select
                        id="amendment-requested-by"
                        className="form-select form-select-sm"
                        value={form.requested_by}
                        disabled={busy}
                        onChange={e => set('requested_by', e.target.value)}
                    >
                        <option value="">{trans.select_user}</option>
                        {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                    </select>
                </div>
                <div className="col-sm-5">
                    <label className="form-label" htmlFor="amendment-date">{t.date}</label>
                    <input
                        id="amendment-date"
                        type="date"
                        className="form-control form-control-sm"
                        value={form.amendment_date}
                        disabled={busy}
                        onChange={e => set('amendment_date', e.target.value)}
                    />
                </div>
            </div>
            <button type="button" className="btn btn-warning btn-sm" onClick={submit} disabled={busy || form.reason.trim() === ''}>
                <i className="fas fa-file-signature me-1" />
                {busy ? trans.saving : t.create}
            </button>
        </div>
    );
}

function AmendmentItem({ amendment, canCancel, trans, onState }) {
    const t = trans.amendment ?? {};
    const badge = amendmentBadge(amendment.status);
    const delta = amendmentDelta(amendment);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState(null);

    const cancel = async () => {
        if (!window.confirm(t.cancel_confirm)) return;
        setBusy(true);
        setError(null);
        try {
            const res = await apiRequest(amendment.cancel_url, { method: 'POST', body: '{}' });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setError(data.message ?? trans.error);
                if (res.status === 409) onState(null);
                return;
            }
            onState(data.state, t.cancelled_ok);
        } catch {
            setError(trans.error);
        } finally {
            setBusy(false);
        }
    };

    return (
        <li className="list-group-item px-0">
            <div className="d-flex align-items-center flex-wrap gap-2">
                <strong className="me-auto">{t.number?.replace(':number', amendment.number)}</strong>
                <span className="small text-body-secondary">{amendment.date}</span>
                <span className={`badge ${badge.className}`}>
                    <i className={`fas ${badge.icon} me-1`} />
                    {t.status?.[amendment.status] ?? amendment.status}
                </span>
            </div>
            <div className="small mt-1" style={{ whiteSpace: 'pre-line' }}>{amendment.reason}</div>
            <div className="small text-body-secondary mt-1">
                {amendment.requested_by && <div>{t.requested_by} : {amendment.requested_by}</div>}
                <div>
                    {t.amount_before} : {formatAmount(amendment.amount_before, trans.currency)}
                    {' → '}
                    {t.amount_after} : {amendment.amount_after === null ? t.amount_pending : formatAmount(amendment.amount_after, trans.currency)}
                    {delta !== null && delta !== 0 && (
                        <span className={`ms-1 ${delta > 0 ? 'text-success' : 'text-danger'}`}>
                            ({delta > 0 ? '+' : ''}{formatAmount(delta, trans.currency)})
                        </span>
                    )}
                </div>
                {amendment.reviewed_at && (
                    <div>
                        {t.decision} : {amendment.reviewed_at} — {amendment.reviewed_by ?? '—'}
                    </div>
                )}
                {amendment.review_gaps && (
                    <div style={{ whiteSpace: 'pre-line' }}>{trans.gaps} : {amendment.review_gaps}</div>
                )}
            </div>
            {error && <div className="alert alert-danger py-1 small mt-1 mb-0">{error}</div>}
            {canCancel && amendment.cancel_url && (
                <button type="button" className="btn btn-outline-secondary btn-sm mt-2" onClick={cancel} disabled={busy}>
                    <i className="fas fa-undo me-1" />
                    {t.cancel}
                </button>
            )}
        </li>
    );
}

function AmendmentsCard({ state, users, trans, endpoint, onState }) {
    const t = trans.amendment ?? {};
    const [notice, setNotice] = useState(null);
    const amendments = state.amendments ?? [];
    const blocker = amendmentBlocker(state);

    const handle = (next, okText) => {
        onState(next);
        setNotice(okText ?? null);
    };

    return (
        <div className="card card-outline card-warning mb-3">
            <div className="card-header d-flex align-items-center gap-2">
                <h3 className="card-title mb-0 me-auto">
                    <i className="fas fa-file-signature me-2" />
                    {t.title}
                </h3>
                {state.pending_amendment && <span className="badge text-bg-warning">{t.status?.pending}</span>}
            </div>
            <div className="card-body">
                <p className="small text-body-secondary">{t.help}</p>
                {notice && <div className="alert alert-success py-2">{notice}</div>}

                {blocker === null && <AmendmentForm users={users} trans={trans} endpoint={endpoint} onState={handle} />}
                {blocker === 'pending_exists' && (
                    <p className="small text-warning-emphasis">
                        <i className="fas fa-info-circle me-1" />
                        {t.pending_exists}
                    </p>
                )}

                {amendments.length === 0 ? (
                    <p className="small text-body-secondary mb-0">{t.none}</p>
                ) : (
                    <ul className="list-group list-group-flush">
                        {amendments.map(a => (
                            <AmendmentItem key={a.id} amendment={a} canCancel={state.can_approve} trans={trans} onState={handle} />
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}

// Ancienne demande de changement (colonnes de la commande) : lecture seule.
function LegacyChangeCard({ change, trans }) {
    const t = trans.amendment ?? {};
    return (
        <div className="card card-outline card-secondary mb-3">
            <div className="card-header d-flex align-items-center gap-2">
                <h3 className="card-title mb-0 me-auto">
                    <i className="fas fa-exchange-alt me-2" />
                    {t.legacy}
                </h3>
                {change.pending && <span className="badge text-bg-warning">{trans.change?.pending}</span>}
            </div>
            <div className="card-body small">
                <p className="text-body-secondary">{t.legacy_help}</p>
                <dl className="mb-0">
                    <dt>{t.requested_by}</dt>
                    <dd>{change.requested_by ?? '—'}</dd>
                    <dt>{t.reason}</dt>
                    <dd style={{ whiteSpace: 'pre-line' }}>{change.reason || '—'}</dd>
                    <dt>{t.legacy_approved_at}</dt>
                    <dd className="mb-0">{change.approved_at ?? '—'}</dd>
                </dl>
            </div>
        </div>
    );
}

function SideCards({ state, users, trans, endpoints, onState }) {
    return (
        <>
            {state.applicable && (
                <AmendmentsCard state={state} users={users} trans={trans} endpoint={endpoints.amendments} onState={onState} />
            )}
            {state.legacy_change && <LegacyChangeCard change={state.legacy_change} trans={trans} />}
        </>
    );
}

export default function ContractReview({ state: initialState, endpoints, users = [], trans }) {
    const [state, setState] = useState(initialState);

    const reload = useCallback(async () => {
        try {
            const res = await apiRequest(endpoints.show, { method: 'GET' });
            if (res.ok) setState((await res.json()).state);
        } catch { /* l'état affiché reste celui de la page */ }
    }, [endpoints.show]);

    // Un document ajouté dans l'onglet GED apparaît au retour sur l'onglet revue.
    useEffect(() => {
        const link = document.querySelector('a[href="#ContractReview"][data-bs-toggle="tab"]');
        if (!link) return undefined;
        link.addEventListener('shown.bs.tab', reload);
        return () => link.removeEventListener('shown.bs.tab', reload);
    }, [reload]);

    const onState = (next) => (next ? setState(next) : reload());

    // Commande interne : ni revue ni avenant, seulement une ancienne demande de changement éventuelle.
    if (!state.applicable) {
        return (
            <div className="row">
                <div className="col-lg-6">
                    {state.legacy_change
                        ? <LegacyChangeCard change={state.legacy_change} trans={trans} />
                        : <p className="text-body-secondary">{trans.errors?.not_applicable}</p>}
                </div>
            </div>
        );
    }

    return (
        <div className="row">
            <div className="col-lg-8">
                <ReviewCard state={state} trans={trans} endpoints={endpoints} onState={onState} />
            </div>
            <div className="col-lg-4">
                <DocumentsCard documents={state.documents ?? []} trans={trans} />
                <SideCards state={state} users={users} trans={trans} endpoints={endpoints} onState={onState} />
            </div>
        </div>
    );
}
