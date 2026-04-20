import React, { useState, useEffect, useCallback } from 'react';

const STATUS_OPTIONS = [
    'Open', 'Started', 'In progress', 'Finished',
    'Suspended', 'To RFQ', 'RFQ in progress', 'Outsourced', 'Supplied',
];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json', ...options.headers },
        ...options,
    });
    if (!res.ok) throw res;
    return res.json();
}

export default function KanbanSetting({ endpoints, trans }) {
    const [rows, setRows]       = useState([]);
    const [title, setTitle]     = useState('');
    const [order, setOrder]     = useState(1);
    const [alert, setAlert]     = useState(null);
    const [loading, setLoading] = useState(false);

    const showAlert = (type, msg) => {
        setAlert({ type, msg });
        setTimeout(() => setAlert(null), 3000);
    };

    const load = useCallback(async () => {
        try {
            const data = await apiFetch(endpoints.list);
            setRows(data);
            const maxOrder = data.reduce((m, r) => Math.max(m, r.order), 0);
            setOrder(maxOrder + 1);
        } catch {
            showAlert('danger', trans.error);
        }
    }, [endpoints.list]);

    useEffect(() => { load(); }, [load]);

    const handleStore = async (e) => {
        e.preventDefault();
        if (!title) { showAlert('danger', trans.title_required); return; }
        setLoading(true);
        try {
            const row = await apiFetch(endpoints.store, {
                method: 'POST',
                body: JSON.stringify({ title, order }),
            });
            setRows(prev => [...prev, row].sort((a, b) => a.order - b.order));
            setTitle('');
            setOrder(order + 1);
            showAlert('success', trans.added);
        } catch {
            showAlert('danger', trans.title_unique);
        } finally {
            setLoading(false);
        }
    };

    const handleUp = async (id) => {
        await apiFetch(endpoints.up.replace('__ID__', id), { method: 'PATCH' });
        await load();
        showAlert('success', trans.updated);
    };

    const handleDown = async (id) => {
        await apiFetch(endpoints.down.replace('__ID__', id), { method: 'PATCH' });
        await load();
        showAlert('success', trans.updated);
    };

    const handleDestroy = async (id) => {
        try {
            await apiFetch(endpoints.destroy.replace('__ID__', id), { method: 'DELETE' });
            setRows(prev => prev.filter(r => r.id !== id));
            showAlert('success', trans.deleted);
        } catch {
            showAlert('danger', trans.error);
        }
    };

    return (
        <>
            {alert && (
                <div className={`alert alert-${alert.type} alert-dismissible`}>
                    {alert.msg}
                    <button type="button" className="close" onClick={() => setAlert(null)}>
                        <span>&times;</span>
                    </button>
                </div>
            )}

            <div className="card">
                <div className="card-body">
                    <form onSubmit={handleStore}>
                        <div className="row">
                            <div className="col-4">
                                <label>{trans.title}</label>
                                <div className="input-group">
                                    <div className="input-group-prepend">
                                        <span className="input-group-text"><i className="fas fa-ruler"></i></span>
                                    </div>
                                    <select
                                        className="form-control"
                                        value={title}
                                        onChange={e => setTitle(e.target.value)}
                                    >
                                        <option value="">{trans.select_type}</option>
                                        {STATUS_OPTIONS.map(opt => (
                                            <option key={opt} value={opt}>{opt}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div className="col-4">
                                <label>{trans.order} :</label>
                                <div className="input-group">
                                    <div className="input-group-prepend">
                                        <span className="input-group-text"><i className="fas fa-tags"></i></span>
                                    </div>
                                    <input
                                        type="number"
                                        className="form-control"
                                        placeholder={trans.sort}
                                        value={order}
                                        onChange={e => setOrder(parseInt(e.target.value) || 1)}
                                    />
                                </div>
                            </div>
                            <div className="col-2">
                                <br />
                                <button type="submit" className="btn btn-success btn-block" disabled={loading}>
                                    {trans.add}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div className="card">
                <div className="card-body">
                    <div className="table-responsive p-0">
                        <table className="table table-hover">
                            <thead>
                                <tr>
                                    <th>{trans.description}</th>
                                    <th>{trans.order}</th>
                                    <th>{trans.action}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 ? (
                                    <tr><td colSpan={3} className="text-center">{trans.no_data}</td></tr>
                                ) : rows.map(row => (
                                    <tr key={row.id}>
                                        <td>{row.title}</td>
                                        <td>{row.order}</td>
                                        <td>
                                            <div className="btn-group btn-group-sm">
                                                <button className="btn btn-secondary" onClick={() => handleUp(row.id)}>
                                                    <i className="fa fa-lg fa-fw fa-sort-amount-down"></i>
                                                </button>
                                            </div>{' '}
                                            <div className="btn-group btn-group-sm">
                                                <button className="btn btn-primary" onClick={() => handleDown(row.id)}>
                                                    <i className="fa fa-lg fa-fw fa-sort-amount-up-alt"></i>
                                                </button>
                                            </div>{' '}
                                            {!row.has_tasks && (
                                                <div className="btn-group btn-group-sm">
                                                    <button className="btn btn-danger" onClick={() => handleDestroy(row.id)}>
                                                        <i className="fa fa-lg fa-fw fa-trash"></i>
                                                    </button>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{trans.description}</th>
                                    <th>{trans.order}</th>
                                    <th>{trans.action}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}
