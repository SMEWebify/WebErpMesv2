import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { DataTable } from './table';
import { apiFetchOrThrow } from '../lib/http';
import { formatCurrency, formatDate } from '../utils';

// Écran « Factures à relancer » : factures échues non soldées, groupées par
// client, niveau proposé selon le retard (InvoiceReminderService). Rien ne
// part sans validation : chaque envoi passe par la fenêtre d'aperçu.

const LS_COL_ORDER   = 'invoice_reminders_table_col_order';
const LS_HIDDEN_COLS = 'invoice_reminders_table_hidden_cols';

const urlFor = (pattern, id) => (pattern ?? '').replace('__ID__', id);

function LevelBadge({ label, color }) {
    if (!label) return null;
    return <span className={`badge text-bg-${color || 'secondary'}`}>{label}</span>;
}

// ---------------------------------------------------------------------------
// Fenêtre d'envoi par e-mail
// ---------------------------------------------------------------------------

function SendModal({ group, endpoints, trans, onClose, onDone }) {
    const [draft, setDraft]       = useState(null);
    const [editHtml, setEditHtml] = useState(false);
    const [busy, setBusy]         = useState(false);
    const [error, setError]       = useState(null);

    const payload = { companies_id: group.companyId, invoice_ids: group.invoiceIds };

    useEffect(() => {
        apiFetchOrThrow(endpoints.preview, { method: 'POST', body: JSON.stringify(payload) })
            .then(setDraft)
            .catch(e => setError(e.message));
    }, []);

    const send = async () => {
        setBusy(true); setError(null);
        try {
            const res = await apiFetchOrThrow(endpoints.send, {
                method: 'POST',
                body: JSON.stringify({ ...payload, to: draft.to, subject: draft.subject, message: draft.message }),
            });
            onDone('success', res.message);
        } catch (e) {
            setError(Object.values(e.errors ?? {}).flat()[0] ?? e.message);
            setBusy(false);
        }
    };

    const set = (k, v) => setDraft(d => ({ ...d, [k]: v }));

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1040 }} onClick={onClose} />
            <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1050 }}>
                <div className="modal-dialog modal-lg modal-dialog-scrollable">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title">
                                <i className="fas fa-envelope me-2" />
                                {(trans.send_title ?? '').replace(':client', group.companyLabel)}
                                {draft?.label && <span className="ms-2"><LevelBadge label={draft.label} color={group.color} /></span>}
                            </h5>
                            <button type="button" className="btn-close" aria-label="Close" onClick={onClose} />
                        </div>
                        <div className="modal-body">
                            {error && <div className="alert alert-danger">{error}</div>}
                            {!draft && !error && <div className="text-center py-4"><i className="fas fa-spinner fa-spin fa-2x" /></div>}
                            {draft && (
                                <>
                                    <div className="mb-3">
                                        <label className="form-label fw-bold" htmlFor="reminder-to">{trans.to}</label>
                                        <input id="reminder-to" type="email" className="form-control" value={draft.to} onChange={e => set('to', e.target.value)} />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label fw-bold" htmlFor="reminder-subject">{trans.subject}</label>
                                        <input id="reminder-subject" type="text" className="form-control" value={draft.subject} onChange={e => set('subject', e.target.value)} />
                                    </div>
                                    <div className="mb-2 d-flex justify-content-between align-items-center">
                                        <span className="form-label fw-bold mb-0">{trans.message}</span>
                                        <div className="form-check form-switch mb-0">
                                            <input id="reminder-edit-html" type="checkbox" className="form-check-input" checked={editHtml} onChange={e => setEditHtml(e.target.checked)} />
                                            <label className="form-check-label small" htmlFor="reminder-edit-html">{trans.edit_html}</label>
                                        </div>
                                    </div>
                                    {editHtml ? (
                                        <textarea className="form-control font-monospace small" rows={12} value={draft.message} onChange={e => set('message', e.target.value)} />
                                    ) : (
                                        // HTML nettoyé côté serveur (SafeHtml) et variables échappées.
                                        <div className="border rounded p-3 bg-body-tertiary" dangerouslySetInnerHTML={{ __html: draft.message }} />
                                    )}
                                    <div className="mt-3 small">
                                        <span className="fw-bold me-2"><i className="fas fa-paperclip me-1" />{trans.attachments} :</span>
                                        {draft.invoices.map(code => <span key={code} className="badge text-bg-light border me-1">{code}.pdf</span>)}
                                    </div>
                                </>
                            )}
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-outline-secondary" onClick={onClose}>{trans.cancel}</button>
                            <button type="button" className="btn btn-primary" disabled={!draft || busy || !draft.to} onClick={send}>
                                {busy ? <><i className="fas fa-spinner fa-spin me-1" />{trans.sending}</> : <><i className="fas fa-paper-plane me-1" />{trans.send}</>}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// Relance faite hors ERP (appel, courrier)
// ---------------------------------------------------------------------------

