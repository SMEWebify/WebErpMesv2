import React, { useEffect, useState } from 'react';
import GuideHelp from './GuideHelp';

// Textes de l'écran (resources/lang/*/billing_ui.php), posés par le composant principal
// avant le rendu de ses enfants ; repli sur le texte français.
let L = {};

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function fmt(n) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n ?? 0);
}

// credited : avoirs émis sur la facture, déduits du reste dû comme dans les relances.
function BalanceBar({ total, paid, credited = 0, remaining }) {
    const pct = total > 0 ? Math.min(100, ((paid + credited) / total) * 100) : 0;
    const color = remaining <= 0 ? 'success' : paid + credited > 0 ? 'warning' : 'danger';

    return (
        <div className="mb-3">
            <div className="d-flex justify-content-between mb-1">
                <span className="text-muted small">
                    {L.paid_label ?? 'Réglé :'} <strong>{fmt(paid)} €</strong>
                    {credited > 0 && <> {L.credited_label ?? '· Avoirs :'} <strong>{fmt(credited)} €</strong></>}
                </span>
                <span className={`text-${remaining <= 0 ? 'success' : 'danger'} small`}>
                    {remaining <= 0
                        ? <><i className="fas fa-check-circle me-1"></i>{L.settled ?? 'Soldée'}</>
                        : <>{L.remaining_label ?? 'Reste :'} <strong>{fmt(remaining)} €</strong></>}
                </span>
                <span className="text-muted small">{L.total_label ?? 'Total :'} <strong>{fmt(total)} €</strong></span>
            </div>
            <div className="progress" style={{ height: 8 }}>
                <div
                    className={`progress-bar bg-${color}`}
                    style={{ width: `${pct}%`, transition: 'width .3s' }}
                ></div>
            </div>
        </div>
    );
}

