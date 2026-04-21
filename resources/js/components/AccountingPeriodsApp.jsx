import React, { useEffect, useState } from 'react';

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

const MONTHS = [
    'Janvier','Février','Mars','Avril','Mai','Juin',
    'Juillet','Août','Septembre','Octobre','Novembre','Décembre',
];

function monthLabel(month, year) {
    return `${MONTHS[month - 1]} ${year}`;
}

function currentYear() { return new Date().getFullYear(); }
function currentMonth() { return new Date().getMonth() + 1; }

export default function AccountingPeriodsApp({ endpoints }) {
    const [periods, setPeriods]   = useState([]);
    const [loading, setLoading]   = useState(true);
    const [working, setWorking]   = useState(false);
    const [error, setError]       = useState(null);
    const [year, setYear]         = useState(currentYear());
    const [month, setMonth]       = useState(currentMonth());

    const years = Array.from({ length: 5 }, (_, i) => currentYear() - 2 + i);

    async function load() {
        setLoading(true);
        try {
            const r = await fetch(endpoints.list, { headers: { Accept: 'application/json' } });
            const data = await r.json();
            setPeriods(data);
        } catch {
            setError('Erreur de chargement');
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { load(); }, []);

    async function handleLock(e) {
        e.preventDefault();
        if (!confirm(`Verrouiller ${monthLabel(month, year)} ? Aucune facture clôturée de cette période ne pourra plus être modifiée.`)) return;
        setWorking(true);
        setError(null);
        try {
            const r = await fetch(endpoints.lock, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrf(),
                },
                body: JSON.stringify({ year, month }),
            });
            const data = await r.json();
            if (!r.ok) { setError(data.message ?? 'Erreur'); return; }
            await load();
        } catch {
            setError('Erreur réseau');
        } finally {
            setWorking(false);
        }
    }

    async function handleUnlock(p) {
        if (!confirm(`Déverrouiller ${monthLabel(p.month, p.year)} ?`)) return;
        setWorking(true);
        setError(null);
        try {
            const url = endpoints.unlock.replace('__year__', p.year).replace('__month__', p.month);
            const r = await fetch(url, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            });
            if (!r.ok) { const d = await r.json(); setError(d.message ?? 'Erreur'); return; }
            await load();
        } catch {
            setError('Erreur réseau');
        } finally {
            setWorking(false);
        }
    }

    return (
        <div className="row">
            {/* Formulaire de verrouillage */}
            <div className="col-md-4">
                <div className="card card-secondary">
                    <div className="card-header">
                        <h3 className="card-title"><i className="fas fa-lock mr-2"></i>Verrouiller une période</h3>
                    </div>
                    <div className="card-body">
                        {error && <div className="alert alert-danger alert-dismissible">
                            {error}
                            <button type="button" className="close" onClick={() => setError(null)}><span>&times;</span></button>
                        </div>}
                        <form onSubmit={handleLock}>
                            <div className="form-group">
                                <label>Année</label>
                                <select className="form-control" value={year} onChange={e => setYear(+e.target.value)}>
                                    {years.map(y => <option key={y} value={y}>{y}</option>)}
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Mois</label>
                                <select className="form-control" value={month} onChange={e => setMonth(+e.target.value)}>
                                    {MONTHS.map((m, i) => <option key={i+1} value={i+1}>{m}</option>)}
                                </select>
                            </div>
                            <button type="submit" className="btn btn-danger btn-block" disabled={working}>
                                <i className="fas fa-lock mr-2"></i>
                                {working ? 'En cours…' : 'Verrouiller'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {/* Liste des périodes verrouillées */}
            <div className="col-md-8">
                <div className="card card-primary">
                    <div className="card-header">
                        <h3 className="card-title"><i className="fas fa-calendar-times mr-2"></i>Périodes verrouillées</h3>
                    </div>
                    <div className="card-body p-0">
                        {loading ? (
                            <div className="text-center py-4">
                                <i className="fas fa-spinner fa-spin mr-2"></i>Chargement…
                            </div>
                        ) : periods.length === 0 ? (
                            <div className="text-center text-muted py-4">
                                <i className="fas fa-unlock fa-2x mb-2 d-block"></i>
                                Aucune période verrouillée
                            </div>
                        ) : (
                            <table className="table table-hover mb-0">
                                <thead className="thead-light">
                                    <tr>
                                        <th>Période</th>
                                        <th>Verrouillé par</th>
                                        <th>Le</th>
                                        <th className="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {periods.map(p => (
                                        <tr key={`${p.year}-${p.month}`}>
                                            <td>
                                                <span className="badge badge-danger p-2" style={{ fontSize: '0.9em' }}>
                                                    <i className="fas fa-lock mr-1"></i>
                                                    {monthLabel(p.month, p.year)}
                                                </span>
                                            </td>
                                            <td>{p.locked_by_name ?? '—'}</td>
                                            <td>{p.locked_at}</td>
                                            <td className="text-right">
                                                <button
                                                    className="btn btn-sm btn-outline-secondary"
                                                    onClick={() => handleUnlock(p)}
                                                    disabled={working}
                                                    title="Déverrouiller"
                                                >
                                                    <i className="fas fa-unlock"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