function LogModal({ group, endpoints, trans, onClose, onDone }) {
    const [channel, setChannel] = useState('phone');
    const [note, setNote]       = useState('');
    const [busy, setBusy]       = useState(false);
    const [error, setError]     = useState(null);

    const save = async () => {
        setBusy(true); setError(null);
        try {
            const res = await apiFetchOrThrow(endpoints.log, {
                method: 'POST',
                body: JSON.stringify({ companies_id: group.companyId, invoice_ids: group.invoiceIds, channel, note }),
            });
            onDone('success', res.message);
        } catch (e) {
            setError(Object.values(e.errors ?? {}).flat()[0] ?? e.message);
            setBusy(false);
        }
    };

    return (
        <>
            <div className="modal-backdrop fade show" style={{ zIndex: 1040 }} onClick={onClose} />
            <div className="modal show d-block" tabIndex="-1" style={{ zIndex: 1050 }}>
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title"><i className="fas fa-phone me-2" />{trans.log_manual} — {group.companyLabel}</h5>
                            <button type="button" className="btn-close" aria-label="Close" onClick={onClose} />
                        </div>
                        <div className="modal-body">
                            {error && <div className="alert alert-danger">{error}</div>}
                            <div className="mb-3">
                                <label className="form-label fw-bold" htmlFor="reminder-channel">{trans.channel}</label>
                                <select id="reminder-channel" className="form-select" value={channel} onChange={e => setChannel(e.target.value)}>
                                    <option value="phone">{trans.channels?.phone}</option>
                                    <option value="letter">{trans.channels?.letter}</option>
                                </select>
                            </div>
                            <div className="mb-2">
                                <label className="form-label fw-bold" htmlFor="reminder-note">{trans.note}</label>
                                <textarea id="reminder-note" className="form-control" rows={3} value={note}
                                          placeholder={trans.note_placeholder} onChange={e => setNote(e.target.value)} />
                            </div>
                            <div className="small text-body-secondary">{group.codes.join(', ')}</div>
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-outline-secondary" onClick={onClose}>{trans.cancel}</button>
                            <button type="button" className="btn btn-primary" disabled={busy} onClick={save}>
                                <i className="fas fa-check me-1" />{trans.save_log}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// Écran
// ---------------------------------------------------------------------------

export default function InvoiceRemindersApp({ endpoints, trans, canSend }) {
    const [rows, setRows]         = useState([]);
    const [levels, setLevels]     = useState([]);
    const [loading, setLoading]   = useState(true);
    const [onlyToRemind, setOnly] = useState(true);
    const [search, setSearch]     = useState('');
    const [selected, setSelected] = useState(new Set());
    const [modal, setModal]       = useState(null);   // { type: 'send'|'log', group }
    const [flash, setFlash]       = useState(null);

    const money = v => formatCurrency(v ?? 0, trans.currency, trans.locale);

    const load = useCallback(() => {
        setLoading(true);
        apiFetchOrThrow(endpoints.list)
            .then(res => { setRows(res.data ?? []); setLevels(res.levels ?? []); setSelected(new Set()); })
            .catch(e => setFlash({ type: 'danger', text: e.message }))
            .finally(() => setLoading(false));
    }, [endpoints.list]);

    useEffect(() => { load(); }, [load]);

    const visibleRows = useMemo(() => {
        const needle = search.trim().toLowerCase();
        return rows.filter(r => (!onlyToRemind || r.proposed_level !== null)
            && (!needle || `${r.company_label} ${r.code}`.toLowerCase().includes(needle)));
    }, [rows, onlyToRemind, search]);

    const toggle = id => setSelected(s => { const n = new Set(s); n.has(id) ? n.delete(id) : n.add(id); return n; });

    // Factures visées par une action sur un client : la sélection de ce client,
    // à défaut ses factures à relancer, à défaut toutes ses factures affichées.
    const groupOf = companyId => {
        const ofCompany = visibleRows.filter(r => r.companies_id === companyId);
        const picked    = ofCompany.filter(r => selected.has(r.id));
        const toRemind  = ofCompany.filter(r => r.proposed_level !== null);
        const target    = picked.length ? picked : (toRemind.length ? toRemind : ofCompany);
        const top       = [...target].sort((a, b) => (b.proposed_level ?? b.current_level ?? 0) - (a.proposed_level ?? a.current_level ?? 0))[0];
        return {
            companyId,
            companyLabel: ofCompany[0]?.company_label ?? '',
            invoiceIds:   target.map(r => r.id),
            codes:        target.map(r => r.code),
            color:        top?.proposed_color,
        };
    };

    const done = (type, text) => { setModal(null); setFlash({ type, text }); load(); };

    const columns = [
        { key: 'select', label: '', hideable: false, align: 'center',
          render: r => <input type="checkbox" className="form-check-input" checked={selected.has(r.id)} onChange={() => toggle(r.id)} aria-label={r.code} />,
          mobile: 'hidden' },
        { key: 'code', label: trans.invoice, bold: true, filter: 'text',
          render: r => <a href={urlFor(endpoints.invoice, r.id)}>{r.code}</a>, mobile: 'title' },
        { key: 'due_date', label: trans.due_date, filter: 'date',
          render: r => formatDate(r.due_date, trans.locale), mobile: 'subtitle' },
        { key: 'days_late', label: trans.days_late, align: 'right',
          render: r => <span className={r.days_late > 30 ? 'text-danger fw-bold' : ''}>{r.days_late}</span> },
        { key: 'amount_ttc', label: trans.amount_ttc, align: 'right', render: r => money(r.amount_ttc), mobile: 'hidden' },
        { key: 'balance', label: trans.balance, align: 'right', bold: true, render: r => money(r.balance),
          total: { value: r => r.balance, format: sum => money(sum) }, mobile: 'amount' },
        { key: 'current_level', label: trans.current_level,
          render: r => r.current_level_label
              ? <span className="small">{r.current_level_label}{r.last_reminder_at && <span className="text-body-secondary"> · {formatDate(r.last_reminder_at, trans.locale)}</span>}</span>
              : <span className="text-body-secondary small">{trans.not_yet}</span>,
          mobile: 'hidden' },
        { key: 'proposed_level', label: trans.proposed_level, align: 'center',
          render: r => r.proposed_level !== null
              ? <LevelBadge label={r.proposed_label} color={r.proposed_color} />
              : <span className="badge text-bg-light border">{trans.up_to_date}</span>,
          mobile: 'badge' },
    ];

    const groupHeader = (row, prev) => {
        if (prev && prev.companies_id === row.companies_id) return null;
        const ofCompany = visibleRows.filter(r => r.companies_id === row.companies_id);
        const total     = ofCompany.reduce((s, r) => s + r.balance, 0);
        const nSelected = ofCompany.filter(r => selected.has(r.id)).length;
        return (
            <div className="d-flex flex-wrap align-items-center gap-2">
                <a href={urlFor(endpoints.company, row.companies_id)} className="fw-bold">{row.company_label}</a>
                <span className="text-body-secondary small">
                    {ofCompany.length} {trans.invoices_count} · {trans.total_due} <strong>{money(total)}</strong>
                    {nSelected > 0 && <> · {nSelected} {trans.selected}</>}
                </span>
                {canSend && (
                    <span className="ms-auto d-flex gap-1">
                        <button type="button" className="btn btn-sm btn-primary" onClick={() => setModal({ type: 'send', group: groupOf(row.companies_id) })}>
                            <i className="fas fa-envelope me-1" />{trans.remind_by_email}
                        </button>
                        <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setModal({ type: 'log', group: groupOf(row.companies_id) })}>
                            <i className="fas fa-phone" /><span className="d-none d-lg-inline ms-1">{trans.log_manual}</span>
                        </button>
                    </span>
                )}
            </div>
        );
    };

    return (
        <div>
            <p className="text-body-secondary">{trans.subtitle}</p>

            {flash && (
                <div className={`alert alert-${flash.type} alert-dismissible`}>
                    {flash.text}
                    <button type="button" className="btn-close" aria-label="Close" onClick={() => setFlash(null)} />
                </div>
            )}
            {!canSend && <div className="alert alert-secondary">{trans.no_permission}</div>}
            {!loading && levels.length === 0 && <div className="alert alert-warning">{trans.no_levels}</div>}

            <div className="card mb-3">
                <div className="card-header d-flex flex-wrap align-items-center gap-2">
                    <div className="btn-group btn-group-sm" role="group">
                        <button type="button" className={`btn ${onlyToRemind ? 'btn-primary' : 'btn-outline-primary'}`} onClick={() => setOnly(true)}>
                            {trans.to_remind_only}
                        </button>
                        <button type="button" className={`btn ${!onlyToRemind ? 'btn-primary' : 'btn-outline-primary'}`} onClick={() => setOnly(false)}>
                            {trans.all_overdue}
                        </button>
                    </div>
                    <input type="search" className="form-control form-control-sm ms-auto" style={{ maxWidth: 280 }}
                           placeholder={trans.search} value={search} onChange={e => setSearch(e.target.value)} />
                    <div className="d-flex gap-1">
                        {levels.map(l => (
                            <span key={l.level} className={`badge text-bg-${l.color}`} title={`J+${l.delay_days}`}>
                                {l.level}. {l.label} (J+{l.delay_days})
                            </span>
                        ))}
                    </div>
                </div>
                <div className="card-body p-0">
                    <DataTable
                        rows={visibleRows}
                        columns={columns}
                        loading={loading}
                        trans={{ ...trans, no_results: trans.empty, total: trans.total_due }}
                        storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
                        rowHref={r => urlFor(endpoints.invoice, r.id)}
                        renderGroupHeader={groupHeader}
                        actionsColumn={false}
                        emptyText={trans.empty}
                    />
                </div>
            </div>

            {modal?.type === 'send' && (
                <SendModal group={modal.group} endpoints={endpoints} trans={trans} onClose={() => setModal(null)} onDone={done} />
            )}
            {modal?.type === 'log' && (
                <LogModal group={modal.group} endpoints={endpoints} trans={trans} onClose={() => setModal(null)} onDone={done} />
            )}
        </div>
    );
}
