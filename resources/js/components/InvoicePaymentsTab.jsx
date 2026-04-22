import React, { useEffect, useState } from 'react';

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function fmt(n) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n ?? 0);
}

function BalanceBar({ total, paid, remaining }) {
    const pct = total > 0 ? Math.min(100, (paid / total) * 100) : 0;
    const color = remaining <= 0 ? 'success' : paid > 0 ? 'warning' : 'danger';

    return (
        <div className="mb-3">
            <div className="d-flex justify-content-between mb-1">
                <span className="text-muted small">Réglé : <strong>{fmt(paid)} €</strong></span>
                <span className={`text-${remaining <= 0 ? 'success' : 'danger'} small`}>
                    {remaining <= 0
                        ? <><i className="fas fa-check-circle mr-1"></i>Soldée</>
                        : <>Reste : <strong>{fmt(remaining)} €</strong></>}
                </span>
                <span className="text-muted small">Total : <strong>{fmt(total)} €</strong></span>
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

export default function InvoicePaymentsTab({ endpoints, paymentMethods, invoiceId }) {
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
            setError('Erreur de chargement');
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
            setError('Erreur réseau');
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete(paymentId) {
        if (!confirm('Supprimer ce règlement ?')) return;
        const url = endpoints.destroy.replace('__payment__', paymentId);
        try {
            await fetch(url, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            });
            await load();
        } catch {
            setError('Erreur réseau');
        }
    }

    return (
        <div>
            <BalanceBar total={data.total} paid={data.paid} remaining={data.remaining} />

            {error && (
                <div className="alert alert-danger alert-dismissible">
                    {error}
                    <button type="button" className="close" onClick={() => setError(null)}><span>&times;</span></button>
                </div>
            )}

            <div className="row">
                {/* Formulaire de saisie */}
                <div className="col-md-4">
                    <div className="card card-secondary card-outline">
                        <div className="card-header"><h3 className="card-title"><i className="fas fa-plus mr-1"></i>Saisir un règlement</h3></div>
                        <div className="card-body">
                            <form onSubmit={handleStore}>
                                <div className="form-group">
                                    <label>Montant (€) <span className="text-danger">*</span></label>
                                    <input
                                        type="number" step="0.01" min="0.01"
                                        className="form-control"
                                        name="amount" value={form.amount}
                                        onChange={handleChange} required
                                        placeholder={data.remaining > 0 ? fmt(data.remaining) : '0.00'}
                                    />
                                </div>
                                <div className="form-group">
                                    <label>Date <span className="text-danger">*</span></label>
                                    <input type="date" className="form-control" name="payment_date" value={form.payment_date} onChange={handleChange} required />
                                </div>
                                <div className="form-group">
                                    <label>Méthode</label>
                                    <select className="form-control" name="payment_method_id" value={form.payment_method_id} onChange={handleChange}>
                                        <option value="">— Choisir —</option>
                                        {paymentMethods.map(m => (
                                            <option key={m.id} value={m.id}>{m.label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="form-group">
                                    <label>Référence</label>
                                    <input type="text" className="form-control" name="reference" value={form.reference} onChange={handleChange} placeholder="N° virement, chèque…" maxLength={100} />
                                </div>
                                <div className="form-group">
                                    <label>Note</label>
                                    <textarea className="form-control" name="note" value={form.note} onChange={handleChange} rows={2} maxLength={500}></textarea>
                                </div>
                                <button type="submit" className="btn btn-success btn-block" disabled={saving || data.remaining <= 0}>
                                    <i className="fas fa-check mr-1"></i>
                                    {saving ? 'Enregistrement…' : 'Enregistrer'}
                                </button>
                                {data.remaining <= 0 && (
                                    <p className="text-success text-center mt-2 small"><i className="fas fa-lock mr-1"></i>Facture soldée</p>
                                )}
                            </form>
                        </div>
                    </div>
                </div>

                {/* Liste des règlements */}
                <div className="col-md-8">
                    <div className="card card-primary card-outline">
                        <div className="card-header"><h3 className="card-title"><i className="fas fa-list mr-1"></i>Historique des règlements</h3></div>
                        <div className="card-body p-0">
                            {loading ? (
                                <div className="text-center py-4"><i className="fas fa-spinner fa-spin mr-2"></i>Chargement…</div>
                            ) : data.payments.length === 0 ? (
                                <div className="text-center text-muted py-4">
                                    <i className="fas fa-inbox fa-2x d-block mb-2"></i>Aucun règlement enregistré
                                </div>
                            ) : (
                                <table className="table table-sm table-hover mb-0">
                                    <thead className="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Méthode</th>
                                            <th>Référence</th>
                                            <th className="text-right">Montant</th>
                                            <th>Par</th>
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
                                                <td className="text-right font-weight-bold">{fmt(p.amount)} €</td>
                                                <td><small className="text-muted">{p.user}</small></td>
                                                <td>
                                                    <button
                                                        className="btn btn-xs btn-outline-danger"
                                                        onClick={() => handleDelete(p.id)}
                                                        title="Supprimer"
                                                    >
                                                        <i className="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="table-success font-weight-bold">
                                            <td colSpan={3} className="text-right">Total réglé</td>
                                            <td className="text-right">{fmt(data.paid)} €</td>
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