export default function InvoicePaymentsTab({ endpoints, paymentMethods, invoiceId, trans = {} }) {
    L = trans?.ui ?? {};
    const [data, setData]         = useState({ payments: [], total: 0, paid: 0, remaining: 0 });
    const [loading, setLoading]   = useState(true);
    const [saving, setSaving]     = useState(false);
    const [error, setError]       = useState(null);

    const [form, setForm] = useState({
        amount:            '',
        payment_date:      new Date().toISOString().slice(0, 10),
        payment_method_id: '',
        reference:         '',
        note:              '',
    });

    async function load() {
        setLoading(true);
        try {
            const r = await fetch(endpoints.index, { headers: { Accept: 'application/json' } });
            setData(await r.json());
        } catch {
            setError((L.error_loading ?? 'Erreur de chargement'));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { load(); }, []);

    function handleChange(e) {
        setForm(f => ({ ...f, [e.target.name]: e.target.value }));
    }

    async function handleStore(e) {
        e.preventDefault();
        setSaving(true);
        setError(null);
        try {
            const r = await fetch(endpoints.store, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
                body: JSON.stringify(form),
            });
            const json = await r.json();
            if (!r.ok) { setError(json.message ?? JSON.stringify(json.errors ?? json)); return; }
            setForm(f => ({ ...f, amount: '', reference: '', note: '' }));
            await load();
        } catch {
            setError((L.network_error ?? 'Erreur réseau'));
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete(paymentId) {
        if (!confirm((L.confirm_delete_payment ?? 'Supprimer ce règlement ?'))) return;
        const url = endpoints.destroy.replace('__payment__', paymentId);
        try {
            await fetch(url, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            });
            await load();
        } catch {
            setError((L.network_error ?? 'Erreur réseau'));
        }
    }

    return (
        <div>
            <BalanceBar total={data.total} paid={data.paid} credited={data.credited} remaining={data.remaining} />

            {error && (
                <div className="alert alert-danger alert-dismissible">
                    {error}
                    <button type="button" onClick={() => setError(null)} aria-label="Close" className="btn-close float-end"></button>
                </div>
            )}

            <div className="row">
                {/* Formulaire de saisie */}
                <div className="col-md-4">
                    <div className="card card-secondary card-outline mb-3">
                        <div className="card-header"><h3 className="card-title"><i className="fas fa-plus me-1"></i>{L.new_payment ?? 'Saisir un règlement'}<GuideHelp guide="facturation" section="enregistrer-les-reglements" /></h3></div>
                        <div className="card-body">
                            <form onSubmit={handleStore}>
                                <div className="mb-3">
                                    <label>{L.amount_eur ?? 'Montant (€)'} <span className="text-danger">*</span></label>
                                    <input
                                        type="number" step="0.01" min="0.01"
                                        className="form-control"
                                        name="amount" value={form.amount}
                                        onChange={handleChange} required
                                        placeholder={data.remaining > 0 ? fmt(data.remaining) : '0.00'}
                                    />
                                </div>
                                <div className="mb-3">
                                    <label>{L.date ?? 'Date'} <span className="text-danger">*</span></label>
                                    <input type="date" className="form-control" name="payment_date" value={form.payment_date} onChange={handleChange} required />
                                </div>
                                <div className="mb-3">
                                    <label>{L.method ?? 'Méthode'}</label>
                                    <select className="form-select" name="payment_method_id" value={form.payment_method_id} onChange={handleChange}>
                                        <option value="">{L.choose ?? '— Choisir —'}</option>
                                        {paymentMethods.map(m => (
                                            <option key={m.id} value={m.id}>{m.label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="mb-3">
                                    <label>{L.reference ?? 'Référence'}</label>
                                    <input type="text" className="form-control" name="reference" value={form.reference} onChange={handleChange} placeholder={L.reference_placeholder ?? 'N° virement, chèque…'} maxLength={100} />
                                </div>
                                <div className="mb-3">
                                    <label>{L.note ?? 'Note'}</label>
                                    <textarea className="form-control" name="note" value={form.note} onChange={handleChange} rows={2} maxLength={500}></textarea>
                                </div>
                                <button type="submit" className="btn btn-success w-100" disabled={saving || data.remaining <= 0}>
                                    <i className="fas fa-check me-1"></i>
                                    {saving ? (L.saving ?? 'Enregistrement…') : (L.save ?? 'Enregistrer')}
                                </button>
                                {data.remaining <= 0 && (
                                    <p className="text-success text-center mt-2 small"><i className="fas fa-lock me-1"></i>{L.invoice_settled ?? 'Facture soldée'}</p>
                                )}
                            </form>
                        </div>
                    </div>
                </div>

                {/* Liste des règlements */}
                <div className="col-md-8">
                    <div className="card card-primary card-outline mb-3">
                        <div className="card-header"><h3 className="card-title"><i className="fas fa-list me-1"></i>{L.payments_history ?? 'Historique des règlements'}</h3></div>
                        <div className="card-body p-0">
                            {loading ? (
                                <div className="text-center py-4"><i className="fas fa-spinner fa-spin me-2"></i>{L.loading ?? 'Chargement…'}</div>
                            ) : data.payments.length === 0 ? (
                                <div className="text-center text-muted py-4">
                                    <i className="fas fa-inbox fa-2x d-block mb-2"></i>{L.no_payment ?? 'Aucun règlement enregistré'}
                                </div>
                            ) : (
                                <table className="table table-sm table-hover mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th>{L.date ?? 'Date'}</th>
                                            <th>{L.method ?? 'Méthode'}</th>
                                            <th>{L.reference ?? 'Référence'}</th>
                                            <th className="text-end">{L.amount ?? 'Montant'}</th>
                                            <th>{L.by ?? 'Par'}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.payments.map(p => (
                                            <tr key={p.id}>
                                                <td>{new Date(p.payment_date).toLocaleDateString('fr-FR')}</td>
                                                <td>{p.payment_method ?? <span className="text-muted">—</span>}</td>
                                                <td>
                                                    {p.reference
                                                        ? <code>{p.reference}</code>
                                                        : <span className="text-muted">—</span>}
                                                    {p.note && <small className="d-block text-muted">{p.note}</small>}
                                                </td>
                                                <td className="text-end fw-bold">{fmt(p.amount)} €</td>
                                                <td><small className="text-muted">{p.user}</small></td>
                                                <td>
                                                    <button
                                                        className="btn btn-sm btn-outline-danger"
                                                        onClick={() => handleDelete(p.id)}
                                                        title={L.delete ?? 'Supprimer'}
                                                    >
                                                        <i className="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="table-success fw-bold">
                                            <td colSpan={3} className="text-end">{L.total_paid ?? 'Total réglé'}</td>
                                            <td className="text-end">{fmt(data.paid)} €</td>
                                            <td colSpan={2}></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
